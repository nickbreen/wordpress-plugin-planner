<?php
register_rest_route($ns, '/calendar/resource', array(
    'methods' => WP_REST_Server::READABLE,
    'permission_callback' => function (WP_REST_Request $request) {
        return current_user_can('planner');
    },
    'callback' => function (WP_REST_Request $request) {
        $resource_pod = get_option('wordpress-plugin-planner-calendar-resource-pod', 'client');
        $pod = pods($resource_pod, []);
        $data = [
            // [
            //     'id' => 0,
            //     'title' => 'Ungrouped'
            // ]
        ];
        while ($pod->fetch()) {
            $data[] = [
                'id' => $pod->id(),
                'title' => $pod->field('name'),
                'url' => get_edit_term_link($pod->id(), $pod->api->pod_data['name'])
            ];
        }
        $res = new WP_REST_Response($data);
        $res->header('Content-Type', 'application/resource+json');
        return $res;

    },
));
