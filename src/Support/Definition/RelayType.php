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
        return collect($this->connections())->transform(function ($edge, $name) {
            if (!isset($edge['resolve'])) {
                $edge['resolve'] = function ($root, array $args, ResolveInfo $info) use ($name, $edge) {
                    $connection = GraphQL::resolveConnection($root, $args, $info, $name);

                    if (isset($edge['load'])) {
                        $this->autoLoad($connection, $edge, $name);
                    }

                    return $connection;
                };
            }

            $edge['args'] = RelayConnectionType::connectionArgs();

            return $edge;

        })->toArray();
    }

    /**
     * Auto load the fields connection(s).
     *
     * @param  Paginator $collection
     * @param  array     $edge
     * @param  string    $name
     * @return void
     */
    protected function autoLoad(Paginator $collection, array $edge, $name)
    {
        $relay = app('relay');

        if ($relay->isParent($name)) {
            if ($typeClass = $this->typeFromSchema($edge['type'])) {
                $type = app($typeClass);
                $connections = $relay->connectionsInRequest($name, $type->connections());
                foreach ($connections as $key => $connection) {
                    if (isset($connection['load'])) {
                        $load = $connection['load'];
                        $load($collection, $relay->connectionArguments($key));
                    }
                }
            }
        }
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
     * Decode cursor from query arguments.
     *
     * @param  array  $args
     * @return integer
     */
    protected function decodeCursor(array $args)
    {
        return isset($args['after']) ? $this->getCursorId($args['after']) : 0;
    }

    /**
     * Get id from encoded cursor.
     *
     * @param  string $cursor
     * @return integer
     */
    protected function getCursorId($cursor)
    {
        return (int)$this->decodeRelayId($cursor);
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
