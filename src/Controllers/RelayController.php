<?php

namespace Nuwave\Relay\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class RelayController extends Controller
{
    /**
     * Excecute GraphQL query.
     *
     * @param  Request $request
     * @return Response
     */
    public function query(Request $request)
    {
        $query = $request->get('query');
        $params = $request->get('variables');

        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        return app('graphql')->query($query, $params);
    }
}
