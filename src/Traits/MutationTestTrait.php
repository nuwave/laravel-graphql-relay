<?php

namespace Nuwave\Relay\Traits;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\FieldDefinition;

trait MutationTestTrait
{
    /**
     * Send mutation query.
     *
     * @param  string $mutationName
     * @param  array  $input
     * @param  array  $outputFields
     * @param  array  $headers
     * @return $this
     */
    public function mutate($mutationName, array $input, array $outputFields = [], array $headers = [])
    {
        $query = $this->generateMutationQuery(
            $mutationName,
            $this->generateOutputFields($mutationName, $outputFields)
        );

        $variables = ['input' => array_merge(['clientMutationId' => (string)time()], $input)];

        return $this->post('graphql', [
            'query'     => $query,
            'variables' => $variables
        ], $headers);
    }

    /**
     * Generate mutation query.
     *
     * @param  string $mutation
     * @param  string  $outputFields
     * @return string
     */
    protected function generateMutationQuery($mutation, $outputFields = '')
    {
        $mutationName = ucfirst($mutation . 'Mutation');
        $inputName = ucfirst($mutation . 'Input');

        return 'mutation ' . $mutationName . '($input: ' . $inputName . '!){'. $mutation . '(input: $input){' . $outputFields . '}}';
    }

    /**
     * Generate list of available output fields.
     *
     * @param  string $mutationName
     * @return string
     */
    protected function generateOutputFields($mutationName, array $outputFields)
    {
        $fields = [];

        if (! empty($outputFields)) {
            foreach ($outputFields as $name => $availableFields) {
                $fields[] = $name . '{'. implode(',', $availableFields) .'}';
            }
        } else {
            $fields = $this->availableOutputFields($mutationName);
        }

        return implode(',', $fields);
    }

    /**
     * Get all available output fields for mutation.
     *
     * @param  string $mutationName
     * @return array
     */
    protected function availableOutputFields($mutationName)
    {
        $outputFields = ['clientMutationId'];
        $mutations = config('relay.schema.mutations');
        $mutation = app($mutations[$mutationName]);

        foreach ($mutation->type()->getFields() as $name => $field) {
            if ($field instanceof FieldDefinition) {
                $objectType = $field->getType();

                if ($objectType instanceof ObjectType) {
                    $fields = $this->includeOutputFields($objectType);
                    $outputFields[] = $name . '{'. implode(',', $fields) .'}';
                }
            }
        }

        return $outputFields;
    }

    /**
     * Determine if output fields should be included.
     *
     * @param  mixed $objectType
     * @return boolean
     */
    protected function includeOutputFields(ObjectType $objectType)
    {
        $fields = [];

        foreach ($objectType->getFields() as $name => $field) {
            $type = $field->getType();

            if ($type instanceof ObjectType) {
                $config = $type->config;

                if (isset($config['name']) && preg_match('/Connection$/', $config['name'])) {
                    continue;
                }
            }

            $fields[] = $name;
        }

        return $fields;
    }
}
