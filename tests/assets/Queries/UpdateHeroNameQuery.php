<?php

namespace Nuwave\Relay\Tests\Assets;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Relay\Mutations\MutationWithClientId;

class UpdateHeroNameQuery extends MutationWithClientId
{
    /**
     * Name of Mutation.
     *
     * @return string
     */
    protected function name()
    {
        return 'updateHeroName';
    }

    /**
     * Input fields for mutation.
     *
     * @return array
     */
    protected function inputFields()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::string())
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string())
            ]
        ];
    }

    /**
     * Output fields for mutation.
     *
     * @return array
     */
    protected function outputFields()
    {
        return [
            'hero' => [
                'type' => GraphQL::type('hero'),
                'resolve' => function ($payload) {

                }
            ]
        ];
    }

    /**
     * Mutate payload.
     *
     * @param  array       $input
     * @param  ResolveInfo $info
     * @return array
     */
    protected function mutateAndGetPayload(array $input, ResolveInfo $info)
    {
        return ['id' => $input['id']];
    }
}
