<?php
namespace PluginBuffet\RazorpayForEdd;

class RegisterRazorPay
{
    public function __construct()
    {
        add_filter('edd_settings_sections_gateways', [ $this , 'addRazorPaySection' ], 10, 1);
        add_filter('edd_settings_gateways', [ $this , 'addRazorPaySettings' ], 10, 1);
        add_filter('edd_payment_gateways', [ $this, 'addRazorPayGateway' ], 10, 1);
//        add_action('edd_razorpay_gateway_cc_form', '__return_false');
    }

    public function addRazorPaySection ( $sections )
    {
        $sections['razorpay_gateway'] = __( 'Razorpay Settings', 'easy-digital-downloads' );
        return $sections;
    }

    public function addRazorPaySettings ( $settings )
    {
        $settings = $this->addRazorPaySetupSettings( $settings );

        $settings = $this->addRazorPayInGateways( $settings );

        return $settings;
    }

    public function addRazorPayGateway( $gateways )
    {
        $gateways['razorpay_gateway'] = [
            'admin_label'    => __( 'RazorPay', 'easy-digital-downloads' ),
            'checkout_label' => __( 'RazorPay', 'easy-digital-downloads' ),
            "supports" => []
        ];

        return $gateways;
    }

    private function addRazorPaySetupSettings( $settings )
    {
        $settings['razorpay_gateway']['razorpay_gateway_header'] = array(
            'id' => 'razorpay_gateway',
            'name' => '<strong>' . __( 'Razorpay Settings', 'razorpay-for-edd' ) . '</strong>',
            'type' => 'header',
            'desc' => __( 'Configure Razorpay Settings', 'razorpay-for-edd' ),
        );
        $settings['razorpay_gateway']['razorpay_key'] = array(
            'id' => 'razorpay_key',
            'name' => __( 'Razorpay Key', 'razorpay-for-edd' ),
            'desc' => __( 'Enter your Razorpay Key', 'razorpay-for-edd' ),
            'type' => 'text',
        );
        $settings['razorpay_gateway']['razorpay_secret'] = array(
            'id' => 'razorpay_secret',
            'name' => __( 'Razorpay Secret', 'razorpay-for-edd' ),
            'desc' => __( 'Enter your Razorpay Secret', 'razorpay-for-edd' ),
            'type' => 'text',
        );

        return $settings;
    }

    private function addRazorPayInGateways ( $settings )
    {
        $settings['main']['gateways']['options']['razorpay_gateway'] = [
            'admin_label'    => __( 'RazorPay', 'easy-digital-downloads' ),
            'checkout_label' => __( 'RazorPay', 'easy-digital-downloads' ),
            "supports" => []
        ];
        $settings['main']['default_gateway']['options']['razorpay_gateway'] = [
            'admin_label'    => __( 'RazorPay', 'easy-digital-downloads' ),
            'checkout_label' => __( 'RazorPay', 'easy-digital-downloads' ),
            "supports" => []
        ];

        return $settings;
    }
}