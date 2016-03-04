<?php

namespace Nuwave\Relay\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LaravelController extends Controller
{
    /**
     * Execute GraphQL query.
     *
     * @param  Request $request
     * @return Response
     */
    public function query(Request $request)
    {
        $query  = $request->get('query');
        $params = $request->get('variables');

        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        return app('graphql')->query($query, $params);
    }
}
