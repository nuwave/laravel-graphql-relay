<?php

namespace Nuwave\Relay\Support\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Nuwave\Relay\Traits\GlobalIdTrait;

class PageInfoType extends GraphQLType
{

    use GlobalIdTrait;

    /**
     * Attributes of PageInfo.
     *
     * @var array
     */
    protected $attributes = [
        'name' => 'pageInfo',
        'description' => 'Information to aid in pagination.'
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
                'resolve' => array($this, 'resolveHasNextPage')
            ],
            'hasPreviousPage' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'When paginating backwards, are there more items?',
                'resolve' => array($this, 'resolveHasPreviousPage')
            ],
            'startCursor' => [
                'type' => Type::string(),
                'description' => 'When paginating backwards, the cursor to continue.',
                'resolve' => array($this, 'resolveStartCursor')
            ],
            'endCursor' => [
                'type' => Type::string(),
                'description' => 'When paginating forwards, the cursor to continue.',
                'resolve' => array($this, 'resolveEndCursor')
            ]
        ];
    }

    /**
     * Determine if collection has a next page.
     *
     * @param  mixed $collection
     * @return boolean
     */
    public function resolveHasNextPage($collection)
    {
        if ($collection instanceof LengthAwarePaginator) {
            return $collection->hasMorePages();
        }

        return false;
    }

    /**
     * Determine if collection has previous page.
     *
     * @param  mixed $collection
     * @return boolean
     */
    public function resolveHasPreviousPage($collection)
    {
        if ($collection instanceof LengthAwarePaginator) {
            return $collection->currentPage() > 1;
        }

        return false;
    }

    /**
     * Resolve start cursor for edge.
     *
     * @param  mixed $collection
     * @return string|null
     */
    public function startCursor($collection)
    {
        if ($collection instanceof LengthAwarePaginator) {
            return $this->encodeGlobalId(
                'arrayconnection',
                $collection->firstItem() * $collection->currentPage()
            );
        }

        return null;
    }

    /**
     * Resolve end cursor for edge.
     *
     * @param  mixed $collection
     * @return string|null
     */
    public function resolveEndCursor($collection)
    {
        if ($collection instanceof LengthAwarePaginator) {
            return $this->encodeGlobalId(
                'arrayconnection',
                $collection->lastItem() * $collection->currentPage()
            );
        }

        return null;
    }
}
