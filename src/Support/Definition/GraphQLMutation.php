<?php

namespace Nuwave\Relay\Support\Definition;

use Illuminate\Support\Collection;
use Nuwave\Relay\Schema\GraphQL;

class GraphQLMutation extends GraphQLField
{
    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        $collection = new Collection($this->args());

        $arguments = func_get_args();

        return $collection
            ->transform(function ($arg) use ($arguments) {
                if (isset($arg['rules'])) {
                    if (is_callable($arg['rules'])) {
                        return call_user_func_array($arg['rules'], $arguments);
                    } else {
                        return $arg['rules'];
                    }
                }

                return null;
            })
            ->merge(call_user_func_array([$this, 'rules'], $arguments))
            ->toArray();
    }

    /**
     * Get the field resolver.
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

            $this->validate($arguments);

            return call_user_func_array($resolver, $arguments);
        };
    }

    /**
     * The validation rules for this mutation.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Validate relay mutation.
     *
     * @param  array $args
     * @throws ValidationError
     * @return void
     */
    protected function validate(array $args)
    {
        $rules = $this->getRules(...$args);

        if (sizeof($rules)) {
            $validator = app('validator')->make($args['input'], $rules);

            if ($validator->fails()) {
                throw with(new ValidationError('Validation failed', $validator));
            }
        }
    }
}
