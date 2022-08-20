<?php
namespace PluginBuffet\RazorpayForEdd;
use PluginBuffet\RazorpayForEdd\Api\RazorAPI;

class CheckoutPage
{
    private $purchaseData;
    private $razorpayOrderId;

    public function init()
    {
        add_action('edd_razorpay_gateway_cc_form', '__return_false');
//        add_filter('edd_checkout_button_purchase', [ $this, 'overwritePaymentButton' ], 10000);
        add_action('edd_gateway_razorpay_gateway', [ $this, 'createRazorpayOrder' ]);
        $this->handlePayment();
    }

    // public function overwritePaymentButton( $button )
    // {
    //     if ( 'razorpay_gateway' == edd_get_chosen_gateway() && edd_get_cart_total() ) {

    //           return $this->hostedCheckOutForm( $this->purchaseData, $this->razorpayOrderId );

    //     }
    // }

    public function createRazorpayOrder( $purchase_data )
    {

        try {
            // Create pending payment in EDD.
            $payment_args = array(
                'price'        => $purchase_data['price'],
                'date'         => $purchase_data['date'],
                'user_email'   => $purchase_data['user_email'],
                'purchase_key' => $purchase_data['purchase_key'],
                'currency'     => edd_get_currency(),
                'downloads'    => $purchase_data['downloads'],
                'cart_details' => $purchase_data['cart_details'],
                'user_info'    => $purchase_data['user_info'],
                'status'       => 'pending',
                'gateway'      => 'razorpay_gateway'
            );

            $payment_id = edd_insert_payment( $payment_args );

            if ( ! $payment_id ) {
                throw new \Exception(
                    __( 'An unexpected error occurred. Please try again.', 'easy-digital-downloads' ),
                    500,
                    sprintf(
                        'Payment creation failed before sending buyer to RazorPay. Payment data: %s',
                        json_encode( $payment_args )
                    )
                );
            }

            $razorpayOrder = [
                'amount' => absint($purchase_data['price'] . '00'),
                'currency' => edd_get_currency(),
                'receipt' => "Payment for EDD Order #$payment_id",
            ];

            /**
             * Filters the arguments sent to PayPal.
             *
             * @param array $order_data    API request arguments.
             * @param array $purchase_data Purchase data.
             * @param int   $payment_id    ID of the EDD payment.
             *
             * @since 2.11
             */
            $order_data = apply_filters( 'edd_razorpay_gateway_order_arguments', $razorpayOrder, $purchase_data, $payment_id );

            try {
                $response = RazorAPI::request('orders', $order_data, 'POST' );
                $revertedResponse = json_decode($response, true);
                $this->razorpayOrderId = $revertedResponse['id'];
                $this->purchaseData = $purchase_data;

                edd_add_order_meta($payment_id, 'razorpay_order_id', $this->razorpayOrderId);

                if ( ! empty( $this->razorpayOrderId ) ) {
                    $paymentData = array(
                        'amount'       => intval($purchase_data['price'] . '00'),
                        'currency'     => edd_get_currency(),
                        'description'  => 'Rafi',
                        'reference_id' => $this->razorpayOrderId,
                        'customer'     => [
                            'email' => $purchase_data['user_email'],
                        ],
                        'callback_url'  => edd_get_success_page_uri(),
                        'notes'        => [
                            'edd_order_id'       => $payment_id,
                        ],
                        'callback_method' => 'get',
                        'notify' => [
                            'email' => true,
                            'sms' => false,
                        ]
                    );

                    $paymentRequest = RazorAPI::request('payment_links', $paymentData, 'POST' );
                    $revertedResponse = json_decode($paymentRequest, true);
                    wp_redirect($revertedResponse['short_url']);
                }
                /*
                 * Send successfully created order ID back.
                 * We also send back a new nonce, for verification in the next step: `capture_order()`.
                 * If the user was just logged into a new account, the previously sent nonce may have
                 * become invalid.
                 */
                $timestamp = time();
                wp_send_json_success( array(
                    'edd_order_id'    => $payment_id,
                    'nonce'           => wp_create_nonce( 'edd_process_paypal' ),
                    'timestamp'       => $timestamp,
                ) );
            } catch ( \Exception $e ) {
                throw new \Exception( __( 'An authentication error occurred. Please try again.', 'easy-digital-downloads' ), $e->getCode(), $e->getMessage() );
            } catch ( \Exception $e ) {
                throw new \Exception( __( 'An error occurred while communicating with PayPal. Please try again.', 'easy-digital-downloads' ), $e->getCode(), $e->getMessage() );
            }
        } catch ( \Exception $e ) {
            if ( ! isset( $payment_id ) ) {
                $payment_id = 0;
            }

            edd_record_gateway_error(
                __( 'Razorpay Gateway Error', 'easy-digital-downloads' ),
                $e->getMessage(),
                $payment_id
            );
            wp_send_json_error( edd_build_errors_html( array(
                'razorpay-error' => $e->getMessage()
            ) ) );
        }
    }

    public function handlePayment( )
    {
        if ($_REQUEST  && isset($_REQUEST['razorpay_payment_id'])) {
            $orderMeta =  $this->getOrderMetaByID( $_REQUEST['razorpay_payment_link_reference_id'] );
            if ( 'paid' == $_REQUEST['razorpay_payment_link_status'] ) {
                $this->completePayment( $orderMeta->edd_order_id );
            } else {
                $this->failPayment( $orderMeta->edd_order_id );
            }
//                $payment = new \EDD_Payment($orderMeta->edd_order_id);

//                if ( 'complete' != $payment->status ) {
//                    edd_update_payment_status( $payment->ID, 'complete' );
//                }
////                $payment->status = 'complete';
////                $payment->save();
//                edd_insert_payment_note($orderMeta->edd_order_id, 'Payment Successful');
//                edd_empty_cart();
//                edd_send_to_success_page();
//            } else {
//                edd_insert_payment_note($orderMeta->edd_order_id, 'Payment Failed');
//                edd_send_to_failed_page();
            }
    }

    private function completePayment( $orderId )
    {
        global $wpdb;

        $wpdb->update( $wpdb->prefix . 'edd_order_items',
            ['status' => 'complete'], ['status' => 'pending', 'order_id' => $orderId]
        );

        $wpdb->update( $wpdb->prefix . 'edd_orders',
            ['status' => 'complete'], ['status' => 'pending', 'id' => $orderId]
        );
    }

    private function failPayment( $orderId )
    {
        global $wpdb;

        $wpdb->update( $wpdb->prefix . 'edd_order_items',
            ['status' => 'failed'], ['status' => 'pending', 'order_id' => $orderId]
        );

        $wpdb->update( $wpdb->prefix . 'edd_orders',
            ['status' => 'failed'], ['status' => 'pending', 'id' => $orderId]
        );
    }

    private function getOrderMetaByID( $razorOrderID )
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edd_ordermeta';
        $data = [];
        $sql = "SELECT * FROM $table_name WHERE meta_key = 'razorpay_order_id' AND meta_value = '$razorOrderID'";
        $results = $wpdb->get_results($sql);

        foreach ($results as $result) {
            $data = $result;
        }

        return $data;
    }
}