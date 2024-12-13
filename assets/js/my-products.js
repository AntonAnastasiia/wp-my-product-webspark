jQuery(document).ready(function ($) {
    $('.delete-product').on('click', function (e) {
        e.preventDefault();

        if (!confirm('Ви дійсно хочете видалити цей товар?')) {
            return;
        }

        const productId = $(this).data('product-id');

        $.ajax({
            url: wp_my_product_webspark.ajax_url,
            type: 'POST',
            data: {
                action: 'wpmpw_delete_product',
                product_id: productId,
                security: wp_my_product_webspark.nonce,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
        });
    });
});
