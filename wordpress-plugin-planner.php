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

$text_domain = 'wordpress-plugin-planner';
$page = 'wordpress-plugin-planner';

function contrast_color($ch) {
    $r = hexdec(substr($ch, 1, 2));
    $g = hexdec(substr($ch, 3, 2));
    $b = hexdec(substr($ch, 5, 2));
    return ((($r*299)+($g*587)+($b*144))/1000) >= 131.5 ? "black" : "white";
};

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
            [$plan->id()] = 'plan';
    }

    $plans = array_filter($plans, function ($days) {
        return array_reduce($days, function ($carry, $item) {
            return $carry + count($item);
        }, 0);
    });

    $driver = pods('driver', []);

    wp_enqueue_script('planner', plugins_url('assets/js/planner.js', __FILE__), ['jquery-ui-datepicker'], '0.0.0', true);
    wp_enqueue_style('planner', plugins_url('assets/css/planner.css', __FILE__), ['jquery-ui', 'plan', 'driver']);

    return require __DIR__ . '/includes/admin/planner.php';
};

add_action('init', function () {
    wp_enqueue_style('plan', plugins_url('assets/css/plan.css', __FILE__));
    wp_enqueue_style('driver', plugins_url('assets/css/drivers.css', __FILE__));
});

register_activation_hook(__FILE__, function () use ($text_domain) {
    add_role('driver', __('Driver', $text_domain), [
        'read_post' => true
    ]);
    add_role('planner', __('Planner', $text_domain), [
        'read_post' => true,
        'edit_post' => true,
        'publish_post' => true,
        'delete_post' => true,
        'edit_others_post' => true
    ]);
    add_option('activated_plugin_'.__FILE__, true);
});

add_action('init', function () {
    if (get_option('activated_plugin_'.__FILE__)) {
        $files = glob(__DIR__.'/templates/pods/*.html');
        if (false !== $files) {
            foreach ($files as $file) {
                $name = basename($file, '.html');
                $template = pods_api()->load_template(['name' => $name]);
                if (false === $template)
                $template = ['name' => $name];
                $template['code'] = file_get_contents($file);
                $id = pods_api()->save_template($template);
            }
        }
        if (!defined('WP_DEBUG'))
            delete_option('activated_plugin_'.__FILE__);
    }
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

add_action('admin_menu', function () use ($function, $page, $text_domain) {

    /**
     * add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null )
     */
    $planner_page = add_menu_page(
        __('Planner', $text_domain), // page_title
        __('Planner', $text_domain), // menu_title
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
        __('Planner', $text_domain), // page_title
        __('Planner', $text_domain), // menu_title
        'planner',
        $page, // menu_slug
        $function
    );

}, 49 );

$endpoint = get_option('wordpress-plugin-planner-driver-plans-endpoint', 'plans');
$label = get_option('wordpress-plugin-planner-driver-plans-label', 'Plans');

add_filter('woocommerce_account_menu_items', function ($items) use ($endpoint, $label, $text_domain) {
    if (current_user_can('driver'))
        $items[$endpoint] = __($label, $text_domain);
    return $items;
});

add_action('init', function () use ($endpoint) {
    add_rewrite_endpoint($endpoint, EP_ROOT|EP_PAGES);
});

add_filter("woocommerce_endpoint_${endpoint}_title", function ($items) use ($label, $text_domain) {
    return __($label, $text_domain);
});

add_action("woocommerce_account_${endpoint}_endpoint", function ($value) use ($endpoint, $label, $text_domain) {
    $iFirstDay = get_option('start_of_week', 1);
    $time = strtotime("midnight last sunday +{$iFirstDay} days", time());
    $driver = pods('driver', [
        'where' => sprintf('user.ID = %d', get_current_user_id())
    ]);
    $plan = pods('plan', [
        'where' => sprintf(
            'driver.user.ID = %d AND UNIX_TIMESTAMP(plan_date.meta_value) BETWEEN %d AND %d',
            get_current_user_id(),
            strtotime("midnight last sunday +{$iFirstDay} days", $time),
            strtotime("midnight next sunday +{$iFirstDay} days + 1 week", $time)
        ),
        'orderby' => 'plan_date.meta_value, time.meta_value, driver.post_title'
    ]);
    wp_enqueue_style('plans', plugins_url('assets/css/plans.css', __FILE__), ['plan','driver']);
    return require __DIR__ . "/templates/my-account/$endpoint.php";
});
