<?php

namespace Nuwave\Relay\Schema;

use GraphQL\Language\AST\ASTField;

class Connection
{
    /**
     * Name of selection.
     *
     * @var string
     */
    public $name;

    /**
     * Depth of selection.
     *
     * @var integer
     */
    public $depth;

    /**
     * Nested path of Selection.
     *
     * @var string
     */
    public $path = '';

    /**
     * Name of root query.
     *
     * @var string
     */
    public $query = '';

    /**
     * Set of arguments for selection.
     *
     * @var array
     */
    public $arguments = [];

    /**
     * If selection has arguments.
     *
     * @return boolean
     */
    public function hasArguments()
    {
        return !empty($this->arguments);
    }

    /**
     * Set arguments of selection.
     *
     * @param ASTField $field
     */
    public function setArguments(ASTField $field)
    {
        if ($field->arguments) {
            foreach ($field->arguments as $argument) {
                $this->arguments[$argument->name->value] = $argument->value->value;
            }
        }
    }

    /**
     * Set connection path.
     *
     * @param string $path
     */
    public function setPath($path = '')
    {
        $this->path = $path;
    }
}
