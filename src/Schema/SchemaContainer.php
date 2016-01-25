<?php

namespace Nuwave\Relay\Schema;

use Closure;
use Illuminate\Support\Collection;
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
    }

    /**
     * Add mutation to collection.
     *
     * @param string $name
     * @param array $options
     */
    public function mutation($name, $namespace)
    {
        $class = empty(trim($this->namespace)) ? $namespace : trim($this->namespace, '\\') . '\\' . $namespace;

        $mutation = new Field($name, $class);

        if ($this->hasMiddlewareStack()) {
            $mutation->addMiddleware($this->middlewareStack);
        }

        $this->mutations->push($mutation);

        return $mutation;
    }

    /**
     * Add query to collection.
     *
     * @param string $name
     * @param array $options
     */
    public function query($name, $namespace)
    {
        $class = empty(trim($this->namespace)) ? $namespace : trim($this->namespace, '\\') . '\\' . $namespace;

        $query = new Field($name, $class);

        if ($this->hasMiddlewareStack()) {
            $query->addMiddleware($this->middlewareStack);
        }

        $this->queries->push($query);

        return $query;
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
        return $this->mutations->transform(function ($mutation, $key) {
            return [
                $mutation->name => $mutation->getAttributes()
            ];
        })->collapse();
    }

    /**
     * Get queries.
     *
     * @return Collection
     */
    public function getQueries()
    {
        return $this->queries->transform(function ($query, $key) {
            return [
                $query->name => $query->getAttributes()
            ];
        })->collapse();
    }

    /**
     * Find mutation by name.
     *
     * @param  string $name
     * @return array
     */
    public function find($name)
    {
        return $this->getMutations()->pull($name);
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
