<?php

namespace Nuwave\Relay\Support\Definition;

use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

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
     * @var \Illuminate\Support\DefinitionsCollection
     */
    protected $fields;

    /**
     * Hidden type field.
     *
     * @var \Illuminate\Support\DefinitionsCollection
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
        $this->hiddenFields = collect($model->getHidden())->flip();
        $this->model        = $model;
    }

    /**
     * Transform eloquent model to graphql type.
     *
     * @return \GraphQL\Type\Definition\ObjectType
     */
    public function toType()
    {
        $graphql = app('graphql');
        $name = $this->getName();
        $description = $this->getDescription();

        if ($fields = $graphql->cache()->get($name)) {
            $this->fields = $fields;
        } else {
            if (method_exists($this->model, 'graphqlFields')) {
                $this->eloquentFields();
            }

            $this->schemaFields();
            $graphql->cache()->store($name, $this->fields);
        }

        return new ObjectType([
            'name'        => $name,
            'description' => $description,
            'fields'      => $this->fields->toArray()
        ]);
    }

    /**
     * Get fields for model.
     *
     * @return \Illuminate\Support\DefinitionsCollection
     */
    public function rawFields()
    {
        if (method_exists($this->model, 'graphqlFields')) {
            $this->eloquentFields();
        } else {
            $this->schemaFields();
        }

        return $this->fields->transform(function ($field, $key) {
            $field['type'] = $this->getRawType($field['type']);

            return $field;
        });
    }

    /**
     * Convert eloquent defined fields.
     *
     * @return array
     */
    public function eloquentFields()
    {
        $fields = collect($this->model->graphqlFields());

        $fields->each(function ($field, $key) {
            if (!$this->skipField($key)) {
                $data = [];
                $data['type'] = $field['type'];
                $data['description'] = isset($field['description']) ? $field['description'] : null;

                if (isset($field['resolve'])) {
                    $data['resolve'] = $field['resolve'];
                } elseif ($method = $this->getModelResolve($key)) {
                    $data['resolve'] = $method;
                }

                $this->fields->put($key, $data);
            }
        });
    }

    /**
     * Create fields for type.
     *
     * @return void
     */
    protected function schemaFields()
    {
        $table = $this->model->getTable();
        $schema = $this->model->getConnection()->getSchemaBuilder();
        $columns = collect($schema->getColumnListing($table));

        $columns->each(function ($column) use ($table, $schema) {
            if (!$this->skipField($column)) {
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

        if ($method = $this->getModelResolve($name)) {
            $field['resolve'] = $method;
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
            $type = Type::id();
        } elseif ($colType === 'integer') {
            $type = Type::int();
        } elseif ($colType === 'float' || $colType === 'decimal') {
            $type = Type::float();
        } elseif ($colType === 'boolean') {
            $type = Type::boolean();
        }

        // Seems a bit odd, but otherwise we'll get an error thrown stating
        // that two types have the same name.
        $type->name = $this->getName().' '.$type->name;

        return $type;
    }

    /**
     * Get raw name for type.
     *
     * @param  Type   $type
     * @return string
     */
    protected function getRawType(Type $type)
    {
        $class = get_class($type);
        $namespace = 'GraphQL\\Type\\Definition\\';

        if ($class == $namespace . 'NonNull') {
            return 'Type::nonNull('. $this->getRawType($type->getWrappedType()) .')';
        } elseif ($class == $namespace . 'IDType') {
            return 'Type::id()';
        } elseif ($class == $namespace . 'IntType') {
            return 'Type::int()';
        } elseif ($class == $namespace . 'BooleanType') {
            return 'Type::bool()';
        } elseif ($class == $namespace . 'FloatType') {
            return 'Type::float()';
        }

        return 'Type::string()';
    }

    /**
     * Check if field should be skipped.
     *
     * @param  string $field
     * @return boolean
     */
    protected function skipField($name = '')
    {
        if ($this->hiddenFields->has($name) || $this->fields->has($name)) {
            return true;
        }

        return false;
    }

    /**
     * Check if model has resolve function.
     *
     * @param  string  $key
     * @return string|null
     */
    protected function getModelResolve($key)
    {
        $method = 'resolve' . studly_case($key) . 'Field';

        if (method_exists($this->model, $method)) {
            return array($this->model, $method);
        }

        return null;
    }

    /**
     * Get name for type.
     *
     * @return string
     */
    protected function getName()
    {
        return $this->model->name ?: ucfirst((new ReflectionClass($this->model))->getShortName());
    }

    /**
     * Get description of type.
     *
     * @return string
     */
    protected function getDescription()
    {
        return $this->model->description ?: null;
    }
}
