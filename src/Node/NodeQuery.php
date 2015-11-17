<?php

namespace Nuwave\Relay\Node;

use GraphQL;
use Nuwave\Relay\GlobalIdTrait;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ResolveInfo;
use Folklore\GraphQL\Support\Query;

class NodeQuery extends Query
{
    use GlobalIdTrait;

    /**
     * Associated GraphQL Type.
     *
     * @return mixed
     */
    public function type()
    {
        return GraphQL::type('node');
    }

    /**
     * Arguments available on node query.
     *
     * @return array
     */
    public function args()
    {
        return [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::id())
            ]
        ];
    }

    /**
     * Resolve query.
     *
     * @param  string $root
     * @param  array $args
     * @return Illuminate\Database\Eloquent\Model|array
     */
    public function resolve($root, array $args, ResolveInfo $info)
    {
        return $this->getModel($args);
    }

    /**
     * Get associated model.
     *
     * @param  array $args
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function getModel(array $args)
    {
        // Here, we decode the base64 id and get the id of the type
        // as well as the type's name.
        //
        list($typeClass, $id) = $this->decodeGlobalId($args['id']);

        // Types must be registered in the graphql.php config file.
        //
        foreach (config('graphql.types') as $type => $class) {
            if ($typeClass == $class) {
                $objectType = app($typeClass);
                $model = $objectType->resolveById($id);

                if (is_array($model)) {
                    $model['graphqlType'] = $type;
                } elseif (is_object($model)) {
                    $model->graphqlType = $type;
                }

                return $model;
            }
        }

        return null;
    }
}
