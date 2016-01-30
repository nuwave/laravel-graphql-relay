<?php

namespace Nuwave\Relay\Schema;

use Closure;
use Illuminate\Http\Request;
use Nuwave\Relay\Schema\FieldCollection as Collection;
use Nuwave\Relay\Schema\Field;
use GraphQL\Language\Source;
use GraphQL\Language\Parser as GraphQLParser;

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
     * Schema parser.
     *
     * @var Parser
     */
    public $parser;

    /**
     * Middleware to be applied to query.
     *
     * @var array
     */
    public $middleware = [];

    /**
     * Connections present in query.
     *
     * @var array
     */
    public $connections = [];

    /**
     * Create new instance of Mutation container.
     *
     * @return void
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;

        $this->mutations = new Collection;
        $this->queries = new Collection;
        $this->types = new Collection;
    }

    /**
     * Set up the graphql request.
     *
     * @param  $query string
     * @return void
     */
    public function setupRequest($query = 'GraphGL request', $operation = 'query')
    {
        $source = new Source($query);
        $ast    = GraphQLParser::parse($source);

        if (isset($ast->definitions[0])) {
            $d            = $ast->definitions[0];
            $operation    = $d->operation ?: 'query';
            $selectionSet = $d->selectionSet->selections;

            $this->parseSelections($selectionSet, $operation);
        }
    }

    /**
     * Check to see if field is a parent.
     *
     * @param  string  $name
     * @return boolean
     */
    public function isParent($name)
    {
        foreach ($this->connections as $connection) {
            if ($this->hasPath($connection, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of connections in query that belong
     * to parent.
     *
     * @param  string $parent
     * @param  array  $connections
     * @return array
     */
    public function connectionsInRequest($parent, array $connections)
    {
        $queryConnections = [];

        foreach ($this->connections as $connection) {
            if ($this->hasPath($connection, $parent) && isset($connections[$connection->name])) {
                $queryConnections[] = $connections[$connection->name];
            }
        }

        return $queryConnections;
    }

    /**
     * Get arguments of connection.
     *
     * @param  string $name
     * @return array
     */
    public function connectionArguments($name)
    {
        $connection = array_first($this->connections, function ($key, $connection) use ($name) {
            return $connection->name == $name;
        });

        if ($connection) {
            return $connection->arguments;
        }

        return [];
    }

    /**
     * Determine if connection has parent in it's path.
     *
     * @param  Connection $connection
     * @param  string     $parent
     * @return boolean
     */
    protected function hasPath(Connection $connection, $parent)
    {
        return preg_match("/{$parent}./", $connection->path);
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
     * Get the middlware for the query.
     *
     * @return array
     */
    public function middleware()
    {
        return $this->middleware;
    }

    /**
     * Get connections for the query.
     *
     * @return array
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * Initialize schema.
     *
     * @param  array $selectionSet
     * @return void
     */
    protected function parseSelections(array $selectionSet = [], $operation = '')
    {
        foreach ($selectionSet as $selection) {
            if ($this->parser->isField($selection)) {
                $schema = $this->find($selection->name->value, $operation);

                if (isset($schema['middleware']) && !empty($schema['middleware'])) {
                    $this->middleware = array_merge($this->middleware, $schema['middleware']);
                }

                if (isset($selection->selectionSet) && !empty($selection->selectionSet->selections)) {
                    $this->connections = array_merge(
                        $this->connections,
                        $this->parser->getConnections(
                            $selection->selectionSet->selections,
                            $selection->name->value
                        )
                    );
                }
            }
        }
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
     * Check if middleware stack is empty.
     *
     * @return boolean
     */
    protected function hasMiddlewareStack()
    {
        return !empty($this->middlewareStack);
    }
}
