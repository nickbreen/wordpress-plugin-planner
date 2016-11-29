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

register_activation_hook(__FILE__, function () {
    $role = add_role('planner', 'Planner', ['edit_plan' => true]);
});

register_deactivation_hook(__FILE__, function () {
    $role = remove_role('planner');
});

add_action('admin_menu', function () {
    /**
     * add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
     */
    $page = 'wordpress-plugin-planner';
    $planner_page = add_menu_page(
        __( 'Planner', 'wordpress-plugin-planner' ), // page_title
        __( 'Planner', 'wordpress-plugin-planner' ), // menu_title
        'edit_plan', // capability
        $page, // menu_slug
        function () use ($page) {

            // Work out the first day of the week
            $iFirstDay = get_option('start_of_week', 1);
            // Clamp the date to the start of the week
            $time = filter_input(INPUT_GET, 'week', FILTER_CALLBACK, [
                'options' => function ($value) use ($iFirstDay) {
                    return strtotime("last sunday +{$iFirstDay} days", strtotime($value));
                }
            ]) ?: strtotime("this sunday +{$iFirstDay} days", time());

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

            $keys = ['plan_date', 'vehicle', 'driver', 'passengers'];
            $fields = array_combine($keys, array_map([$plan, 'fields'], $keys));

            while ($plan->fetch()) {
                $plans[$plan->field('plan.post_title', null, true) ? $plan->field('plan.post_title', null, true) : '']
                    [date('w', strtotime($plan->field('plan_date')))][$plan->field('ID')] =
                        array_combine($keys, array_map([$plan, 'field'], $keys));
            }
            // TODO form?

            wp_enqueue_script('planner', plugins_url( 'assets/js/planner.js', __FILE__ ), ['jquery-ui-datepicker', 'jquery.chosen'], '0.0.0', true);
            wp_enqueue_style('planner', plugins_url( 'assets/css/planner.css', __FILE__ ));

            return (function () use ($page, $time, $plans, $fields) {
                return require( __DIR__ . '/includes/admin/planner.php');
            })();
        }, // function
        'dashicons-calendar', // icon_url
        30 // position
    );
}, 49 );
