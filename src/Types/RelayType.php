<?php

namespace Nuwave\Relay\Types;

use GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Nuwave\Relay\Traits\GlobalIdTrait;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;


abstract class RelayType extends \Folklore\GraphQL\Support\Type
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
            $edge['resolve'] = function ($collection, array $args, ResolveInfo $info) use ($name) {
                $items = $this->getItems($collection, $info, $name);

                if (isset($args['first'])) {
                    $total       = $items->count();
                    $first       = $args['first'];
                    $after       = $this->decodeCursor($args);
                    $currentPage = $first && $after ? floor(($first + $after) / $first) : 1;

                    return new Paginator(
                        $items->slice($after)->take($first),
                        $total,
                        $first,
                        $currentPage
                    );
                }

                return new Paginator(
                    $items,
                    count($items),
                    count($items)
                );
            };

            $edge['args'] = ConnectionType::connectionArgs();

            return $edge;

        })->toArray();
    }

    /**
     * @param             $collection
     * @param ResolveInfo $info
     * @param             $name
     * @return mixed|Collection
     */
    protected function getItems($collection, ResolveInfo $info, $name)
    {
        $items = [];

        if ($collection instanceof Model) {
            // Selects only the fields requested, instead of select *
            $items = method_exists($collection, $name)
                ? $collection->$name()->select(...$this->getSelectFields($info))->get()
                : $collection->getAttribute($name);
            return $items;
        } elseif (is_object($collection) && method_exists($collection, 'get')) {
            $items = $collection->get($name);
            return $items;
        } elseif (is_array($collection) && isset($collection[$name])) {
            $items = new Collection($collection[$name]);
            return $items;
        }

        return $items;
    }

    /**
     * Select only certain fields on queries instead of all fields.
     *
     * @param ResolveInfo $info
     * @return array
     */
    protected function getSelectFields(ResolveInfo $info)
    {
        return collect($info->getFieldSelection(4)['edges']['node'])
            ->reject(function ($value) {
                is_array($value);
            })->keys()->toArray();
    }

    /**
     * Decode cursor from query arguments.
     *
     * @param  array  $args
     * @return integer
     */
    public function decodeCursor(array $args)
    {
        return isset($args['after']) ? $this->getCursorId($args['after']) : 0;
    }

    /**
     * Get the identifier of the type.
     *
     * @param \Illuminate\Database\Eloquent\Model $obj
     * @return mixed
     */
    public function getIdentifier(Model $obj)
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
