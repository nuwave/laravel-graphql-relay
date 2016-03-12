<?php

namespace Nuwave\Relay\Traits;

use Illuminate\Http\Request;
use GraphQL\Language\Source;
use GraphQL\Language\Parser;

trait RelayMiddleware
{
    /**
     * Genarate middleware and connections from query.
     *
     * @param  Request $request
     * @return array
     */
    public function setupQuery(Request $request)
    {
        $relay = app('relay');
        $relay->setupRequest($request->get('query'));

        foreach ($relay->middleware() as $middleware) {
            $this->middleware($middleware);
        }
    }

    /**
     * Process GraphQL query.
     *
     * @param  Request $request
     * @return Response
     */
    public function graphqlQuery(Request $request)
    {
        $query = $request->get('query');
        $params = $request->get('variables');

        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        return app('graphql')->query($query, $params);
    }
}
