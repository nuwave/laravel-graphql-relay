<?php

namespace Nuwave\Relay\Tests\Assets\Queries;

use GraphQL;
use Nuwave\Relay\Tests\Assets\Data\StarWarsData;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Folklore\GraphQL\Support\Query;

class HumanByName extends Query
{
    /**
     * Query's return type.
     *
     * @return mixed
     */
    public function type()
    {
        return GraphQL::type('human');
    }

    /**
     * Arguments query accepts.
     *
     * @return array
     */
    public function args()
    {
        return [
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string())
            ]
        ];
    }

    /**
     * Resolve the query.
     *
     * @param  mixed       $_
     * @param  array       $args
     * @param  ResolveInfo $info
     * @return array
     */
    public function resolve($_, array $args, ResolveInfo $info)
    {
        $humans = StarWarsData::humans();

        foreach ($humans as $human) {
            if ($human['name'] == $args['name']) {
                return $human;
            }
        }

        return null;
    }
}
