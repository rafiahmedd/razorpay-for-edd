<?php
namespace PluginBuffet\RazorpayForEdd\Api;

class RazorAPI{

    public static function request ( $path, $orderData, $method )
    {
        $url = 'https://api.razorpay.com/v1/' . $path ;
        $keyId = edd_get_option( 'razorpay_key' );
        $keySecret = edd_get_option( 'razorpay_secret' );
        $bearer = 'Basic ' . base64_encode( $keyId . ':' . $keySecret );

        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => $bearer,
        );

        $method = strtoupper( $method );
        $method == 'POST' ? $method = 'wp_remote_post' : $method = 'wp_remote_get';

        $response = $method( $url, array(
            'headers' => $headers,
            'body' => json_encode( $orderData ),
        ) );

        return wp_remote_retrieve_body($response);
    }

}