<?php

/**
 * Plugin Name: WP My Product Webspark
 * Description: Цей плагін дозволяє користувачам додавати нові продукти до магазину WooCommerce через їх обліковий запис. Продукти будуть відправлені на перевірку перед публікацією.
 * Version: 1.0.0
 * Author: Anastasiia Anton
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_My_Product_Webspark
{

    public function __construct()
    {
        if (!$this->check_wc_dependency()) {
            return;
        }

        $this->init_hooks();
    }

    private function check_wc_dependency()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p><strong>WP My Product Webspark</strong> потрібно встановити та активувати WooCommerce.</p></div>';
            });
            return false;
        }
        return true;
    }

    private function init_hooks()
    {
        add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu_items']);
        add_action('init', [$this, 'add_endpoints']);
        add_action('woocommerce_account_add-product_endpoint', [$this, 'add_product_content']);
        add_action('woocommerce_account_my-products_endpoint', [$this, 'my_products_content']);
        add_action('template_redirect', [$this, 'handle_add_product_submission']);
        add_action('init', [$this, 'register_pending_status']);
        add_action('save_post', [$this, 'send_admin_email'], 10, 2);
        add_filter('woocommerce_email_classes', [$this, 'email_settings']);
        add_action('wp_enqueue_scripts', [$this, 'wpmpw_enqueue_scripts']);
        add_action('wp_ajax_wpmpw_delete_product', [$this, 'wpmpw_delete_product']);
        add_action('init', [ $this, 'restrict_uploads' ]);

    }

    public function add_account_menu_items($items)
    {
        $items['add-product'] = __('Add Product', 'wp-my-product-webspark');
        $items['my-products'] = __('My Products', 'wp-my-product-webspark');
        return $items;
    }

    public function add_endpoints()
    {
        add_rewrite_endpoint('add-product', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('my-products', EP_ROOT | EP_PAGES);
    }

    public function add_product_content()
    {
        $product_data = [];

        if (isset($_GET['edit_product']) && is_numeric($_GET['edit_product'])) {
            $product_id = intval($_GET['edit_product']);
            if (get_post_field('post_author', $product_id) == get_current_user_id()) {
                $product = wc_get_product($product_id);

                if ($product) {
                    $product_data = [
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'price' => $product->get_regular_price(),
                        'stock' => $product->get_stock_quantity(),
                        'description' => $product->get_description(),
                        'image_id' => $product->get_image_id(),
                    ];
                }
            }
        }

        wc_get_template(
            'myaccount/add-product.php',
            ['product_data' => $product_data],
            '',
            plugin_dir_path(__FILE__) . 'templates/'
        );
        
    }

     public function restrict_uploads() {
        add_filter('upload_mimes', function($mimes) {
            return [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png'          => 'image/png',
                'gif'          => 'image/gif',
            ];
        });

        add_filter('ajax_query_attachments_args', function($query) {
            $user_id = get_current_user_id();

            if (!current_user_can('manage_options')) { 
                $query['author'] = $user_id;
            }

            return $query;
        });
    }


    public function handle_add_product_submission()
    {
        if (!isset($_POST['add_product_nonce']) || !wp_verify_nonce($_POST['add_product_nonce'], 'add_product_action')) {
            return;
        }

        if (!is_user_logged_in() || !isset($_POST['submit_product'])) {
            return;
        }

        $user_id = get_current_user_id();
        $product_name = sanitize_text_field($_POST['product_name']);
        $product_price = sanitize_text_field($_POST['product_price']);
        $product_stock = intval($_POST['product_stock']);
        $product_description = isset($_POST['product_description']) ? wp_kses_post($_POST['product_description']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if ($product_id) {
        	do_action('wpmpw_new_product_created_notification', $product_id, $user_id);
            $current_author = get_post_field('post_author', $product_id);
            if ($current_author != $user_id) {
                wc_add_notice(__('Ви не маєте права редагувати цей продукт.', 'wp-my-product-webspark'), 'error');
                return;
            }

            $product = [
                'ID'           => $product_id,
                'post_title'   => $product_name,
                'post_content' => $product_description,
                'post_status'  => 'pending',
                'post_type'    => 'product',
            ];

            wp_update_post($product);
           
        } else {
            do_action('wpmpw_new_product_created_notification', $product_id, $user_id);
            $product = [
                'post_title'   => $product_name,
                'post_content' => $product_description,
                'post_status'  => 'pending',
                'post_type'    => 'product',
                'post_author'  => $user_id,
            ];

            $product_id = wp_insert_post($product);

        }

        if (is_wp_error($product_id)) {
            wc_add_notice(__('Під час збереження продукту сталася помилка.', 'wp-my-product-webspark'), 'error');
            return;
        }

        update_post_meta($product_id, '_regular_price', $product_price);
        update_post_meta($product_id, '_price', $product_price);
        update_post_meta($product_id, '_stock', $product_stock);

      if (!empty($_FILES['product_image']['name'])) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $file_type = wp_check_filetype($_FILES['product_image']['name']);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

    if (in_array($file_type['type'], $allowed_types)) {
        $uploaded = media_handle_upload('product_image', $product_id);

        if (is_wp_error($uploaded)) {
            wc_add_notice(__('Помилка завантаження зображення: ', 'wp-my-product-webspark') . $uploaded->get_error_message(), 'error');
            return; 
        } else {
            set_post_thumbnail($product_id, $uploaded);
        }
    } else {
        wc_add_notice(__('Недійсний тип файлу. Будь ласка, завантажте зображення.', 'wp-my-product-webspark'), 'error');
        return;
    
}

        update_post_meta($product_id, '_added_via_my_account', 'yes');

        wc_add_notice(__('Товар успішно збережено!', 'wp-my-product-webspark'), 'success');
        wp_safe_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')) . 'my-products');
        exit;
    }

    public function my_products_content()
    {
        $user_id = get_current_user_id();

        $args = [
            'post_type'      => 'product',
            'post_status'    => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
            'author'         => $user_id,
            'meta_query'     => [
                [
                    'key'   => '_added_via_my_account',
                    'value' => 'yes',
                    'compare' => '=',
                ],
            ],
        ];

        $query = new WP_Query($args);
        $products = $query->posts;
        wc_get_template(
            'myaccount/my-products.php',
            ['products' => $products],
            '',
            plugin_dir_path(__FILE__) . 'templates/'
        );
    }

    public function register_pending_status()
    {
        register_post_status('wc-pending-review', [
            'label'                     => _x('Pending Review', 'Product status', 'wp-my-product-webspark'),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Pending Review (%s)', 'Pending Review (%s)', 'wp-my-product-webspark'),
        ]);
    }

    public function send_admin_email($post_id, $post)
    {
        if ($post->post_type !== 'product' || $post->post_status !== 'pending-review') {
            return;
        }

        $author_id = $post->post_author;
        $author_url = admin_url("user-edit.php?user_id={$author_id}");
        $edit_product_url = admin_url("post.php?post={$post_id}&action=edit");

        $subject = __('Новий продукт очікує на розгляд', 'wp-my-product-webspark');
        $message = sprintf(
            "<p><strong>%s</strong></p><p>%s</p><p>%s</p>",
            __('Товар:', 'wp-my-product-webspark') . " " . $post->post_title,
            __('Сторінка автора:', 'wp-my-product-webspark') . " <a href='{$author_url}'>{$author_url}</a>",
            __('Редагувати:', 'wp-my-product-webspark') . " <a href='{$edit_product_url}'>{$edit_product_url}</a>"
        );

        wp_mail(get_option('admin_email'), $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
    }

    public function email_settings($email_classes)
{
    include_once plugin_dir_path(__FILE__) . 'includes/class-wc-email-new-product-pending.php';
    $email_classes['WC_Email_New_Product_Created'] = new WC_Email_New_Product_Created();
    return $email_classes;
}



    public function wpmpw_enqueue_scripts()
    {
        wp_enqueue_script('my-products-js', plugin_dir_url(__FILE__) . 'assets/js/my-products.js', ['jquery'], '1.0', true);
        wp_enqueue_media(); 

        wp_localize_script('my-products-js', 'wp_my_product_webspark', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('delete_product_nonce'),
        ]);
    }

    public function wpmpw_delete_product()
    {
        check_ajax_referer('delete_product_nonce', 'security');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

        if (!$product_id || get_post_type($product_id) !== 'product') {
            wp_send_json_error(['message' => __('Невірний ID товару.', 'wp-my-product-webspark')]);
        }

        if (get_current_user_id() !== (int) get_post_field('post_author', $product_id)) {
            wp_send_json_error(['message' => __('Ви не маєте прав для видалення цього товару.', 'wp-my-product-webspark')]);
        }

        $result = wp_delete_post($product_id, true);

        if ($result) {
            wp_send_json_success(['message' => __('Товар успішно видалено.', 'wp-my-product-webspark')]);
        } else {
            wp_send_json_error(['message' => __('Помилка видалення товару.', 'wp-my-product-webspark')]);
        }
    }
}

new WP_My_Product_Webspark();
