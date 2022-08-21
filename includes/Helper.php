<?php

namespace PluginBuffet\RazorpayForEdd;

class Helper
{
    public static function getRazorPayWebhookToken()
    {
        $token = get_option( 'razorpay_webhook_token' );
    
        if (!$token) {
            add_option( 'razorpay_webhook_token', uniqid('razor_') );
            $token = get_option( 'razorpay_webhook_token' );
        }
        
        return $token;
    }
}