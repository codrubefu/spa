<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );

	return;
}

?>
<form name="checkout" method="post" class="checkout woocommerce-checkout"
      action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

        <div id="customer_details">
            <div class="col-1">
                <div class="woocommerce-billing-fields__field-wrapper">
					<?php
					$fields = $checkout->get_checkout_fields( 'billing' );

					foreach ( $fields as $key => $field ) {

						if ( $key == 'billing_company' ) {
							continue;
						}
						woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
						if ( $key === 'billing_email' ) {
							?>

                            <p>
                                <span class="woocommerce-input-wrapper">
                                    <label>
                                        <select id="type" class="type">
                                            <option value="0">Persoana fizica</option>
                                            <option value="1">Persoana juridica</option>
                                        </select>
                                    </label>
                                </span>
                            </p>
                            <div id="custom_checkout_fields" style="display: none">
								<?php woocommerce_form_field( 'billing_company', $fields['billing_company'], $checkout->get_value( 'billing_company' ) ); ?>
								<?php echo do_shortcode( '[custom_checkout_fields]' ); ?>
                            </div>
							<?php
						}
					}
					?>
                </div>
            </div>
        </div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

    <h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>

	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

    <div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
    </div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
