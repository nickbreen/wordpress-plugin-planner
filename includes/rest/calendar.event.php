<?php
register_rest_route($ns, '/calendar/event', array(
    'methods' => WP_REST_Server::READABLE,
    'permission_callback' => function (WP_REST_Request $request) {
        return current_user_can('planner') || current_user_can('driver');
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
                'url' => get_edit_post_link($pod->id(), null) ?? get_permalink($pod->id()),
                'resourceId' => $pod->field('group.term_id') ? $pod->field('group.term_id') : 0
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
