<?php
register_rest_route($ns, '/(?<pod>driver|vehicle)', array(
    'methods' => WP_REST_Server::READABLE,
    'permission_callback' => function (WP_REST_Request $request) {
        return current_user_can('planner');
    },
    'callback' => function (WP_REST_Request $request) use ($templates) {
        $pod = pods($request->get_param('pod'), []);
        if ($request->get_param('template')) {
            $data = $pod->template($request->get_param('template'));
        } else {
            $data = [];
            while ($pod->fetch()) {
                $data[] = $pod->export_data();
            }
        }
        return $data;
    },
    'args' => array(
        'pod' => array(
            'validate_callback' => function ($param, WP_REST_Request $request, $key) use ($templates) {
                return !!pods($param, null, true);
            },
        ),
        'template' => array(
            'validate_callback' => function ($param, WP_REST_Request $request, $key) use ($templates) {
                return isset($templates[$param]);
            },
        ),
    ),
));

register_rest_route($ns, '/(?<pod>airport|transfer|tour)/(?<id>\d+)', array(
    'methods' => 'GET, PATCH',
    'permission_callback' => function (WP_REST_Request $request) {
        return current_user_can('planner');
    },
    'callback' => function (WP_REST_Request $request) use ($templates) {
        $pod = pods($request->get_param('pod'), $request->get_param('id'));
        if (!$pod->exists())
            return new WP_Error(sprintf("Item `%d` for pod `%s` does not exist", $request->get_param('id'), $request->get_param('pod')));
        switch ($request->get_method()) {
            case 'GET':
                return $pod->export();
            case 'PATCH':
                user_error(print_r($request->get_json_params(),1));
                $params = array_map(
                    function ($v) {
                        return array_map(function ($v) {
                                if (is_object($v))
                                    return $v->ID;
                                else if (is_array($v))
                                    return $v['ID'];
                                else
                                    return $v;
                            }, $v);
                    },
                    $request->get_json_params()
                );
                user_error(json_encode($params));
                $id = $pod->save($params);
                $response = new WP_REST_Response($pod->export_data());
                $response->set_status($id ? 201 : 400);
                return $response;
            default:
                return WP_Error("Method Not Supported");
        }
    },
    'args' => array(
        'driver' => array(
            'validate_callback' => function ($value, WP_REST_Request $request, $key) {
                return count($value) == count(array_filter($value, function ($v) use ($key) {
                    if (is_object($v))
                        return pods($key, $v->ID)->exists();
                    else if (is_array($v))
                        return pods($key, $v['ID'])->exists();
                    else if (is_scalar($v))
                        return pods($key, $v)->exists();
                    else
                        return false;
                }));
            },
        ),
        'vehicle' => array(
            'validate_callback' => function ($value, WP_REST_Request $request, $key) {
                return count($value) == count(array_filter($value, function ($v) use ($key) {
                    if (is_object($v))
                        return pods($key, $v->ID)->exists();
                    else if (is_array($v))
                        return pods($key, $v['ID'])->exists();
                    else if (is_scalar($v))
                        return pods($key, $v)->exists();
                    else
                        return false;
                }));
            },
        ),
    ),
));
