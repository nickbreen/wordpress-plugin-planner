<?php
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
