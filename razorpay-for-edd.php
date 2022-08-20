<?php
/**
 * Plugin Name:     RazorPay For EDD (RazorPay for Easy Digital Downloads)
 * Plugin URI:      https://pluginbuffet.com/razorpay-for-edd/
 * Description:     Easily accept payments on your Easy Digital Downloads store using RazorPay.
 * Version:         1.0.0
 * Author:          PluginBuffet
 * Author URI:      https://pluginbuffet.com
 * Text Domain:     razorpay-for-edd
 * Domain Path:     /languages
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'RazorPayForEDD' ) ) {
    require 'vendor/autoload.php';

    /**
     * Main EDD_Plugin_Name class
     *
     * @since       1.0.0
     */
    class RazorPayForEDD {

        /**
         * @var         RazorPayForEDD $instance The one true RazorPayForEDD
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true RazorPayForEDD
         */
        public static function instance( $instance = false ) {
            if( !self::$instance ) {
                self::$instance = new RazorPayForEDD();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            } elseif( $instance ) {
                return self::$instance = new PluginBuffet\RazorpayForEdd . '\\' . $instance();
            }

            return self::$instance;
        }

        private function setup_constants() {
            // Plugin version
            define( 'RAZORPAY_FOR_EDD_V', '1.0.0' );

            // Plugin path
            define( 'RAZORPAY_FOR_EDD_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'RAZORPAY_FOR_EDD_URL', plugin_dir_url( __FILE__ ) );
        }

        private function includes() {
            return;
        }

        private function hooks()
        {
            new \PluginBuffet\RazorpayForEdd\RegisterRazorPay();
            (new \PluginBuffet\RazorpayForEdd\CheckoutPage())->init();
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = RAZORPAY_FOR_EDD_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_plugin_name_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-plugin-name' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-plugin-name', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/razorpay-for-edd/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-plugin-name/ folder
                load_textdomain( 'razorpay-for-edd', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-plugin-name/languages/ folder
                load_textdomain( 'razorpay-for-edd', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'razorpay-for-edd', false, $lang_dir );
            }
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Plugin_Name
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \RazorPayForEDD The one true RazorPayForEDD
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function RazorPayForEDD_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class.extension-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FxILE__ ) );
        $activation = $activation->run();
    } else {
        return RazorPayForEDD::instance();
    }
}
add_action( 'plugins_loaded', 'RazorPayForEDD_load' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function RazorPayForEDDActivation() {
    return true;
}
register_activation_hook( __FILE__, 'RazorPayForEDDActivation' );
