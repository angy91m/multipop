<?php
defined( 'ABSPATH' ) || exit;
?>

<style type="text/css">
div.wc-block-components-express-payment-continue-rule.wc-block-components-express-payment-continue-rule--checkout {visibility:hidden;}
.wc-block-checkout__terms.wc-block-checkout__terms--with-separator {
    border-top: unset;
    padding-top: 0;
}
</style>
<!-- wp:woocommerce/checkout -->
    <div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading">
        <div class="wp-block-woocommerce-checkout-fields-block">
            <!-- wp:woocommerce/checkout-express-payment-block -->
                <div class="wp-block-woocommerce-checkout-express-payment-block"></div>
            <!-- /wp:woocommerce/checkout-express-payment-block -->
            <!-- wp:woocommerce/checkout-terms-block -->
                <div class="wp-block-woocommerce-checkout-terms-block"></div>
            <!-- /wp:woocommerce/checkout-terms-block -->
            <!-- wp:woocommerce/checkout-actions-block -->
            <!-- /wp:woocommerce/checkout-actions-block -->
        </div>
        <!-- wp:woocommerce/checkout-totals-block -->
            <div class="wp-block-woocommerce-checkout-totals-block">
                <!-- wp:woocommerce/checkout-order-summary-block -->
                    <div class="wp-block-woocommerce-checkout-order-summary-block">
                        <!-- wp:woocommerce/checkout-order-summary-cart-items-block -->
                            <div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
                        <!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->
                        <!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->
                            <div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
                        <!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->
                        <!-- wp:woocommerce/checkout-order-summary-totals-block -->
                            <div class="wp-block-woocommerce-checkout-order-summary-totals-block">
                                <!-- wp:woocommerce/checkout-order-summary-subtotal-block -->
                                    <div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
                                <!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->
                                <!-- wp:woocommerce/checkout-order-summary-fee-block -->
                                    <div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
                                <!-- /wp:woocommerce/checkout-order-summary-fee-block -->
                                <!-- wp:woocommerce/checkout-order-summary-discount-block -->
                                    <div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
                                <!-- /wp:woocommerce/checkout-order-summary-discount-block -->
                                <!-- wp:woocommerce/checkout-order-summary-shipping-block -->
                                    <div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
                                <!-- /wp:woocommerce/checkout-order-summary-shipping-block -->
                                <!-- wp:woocommerce/checkout-order-summary-taxes-block -->
                                    <div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
                                <!-- /wp:woocommerce/checkout-order-summary-taxes-block -->
                            </div>
                        <!-- /wp:woocommerce/checkout-order-summary-totals-block -->
                    </div>
                <!-- /wp:woocommerce/checkout-order-summary-block -->
            </div>
        <!-- /wp:woocommerce/checkout-totals-block -->
    </div>
<!-- /wp:woocommerce/checkout -->
<?php