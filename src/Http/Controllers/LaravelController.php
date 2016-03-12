<?php

namespace Nuwave\Relay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nuwave\Relay\Traits\RelayMiddleware;

class LaravelController extends Controller
{
    use RelayMiddleware;

    /**
     * Create new instance of grapql controller.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->setupQuery($request);
    }

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
