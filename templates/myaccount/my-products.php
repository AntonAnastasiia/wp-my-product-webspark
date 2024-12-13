<h2><?php _e('Мої товари', 'wp-my-product-webspark'); ?></h2>
<?php if (!empty($products)) : ?>
    <table>
        <thead>
            <tr>
                <th><?php _e('Назва товару', 'wp-my-product-webspark'); ?></th>
                <th><?php _e('Кількість', 'wp-my-product-webspark'); ?></th>
                <th><?php _e('Ціна', 'wp-my-product-webspark'); ?></th>
                <th><?php _e('Статус', 'wp-my-product-webspark'); ?></th>
                <th><?php _e('Редагувати', 'wp-my-product-webspark'); ?></th>
                <th><?php _e('Видалити', 'wp-my-product-webspark'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product) : ?>
                <?php $product_object = wc_get_product($product->ID); ?>
                <tr>
                    <td><?php echo esc_html($product->post_title); ?></td>
                    <td><?php echo esc_html($product_object->get_stock_quantity()); ?></td>
                    <td><?php echo wc_price($product_object->get_price()); ?></td>
                    <td><?php echo esc_html($product_object->get_status()); ?></td>
                <?php
                    echo '<td><a href="' . esc_url(add_query_arg(['edit_product' => $product->ID], wc_get_account_endpoint_url('add-product'))) . '">' . __('Редагування', 'wp-my-product-webspark') . '</a></td>';
                    echo '<td><a href="#" class="delete-product" data-product-id="' . esc_attr($product->ID) . '">' . __('Видалити', 'wp-my-product-webspark') . '</a></td>';
                ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p><?php _e('Товарів не знайдено.', 'wp-my-product-webspark'); ?></p>
<?php endif; ?>

