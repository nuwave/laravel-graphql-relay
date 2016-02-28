<?php

namespace Nuwave\Relay\Schema;

use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Error;
use GraphQL\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InterfaceType;
use Folklore\GraphQL\Error\ValidationError;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Nuwave\Relay\Types\EloquentType;

class GraphQL
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Collection of registered queries.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $queries;

    /**
     * Collection of registered types.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $types;

    /**
     * Collection of type instances.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $typeInstances;

    /**
     * Collection of registered mutations.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $mutations;

    /**
     * Create a new instance of GraphQL.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->types = collect();
        $this->queries = collect();
        $this->mutations = collect();
        $this->typeInstances = collect();
    }

    /**
     * Execute GraphQL query.
     *
     * @param  string $query
     * @param  array $variables
     * @return array
     */
    public function query($query, $variables = [])
    {
        $result = $this->queryAndReturnResult($query, $variables);

        if (!empty($result->errors))
        {
            return [
                'data' => $result->data,
                'errors' => array_map([$this, 'formatError'], $result->errors)
            ];
        }

        return ['data' => $result->data];
    }

    /**
     * Execute GraphQL query.
     *
     * @param  string $query
     * @param  array $variables
     * @return array
     */
    public function queryAndReturnResult($query, $variables = [])
    {
        return GraphQLBase::executeAndReturnResult($this->schema(), $query, null, $variables);
    }

    /**
     * Generate GraphQL Schema.
     *
     * @return \GraphQL\Schema
     */
    public function schema()
    {
        $schema = config('graphql.schema');

        $this->types->each(function ($type, $key) {
            $this->type($key);
        });

        $queries = $this->queries->merge(array_get($schema, 'query', []));
        $mutations = $this->mutations->merge(array_get($schema, 'mutation', []));

        $queryTypes = $this->generateType($queries, ['name' => 'Query']);
        $mutationTypes = $this->generateType($mutations, ['name' => 'Mutation']);

        return new Schema($queryTypes, $mutationTypes);
    }

    /**
     * Generate type from collection of fields.
     *
     * @param  Collection $fields
     * @param  array     $options
     * @return \GraphQL\Type\Definition\ObjectType
     */
    public function generateType(Collection $fields, $options = [])
    {
        $typeFields = $fields->transform(function ($field) {
            if (is_string($field)) {
                return app($field)->toArray();
            }

            return $field;
        })->toArray();

        return new ObjectType(array_merge(['fields' => $typeFields], $options));
    }

    /**
     * Add mutation to collection.
     *
     * @param string $name
     * @param mixed $mutator
     */
    public function addMutation($name, $mutator)
    {
        $this->mutations->put($name, $mutator);
    }

    /**
     * Add query to collection.
     *
     * @param string $name
     * @param mixed $query
     */
    public function addQuery($name, $query)
    {
        $this->queries->put($name, $query);
    }

    /**
     * Add type to collection.
     *
     * @param mixed $class
     * @param string|null $name
     */
    public function addType($class, $name = null)
    {
        if (!$name) {
            $type = is_object($class) ? $class : app($class);
            $name = $type->name;
        }

        $this->types->put($name, $class);
    }

    /**
     * Get instance of type.
     *
     * @param  string $name
     * @param  boolean $fresh
     * @return mixed
     */
    public function type($name, $fresh = false)
    {
        if (!$this->types->has($name)) {
            throw new \Exception("Type [{$name}] not found.");
        }

        if (!$fresh && $this->typeInstances->has($name)) {
            return $this->typeInstances->get($name);
        }

        $type = $this->types->get($name);
        if (!is_object($type)) {
            $type = app($type);
        }

        $instance = $type instanceof Model ? (new EloquentType($type))->toType() : $type->toType();

        $this->typeInstances->put($name, $instance);

        if ($type->interfaces) {
            InterfaceType::addImplementationToInterfaces($instance);
        }

        return $instance;
    }

    /**
     * Format error for output.
     *
     * @param  Error  $e
     * @return array
     */
    public function formatError(Error $e)
    {
        $error = ['message' => $e->getMessage()];

        $locations = $e->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function ($location) {
                return $location->toArray();
            }, $locations);
        }

        $previous = $e->getPrevious();
        if ($previous && $previous instanceof ValidationError) {
            $error['validation'] = $previous->getValidatorMessage();
        }

        return $error;
    }
}
