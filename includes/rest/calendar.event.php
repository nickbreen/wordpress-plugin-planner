<?php
register_rest_route($ns, '/calendar/event', array(
    'methods' => WP_REST_Server::READABLE,
    'permission_callback' => function (WP_REST_Request $request) {
        return current_user_can('planner') || current_user_can('driver');
    },
    'callback' => function (WP_REST_Request $request) use ($ns) {
        $driver = current_user_can('planner') ? false : pods('driver', [
            'where' => sprintf('user.id = %d', get_current_user_id())
        ]);

        $event_pods = get_option('wordpress-plugin-planner-calendar-event-pods', [
            'tour' => ['start_date', 'end_date', 'pick_up_time', 'midnight tomorrow'],
            'airport' => ['date', 'date', 'flight_time'],
            'transfer' => ['pick_up_datetime', 'drop_off_datetime'],
        ]);
        $data = [];
        foreach ($event_pods as $event_pod => $date_column) {
            $pod = pods($event_pod, [
                'where' => sprintf(
                    '%2$d < UNIX_TIMESTAMP(DATE_ADD(%5$s, INTERVAL 1 DAY)) AND '.
                    '%3$d > UNIX_TIMESTAMP(%4$s) AND '.
                    '%1$d IN (0, driver.id)',
                    // From https://stackoverflow.com/a/2546046/4016256
                    $driver ? ($driver->id() ?? -1 ) : 0, // show all to planners, and only the driver's to a driver
                    strtotime($request['start']),
                    strtotime($request['end']),
                    $date_column[0],
                    $date_column[1]
                )
            ]);
            while ($pod->fetch()) {
                $color = $pod->field('driver.colour') ?? null;
                $data[] = [
                    'id' => $pod->id(),
                    'title' => $pod->field('job_type') . ($pod->field('tour_name') ? ': '.$pod->field('tour_name') : ''),
                    'content' => pods($event_pod, $pod->id())->template($event_pod),
                    'data' => [
                        'url' => rest_url('/wp/v2/plan/' . $pod->id()),
                        'nonce' => wp_create_nonce('wp_rest'),
                    ],
                    'start' => date('c', strtotime($pod->field($date_column[0]).' '.$pod->field($date_column[2]))),
                    'end' => $date_column[0] == $date_column[1] ? null : date('c', strtotime(sprintf('%s %s', $date_column[3], $pod->field($date_column[1])))),
                    'color' => is_array($color) ? current($color) : $color,
                    'textColor' => wordpress_plugin_planner_contrast_color(is_array($color) ? current($color) : $color),
                    'borderColor' => 'rgba(0,0,0,0.25)',
                    'url' => admin_url(sprintf("admin.php?page=pods-manage-%s&action=add&id=%d", $event_pod, $pod->id())) ?: get_permalink($pod->id()),
                    'resourceId' => $pod->field('client_name.term_id')
                ];
            }
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
