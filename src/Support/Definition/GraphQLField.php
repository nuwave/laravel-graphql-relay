<?php

namespace Nuwave\Relay\Support\Definition;

use Illuminate\Support\Fluent;
use Nuwave\Relay\Schema\GraphQL;
use Illuminate\Support\DefinitionsFluent;

class GraphQLField extends Fluent
{
    /**
     * The container instance of GraphQL.
     *
     * @var \Laravel\Lumen\Application|mixed
     */
    protected $graphQL;

    /**
     * GraphQLType constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->graphQL = app('graphql');
    }

    /**
     * Arguments this field accepts.
     *
     * @return array
     */
    public function args()
    {
        return [];
    }

    /**
     * Field attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * The field type.
     *
     * @return \GraphQL\Type\Definition\ObjectType
     */
    public function type()
    {
        return null;
    }

    /**
     * Get the attributes of the field.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = array_merge($this->attributes, [
            'args' => $this->args()
        ], $this->attributes());

        $attributes['type'] = $this->type();

        $attributes['resolve'] = $this->getResolver();

        return $attributes;
    }

    /**
     * Get the field resolver.
     *
     * @return \Closure|null
     */
    protected function getResolver()
    {
        if(!method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = array($this, 'resolve');

        return function() use ($resolver) {
            return call_user_func_array($resolver, func_get_args());
        };
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return isset($attributes[$key]) ? $attributes[$key]:null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->getAttributes()[$key]);
    }
}
