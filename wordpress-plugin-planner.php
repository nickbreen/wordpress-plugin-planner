<?php
/*
Plugin Name: Planner
Version: 0.2.0
Description: Uses pods to plan group tours.
Author: Nick Breen
Author URI: http://foobar.net.nz
Plugin URI: https://github.com/nickbreen/wordpress-plugin-planner
Text Domain: wordpress-plugin-planner
Domain Path: /languages
*/

$capability = "plan";
$page = 'wordpress-plugin-planner';

$function = function () use ($page) {

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

    $plan = pods('plan')->find([
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
            [$plan->field('ID')] =
                array_combine(array_keys($fields), array_map([$plan, 'display'], array_keys($fields)));
    }

    wp_enqueue_script('planner', plugins_url( 'assets/js/planner.js', __FILE__ ), ['jquery-ui-datepicker'], '0.0.0', true);
    wp_enqueue_style('planner', plugins_url( 'assets/css/planner.css', __FILE__ ), ['jquery-ui']);

    return (function () use ($page, $time, $plans, $fields) {
        return require( __DIR__ . '/includes/admin/planner.php');
    })();
};

register_activation_hook(__FILE__, function () use ($capability) {
    add_role('driver', __('Driver', 'wordpress-plugin-planner'), [$capability => true]);
    add_role('planner', __('Planner', 'wordpress-plugin-planner'), [$capability => true]);
    pods_api()->import_package(json_decode(file_get_contents('package.json')));
});

register_deactivation_hook(__FILE__, function () {
    remove_role('planner');
    remove_role('driver');
});

add_filter('custom_menu_order', function ($menu_ord) use ($page) {
    global $submenu;
    array_unshift($submenu[$page], array_pop($submenu[$page]));
    return $menu_ord;
});

add_action('admin_menu', function () use ($function, $capability, $page) {

    /**
     * add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
     */
    $planner_page = add_menu_page(
        __('Planner', 'wordpress-plugin-planner'), // page_title
        __('Planner', 'wordpress-plugin-planner'), // menu_title
        'plan', // capability
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
        $capability,
        $page, // menu_slug
        $function
    );
}, 49 );
