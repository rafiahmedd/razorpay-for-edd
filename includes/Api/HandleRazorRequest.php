<?php
namespace PluginBuffet\RazorpayForEdd\Api;

class HandleRazorRequest
{
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
    public function handleRequest()
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
}