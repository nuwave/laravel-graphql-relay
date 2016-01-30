<?php

namespace Nuwave\Relay\Types;

use Closure;
use GraphQL;
use GraphQL\Language\AST\ListType;
use Folklore\GraphQL\Support\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Nuwave\Relay\GlobalIdTrait;
use Nuwave\Relay\Schema\SchemaContainer;

abstract class RelayType extends GraphQLType
{
    use GlobalIdTrait;

    /**
     * Schema container.
     *
     * @var SchemaContainer
     */
    protected $container;

    /**
     * List of fields with global identifier.
     *
     * @return array
     */
    public function fields()
    {
        return array_merge($this->relayFields(), $this->getConnections(), [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'ID of type.',
                'resolve' => function ($obj) {
                    return $this->encodeGlobalId(get_called_class(), $this->getIdentifier($obj));
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
    public function getConnections()
    {
        $edges = [];

        foreach ($this->connections() as $name => $edge) {
            $injectCursor = isset($edge['injectCursor']) ? $edge['injectCursor'] : null;
            $resolveCursor = isset($edge['resolveCursor']) ? $edge['resolveCursor'] : null;

            $edgeType = $this->edgeType($name, $edge['type'], $resolveCursor);
            $connectionType = $this->connectionType($name, Type::listOf($edgeType), $injectCursor);

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
                        'type' => Type::string()
                    ]
                ],
                'resolve' => function ($parent, array $args, ResolveInfo $info) use ($name, $edge) {
                    $collection = isset($edge['resolve']) ? $edge['resolve'] : $this->autoResolve($parent, $args, $info, $name);

                    $this->autoLoad($collection, $edge, $name);

                    return $collection;
                }
            ];
        }

        return $edges;
    }

    /**
     * Auto resolve the connection.
     *
     * @param  mixed       $parent
     * @param  array       $args
     * @param  ResolveInfo $info
     * @param  string      $name
     * @return Paginator
     */
    protected function autoResolve($parent, array $args, ResolveInfo $info, $name = '')
    {
        $items = [];

        if ($parent instanceof Model) {
            $items = $parent->getAttribute($name);
        } else if (is_object($parent) && method_exists($parent, 'get')) {
            $items = $parent->get($name);
        } else if (is_array($parent) && isset($parent[$name])) {
            $items = new Collection($parent[$name]);
        }

        if (isset($args['first'])) {
            $total = $items->count();
            $first = $args['first'];
            $after = $this->decodeCursor($args);
            $currentPage = $first && $after ? floor(($first + $after) / $first) : 1;

            return new Paginator(
                $items->slice($after)->take($first),
                $total,
                $first,
                $currentPage
            );
        }

        return new Paginator(
            $items,
            count($items),
            count($items)
        );
    }

    /**
     * Auto load the fields connection(s).
     *
     * @param  Paginator $collection
     * @param  array     $edge
     * @param  string    $name
     * @return void
     */
    protected function autoLoad(Paginator $collection, array $edge, $name)
    {
        $relay = $this->getContainer();

        if ($relay->isParent($name)) {
            if ($typeClass = $this->typeFromSchema($edge['type'])) {
                $type = app($typeClass);
                $connections = $relay->connectionsInRequest($name, $type->connections());

                foreach ($connections as $key => $connection) {
                    if (isset($connection['load'])) {
                        $load = $connection['load'];

                        $load($collection, $relay->connectionArguments($key));
                    }
                }
            }
        }
    }

    /**
     * Generate PageInfo object type.
     *
     * @return ObjectType
     */
    protected function pageInfoType()
    {
        return GraphQL::type('pageInfo');
    }

    /**
     * Generate EdgeType.
     *
     * @param  string $name
     * @param  mixed $type
     * @return ObjectType
     */
    protected function edgeType($name, $type, Closure $resolveCursor = null)
    {
        if ($type instanceof ListOfType) {
            $type = $type->getWrappedType();
        }

        return new ObjectType([
            'name' => ucfirst($name) . 'Edge',
            'fields' => [
                'node' => [
                    'type' => $type,
                    'description' => 'The item at the end of the edge.',
                    'resolve' => function ($edge, array $args, ResolveInfo $info) {
                        return $edge;
                    }
                ],
                'cursor' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'A cursor for use in pagination.',
                    'resolve' => function ($edge, array $args, ResolveInfo $info) use ($resolveCursor) {
                        if ($resolveCursor) {
                            return $resolveCursor($edge, $args, $info);
                        }

                        return $this->resolveCursor($edge);
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
    protected function connectionType($name, $type, Closure $injectCursor = null)
    {
        if (!$type instanceof ListOfType) {
            $type = Type::listOf($type);
        }

        return new ObjectType([
            'name' => ucfirst($name) . 'Connection',
            'fields' => [
                'edges' => [
                    'type' => $type,
                    'resolve' => function ($collection, array $args, ResolveInfo $info) use ($injectCursor) {
                        if ($injectCursor) {
                            return $injectCursor($collection, $args, $info);
                        }

                        return $this->injectCursor($collection);
                    }
                ],
                'pageInfo' => [
                    'type' => Type::nonNull($this->pageInfoType()),
                    'description' => 'Information to aid in pagination.',
                    'resolve' => function ($collection, array $args, ResolveInfo $info) {
                        return $collection;
                    }
                ]
            ]
        ]);
    }

    /**
     * Inject encoded cursor into collection items.
     *
     * @param  mixed $collection
     * @return mixed
     */
    protected function injectCursor($collection)
    {
        if ($collection instanceof LengthAwarePaginator) {
            $page = $collection->currentPage();

            foreach ($collection as $x => &$item) {
                $cursor = ($x + 1) * $page;
                $encodedCursor = $this->encodeGlobalId('arrayconnection', $cursor);

                if (is_array($item)) {
                    $item['relayCursor'] = $encodedCursor;
                } else if (is_object($item) && is_array($item->attributes)) {
                    $item->attributes['relayCursor'] = $encodedCursor;
                } else {
                    $item->relayCursor = $encodedCursor;
                }
            }
        }

        return $collection;
    }

    /**
     * Resolve encoded relay cursor for item.
     *
     * @param  mixed $edge
     * @return string
     */
    protected function resolveCursor($edge)
    {
        if (is_array($edge) && isset($edge['relayCursor'])) {
            return $edge['relayCursor'];
        } elseif (is_array($edge->attributes)) {
            return $edge->attributes['relayCursor'];
        }

        return $edge->relayCursor;
    }

    /**
     * Resolve type from schema.
     *
     * @param  ListOfType|Type $edge
     * @return string|null
     */
    protected function typeFromSchema($edge)
    {
        $type = $edge instanceof ListOfType ? $edge->getWrappedType() : $edge;
        $schema = config('graphql.types');

        if ($typeClass = array_get($schema, $type->toString())) {
            return $typeClass;
        }

        return array_get($schema, strtolower($type->toString()));
    }

    /**
     * Decode cursor from query arguments.
     *
     * @param  array  $args
     * @return integer
     */
    public function decodeCursor(array $args)
    {
        return isset($args['after']) ? $this->getCursorId($args['after']) : 0;
    }

    /**
     * Get id from encoded cursor.
     *
     * @param  string $cursor
     * @return integer
     */
    protected function getCursorId($cursor)
    {
        return (int)$this->decodeRelayId($cursor);
    }

    /**
     * Get schema container instance.
     *
     * @return SchemaContainer
     */
    protected function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        $this->container = app('relay');

        return $this->container;
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
    abstract protected function relayFields();

    /**
     * Fetch type data by id.
     *
     * @param  string $id
     * @return mixed
     */
    abstract public function resolveById($id);
}
