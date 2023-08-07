<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 16/7/16
 * Time: 3:44 PM
 */

namespace Axisubs\Helper;

use Axisubs\Models\Admin\Customers;

class Common
{
    public static $instance = null;

    /**
     * get an instance
     * @param array $config
     * @return
     * * */
    public static function getInstance(array $config = array())
    {
        if (!self::$instance) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * For generating invoice number
     * */
    public function getInvoiceNumber($subscription_id){
        $invoicePrefix = Config::getInstance()->get('invoice_prefix', 'axisubs_wp');
        return $invoicePrefix.'-'.$subscription_id;
    }

    /**
     * get subscription id from invoice number
     * */
    public function getSubscriptionIdFromInvoiceNumber($invoice){
        $invoiceArray = explode('-', $invoice);
        $lastIndex = count($invoiceArray)-1;
        if(isset($invoiceArray[$lastIndex])){
            $subscriptionId = $invoiceArray[$lastIndex];
        } else {
            $subscriptionId = 0;
        }
        return $subscriptionId;
       // return str_replace('axisubs_wp-', '', $invoice); 
    }

    /**
     * For Round the value
     * */
    public static function roundPrice($price){
        return round($price, 2);
    }

    /**
     * For setting the customer details in session for calculating tax
     * */
    public static function setCustomerDetailsInSession(){
        $user_details = ManageUser::getInstance()->getUserDetails();
        if(isset($user_details->ID) && $user_details->ID){
            $customer = Customers::getCustomerDetails($user_details->ID);
            if($customer) {
                $customerMeta = $customer->meta;
                $customerPrefix = $customer->ID . '_' . $customer->post_type . '_';
                $session = Session();
                if (isset($customerMeta[$customerPrefix . 'country']) && $customerMeta[$customerPrefix . 'country'] != '') {
                    $session->set('customer_billing_country', $customerMeta[$customerPrefix . 'country']);
                }
                if (isset($customerMeta[$customerPrefix . 'province']) && $customerMeta[$customerPrefix . 'province'] != '') {
                    $session->set('customer_billing_state', $customerMeta[$customerPrefix . 'province']);
                }
                if (isset($customerMeta[$customerPrefix . 'pincode']) && $customerMeta[$customerPrefix . 'pincode'] != '') {
                    $session->set('customer_billing_zip', $customerMeta[$customerPrefix . 'pincode']);
                }
                if (isset($customerMeta[$customerPrefix . 'city']) && $customerMeta[$customerPrefix . 'city'] != '') {
                    $session->set('customer_billing_city', $customerMeta[$customerPrefix . 'city']);
                }
                if (isset($customerMeta[$customerPrefix . 'country']) && $customerMeta[$customerPrefix . 'country'] != '') {
                    $session->set('customer_billing_country', $customerMeta[$customerPrefix . 'country']);
                }
                if (isset($customerMeta[$customerPrefix . 'vat_number']) && $customerMeta[$customerPrefix . 'vat_number'] != '') {
                    $session->set('customer_billing_vat_number', $customerMeta[$customerPrefix . 'vat_number']);
                }
            }
        }
    }

    /**
     * check installation status
     * */
    public static function checkCompleteInstall(){
        $status = get_option('axisubs_installation_wizard', 0);
        if($status >= 3){
            return true;
        } else {
            return false;
        }
    }

    /**
     * set installation wizard
     * */
    public static function setInstallWizard($val = 1){
        $status = get_option('axisubs_installation_wizard', '');
        if($status != ''){
            update_option('axisubs_installation_wizard', $val);
        } else {
            add_option('axisubs_installation_wizard', $val);
        }
    }

    /**
     * To load the editor
     * */
    public static function getEditor($fieldname, $editor_id, $message = '', $height = 100){
        $settings = array('textarea_name' => $fieldname,
            'editor_height' => $height);
        ob_start();
        wp_editor( $message, $editor_id, $settings );
        $editor = ob_get_clean();
        //ob_end_flush();

        return $editor;
    }

    /**
     * To get Plugin version
     * */
    public static function getPluginVersion(){
        return get_plugin_data(AXISUBS_PLUGIN_PATH.'plugin.php')['Version'];
    }

    /**
     * To set Twig Global data
     * */
    public static function setTwigGlobalVariables(){
        herbert('Twig_Environment')->addGlobal('plugin_version', Common::getPluginVersion());
    }
}