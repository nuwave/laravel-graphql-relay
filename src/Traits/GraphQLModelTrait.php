<?php

namespace Nuwave\Relay\Traits;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;

trait GraphQLModelTrait
{

    /**
     * used to store eagerloadingArr temporarily while looping
     * @var array
     */
    private $eagerloadingArr = [];

    /**
     * check arguements and add eagerloading to the query
     *
     * @param Builder $query
     * @param ResolveInfo $info graphql info object
     * @param array $allowedConnections array of connections that will be eagerloaded automatically
     *
     * @return Builder
     */
    public function scopeEagerloader(Builder $query, ResolveInfo $info, array $allowedConnections = [])
    {

        $fields = $info->getFieldSelection(10);

        // flip the allowedConnection array around to search for keys not for values (performance)
        $allowedConnectionsFlipped = array_flip($allowedConnections);

        // walk through fields and create eager loading array
        $eager_arr = $this->eagerloaderWalk($fields, '', $allowedConnectionsFlipped);


        return $query->with($eager_arr);

    }

    /**
     * walks through nodes and generates an eager loading array
     *
     * @param array  $nodes an of nodes to walk through
     * @param string $breadcrumb current path we want to add
     * @param array $allowedConnectionsFlipped array of connections that will be eagerloaded automatically where keys are the field names
     *
     * @return array
     */
    public function eagerloaderWalk($nodes, $breadcrumb = '', array $allowedConnectionsFlipped = [])
    {

        // loop through nodes and check for new subnodes in edges
        $didGoDown = false;
        foreach ($nodes as $field => $keys) {
            // if a field has subvalues like edges -> it is a connection
            if (is_array($keys) && isset($keys['edges']) && isset($keys['edges']['node'])
                && (count($allowedConnectionsFlipped) < 1 || isset($allowedConnectionsFlipped[$field])) ) {
                $didGoDown = true;
                $this->eagerloaderWalk(
                    $keys['edges']['node'],
                    $breadcrumb .".". $field
                );
            }
        }

        // if we are not on top level and didn't go down anywhere this loop -> add it to eagerloadingArr
        if($breadcrumb != '' && !$didGoDown) {
            $this->eagerloadingArr[] = substr($breadcrumb, 1);
        }

        return $this->eagerloadingArr;

    }

}