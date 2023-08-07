<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Coupon
 * Plugin URI:        http://flycart.org/
 * Description:       A plugin for Axis-Subscriptions Coupon.
 * Version:           1.0.0
 * Author:            Ashlin
 * Author URI:        http://flycart.org/
 * Text Domain:       axisubs-app-coupon
 * License:           MIT
 * Update Service:    none
 * Plugin ID:         axisubs-app-coupon
 */

use Events\Event;
use AxisubsAppCoupon\Controllers\Admin\AxisubsAppCoupon;
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
require_once( WP_PLUGIN_DIR.'/'.dirname( plugin_basename(__FILE__) ).'/app/Models/Admin/AxisubsAppCoupon.php');
require_once( WP_PLUGIN_DIR.'/'.dirname( plugin_basename(__FILE__) ).'/app/Controllers/Admin/AxisubsAppCoupon.php');

if (!defined('AXISUBS_APP_COUPON_PLUGIN_PATH')) {
    define( 'AXISUBS_APP_COUPON_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

Event::listen('axisubs-app-coupon_loadView', array('AxisubsAppCouponPlugin', 'loadFormView'), '1');
Event::listen('axisubs-app-coupon_appTask', array('AxisubsAppCouponPlugin', 'runAppTask'), '1');

Event::listen('axisubs-app-coupon_ajax', array('AxisubsAppCouponPlugin', 'runAppTask'), '1');
Event::listen('axisubs-app-additionalPrice', array('AxisubsAppCouponPlugin', 'additionalPrice'), '10');
Event::listen('axisubs-app_clearSession', array('AxisubsAppCouponPlugin', 'clearSessionVariables'), '10');
Event::listen('axisubs-app-coupon_loadContentAfterPrice', array('AxisubsAppCouponPlugin', 'loadContentAfterPrice'), '10');
Event::listen('axisubs-app-coupon_loadContentInPriceList', array('AxisubsAppCouponPlugin', 'loadContentInPriceList'), '9');
Event::listen('axisubs-app-coupon_hasFunction', array('AxisubsAppCouponPlugin', 'hasHook'), '1');
Event::listen('axisubs-app-coupon_loadContentInSubscriptionPriceList', array('AxisubsAppCouponPlugin', 'loadContentInSubscriptionPriceList'), '1');

class AxisubsAppCouponPlugin
{
    protected static $hooks = array('loadView',
        'loadContentInPriceList',
        'loadContentAfterPrice',
        'loadContentInSubscriptionPriceList');

    /**
     * To check function exist
     * */
    public static function hasHook($functionName){
        if(in_array($functionName, AxisubsAppCouponPlugin::$hooks)){
            return 100;
        } else {
            return false;
        }
    }

    /**
     * For load Form/View form
     * */
    public static function loadFormView()
    {
        AxisubsAppCoupon::loadAppView();
    }

    /**
     * Run app task
     * */
    public static function runAppTask()
    {
        $http = Http::capture();
        $paypalObject = new AxisubsAppCoupon();
        $paypalObject->execute($http);
    }

    /**
     * Load content after price
     * */
    public static function loadContentAfterPrice($item){
        $plan = $item[0];
        $subscription = $item[2];
        $page = $item[3];
        if($page == 'confirm'){
            return '';
        }
        if($plan->meta[$plan->ID.'_'.$plan->post_type.'_type'] == 'free'){
            return '';
        }
        return AxisubsAppCoupon::loadCouponForm($plan, $subscription);
    }

    /**
     * Load content after price
     * */
    public static function loadContentInPriceList($item){
        $plan = $item[0];
        $subscription = $item[2];
        $page = $item[3];
        if($plan->meta[$plan->ID.'_'.$plan->post_type.'_type'] == 'free'){
            return '';
        }
        return AxisubsAppCoupon::loadCouponDetails($plan, $subscription, $page);
    }

    /**
     * Load content in subscription price
     * */
    public static function loadContentInSubscriptionPriceList($item){
        $subscription = $item[0];
        $object = new AxisubsAppCoupon();
        return $object->loadCouponPriceListInSubscriptionPage($subscription);
    }

    /**
     * Axjax call
     * */
    public static function ajaxCall(){
        $http = Http::capture();
        $object = new AxisubsAppCoupon();
        $appTask = $http->get('apptask', '');
        if($appTask != ''){
            $object->$appTask();
        } else {
            echo '';
        }
    }

    /**
     * adding / changing price based on discount
     * */
    public static function additionalPrice($item){
        $paypalObject = new AxisubsAppCoupon();
        if($item['additionalPrice']){
            $additionalPrice = $item['additionalPrice'];
        } else {
            $additionalPrice = array();
        }
        $discount = $paypalObject->calculateDiscount($item);
        $additionalPrice['discount'] = $discount;
        if(isset($item->meta)){
            $metaArray = $item->meta;
            $metaArray['total_excluding_discount'] = $item->meta['total_price'];
            $metaArray['total_price'] = $item->meta['total_price'] - $discount;
            $item->meta = $metaArray;
        } else {
            $metaArray = $item['meta'];
            $metaArray['total_excluding_discount'] = $item['meta']['total_price'];
            $metaArray['total_price'] = $item['meta']['total_price'] - $discount;
            $item['meta'] = $metaArray;
        }

        $item['additionalPrice'] = $additionalPrice;
    }

    /**
     * Clear Session variables
     * */
    public static function clearSessionVariables(){
        AxisubsAppCoupon::clearSession();
    }
}
