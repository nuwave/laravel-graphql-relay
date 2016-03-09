<?php

namespace Nuwave\Relay\Support\Definition;

use Illuminate\Support\Collection;
use Nuwave\Relay\Support\ValidationError;
use Nuwave\Relay\Schema\GraphQL;
use Nuwave\Relay\Traits\GlobalIdTrait;

class GraphQLMutation extends GraphQLField
{
    use GlobalIdTrait;

    /**
     * Rules to apply to mutation.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Get rules for mutation.
     *
     * @return array
     */
    public function getRules()
    {
        $arguments = func_get_args();

        return collect($this->args())
            ->transform(function ($arg, $name) use ($arguments) {
                if (isset($arg['rules'])) {
                    if (is_callable($args['rules'])) {
                        return call_user_func_array($arg['rules'], $arguments);
                    }
                    return $arg['rules'];
                }
                return null;
            })->merge(call_user_func_array([$this, 'rules'], $arguments))
            ->toArray();
    }

    /**
     * Get the mutation resolver.
     *
     * @return \Closure|null
     */
    protected function getResolver()
    {
        if (!method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = array($this, 'resolve');

        return function () use ($resolver) {
            $arguments = func_get_args();
            $rules = call_user_func_array([$this, 'getRules'], $arguments);

            if (sizeof($rules)) {
                $args = array_get($arguments, 1, []);
                $validator = app('validator')->make($args, $rules);

                if ($validator->fails()) {
                    throw with(new ValidationError('validation'))->setValidator($validator);
                }
            }

            return call_user_func_array($resolver, $arguments);
        };
    }
}
