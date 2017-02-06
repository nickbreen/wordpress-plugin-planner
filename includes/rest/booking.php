<?php
register_rest_route($ns, '/booking', array(
    'methods' => WP_REST_Server::READABLE,
    'permission_callback' => function (WP_REST_Request $request) {
        return current_user_can('planner');
    },
    'callback' => function (WP_REST_Request $request) use ($templates) {
        $pod = pods('wc_booking', [
            'orderby' => '_booking_start.meta_value',
            'select' => 't.*, '.
                'STR_TO_DATE(_booking_start.meta_value, GET_FORMAT(DATETIME,"INTERNAL")) AS booking_start_date, '.
                '_booking_resource_id.meta_value AS resource_id, '.
                'CAST(_booking_product_id.meta_value AS UNSIGNED) AS product_id',
            'where' => sprintf(
                't.post_status IN ("confirmed", "paid", "complete") '.
                'AND UNIX_TIMESTAMP(STR_TO_DATE(_booking_start.meta_value, GET_FORMAT(DATETIME,"INTERNAL"))) BETWEEN %d AND %d',
                strtotime($request['start']),
                strtotime($request['end'])
            )
        ]);

        if (!$pod->total_found())
            return new WP_REST_Response(get_post_type_object('wc_booking')->labels->not_found, 200);

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
        'start' => array(
            'required' => true,
            'validate_callback' => function ($param, WP_REST_Request $request, $key) {
                return strtotime($param);
            },
        ),
        'end' => array(
            'required' => true,
            'validate_callback' => function ($param, WP_REST_Request $request, $key) {
                return strtotime($param);
            },
        ),
        'template' => array(
            'validate_callback' => function ($param, WP_REST_Request $request, $key) use ($templates) {
                return isset($templates[$param]);
            },
        )
    ),
));
