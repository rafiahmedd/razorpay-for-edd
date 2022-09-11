<?php
namespace RazorpayForEdd\App\Api;

class HandleRazorRequest
{
    public function init()
    {
        if ( edd_get_option('webhook_status') ) {
            $this->handleRequest();
        }
    }

    // Method to check if the request is from Razorpay and is a valid request
    private function checkValidWebhook( $webhookToken )
    {
        $token = get_option( 'razorpay_webhook_token' );
        if ( $webhookToken != $token ) {
            return false;
        }
        return true;
    }

    /**
     * Handle the webhook request
     */
    private function handleRequest()
    {
        $webhookToken = isset($_REQUEST['edd_razorpay_webhook_token']) ?? $_REQUEST['edd_razorpay_webhook_token'];
        if ( !$this->checkValidWebhook( $webhookToken ) ) {
            return;
        }
        $body = file_get_contents('php://input');
        $body = json_decode($body, true);
        $eddOrderID = $body['payload']['payment']['entity']['notes']['edd_order_id'];
        $event = $body['event'];
        return $this->manageEvent( $event, intval($eddOrderID) );
    }

    // Method to manage the razorpay events like refund 
    private function manageEvent( $event, $eddOrderID )
    {
        switch ($event) {
            case 'refund.created':
                return $this->handleRefund( $eddOrderID );
                break;
            case 'payment_link.paid':
                return $this->handlePaid( $eddOrderID );
                break;
            case 'payment_link.failed':
                return $this->handleFailed( $eddOrderID );
                break;
          }
    }

    // Method to handle the refund event
    private function handleRefund( $eddOrderID )
    {
        global $wpdb;

        $wpdb->update( $wpdb->prefix . 'edd_order_items',
            ['status' => 'refunded'], [ 'order_id' => $eddOrderID ]
        );

        $wpdb->update( $wpdb->prefix . 'edd_orders',
            ['status' => 'refunded'], [ 'id' => $eddOrderID]
        );
    }

    // Method to handle the paid event
    private function handlePaid( $eddOrderID )
    {
        global $wpdb;

        $wpdb->update( $wpdb->prefix . 'edd_order_items',
            ['status' => 'complete'], ['status' => 'pending', 'order_id' => $eddOrderID]
        );

        $wpdb->update( $wpdb->prefix . 'edd_orders',
            ['status' => 'complete'], ['status' => 'pending', 'id' => $eddOrderID]
        );
    }

    // Method to handle the failed event
    private function handleFailed( $eddOrderID )
    {
        global $wpdb;

        $wpdb->update( $wpdb->prefix . 'edd_order_items',
            ['status' => 'failed'], ['status' => 'pending', 'order_id' => $eddOrderID]
        );
        $wpdb->update( $wpdb->prefix . 'edd_orders',
            ['status' => 'failed'], ['status' => 'pending', 'id' => $eddOrderID]
        );
    }
}