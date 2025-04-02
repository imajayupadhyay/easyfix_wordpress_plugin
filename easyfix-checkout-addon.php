<?php
/**
 * Plugin Name: EasyFix Checkout Addon
 * Description: Adds EasyFix service selector on WooCommerce checkout with pincode validation.
 * Version: 1.2
 * Author: Ajay Upadhyay
 */

if (!defined('ABSPATH')) exit;

// ✅ Load jQuery for use
add_action('wp_enqueue_scripts', function () {
    if (is_checkout()) {
        wp_enqueue_script('jquery');
    }
});

// ✅ Add EasyFix block on WooCommerce checkout
add_action('woocommerce_review_order_before_payment', 'easyfix_render_checkout_block');
function easyfix_render_checkout_block() {
    ?>
    <div id="easyfix-checker-container">
        <label for="pincode">Enter Pincode to check EasyFix availability:</label>
        <input type="text" id="easyfix-pincode" maxlength="6" />

        <div id="easyfix-action-button">
            <button id="easyfix-check">Check Availability</button>
        </div>

        <div id="easyfix-result-message"></div>
    </div>

    <style>
        #easyfix-checker-container {
            border: 1px solid #e1e1e1;
            padding: 20px;
            margin: 30px 0;
            background-color: #f9f9f9;
            border-radius: 6px;
        }
        #easyfix-checker-container label {
            font-weight: 600;
            display: block;
            margin-bottom: 10px;
            font-size: 15px;
        }
        #easyfix-checker-container input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 10px;
        }
        #easyfix-checker-container button {
            background-color: rgb(232, 39, 190);
            color: #fff;
            border: none;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
        }
        #easyfix-checker-container button:hover {
            background-color: rgb(200, 30, 160);
        }
        #easyfix-result-message {
            margin-top: 12px;
            font-size: 14px;
            font-weight: 500;
        }
        .easyfix-add-btn {
            background-color: #d4a017;
            margin-top: 10px;
        }
        .easyfix-add-btn:hover {
            background-color: #b88a12;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const validPincodes = ['110001', '560001', '400001'];
            const productId = 9853;

            const result = document.getElementById('easyfix-result-message');
            const actionDiv = document.getElementById('easyfix-action-button');

            document.getElementById('easyfix-check').addEventListener('click', function () {
                const pin = document.getElementById('easyfix-pincode').value.trim();

                if (validPincodes.includes(pin)) {
                    result.textContent = "✅ EasyFix is available!";
                    actionDiv.innerHTML = `
                        <button id="easyfix-add-cart" class="easyfix-add-btn">Add EasyFix to Cart</button>
                    `;

                    document.getElementById('easyfix-add-cart').addEventListener('click', function () {
                        jQuery.post("<?php echo admin_url('admin-ajax.php'); ?>", {
                            action: "easyfix_add_to_cart",
                            pincode: pin
                        }, function (response) {
                            if (response.success) {
                                result.textContent = "✅ EasyFix added to cart. Please continue with checkout.";

                                // Prevent auto Razorpay trigger
                                sessionStorage.setItem('easyfix_cart_added', '1');

                                // Soft reload to refresh checkout WITHOUT triggering payment
                                window.location.href = window.location.href.split('#')[0];
                            } else {
                                result.textContent = "❌ Failed to add EasyFix.";
                            }
                        });
                    });

                } else {
                    result.textContent = "❌ Sorry, EasyFix is not available in this pincode.";
                }
            });

            // Optional: remove the flag if exists to ensure Razorpay doesn’t auto fire
            if (sessionStorage.getItem('easyfix_cart_added')) {
                sessionStorage.removeItem('easyfix_cart_added');
            }
        });
    </script>
    <?php
}

// ✅ Handle Ajax: Add EasyFix product to cart
add_action('wp_ajax_easyfix_add_to_cart', 'easyfix_add_to_cart');
add_action('wp_ajax_nopriv_easyfix_add_to_cart', 'easyfix_add_to_cart');

function easyfix_add_to_cart() {
    $valid_pins = ['110001', '560001', '400001'];
    $pincode = sanitize_text_field($_POST['pincode']);
    $product_id = 9853;

    if (in_array($pincode, $valid_pins)) {
        // Prevent duplicates
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                wp_send_json_success('Already in cart');
            }
        }

        WC()->cart->add_to_cart($product_id, 1);
        wp_send_json_success('Added to cart');
    }

    wp_send_json_error('Not serviceable');
}
