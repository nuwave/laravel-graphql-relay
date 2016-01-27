<?php

namespace Nuwave\Relay\Traits;

use Illuminate\Http\Request;
use GraphQL\Language\Source;
use GraphQL\Language\Parser;

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
        $source = new Source($request->get('query', 'GraphQL request'));
        $ast    = Parser::parse($source);

        if (isset($ast->definitions[0])) {
            $d            = $ast->definitions[0];
            $operation    = $d->operation ?: 'query';
            $selectionSet = $d->selectionSet->selections;

            foreach ($selectionSet as $selection) {
                if (is_object($selection) && $selection instanceof \GraphQL\Language\AST\Field) {
                    try {
                        $schema = $relay->find($selection->name->value, $operation);

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
