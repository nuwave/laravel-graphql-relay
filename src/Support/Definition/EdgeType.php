<?php

namespace Nuwave\Relay\Support\Definition;

use GraphQL;
use Closure;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EdgeType extends GraphQLType
{
    /**
     * The name of the edge (i.e. `User`).
     *
     * @var string
     */
    protected $name = '';

    /**
     * The type of edge.
     *
     * @var \Closure
     */
    protected $type;

    /**
     * Special fields present on this connection type.
     *
     * @param        $name
     * @param string $type
     */
    public function __construct($name, $type)
    {
        parent::__construct();

        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Fields that exist on every connection.
     *
     * @return array
     */
    public function fields()
    {
        return [
            'node' => [
                'type' => array($this, 'nodeType'),
                'description' => 'The item at the end of the edge.',
                'resolve' => array($this, 'edge'),
            ],
            'cursor' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'A cursor for use in pagination.',
                'resolve' => array($this, 'resolveCursor'),
            ]
        ];
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name' => ucfirst($this->name).'Edge',
            'description' => 'An edge in a connection.',
            'fields' => $this->fields(),
        ];
    }

    /**
     * Create the instance of the connection type.
     *
     * @return ObjectType
     */
    public function toType()
    {
        return new ObjectType($this->toArray());
    }

    /**
     * Type of node.
     *
     * @return mixed
     */
    public function nodeType()
    {
        if (is_object($this->type)) {
            return $this->type;
        }

        return $this->getNodeType($this->type);
    }

    /**
     * Resolve node at end of edge.
     *
     * @param  mixed $edge
     * @return mixed
     */
    public function resolveNode($edge)
    {
        return $edge;
    }

    /**
     * Resolve cursor for edge.
     *
     * @param  mixed $edge
     * @return string
     */
    public function resolveCursor($edge)
    {
        if (is_array($edge) && isset($edge['relayCursor'])) {
            return $edge['relayCursor'];
        } elseif (is_array($edge->attributes)) {
            return $edge->attributes['relayCursor'];
        }

        return $edge->relayCursor;
    }

    /**
     * Get node at the end of the edge.
     *
     * @param  string $name
     * @return \GraphQL\Type\Definition\OutputType
     */
    protected function getNodeType($name)
    {
        $graphql = app('graphql');

        return $graphql->hasType($this->type) ? $graphql->getType($this->type) : $graphql->type($this->type);
    }
}
