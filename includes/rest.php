<?php
add_action('rest_api_init', function (WP_REST_Server $server) use ($ns) {
    $templates = pods_api()->load_templates();
    $rests = glob(__DIR__ . '/rest/*.php');
    foreach ($rests as $rest)
        require $rest;
});
