<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Paypal
 * Plugin URI:        http://flycart.org/
 * Description:       Paypal payment plugin for Axis Subscription.
 * Version:           1.0.0
 * Author:            Ashlin
 * Author URI:        http://flycart.org/
 * Text Domain:       axisubs-app-payment-paypal
 * License:           MIT
 * Update Service:    none
 * Plugin ID:         axisubs-app-payment-paypal
 */

use Events\Event;
use AxisubsAppPaypal\Controllers\Admin\AxisubsAppPaypal;
use Herbert\Framework\Http;

if(!class_exists('GetAxisSubscriptionsPlugin')) {
    class GetAxisSubscriptionsPlugin
    {
        private static $_instance = null;

        /**
         * Get the single instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        private function __construct() {

        }

        public function getAxisubPlugin(){
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugins = get_plugins();
            $axisubsApps = array();
            foreach($plugins as $key => $value){
                if($value['TextDomain'] == 'axis-subscriptions'){
                    $names = explode('/', $key);
                    $axisubsAppsNames = $names[0];
                    $value['pluginPath'] = $key;
                    $value['pluginFolder'] = $axisubsAppsNames;
                    $value['pluginImage'] = plugins_url($value['pluginFolder']).'/resources/assets/images/logo.png';
                    if(is_plugin_active($key)){
                        $value['active'] = true;
                    } else {
                        $value['active'] = false;
                    }
                    $axisubsApps = $value;
                    break;
                }
            }

            return $axisubsApps;
        }
    }

    $pluginObject = GetAxisSubscriptionsPlugin::instance();
    $axisubPlugin = $pluginObject->getAxisubPlugin();
}

if(empty($axisubPlugin)){
    return false;
}

if(!file_exists(WP_PLUGIN_DIR.'/'.$axisubPlugin['pluginPath'])){
    return false;
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( !is_plugin_active($axisubPlugin['pluginPath']) ) {
    return false;
}
/**
 * Include vendor
 * */
require_once( WP_PLUGIN_DIR.'/'.$axisubPlugin['pluginFolder'].'/vendor/autoload.php');
require_once( WP_PLUGIN_DIR.'/'.$axisubPlugin['pluginFolder'].'/vendor/getherbert/framework/bootstrap/autoload.php');

require_once( WP_PLUGIN_DIR.'/'.dirname( plugin_basename(__FILE__) ).'/app/Controllers/Controller.php');
require_once( WP_PLUGIN_DIR.'/'.dirname( plugin_basename(__FILE__) ).'/app/Models/Admin/AxisubsAppPaypal.php');
require_once( WP_PLUGIN_DIR.'/'.dirname( plugin_basename(__FILE__) ).'/app/Controllers/Admin/AxisubsAppPaypal.php');

if (!defined('AXISUBS_APP_PAYPAL_PLUGIN_PATH')) {
    define( 'AXISUBS_APP_PAYPAL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

Event::listen('axisubs-app-payment-paypal_loadView', array('AxisubsAppPaypalPlugin', 'loadFormView'), '1');
Event::listen('axisubs-app-payment-paypal_appTask', array('AxisubsAppPaypalPlugin', 'runAppTask'), '1');
Event::listen('axisubs-app-payment-paypal_paymentOptionRadio', array('AxisubsAppPaypalPlugin', 'paymentRadioButton'), '1');
Event::listen('axisubs-app-payment-paypal_paymentForm', array('AxisubsAppPaypalPlugin', 'loadPaymentForm'), '1');
Event::listen('axisubs-app-payment-paypal_paymentTask', array('AxisubsAppPaypalPlugin', 'processPaymentTasks'), '1');

Event::listen('axisubs-app-payment-paypal_hasFunction', array('AxisubsAppPaypalPlugin', 'hasHook'), '1');
Event::listen('axisubs-app-payment-paypal_cancelSubscription', array('AxisubsAppPaypalPlugin', 'cancelSubscription'), '1');
Event::listen('axisubs-app-payment-paypal_preProcessSubscriptionView', array('AxisubsAppPaypalPlugin', 'preProcessSubscriptionView'), '1');

class AxisubsAppPaypalPlugin
{
    protected static $hooks = array('loadView');

    /**
     * To check function exist
     * */
    public static function hasHook($functionName){
        if(in_array($functionName, AxisubsAppPaypalPlugin::$hooks)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * For load Form/View form
     * */
    public static function loadFormView(){
        AxisubsAppPaypal::loadAppView();
    }

    /**
     * Run app task
     * */
    public static function runAppTask(){
        $http = Http::capture();
        $paypalObject = new AxisubsAppPaypal();
        $paypalObject->execute($http);
    }

    /**
     * Load payment radio button
     * */
    public static function paymentRadioButton(){
        $html = '<div class="axisubs_payment_each_block">';
        $html .='<input type="radio" id="axisubs_payment_paypal" name="payment" value="axisubs-app-payment-paypal" />';
        $html .= '<label for="axisubs_payment_paypal">Paypal</label>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Load payment Form
     * */
    public static function loadPaymentForm($args){
        $subscription = $args[0];
        $plans = $args[1];
        $paypalObject = new AxisubsAppPaypal();
        return $paypalObject->loadPaymentForm($subscription, $plans);
    }

    /**
     * Run payment tasks
     * */
    public static function processPaymentTasks($args){
        $paypalObject = new AxisubsAppPaypal();
        return $paypalObject->processPayment();
    }

    /**
     * Cancel subscription
     * */
    public static function cancelSubscription($subscription){
        $controllerObj = new AxisubsAppPaypal();
        $model = $controllerObj->getModel();
        $model->cancelPaypalSubscription($subscription);
    }

    /**
     * Cancel subscription
     * */
    public static function preProcessSubscriptionView($args){
        $subscription = $args[0];
        $data = &$args[1];
        $itemPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
        $payment_processor = $subscription->meta[$itemPrefix.'payment_processor'];
        if($payment_processor == 'axisubs-app-payment-paypal'){
            $controllerObj = new AxisubsAppPaypal();
            $model = $controllerObj->getModel();
            $api = $model->hasAccessToCancel();
            if(is_array($api)){
                $data['allow_cancel']  = true;
            }
        }
    }
}
