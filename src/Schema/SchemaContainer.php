<?php

namespace Nuwave\Relay\Schema;

use Closure;
use Nuwave\Relay\Schema\FieldCollection as Collection;
use Nuwave\Relay\Schema\Field;

class SchemaContainer
{
    /**
     * Mutation collection.
     *
     * @var Collection
     */
    protected $mutations;

    /**
     * Query collection.
     *
     * @var Collection
     */
    protected $queries;

    /**
     * Type collection.
     *
     * @var Collection
     */
    protected $types;

    /**
     * Schema middleware stack.
     *
     * @var array
     */
    protected $middlewareStack = [];

    /**
     * Current namespace.
     *
     * @var array
     */
    protected $namespace = '';

    /**
     * Create new instance of Mutation container.
     *
     * @return void
     */
    public function __construct()
    {
        $this->mutations = new Collection;
        $this->queries = new Collection;
        $this->types = new Collection;
    }

    /**
     * Add mutation to collection.
     *
     * @param string $name
     * @param array $options
     * @return Field
     */
    public function mutation($name, $namespace)
    {
        $mutation = $this->createField($name, $namespace);

        $this->mutations->push($mutation);

        return $mutation;
    }

    /**
     * Add query to collection.
     *
     * @param string $name
     * @param array $options
     * @return Field
     */
    public function query($name, $namespace)
    {
        $query = $this->createField($name, $namespace);

        $this->queries->push($query);

        return $query;
    }

    /**
     * Add type to collection.
     *
     * @param  string $name
     * @param  string $namespace
     * @return Field
     */
    public function type($name, $namespace)
    {
        $type = $this->createField($name, $namespace);

        $this->types->push($type);

        return $type;
    }

    /**
     * Get class name.
     *
     * @param  string $namespace
     * @return string
     */
    protected function getClassName($namespace)
    {
        return empty(trim($this->namespace)) ? $namespace : trim($this->namespace, '\\') . '\\' . $namespace;
    }

    /**
     * Get field and attach necessary middleware.
     *
     * @param  string $name
     * @param  string $namespace
     * @return Field
     */
    protected function createField($name, $namespace)
    {
        $field = new Field($name, $this->getClassName($namespace));

        if ($this->hasMiddlewareStack()) {
            $field->addMiddleware($this->middlewareStack);
        }

        return $field;
    }

    /**
     * Group child elements.
     *
     * @param  array   $middleware
     * @param  Closure $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback)
    {
        $oldNamespace = $this->namespace;

        if (isset($attributes['middleware'])) {
            $this->middlewareStack[] = $attributes['middleware'];
        }

        if (isset($attributes['namespace'])) {
            $this->namespace  .= '\\' . trim($attributes['namespace'], '\\');
        }

        $callback();

        if (isset($attributes['middleware'])) {
            array_pop($this->middlewareStack);
        }

        if (isset($attributes['namespace'])) {
            $this->namespace = $oldNamespace;
        }
    }

    /**
     * Get mutations.
     *
     * @return Collection
     */
    public function getMutations()
    {
        return $this->mapFields($this->mutations);
    }

    /**
     * Get queries.
     *
     * @return Collection
     */
    public function getQueries()
    {
        return $this->mapFields($this->queries);
    }

    /**
     * Get queries.
     *
     * @return Collection
     */
    public function getTypes()
    {
        return $this->mapFields($this->types);
    }

    /**
     * Transform fields into collapsed collection.
     *
     * @param  Collection $collection
     * @return Collection
     */
    public function mapFields(Collection $collection)
    {
        return $collection->map(function ($field) {
            return [
                $field->name => $field->getAttributes()
            ];
        })->collapse();
    }

    /**
     * Find by operation type and name.
     *
     * @param  string $name
     * @param  string $operation
     * @return array
     */
    public function find($name, $operation = 'query')
    {
        if ($operation == 'mutation') {
            return $this->findMutation($name);
        } elseif ($operation == 'type') {
            return $this->findType($name);
        }

        return $this->findQuery($name);
    }

    /**
     * Find mutation by name.
     *
     * @param  string $name
     * @return array
     */
    public function findMutation($name)
    {
        return $this->getMutations()->pull($name);
    }

    /**
     * Find query by name.
     *
     * @param  string $name
     * @return array
     */
    public function findQuery($name)
    {
        return $this->getQueries()->pull($name);
    }

    /**
     * Find type by name.
     *
     * @param  string $name
     * @return array
     */
    public function findType($name)
    {
        return $this->getTypes()->pull($name);
    }

    /**
     * Check if middleware stack is empty.
     *
     * @return boolean
     */
    protected function hasMiddlewareStack()
    {
        return !empty($this->middlewareStack);
    }
}
