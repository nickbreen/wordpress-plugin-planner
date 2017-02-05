<?php
add_action('rest_api_init', function (WP_REST_Server $server) use ($ns) {
    register_rest_route($ns, '/calendar/event', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function (WP_REST_Request $request) {
            return current_user_can('planner');
        },
        'callback' => function (WP_REST_Request $request) use ($ns) {
            $pod = pods('plan', [
                'where' => sprintf(
                    'UNIX_TIMESTAMP(plan_date.meta_value) BETWEEN %1$d AND %2$d '.
                    'OR '.
                    'UNIX_TIMESTAMP(DATE_ADD(plan_date.meta_value, INTERVAL duration.meta_value DAY)) BETWEEN %1$d AND %2$d ',
                    // TODO support duations > 7 days
                    strtotime($request['start']),
                    strtotime($request['end'])
                )
            ]);
            $data = [];
            while ($pod->fetch()) {
                $color = $pod->field('driver.colour') ?? null;
                $data[] = [
                    'id' => $pod->id(),
                    'title' => $pod->field('post_title'),
                    'content' => pods('plan', $pod->id())->template('plan'),
                    'data' => [
                        'url' => rest_url('/wp/v2/plan/' . $pod->id()),
                        'nonce' => wp_create_nonce('wp_rest'),
                    ],
                    'start' => $pod->field('plan_date').'T'.$pod->field('pu_time'),
                    'end' => date('Y-m-d', strtotime(sprintf('%s +%d days', $pod->field('plan_date'), $pod->field('duration')))),
                    'color' => is_array($color) ? current($color) : $color,
                    'textColor' => wordpress_plugin_planner_contrast_color(is_array($color) ? current($color) : $color),
                    'borderColor' => 'rgba(0,0,0,0.25)',
                    'url' => get_edit_post_link($pod->id(), null),
                    'resourceId' => $pod->field('plan_group.term_id') ? $pod->field('plan_group.term_id') : 0
                ];
            }
            $res = new WP_REST_Response($data);
            $res->header('Content-Type', 'application/event+json');
            return $res;
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
            )
        ),
    ));
    register_rest_route($ns, '/calendar/resource', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function (WP_REST_Request $request) {
            return current_user_can('planner');
        },
        'callback' => function (WP_REST_Request $request) {
            $pod = pods('plan_group', []);
            $data = [
                [
                    'id' => 0,
                    'title' => 'Ungrouped'
                ]
            ];
            while ($pod->fetch()) {
                $data[] = [
                    'id' => $pod->id(),
                    'title' => $pod->field('name'),
                    'url' => get_edit_post_link($pod->id(), null)
                ];
            }
            $res = new WP_REST_Response($data);
            $res->header('Content-Type', 'application/resource+json');
            return $res;

        },
    ));
    $templates = pods_api()->load_templates();
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
});
