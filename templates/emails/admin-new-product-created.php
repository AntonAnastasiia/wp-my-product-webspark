<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<p><?php printf(__('Назва товару: %s', 'wp-my-product-webspark'), $product_name); ?></p>
<p><?php printf(__('Посилання на сторінку автора товару: <a href="%s">Сторінка Автора</a>', 'wp-my-product-webspark'), $author_url); ?></p>
<p><?php printf(__('Посилання на сторінку редагування товару: <a href="%s">Редагувати</a>', 'wp-my-product-webspark'), $edit_product_url); ?></p>
