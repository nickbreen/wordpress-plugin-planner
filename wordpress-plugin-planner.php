<?php
/*
Plugin Name: Planner
Version: 0.9.2
Description: Uses pods to plan group tours.
Author: Nick Breen
Author URI: http://foobar.net.nz
Plugin URI: https://github.com/nickbreen/wordpress-plugin-planner
Text Domain: wordpress-plugin-planner
Domain Path: /languages
*/

$text_domain = 'wordpress-plugin-planner';
$page = 'wordpress-plugin-planner';

function wordpress_plugin_planner_contrast_color($ch) {
    if (is_array($ch))
        $ch = current($ch);
    if (!$ch)
        return "inherit";
    $r = hexdec(substr($ch, 1, 2));
    $g = hexdec(substr($ch, 3, 2));
    $b = hexdec(substr($ch, 5, 2));
    return ((($r*299)+($g*587)+($b*144))/1000) >= 131.5 ? "black" : "white";
};

function wordpress_plugin_planner_fmt_date($time) {
    return date(get_option('date_format'), strtotime($time));
}

function wordpress_plugin_planner_plan_link($id) {
    return get_edit_post_link($id) ?? get_permalink($id);
}

$function = function () use ($page, $text_domain) {

    // Work out the first day of the week
    $iFirstDay = get_option('start_of_week', 1);
    // Clamp the date to the start of the week
    $time = filter_input(INPUT_GET, 'week', FILTER_CALLBACK, [
        'options' => function ($value) use ($iFirstDay) {
            return strtotime("midnight last sunday +{$iFirstDay} days", strtotime($value));
        }
    ]) ?: strtotime("midnight last sunday +{$iFirstDay} days", time());

    $plans = [];

    $planName = pods('plan_group', []);

    while ($planName->fetch()) {
        $plans[$planName->field('name')] = array_fill($iFirstDay, 7, []);
    }
    $plans[''] = array_fill($iFirstDay, 7, []);

    $plan = pods('plan', [
        'where' => sprintf(
            'UNIX_TIMESTAMP(plan_date.meta_value) BETWEEN %d AND %d',
            strtotime("midnight last sunday +{$iFirstDay} days", $time),
            strtotime("midnight next sunday +{$iFirstDay} days", $time)
        )
    ]);

    while ($plan->fetch()) {
        $plans[$plan->field('plan_group.name', null, true) ? $plan->field('plan_group.name', null, true) : '']
            [date('w', strtotime($plan->field('plan_date')))]
            [$plan->id()] = 'plan';
    }

    $plans = array_filter($plans, function ($days) {
        return array_reduce($days, function ($carry, $item) {
            return $carry + count($item);
        }, 0);
    });

    $driver = pods('driver', []);

    $vehicle = pods('vehicle', []);

    $booking = pods('wc_booking', [
        'orderby' => '_booking_start.meta_value',
        'select' => 't.*, '.
            '_booking_start.meta_value AS booking_start_date, '.
            '_booking_resource_id.meta_value AS resource_id, '.
            'CAST(_booking_product_id.meta_value AS UNSIGNED) AS product_id',
        'where' => sprintf(
            't.post_status IN ("confirmed", "paid", "complete" ) '.
            'AND UNIX_TIMESTAMP(STR_TO_DATE(_booking_start.meta_value, GET_FORMAT(DATETIME,"INTERNAL"))) BETWEEN %d AND %d',
            strtotime("midnight last sunday +{$iFirstDay} days", $time),
            strtotime("midnight next sunday +{$iFirstDay} days", $time)
        )
    ]);

    wp_enqueue_script('planner', plugins_url('assets/js/planner.js', __FILE__), ['jquery-ui-datepicker']);
    wp_enqueue_style('planner', plugins_url('assets/css/planner.css', __FILE__), ['jquery-ui', "$page-plan", "$page-driver"]);

    return require __DIR__ . '/includes/admin/planner.php';
    // TODO use pods_view, but this is wrong
    // return pods_view(__DIR__ . '/includes/admin/planner.php', compact(array('page', 'text_domain', 'plans', 'driver', 'time')), 0, 'cache', true);
};

add_action('admin_enqueue_scripts', function () use ($page) {
    wp_enqueue_style("$page-fonts", 'https://fonts.googleapis.com/css?family=Share+Tech+Mono');
    wp_enqueue_style("$page-plan", plugins_url('assets/css/plan.css', __FILE__));
    wp_enqueue_style("$page-driver", plugins_url('assets/css/drivers.css', __FILE__));
    wp_enqueue_style("$page-vehicle", plugins_url('assets/css/vehicles.css', __FILE__), ["$page-fonts"]);
    wp_enqueue_style("$page-bookings", plugins_url('assets/css/bookings.css', __FILE__));
});

add_action('wp_enqueue_scripts', function () use ($page) {
    if (is_singular('plan')) {
        wp_enqueue_style("$page-plan", plugins_url('assets/css/plan.css', __FILE__));
        wp_enqueue_style("$page-driver", plugins_url('assets/css/drivers.css', __FILE__));
        wp_enqueue_style("$page-vehicle", plugins_url('assets/css/vehicles.css', __FILE__));
        wp_enqueue_style("$page-plans", plugins_url('assets/css/plans.css', __FILE__), ["$page-plan","$page-driver","$page-vehicle"]);
        wp_enqueue_style("$page-passengers", plugins_url('assets/css/passengers.css', __FILE__));
        wp_enqueue_script("$page-plans", plugins_url('assets/js/plans.js', __FILE__), ['jquery'], '0.0.0', true);
    }
});

register_deactivation_hook(__FILE__, function () {
    remove_role('planner');
    remove_role('driver');
    remove_role('school');
    remove_role('hotel');
});

register_activation_hook(__FILE__, function () use ($text_domain) {
    add_role('planner', __('Planner', $text_domain), array_fill_keys([
        'add_users',
        'create_users',
        'delete_others_posts',
        'delete_posts',
        'delete_private_posts',
        'delete_published_posts',
        'delete_users',
        'edit_others_posts',
        'edit_posts',
        'edit_private_posts',
        'edit_published_posts',
        'edit_users',
        'list_users',
        'promote_users',
        'publish_posts',
        'read_posts',
        'read_private_posts',
        'remove_users'
    ], true));
    $customer = get_role('customer');
    add_role('school', __('School', $text_domain), $customer->capabilities);
    add_role('hotel', __('Hotel', $text_domain), $customer->capabilities);
    $subscriber = get_role('subscriber');
    add_role('driver', __('Driver', $text_domain), $subscriber->capabilities);
});

if (defined('WP_DEBUG') && constant('WP_DEBUG')) add_action('admin_init', function () {
    if (pods_access(['pods'])) {
        $templates = pods_api()->load_templates();
        if ($templates) {
            foreach ($templates as $name => $template) {
                $file = __DIR__."/templates/pods/{$name}.html";
                if (file_exists($file)){
                    $template['code'] = file_get_contents($file);
                    $id = pods_api()->save_template($template);
                }
            }
        }
    }
});

add_filter('custom_menu_order', function ($menu_ord) use ($page) {
    global $submenu;
    // www: PHP Warning: next() expects parameter 1 to be array, null given in /var/www/wp-content/plugins/wordpress-plugin-planner/wordpress-plugin-planner.php on line 165
    if (!array_key_exists($page, $submenu) || !is_array($submenu[$page]))
        return $menu_ord;
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

add_filter('default_title', function ($post_title, $post) {
    if (in_array($post->post_type, ["plan"])) {
        $post_type = get_post_type_object($post->post_type);
        $post_title = sprintf("%s %s", $post_type->labels->singular_name, date("Y-m-d", strtotime($post->post_date)));
    }
    return $post_title;
}, 10, 2);

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
            strtotime("midnight next sunday +{$iFirstDay} days + 1 year", $time)
        ),
        'orderby' => 'plan_date.meta_value, time.meta_value, driver.post_title'
    ]);
    wp_enqueue_style('plan', plugins_url('assets/css/plan.css', __FILE__));
    wp_enqueue_style('driver', plugins_url('assets/css/driver.css', __FILE__));
    wp_enqueue_style('plans', plugins_url('assets/css/plans.css', __FILE__), ['plan','driver']);
    wp_enqueue_script('plans.js', plugins_url('assets/js/plans.js', __FILE__), ['jquery'], '0.0.0', true);
    return require __DIR__ . "/templates/my-account/$endpoint.php";
});

add_filter('the_content', function ($content) {
    global $post;
    if (is_singular('plan')) {
        $field = pods('plan')->fields['passengers'];
        $content .= pods('passenger', [
                'where' => sprintf(
                    'pandarf_parent_pod_id = %d AND pandarf_parent_post_id = %d AND pandarf_pod_field_id = %d',
                    $field['pod_id'],
                    $post->ID,
                    $field['id']
                ),
                'orderby' => 'pandarf_order'
            ])->template('passenger');
    }
    return $content;
});
