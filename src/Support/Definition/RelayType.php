<?php

namespace Nuwave\Relay\Support\Definition;

use GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Nuwave\Relay\Traits\GlobalIdTrait;

abstract class RelayType extends GraphQLType
{

    use GlobalIdTrait;

    /**
     * List of fields with global identifier.
     *
     * @return array
     */
    public function fields()
    {
        return array_merge($this->relayFields(), $this->getConnections(), [
            'id' => [
                'type'        => Type::nonNull(Type::id()),
                'description' => 'ID of type.',
                'resolve'     => function ($obj) {
                    return $this->encodeGlobalId(get_called_class(), $this->getIdentifier($obj));
                },
            ],
        ]);
    }

    /**
     * Available connections for type.
     *
     * @return array
     */
    protected function connections()
    {
        return [];
    }

    /**
     * Generate Relay compliant edges.
     *
     * @return array
     */
    public function getConnections()
    {
        $graphql = app('graphql');

        return collect($this->connections())->transform(function ($edge, $name) use ($graphql) {
            if (!isset($edge['resolve'])) {
                $edge['resolve'] = function ($root, array $args, ResolveInfo $info) use ($name) {
                    return GraphQL::resolveConnection($root, $args, $info, $name);
                };
            }

            $edge['args'] = RelayConnectionType::connectionArgs();

            return $edge;

        })->toArray();
    }

    /**
     * Get the identifier of the type.
     *
     * @param  mixed $obj
     * @return mixed
     */
    public function getIdentifier($obj)
    {
        return $obj->id;
    }

    /**
     * List of available interfaces.
     *
     * @return array
     */
    public function interfaces()
    {
        return [GraphQL::type('node')];
    }

    /**
     * Get list of available fields for type.
     *
     * @return array
     */
    abstract protected function relayFields();

    /**
     * Fetch type data by id.
     *
     * @param string $id
     *
     * @return mixed
     */
    abstract public function resolveById($id);
}
