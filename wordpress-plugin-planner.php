<?php
/*
Plugin Name: Planner
Version: 0.5.0
Description: Uses pods to plan group tours.
Author: Nick Breen
Author URI: http://foobar.net.nz
Plugin URI: https://github.com/nickbreen/wordpress-plugin-planner
Text Domain: wordpress-plugin-planner
Domain Path: /languages
*/

$page = 'wordpress-plugin-planner';

$contrast = function ($ch) {
    $r = hexdec(substr($ch, 1, 2));
    $g = hexdec(substr($ch, 3, 2));
    $b = hexdec(substr($ch, 5, 2));
    return ((($r*299)+($g*587)+($b*144))/1000) >= 131.5 ? "black" : "white";
};

$function = function () use ($page, $contrast) {

    // Work out the first day of the week
    $iFirstDay = get_option('start_of_week', 1);
    // Clamp the date to the start of the week
    $time = filter_input(INPUT_GET, 'week', FILTER_CALLBACK, [
        'options' => function ($value) use ($iFirstDay) {
            return strtotime("midnight last sunday +{$iFirstDay} days", strtotime($value));
        }
    ]) ?: strtotime("midnight last sunday +{$iFirstDay} days", time());

    $plans = [];

    $planName = pods('plan_name', []);

    while ($planName->fetch()) {
        $plans[$planName->field('post_title')] = array_fill($iFirstDay, 7, []);
    }
    $plans[''] = array_fill($iFirstDay, 7, []);

    $plan = pods('plan', [
        'where' => sprintf(
            'UNIX_TIMESTAMP(plan_date.meta_value) BETWEEN %d AND %d',
            strtotime("midnight last sunday +{$iFirstDay} days", $time),
            strtotime("midnight next sunday +{$iFirstDay} days", $time)
        )
    ]);

    $fields = array_intersect_key(
        $plan->fields(),
        array_flip(['pu_loc', 'pu_time', 'passengers', 'flight', 'vehicle', 'driver'])
    );

    while ($plan->fetch()) {
        $plans[$plan->field('plan.post_title', null, true) ? $plan->field('plan.post_title', null, true) : '']
            [date('w', strtotime($plan->field('plan_date')))]
            [] = $plan->id();
    }

    $plans = array_filter($plans, function ($days) {
        return array_reduce($days, function ($carry, $item) {
            return $carry + count($item);
        }, 0);
    });

    $driver = pods('driver', []);

    wp_enqueue_script('planner', plugins_url( 'assets/js/planner.js', __FILE__ ), ['jquery-ui-datepicker'], '0.0.0', true);
    wp_enqueue_style('planner', plugins_url( 'assets/css/planner.css', __FILE__ ), ['jquery-ui']);

    return require( __DIR__ . '/includes/admin/planner.php');
};

register_activation_hook(__FILE__, function () {
    add_role('driver', __('Driver', 'wordpress-plugin-planner'), [
        'read_post' => true
    ]);
    add_role('planner', __('Planner', 'wordpress-plugin-planner'), [
        'read_post' => true,
        'edit_post' => true,
        'publish_post' => true,
        'delete_post' => true,
        'edit_others_post' => true
    ]);
});

register_deactivation_hook(__FILE__, function () {
    remove_role('planner');
    remove_role('driver');
});

add_filter('custom_menu_order', function ($menu_ord) use ($page) {
    global $submenu;
    while (next($submenu[$page])[2] != $page);
    if (key($submenu[$page]))
        array_unshift($submenu[$page], array_splice($submenu[$page], key($submenu[$page]), 1)[0]);
    return $menu_ord;
});

add_filter('redirect_post_location', function ($location) {
    global $post;
    if (
        ($ref = wp_get_original_referer()) && $post &&
        in_array($post->post_type, ['plan','driver','vehicle']) &&
        (isset($_POST['publish']) || $post->post_status == 'publish')
    ) {
        return $ref;
    }
    return $location;
});

add_action('admin_menu', function () use ($function, $page) {

    /**
     * add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
     */
    $planner_page = add_menu_page(
        __('Planner', 'wordpress-plugin-planner'), // page_title
        __('Planner', 'wordpress-plugin-planner'), // menu_title
        'planner', // capability$submenu[$page], array_pop($submenu[$page]));
        $page, // menu_slug
        $function,
        'dashicons-calendar', // icon_url
        null // position
    );

    /**
     * add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '' )
     */
    $calendar_page = add_submenu_page(
        $page,
        __('Planner', 'wordpress-plugin-planner'), // page_title
        __('Planner', 'wordpress-plugin-planner'), // menu_title
        'planner',
        $page, // menu_slug
        $function
    );

}, 49 );
