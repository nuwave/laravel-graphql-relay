<?php

namespace Nuwave\Relay\Types;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Folklore\GraphQL\Support\Type as GraphQLType;

class PageInfoType extends GraphQLType
{
    /**
     * Attributes of PageInfo.
     *
     * @var array
     */
    protected $attributes = [
        'name' => 'PageInfo',
        'description' => 'Information about pagination in a connection.'
    ];

    /**
     * Fields available on PageInfo.
     *
     * @return array
     */
    public function fields()
    {
        return [
            'hasNextPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating forwards, are there more items?',
                'resolve' => function ($collection, $test) {
                    if ($collection instanceof LengthAwarePaginator) {
                        return $collection->hasMorePages();
                    }

                    return false;
                }
            ],
            'hasPreviousPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating backwards, are there more items?',
                'resolve' => function ($collection) {
                    if ($collection instanceof LengthAwarePaginator) {
                        return $collection->currentPage() > 1;
                    }

                    return false;
                }
            ]
        ];
    }
}
