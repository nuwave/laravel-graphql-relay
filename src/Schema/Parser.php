<?php

namespace Nuwave\Relay\Schema;

use GraphQL\Language\AST\Field;
use Illuminate\Support\Collection;

class Parser
{
    /**
     * Parsed connections.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Current selection path.
     *
     * @var array
     */
    protected $path = [];

    /**
     * Current depth.
     *
     * @var integer
     */
    protected $depth = 0;

    /**
     * Relay edge names.
     *
     * @var array
     */
    protected $relayEdges = ['pageInfo', 'edges', 'node'];

    /**
     * Determine if selection is a Field
     * .
     * @param  mixed  $selection
     * @return boolean
     */
    public function isField($selection)
    {
        return is_object($selection) && $selection instanceof Field;
    }

    /**
     * Get arguments from query.
     *
     * @param  array $selectionSet
     * @param  string $root
     * @return array
     */
    public function getConnections(array $selectionSet = [], $root = '')
    {
        $this->initialize();

        $this->parseFields($selectionSet, $root);

        return $this->connections;
    }

    /**
     * Set the selection set.
     *
     * @return void
     */
    public function initialize()
    {
        $this->depth = 0;
        $this->path = [];
        $this->connections = [];
    }

    /**
     * Determine if field has selection set.
     *
     * @param  Field   $field
     * @return boolean
     */
    protected function hasChildren($field)
    {
        return $this->isField($field) && isset($field->selectionSet) && !empty($field->selectionSet->selections);
    }

    /**
     * Determine if name is a relay edge.
     *
     * @param  string  $name
     * @return boolean
     */
    protected function isEdge($name)
    {
        return in_array($name, $this->relayEdges);
    }

    /**
     * Parse arguments.
     *
     * @param  array $selectionSet
     * @param  string $root
     * @return void
     */
    protected function parseFields(array $selectionSet = [], $root = '')
    {
        foreach ($selectionSet as $field) {
            if ($this->hasChildren($field)) {
                $name = $field->name->value;;

                if (!$this->isEdge($name)) {
                    $this->path[] = $name;

                    $connection = new Connection;
                    $connection->name = $name;
                    $connection->root = $root;
                    $connection->path = implode('.', $this->path);
                    $connection->depth = count($this->path);
                    $connection->setArguments($field);

                    $this->connections[] = $connection;
                }

                $this->parseFields($field->selectionSet->selections, $root);
            }
        }

        array_pop($this->path);
    }
}
