<?php
/*
Plugin Name: Planner
Version: 0.10.0
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
    wp_enqueue_script('planner');
    wp_enqueue_style('planner');
    wp_localize_script('planner', 'planner', [
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

    return require __DIR__ . '/includes/admin/planner.php';
};

add_action('rest_api_init', function (WP_REST_Server $server) use ($ns) {
    register_rest_route($ns, '/calendar/event', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function (WP_REST_Request $request) {
            return current_user_can('planner');
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
                    'url' => get_edit_post_link($pod->id(), null),
                    'resourceId' => $pod->field('plan_group.term_id') ? $pod->field('plan_group.term_id') : 0
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
    $templates = pods_api()->load_templates();
    register_rest_route($ns, '/booking', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function (WP_REST_Request $request) {
            return current_user_can('planner');
        },
        'callback' => function (WP_REST_Request $request) use ($templates) {
            $pod = pods('wc_booking', [
                'orderby' => '_booking_start.meta_value',
                'select' => 't.*, '.
                    'STR_TO_DATE(_booking_start.meta_value, GET_FORMAT(DATETIME,"INTERNAL")) AS booking_start_date, '.
                    '_booking_resource_id.meta_value AS resource_id, '.
                    'CAST(_booking_product_id.meta_value AS UNSIGNED) AS product_id',
                'where' => sprintf(
                    't.post_status IN ("confirmed", "paid", "complete") '.
                    'AND UNIX_TIMESTAMP(STR_TO_DATE(_booking_start.meta_value, GET_FORMAT(DATETIME,"INTERNAL"))) BETWEEN %d AND %d',
                    strtotime($request['start']),
                    strtotime($request['end'])
                )
            ]);

            if (!$pod->total_found())
                return new WP_REST_Response(get_post_type_object('wc_booking')->labels->not_found, 200);

            if ($request->get_param('template')) {
                $data = $pod->template($request->get_param('template'));
            } else {
                $data = [];
                while ($pod->fetch()) {
                    $data[] = $pod->export_data();
                }
            }
            return $data;
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
            ),
            'template' => array(
                'validate_callback' => function ($param, WP_REST_Request $request, $key) use ($templates) {
                    return isset($templates[$param]);
                },
            )
        ),
    ));
    register_rest_route($ns, '/(?<pod>driver|vehicle)', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => function (WP_REST_Request $request) {
            return current_user_can('planner');
        },
        'callback' => function (WP_REST_Request $request) use ($templates) {
            $pod = pods($request->get_param('pod'), []);
            if ($request->get_param('template')) {
                $data = $pod->template($request->get_param('template'));
            } else {
                $data = [];
                while ($pod->fetch()) {
                    $data[] = $pod->export_data();
                }
            }
            return $data;
        },
        'args' => array(
            'pod' => array(
                'validate_callback' => function ($param, WP_REST_Request $request, $key) use ($templates) {
                    return !!pods($param, null, true);
                },
            ),
            'template' => array(
                'validate_callback' => function ($param, WP_REST_Request $request, $key) use ($templates) {
                    return isset($templates[$param]);
                },
            ),
        ),
    ));
});

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

    wp_register_script('planner', plugins_url('assets/js/planner.js', __FILE__), ['fullcalendar-scheduler', 'jquery-ui-dialog'], $version, true);
    wp_register_style('planner', plugins_url('assets/css/planner.css', __FILE__), ["$page-vehicle", "$page-plan", "$page-driver", "$page-bookings", 'fullcalendar-scheduler-all', 'wp-jquery-ui-dialog'], $version);

    wp_register_style("$page-plans", plugins_url('assets/css/plan.css', __FILE__), [], $version);
    wp_register_style("$page-passengers", plugins_url('assets/css/passengers.css', __FILE__), [], $version);

    wp_register_script("$page-plans", plugins_url('assets/js/plans.js', __FILE__), ['jquery'], $version, true);

    if (is_singular('plan')) {
        wp_enqueue_style("$page-plans");
        wp_enqueue_style("$page-passengers");
        wp_enqueue_script("$page-plans");
    }
};
add_action('wp_enqueue_scripts', $scripts);
add_action('admin_enqueue_scripts', $scripts);

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
