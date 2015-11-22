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
     * @param  array $outputFields
     * @return $this
     */
    public function mutate($mutationName, array $input, array $outputFields = [])
    {
        $query = $this->generateMutationQuery(
            $mutationName,
            $this->generateOutputFields($mutationName, $outputFields)
        );

        $variables = ['input' => array_merge(['clientMutationId' => (string)time()], $input)];

        return $this->post('graphql', [
            'query'     => $query,
            'variables' => $variables
        ]);
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
        $mutations = config('graphql.schema.mutation');
        $mutation = app($mutations[$mutationName]);

        foreach ($mutation->type()->getFields() as $name => $field) {
            if ($field instanceof FieldDefinition) {
                $objectType = $field->getType();

                if ($objectType instanceof ObjectType) {
                    $fields = array_keys($objectType->getFields());
                    $outputFields[] = $name . '{'. implode(',', $fields) .'}';
                }
            }
        }

        return $outputFields;
    }
}
