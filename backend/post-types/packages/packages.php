<?php

/**
 * File Type: Memberships Post Type
 */
if (!class_exists('post_type_packages')) {

    class post_type_packages {

        /**
         * Start Contructer Function
         */
        public function __construct() {
            add_action('init', array(&$this, 'foodbakery_packages_register'), 12);
            add_filter('manage_packages_posts_columns', array($this, 'packages_cpt_columns'));
            add_action('manage_packages_posts_custom_column', array($this, 'custom_packages_column'), 10, 2);
            add_shortcode('foodbakery_package', array($this, 'foodbakery_package_shortcode_function'));
            add_action('admin_head', array($this, 'title_moving_callback'));
            add_action('wp_ajax_become_publisher_warning_message', array($this, 'become_publisher_warning_message'));
            add_action('wp_ajax_nopriv_become_publisher_warning_message', array($this, 'become_publisher_warning_message'));
        }

        /**
         * Start Wp's Initilize action hook Function
         */
        public function foodbakery_packages_init() {
            // Initialize Post Type
            $this->foodbakery_packages_register();
        }

        /**
         * Start Moving package title in metabox
         */
        function title_moving_callback() {
            ?>
            <script type="text/javascript">
                (function ($) {
                    $(document).ready(function () {
                        $('#foodbakery_title_move').append($('#titlediv'));
                    });

                })(jQuery);
            </script>
            <?php

        }
        /**
         * Start Function How to Register post type
         */
        public function foodbakery_packages_register() {
            $labels = array(
                'name' => foodbakery_plugin_text_srt('foodbakery_post_type_package_name'),
                'singular_name' => foodbakery_plugin_text_srt('foodbakery_post_type_package_singular_name'),
                'menu_name' => foodbakery_plugin_text_srt('foodbakery_post_type_package_menu_name'),
                'name_admin_bar' => foodbakery_plugin_text_srt('foodbakery_post_type_package_name_admin_bar'),
                'add_new' => foodbakery_plugin_text_srt('foodbakery_post_type_package_add_new'),
                'add_new_item' => foodbakery_plugin_text_srt('foodbakery_post_type_package_add_new_item'),
                'new_item' => foodbakery_plugin_text_srt('foodbakery_post_type_package_new_item'),
                'edit_item' => foodbakery_plugin_text_srt('foodbakery_post_type_package_edit_item'),
                'view_item' => foodbakery_plugin_text_srt('foodbakery_post_type_package_view_item'),
                'all_items' => foodbakery_plugin_text_srt('foodbakery_post_type_package_all_items'),
                'search_items' => foodbakery_plugin_text_srt('foodbakery_post_type_package_search_items'),
                'not_found' => foodbakery_plugin_text_srt('foodbakery_post_type_package_not_found'),
                'not_found_in_trash' => foodbakery_plugin_text_srt('foodbakery_post_type_package_not_found_in_trash'),
            );
            $args = array(
                'labels' => $labels,
                'description' => foodbakery_plugin_text_srt('foodbakery_packages'),
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'menu_position' => 31,
                'menu_icon' => wp_foodbakery::plugin_url() . 'assets/backend/images/tool.png',
                'query_var' => false,
                'rewrite' => array('slug' => 'packages'),
                'capability_type' => 'post',
                'has_archive' => false,
                'hierarchical' => false,
                'exclude_from_search' => true
            );

            register_post_type('packages', $args);
        }

        /*
         * add custom column to to row
         */

        public function packages_cpt_columns($columns) {

            $new_columns = array(
                'shortcode' => __('Membership Purchase Buton', 'ThemeName'),
            );
            return array_merge($columns, $new_columns);
        }
        /*
         * add column values for each row
         */

        public function custom_packages_column($column) {
            switch ($column) {

                case 'shortcode' :
                    $post_id = get_the_id();
                    $column_shortcode = '[foodbakery_package package_id="' . $post_id . '"]';
                    echo esc_html($column_shortcode);
                    break;
            }
        }

        public function foodbakery_package_shortcode_function($atts) {
            global $foodbakery_html_fields_frontend, $foodbakery_plugin_options, $current_user;
            $single_package_dashboard = isset($foodbakery_plugin_options['foodbakery_package_page']) ? $foodbakery_plugin_options['foodbakery_package_page'] : '';
            $page_link = get_the_permalink($single_package_dashboard);
            $atts = shortcode_atts(
                    array(
                          'package_id' => ' ',
                    ), $atts, 'foodbakery_package');
            $package_id = $atts['package_id'];
            $output = '';
            $rand_numb = rand(1000000, 99999999);
            if (isset($_POST['foodbakery_package_buy']) && $_POST['foodbakery_package_buy'] == '1') {
                $package_id = isset($_POST['package_id']) ? $_POST['package_id'] : 0;

                if (is_user_logged_in() && current_user_can('foodbakery_publisher')) {
                    $form_rand_numb = isset($_POST['foodbakery_package_random']) ? $_POST['foodbakery_package_random'] : '';
                    $form_rand_transient = get_transient('foodbakery_package_random');
                    if ($form_rand_transient != $form_rand_numb) {
                        $foodbakery_restaurant_obj = new foodbakery_publisher_restaurant_actions();
                        $company_id = foodbakery_company_id_form_user_id($current_user->ID);
                        set_transient('foodbakery_package_random', $form_rand_numb, 60 * 60 * 24 * 30);
                        $foodbakery_restaurant_obj->foodbakery_restaurant_add_transaction('buy_package', 0, $package_id, $company_id);
                    }
                }
            }
            if (is_user_logged_in() && current_user_can('foodbakery_publisher')) {
                if (true === Foodbakery_Member_Permissions::check_permissions('packages')) {
                    $output .= '
                    <form method="post">
                        <input type="hidden" name="foodbakery_package_buy" value="1" />
                        <input type="hidden" name="foodbakery_package_random" value="' . absint($rand_numb) . '" />
                        <input type="hidden" name="package_id" value="' . absint($package_id) . '" />
                        <div class="foodbakery-subscribe-pkg-btn">

                            <input type="submit"  value="' . esc_html__('Buy Now', 'foodbakery') . '">
                            <i class="icon-controller-play"></i>
                        </div>
                    </form>';
                }
            } else if (is_user_logged_in() && !current_user_can('foodbakery_publisher')) {
                $output .= '<a data-id="' . absint($rand_numb) . '" href="javascript:void(0);" class="foodbakery-subscribe-pkg text-color">' . esc_html__('Buy Now', 'foodbakery') . '<i class="icon-controller-play"></i></a>';
            } else if (!is_user_logged_in()) {
                $output .= '<a href="#" data-target="#sign-in" data-msg="' . esc_html__('You have to login for purchase restaurant.', 'foodbakery') . '" data-toggle="modal" class="foodbakery-subscribe-pkg text-color">' . esc_html__('Buy Now', 'foodbakery') . '<i class="icon-controller-play"></i></a>';
            }
            $output .= '<div id="response-' . $rand_numb . '" class="response-holder" style="display: none;">
					<div class="alert alert-warning fade in">' . __('Only a restaurant can subscribe a Membership.', 'foodbakery') . '</div>
				</div>';
            return $output;
        }

        public function become_publisher_warning_message() {
            $json['type'] = "error";
            $json['msg'] = __('Only a restaurant can subscribe a Membership.', 'foodbakery');
            echo json_encode($json);
            wp_die();
        }

        public function assing_page_temp_by_id() {
            global $foodbakery_plugin_options;
            $package_detail_page = isset($foodbakery_plugin_options['foodbakery_package_page']) ? $foodbakery_plugin_options['foodbakery_package_page'] : '';
            if (-1 != $package_detail_page) {
                update_post_meta($package_detail_page, '_wp_page_template', 'packages-template.php');
            }
        }

        // End of class	
    }

    // Initialize Object
    $packages_object = new post_type_packages();
    $packages_object->assing_page_temp_by_id();
}

add_action('admin_head', 'foodbakery_packages_remove_help_tabs');

function foodbakery_packages_remove_help_tabs() {
    $screen = get_current_screen();
    if (isset($screen) && $screen->post_type == 'packages') {
        add_filter('screen_options_show_screen', '__return_false');
        add_filter('bulk_actions-edit-packages', '__return_empty_array');
        echo '<style type="text/css">
			.post-type-packages .tablenav.bottom,
			.post-type-packages .tablenav.top,
			.post-type-packages .subsubsub,
			.post-type-packages .search-box,
			.post-type-packages .hide-if-no-js,
			.post-type-packages #postdivrich{
			display: none;
			}
		</style>';
    }
}

function package_columns($columns) {
    unset($columns['date'], $columns['cb']);

    return $columns;
}

add_filter('manage_packages_posts_columns', 'package_columns');

function hide_publishing_actions() {
    $my_post_type = 'packages';
    global $post;
    if ($post->post_type == 'packages') {
        echo '<style type="text/css">
		    #misc-publishing-actions,
                    #minor-publishing-actions{
                        display:none;
                    }
		    
                </style>';
    }
}

add_action('admin_head-post.php', 'hide_publishing_actions');
add_action('admin_head-post-new.php', 'hide_publishing_actions');
