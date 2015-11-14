<?php

namespace Nuwave\Relay\Types;

use GraphQL;
use Nuwave\Relay\GlobalIdTrait;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use Folklore\GraphQL\Support\Type as GraphQLType;

abstract class RelayType extends GraphQLType
{
    use GlobalIdTrait;

    /**
     * List of fields with global identifier.
     *
     * @return array
     */
    public function fields()
    {
        return array_merge($this->baseFields(), $this->getEdges(), [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'ID of type.',
                'resolve' => function ($obj) {
                    return $this->encodeGlobalId(self::class, $this->getIdentifier($obj));
                },
            ],
        ]);
    }

    /**
     * Get the identifier of the type.
     *
     * @param  mixed $obj
     * @return mixed
     */
    public function getIdentifier($obj)
    {
        return $obj->id;
    }

    /**
     * List of available interfaces.
     *
     * @return array
     */
    public function interfaces()
    {
        return [GraphQL::type('node')];
    }

    /**
     * Generate Relay compliant edges.
     *
     * @return array
     */
    public function getEdges()
    {
        $edges = [];

        foreach ($this->connections() as $name => $edge) {
            $edgeType = $this->edgeType($name, $edge['type']);
            $connectionType = $this->connectionType($name, Type::listOf($edgeType));

            $edges[$name] = [
                'type' => $connectionType,
                'description' => 'A connection to a list of items.',
                'args' => [
                    'first' => [
                        'name' => 'first',
                        'type' => Type::int()
                    ],
                    'after' => [
                        'name' => 'after',
                        'type' => Type::int()
                    ]
                ],
                'resolve' => $edge['resolve']
            ];
        }

        return $edges;
    }

    /**
     * Generate PageInfo object type.
     *
     * @return ObjectType
     */
    protected function pageInfoType()
    {
        return new ObjectType([
            'name' => 'PageInfo',
            'description' => 'Information about pagination in a connection.',
            'fields' => [
                'hasNextPage' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'When paginating forwards, are there more items?',
                    'resolve' => function ($collection, $test) {
                        return $collection->hasMorePages();
                    }
                ],
                'hasPreviousPage' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'description' => 'When paginating backwards, are there more items?',
                    'resolve' => function ($collection) {
                        return $collection->currentPage() > 1;
                    }
                ]
            ]
        ]);
    }

    /**
     * Generate EdgeType.
     *
     * @param  string $name
     * @param  mixed $type
     * @return ObjectType
     */
    protected function edgeType($name, $type)
    {
        return new ObjectType([
            'name' => ucfirst($name) . 'Edge',
            'fields' => [
                'node' => [
                    'type' => $type,
                    'description' => 'The item at the end of the edge.',
                    'resolve' => function ($node) {
                        return $node;
                    }
                ],
                'cursor' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'A cursor for use in pagination.',
                    'resolve' => function ($parent, $args) {
                        \Log::info('CURSOR_RESOLVE', [$args]);
                    }
                ]
            ]
        ]);
    }

    /**
     * Create ConnectionType.
     *
     * @param  string $name
     * @param  mixed $type
     * @return ObjectType
     */
    protected function connectionType($name, $type)
    {
        return new ObjectType([
            'name' => ucfirst($name) . 'Connection',
            'fields' => [
                'edges' => [
                    'type' => $type,
                    'resolve' => function ($collection) {
                        return $collection;
                    }
                ],
                'pageInfo' => [
                    'type' => Type::nonNull($this->pageInfoType()),
                    'description' => 'Information to aid in pagination.',
                    'resolve' => function ($edges) {
                        return $edges;
                    }
                ]
            ]
        ]);
    }

    /**
     * Available connections for type.
     *
     * @return array
     */
    protected function connections()
    {
        return [];
    }

    /**
     * Get list of available fields for type.
     *
     * @return array
     */
    abstract protected function baseFields();

    /**
     * Fetch type data by id.
     *
     * @param  string $id
     * @return mixed
     */
    abstract public function resolveById($id);
}
