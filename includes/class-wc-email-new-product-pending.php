<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Email_New_Product_Created extends WC_Email
{
    public function __construct()
    {
        $this->id             = 'new_product_created';
        $this->title          = __('New Product Created', 'wp-my-product-webspark');
        $this->description    = __('This email is sent when a new product is created or updated via the My Account page.', 'wp-my-product-webspark');
        $this->template_html  = 'emails/admin-new-product-created.php';
        $this->template_plain = 'emails/plain/admin-new-product-created.php';
        $this->placeholders   = [
            '{product_name}' => '',
            '{author_url}'   => '',
            '{edit_product_url}' => '',
        ];

        $this->template_base = plugin_dir_path(__FILE__) . '../templates/';
        $this->recipient     = $this->get_option('recipient', get_option('admin_email'));

        parent::__construct();

        add_action('wpmpw_new_product_created_notification', [$this, 'trigger'], 10, 2);
    }

    public function trigger($product_id, $product_author)
    {
        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        $product = wc_get_product($product_id);
        $author_url = admin_url("user-edit.php?user_id={$product_author}");
        $edit_product_url = admin_url("post.php?post={$product_id}&action=edit");

        $this->placeholders['{product_name}'] = $product->get_name();
        $this->placeholders['{author_url}'] = $author_url;
        $this->placeholders['{edit_product_url}'] = $edit_product_url;

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'email_heading'   => $this->get_heading(),
                'product_name'    => $this->placeholders['{product_name}'],
                'author_url'      => $this->placeholders['{author_url}'],
                'edit_product_url'=> $this->placeholders['{edit_product_url}'],
                'email'           => $this,
            ],
            '',
            $this->template_base
        );
    }

    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'email_heading'   => $this->get_heading(),
                'product_name'    => $this->placeholders['{product_name}'],
                'author_url'      => $this->placeholders['{author_url}'],
                'edit_product_url'=> $this->placeholders['{edit_product_url}'],
                'email'           => $this,
            ],
            '',
            $this->template_base
        );
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'wp-my-product-webspark'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'wp-my-product-webspark'),
                'default' => 'yes',
            ],
            'recipient' => [
                'title'       => __('Recipient', 'wp-my-product-webspark'),
                'type'        => 'email',
                'description' => __('The email address where notifications will be sent. Defaults to the admin email.', 'wp-my-product-webspark'),
                'default'     => get_option('admin_email'),
                'placeholder' => '',
            ],
        ];
    }
}
