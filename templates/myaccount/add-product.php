<h2><?php _e('Додати новий товар', 'wp-my-product-webspark'); ?></h2>
<?php
$product_name = isset($product_data['name']) ? esc_attr($product_data['name']) : '';
$product_price = isset($product_data['price']) ? esc_attr($product_data['price']) : '';
$product_stock = isset($product_data['stock']) ? esc_attr($product_data['stock']) : '';
$product_description = isset($product_data['description']) ? esc_textarea($product_data['description']) : '';
$product_image_id = isset($product_data['image_id']) ? intval($product_data['image_id']) : '';
$product_id = isset($product_data['id']) ? intval($product_data['id']) : '';
?>

<form method="POST" enctype="multipart/form-data">
    <?php wp_nonce_field('add_product_action', 'add_product_nonce'); ?>

    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

    <p>
        <label for="product_name"><?php _e('Назва товару', 'wp-my-product-webspark'); ?></label>
        <input type="text" name="product_name" id="product_name" value="<?php echo $product_name; ?>" required>
    </p>

    <p>
        <label for="product_price"><?php _e('Ціна товару', 'wp-my-product-webspark'); ?></label>
        <input type="number" step="0.01" name="product_price" id="product_price" value="<?php echo $product_price; ?>" required>
    </p>

    <p>
        <label for="product_stock"><?php _e('Кількість', 'wp-my-product-webspark'); ?></label>
        <input type="number" name="product_stock" id="product_stock" value="<?php echo $product_stock; ?>" required>
    </p>

<p>
    <label for="product_description"><?php _e('Опис', 'wp-my-product-webspark'); ?></label>
    <?php
    wp_editor(
        $product_description, 
        'product_description', 
        [
            'textarea_name' => 'product_description', 
            'media_buttons' => false, 
            'textarea_rows' => 10, 
        ]
    );
    ?>
</p>

    <p>
        <label for="product_image"><?php _e('Зображення', 'wp-my-product-webspark'); ?></label>
        <input type="file" name="product_image" id="product_image">
        <?php if ($product_image_id): ?>
            <?php echo wp_get_attachment_image($product_image_id, 'thumbnail'); ?>
        <?php endif; ?>
    </p>

    <p>
        <button type="submit" name="submit_product">
            <?php echo $product_id ? __('Оновити', 'wp-my-product-webspark') : __('Зберегти', 'wp-my-product-webspark'); ?>
        </button>
    </p>
</form>

