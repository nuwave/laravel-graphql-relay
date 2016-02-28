<?php

namespace Nuwave\Relay\Types;

use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

class EloquentType
{
    /**
     * Eloquent model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Available fields.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $fields;

    /**
     * Hidden type field.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $hiddenFields;

    /**
     * Create new instance of eloquent type.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->fields       = collect();
        $this->hiddenFields = collect($model->getHidden());
        $this->model        = $model;
    }

    /**
     * Transform eloquent model to graphql type.
     *
     * @return \GraphQL\Type\Definition\ObjectType
     */
    public function toType()
    {
        $this->createFields();

        $config = [];
        $config['name'] = $this->model->name ?: ucfirst((new ReflectionClass($this->model))->getShortName());
        $config['$description'] = isset($this->model->description) ? $this->model->description : null;
        $config['fields'] = $this->fields->toArray();

        return new ObjectType($config);
    }

    /**
     * Create fields for type.
     *
     * @return void
     */
    protected function createFields()
    {
        $table = $this->model->getTable();
        $schema = $this->model->getConnection()->getSchemaBuilder();
        $columns = collect($schema->getColumnListing($table));

        $columns->each(function ($column) use ($table, $schema) {
            if (!$this->skipAutoGenerate($column)) {
                $this->generateField(
                    $column,
                    $schema->getColumnType($table, $column)
                );
            }
        });
    }

    /**
     * Generate type field from schema.
     *
     * @param  string $name
     * @param  string $colType
     * @return void
     */
    protected function generateField($name, $colType)
    {
        $field = [];
        $field['type'] = $this->resolveTypeByColumn($name, $colType);
        $field['description'] = isset($this->descriptions['name']) ? $this->descriptions[$name] : null;

        if ($name === $this->model->getKeyName()) {
            $field['description'] = $field['description'] ?: 'Primary id of type.';
        }

        $resolve = 'resolve' . ucfirst(camel_case($name));

        if (method_exists($this->model, $resolve)) {
            $field['resolve'] = function ($root) use ($resolve) {
                return $this->model->{$resolve}($root);
            };
        }

        $fieldName = $this->model->camelCase ? camel_case($name) : $name;

        $this->fields->put($fieldName, $field);
    }

    /**
     * Resolve field type by column info.
     *
     * @param  string $name
     * @param  string $colType
     * @return \GraphQL\Type\Definition\Type
     */
    protected function resolveTypeByColumn($name, $colType)
    {
        $type = Type::string();

        if ($name === $this->model->getKeyName()) {
            $field['type'] = Type::nonNull(Type::id());
        } elseif ($type === 'integer') {
            $field['type'] = Type::int();
        } elseif ($type === 'float' || $type === 'decimal') {
            $field['type'] = Type::float();
        } elseif ($type === 'boolean') {
            $field['type'] = Type::boolean();
        }

        return $type;
    }

    /**
     * Check if field should be skipped.
     *
     * @param  string $field
     * @return boolean
     */
    protected function skipAutoGenerate($name = '')
    {
        if ($this->hiddenFields->has($name)) {
            return true;
        }

        return false;
    }
}
