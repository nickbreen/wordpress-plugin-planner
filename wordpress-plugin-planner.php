<?php
/*
Plugin Name: Planner
Version: 0.12.0
Description: Uses pods to plan group tours.
Author: Nick Breen
Author URI: https://github.com/nickbreen
Plugin URI: https://github.com/nickbreen/wordpress-plugin-planner
Text Domain: wordpress-plugin-planner
Domain Path: /languages
License: GPL2
*/

$plugin = get_plugin_data(__FILE__);
$text_domain = $plugin['TextDomain'];
$version = $plugin['Version'];
$page = plugin_basename(__DIR__);
$ns = "/{$page}/v{$version}";

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

$function = function () use ($page, $text_domain, $ns) {
    wp_enqueue_script("$page-planner");
    wp_enqueue_style("$page-planner");
    wp_localize_script("$page-planner", 'planner', [
        'calendar' => [
            'schedulerLicenseKey' => 'GPL-My-Project-Is-Open-Source',
            'titleFormat' => '[Week] w Y',
            'columnFormat' => 'ddd, Do MMM',
            'timeFormat' => 'h:mm a',
            'defaultView' => 'timelineWeek',
            'resourceAreaWidth' => '12.5%',
            'resourceLabelText' => 'Groups',
            'slotDuration' => [ 'days' => 1],
            'slotLabelFormat' => [ 'ddd', 'Do MMM' ],
            'firstDay' => intval(get_option('start_of_week', 0)),
            'eventSources' => [
                'url' => rest_url($ns . '/calendar/event'),
                'cache' => true,
                'color' => '#eee', // Boring neutral color for driver-less plans
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                ],
                'editable' => false,
            ],
            'resources' => [
                'url' => rest_url($ns . '/calendar/resource'),
                'cache' => true,
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                ],
            ],
            'header' => [
                'left' => 'plan booking',
                'center' => 'prev title next',
                'right' => 'driver vehicle'
            ],
            'customButtons' => [
                'booking' => [
                    'text' => get_post_type_object('wc_booking')->label,
                    'url' => admin_url("post-new.php?post_type=wc_booking")
                ],
                'plan' => [
                    'text' => get_post_type_object('plan')->labels->add_new_item,
                    'url' => admin_url("post-new.php?post_type=plan")
                ],
                'driver' => [
                    'text' => get_post_type_object('driver')->label,
                ],
                'vehicle' => [
                    'text' => get_post_type_object('vehicle')->label,
                ],
            ],
        ],
        'booking' => [
            'dialog' => [
                'title' => get_post_type_object('wc_booking')->label,
                'buttons' => [
                    'new' => [
                        'text' => get_post_type_object('wc_booking')->labels->add_new_item,
                        'url' => admin_url('edit.php?post_type=wc_booking&page=create_booking')
                    ],
                ],
            ],
            'rest' => [
                'url' => rest_url($ns . '/booking'),
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                ],
                'data' => [
                    'template' => 'booking',
                    'start' => true,
                    'end' => true
                ],
            ],
        ],
        'driver' => [
            'dialog' => [
                'title' => get_post_type_object('driver')->label,
                'content' => '<p>Drag a driver onto a plan.</p>',
                'buttons' => [
                    'new' => [
                        'text' => get_post_type_object('driver')->labels->add_new_item,
                        'url' => admin_url("post-new.php?post_type=driver"),
                    ],
                ],
            ],
            'rest' => [
                'url' => rest_url($ns . '/driver'),
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                ],
                'data' => [
                    'template' => 'driver'
                ],
            ],
        ],
        'vehicle' => [
            'dialog' => [
                'title' => get_post_type_object('vehicle')->label,
                'content' => '<p>Drag a vehicle onto a plan.</p>',
                'buttons' => [
                    'new' => [
                        'text' => get_post_type_object('vehicle')->labels->add_new_item,
                        'url' => admin_url("post-new.php?post_type=vehicle")
                    ],
                ],
            ],
            'rest' => [
                'url' => rest_url($ns . '/vehicle'),
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                ],
                'data' => [
                    'template' => 'vehicle'
                ],
            ],
        ],
    ]);

    return require __DIR__ . '/templates/admin/planner.php';
};

$scripts = function () use ($page, $version) {
    wp_register_script('moment', plugins_url('bower_components/moment/min/moment.min.js', __FILE__), [], '2.17.1', true);
    wp_register_script('fullcalendar', plugins_url('bower_components/fullcalendar/dist/fullcalendar.min.js', __FILE__), ['jquery','moment'], '3.1.0', true);
    wp_register_style('fullcalendar-all', plugins_url('bower_components/fullcalendar/dist/fullcalendar.min.css', __FILE__), [], '3.1.0');
    wp_register_style('fullcalendar-print', plugins_url('bower_components/fullcalendar/dist/fullcalendar.print.min.css', __FILE__), [], '3.1.0', 'print');
    wp_register_script('fullcalendar-scheduler', plugins_url('bower_components/fullcalendar-scheduler/dist/scheduler.min.js', __FILE__), ['fullcalendar'], '1.5.0', true);
    wp_register_style('fullcalendar-scheduler-all', plugins_url('bower_components/fullcalendar-scheduler/dist/scheduler.min.css', __FILE__), ['fullcalendar-all', 'fullcalendar-print'], '1.5.0');

    wp_register_style("$page-fonts", 'https://fonts.googleapis.com/css?family=Share+Tech+Mono');
    wp_register_style("$page-vehicle", plugins_url('assets/css/vehicles.css', __FILE__), ["$page-fonts"], $version);
    wp_register_style("$page-plan", plugins_url('assets/css/plan.css', __FILE__), [], $version);
    wp_register_style("$page-driver", plugins_url('assets/css/drivers.css', __FILE__), [], $version);
    wp_register_style("$page-bookings", plugins_url('assets/css/bookings.css', __FILE__), [], $version);

    wp_register_script("$page-planner", plugins_url('assets/js/planner.js', __FILE__), ['fullcalendar-scheduler', 'jquery-ui-dialog'], $version, true);
    wp_register_style("$page-planner", plugins_url('assets/css/planner.css', __FILE__), ["$page-vehicle", "$page-plan", "$page-driver", "$page-bookings", 'fullcalendar-scheduler-all', 'wp-jquery-ui-dialog'], $version);

    wp_register_script("$page-planner-driver", plugins_url('assets/js/planner-driver.js', __FILE__), ['fullcalendar-scheduler'], $version, true);
    wp_register_style("$page-planner-driver", plugins_url('assets/css/planner-driver.css', __FILE__), ["$page-plan", 'fullcalendar-scheduler-all', 'wp-jquery-ui-dialog'], $version);

    wp_register_style("$page-passengers", plugins_url('assets/css/passengers.css', __FILE__), [], $version);
};
add_action('wp_enqueue_scripts', $scripts);
add_action('admin_enqueue_scripts', $scripts);
add_action('wp_enqueue_scripts', function () use ($page) {
    if (is_singular('plan')) {
        wp_enqueue_style("$page-planner-driver");
    }
}, 50);

register_deactivation_hook(__FILE__, function () {
    remove_role('planner');
    remove_role('driver');
    remove_role('school');
    remove_role('hotel');
});

register_activation_hook(__FILE__, function () use ($text_domain) {
    $post_type_caps = call_user_func_array('array_merge', array_map(function ($post_type) {
        return array_values(get_object_vars(get_post_type_object($post_type)->cap));
    }, ['driver', 'plan', 'vehicle', 'wc_booking']));
    $taxonomy_caps = call_user_func_array('array_merge', array_map(function ($tax) {
        return array_values(get_object_vars(get_taxonomy($tax)->cap));
    }, ['location', 'plan_group']));
    $custom_types = ['passenger' => ['add', 'edit', 'delete']];
    $custom_type_caps = call_user_func_array('array_merge', array_map(function ($type, $caps) {
        return array_map(function ($cap) use ($type) {
            return "pods_{$cap}_{$type}";
        }, $caps);
    }, array_keys($custom_types), $custom_types));
    $user_caps = array_map(function ($cap) {
        return "{$cap}_users";
    }, ['add', 'create', 'delete', 'edit', 'list', 'promote', 'remove']);
    add_role('planner', __('Planner', $text_domain), array_fill_keys(
        $post_type_caps + $taxonomy_caps + $custom_type_caps + $user_caps,
        true
    ));
    $customer = get_role('customer');
    add_role('school', __('School', $text_domain), $customer->capabilities);
    add_role('hotel', __('Hotel', $text_domain), $customer->capabilities);
    $subscriber = $customer; //get_role('subscriber');
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
        in_array($post->post_type, ['plan', 'driver', 'vehicle']) &&
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

register_activation_hook(__FILE__, function () use ($endpoint) {
    add_rewrite_endpoint($endpoint, EP_ROOT|EP_PAGES);
    flush_rewrite_rules();
});

add_filter('query_vars', function ($vars) use ($endpoint) {
    $vars[] = $endpoint;
    return $vars;
});

add_filter("woocommerce_endpoint_${endpoint}_title", function ($items) use ($label, $text_domain) {
    return __($label, $text_domain);
});

add_action("woocommerce_account_${endpoint}_endpoint", function ($value) use ($endpoint, $label, $text_domain, $page, $ns) {
    wp_enqueue_style("$page-planner-driver");
    wp_enqueue_script("$page-planner-driver");
    wp_localize_script("$page-planner-driver", 'planner', [
        'calendar' => [
            'schedulerLicenseKey' => 'GPL-My-Project-Is-Open-Source',
            'timeFormat' => 'h:mm a',
            'height' => 'auto',
            'contentHeight' => 'auto',
            'defaultView' => 'listWeek',
            'firstDay' => intval(get_option('start_of_week', 0)),
            'eventSources' => [
                'url' => rest_url($ns . '/calendar/event'),
                'cache' => true,
                'headers' => [
                    'X-WP-Nonce' => wp_create_nonce('wp_rest'),
                ],
                'editable' => false,
            ],
            'header' => [
                'left' => '',
                'center' => 'prev title next',
                'right' => 'today'
            ],
        ],
    ]);

    return require __DIR__ . "/templates/my-account/$endpoint.php";
});

// add_filter('wc_get_template', function ($located, $template_name, $args, $template_path, $default_path) {
//     error_log(print_r(func_get_args(), true));
// }, 10, 5);

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

require_once __DIR__ . '/includes/rest.php';
