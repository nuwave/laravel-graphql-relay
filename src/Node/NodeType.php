<?php

namespace Nuwave\Relay\Node;

use GraphQL;
use GraphQL\Type\Definition\Type;
use Nuwave\Relay\Support\Definition\GraphQLInterface;

class NodeType extends GraphQLInterface
{
    /**
     * Interface attributes.
     *
     * @var array
     */
    public function attributes()
    {
        return [
            'name' => 'Node',
            'description' => 'An object with an ID.'
        ];
    }

    /**
     * Available fields on type.
     *
     * @return array
     */
    public function fields()
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'The id of the object.'
            ]
        ];
    }

    /**
     * Resolve the interface.
     *
     * @param  mixed $obj
     * @return mixed
     */
    public function resolveType($obj)
    {
        if (is_array($obj)) {
            return GraphQL::type($obj['graphqlType']);
        }

        return GraphQL::type($obj->graphqlType);
    }
}
