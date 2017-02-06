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
