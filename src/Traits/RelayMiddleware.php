<?php

namespace Nuwave\Relay\Traits;

use Illuminate\Http\Request;
use Nuwave\Relay\Context;

trait RelayMiddleware
{
    /**
     * Middleware to be attached to GraphQL query.
     *
     * @var array
     */
    protected $relayMiddleware = [];

    /**
     * Genarate middleware to be run on query.
     *
     * @param  Request $request
     * @return array
     */
    public function queryMiddleware(Request $request)
    {
        $relay  = app('relay');
        $schema = app('graphql')->schema();
        $query  = $request->get('query');
        $params = $request->get('variables');

        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        if ($context = Context::get($schema, $query, null, $params)) {
            $context      = Context::get($schema, $query, null, $params);
            $operation    = $context->operation->operation;
            $selectionSet = $context->operation->selectionSet->selections;

            foreach ($selectionSet as $selection) {
                if (is_object($selection) && $selection instanceof \GraphQL\Language\AST\Field) {
                    try {
                        $schema = $relay->find(
                            $selection->name->value,
                            $context->operation->operation
                        );

                        if (isset($schema['middleware']) && !empty($schema['middleware'])) {
                            $this->relayMiddleware = array_merge($this->relayMiddleware, $schema['middleware']);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return array_unique($this->relayMiddleware);
    }
}
