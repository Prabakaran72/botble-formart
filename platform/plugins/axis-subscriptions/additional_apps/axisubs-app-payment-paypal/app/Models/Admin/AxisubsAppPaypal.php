<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 1:25 PM
 */

namespace AxisubsAppPaypal\Models\Admin;

//use Herbert\Framework\Models\Post;
use Corcel\Post;
use Herbert\Framework\Models\PostMeta;
use Herbert\Framework\Http;
use Axisubs\Models\Site\Plans;

class AxisubsAppPaypal extends Post
{
    public $_folder = 'axisubs-app-payment-paypal';
    /**
     * The table associated with the model.
     *
     * @var string
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $_item = array();

    public static $post_type = 'axis_paypal_config';

    public function __construct(array $attributes = [])
    {
    }

    public function getConfig()
    {
        $item = parent::all()->where('post_type', AxisubsAppPaypal::$post_type)->first();
        if(!empty($item)) {
            if ($item->meta() != null) {
                $item->meta = $item->meta()->pluck('meta_value', 'meta_key')->toArray();
            }
        }
        $this->_item = $item;
        return $this->_item;
    }

    public function getConfigData($fieldName, $default = ''){
        if(empty($this->_item)){
            $this->_item = $this->getConfig();
        }
        if(!empty($this->_item)){
            $key = $this->_item->ID . '_'.AxisubsAppPaypal::$post_type.'_' . $fieldName;
            if(isset($this->_item->meta[$key])){
                if($this->_item->meta[$key] == ''){
                    $result = $default;
                } else {
                    $result = $this->_item->meta[$key];
                }
            } else {
                $result = $default;
            }
        } else {
            $result = $default;
        }

        return $result;
    }

    /**
     * Save Paypal Config
     * */
    public static function saveConfig($post)
    {
        $postDB = \Corcel\Post::where('post_type', AxisubsAppPaypal::$post_type)->get();
        $postTable = $postDB->first();
        if ($postDB->count() == 0) {
            $postTable = new Post();
            $postTable->post_name = 'axisubs_app_paypal_config';
            $postTable->post_title = 'axisubs_app_paypal_config';
            $postTable->post_type = AxisubsAppPaypal::$post_type;
            $postTable->save();
            $postDB = \Corcel\Post::where('post_type', AxisubsAppPaypal::$post_type)->get();
            $postTable = $postDB->first();
        }
        foreach ($post['axisubs']['payment'] as $key => $val) {
            $key = $postTable->ID . '_'.AxisubsAppPaypal::$post_type.'_' . $key;
            $postTable->meta->$key = $val;
        }
        $result = $postTable->save();

        return $result;
    }

    public function processCancelPayment(){
        $planObject = Plans::getInstance();
        $sessionData = Session()->get('axisubs_subscribers');
        $subscription_id  = $sessionData['current_subscription_id'];
        $transaction_data = array(
            'payment_processor' => $this->_folder,
            'processor_status' => 'CANCELED'
        );

        $planObject->paymentCanceled($subscription_id, $transaction_data);

        $returnResult['status'] = 0;
        $returnResult['message'] = 'Canceled payment';
        return $returnResult;
    }

    /**
     * payment return display
     * */
    public function processPaymentReturnDisplay(){
        $http = Http::capture();
        $data = $http->all();
        $data['transaction_details'] = $this->_getFormattedTransactionDetails($data);
        $this->_log($data['transaction_details']);
        $returnResult['status'] = 200;
        $returnResult['message'] = $this->getConfigData('return_message', 'Thank you for payment. Your transaction is being processed.');

        return $returnResult;
    }

    /**
     * Notify payment
     * */
    public function processPaymentReturn(){
        $planObject = Plans::getInstance();
        $http = Http::capture();
        $custom = $http->get('custom');
        $subscription_id  = $custom;
        $data = $http->all();
        $data['transaction_details'] = $this->_getFormattedTransactionDetails($data);
        $this->_log($data['transaction_details']);
        $errors = array();
        if($subscription_id && $subscription_id > 0){
            $subscription = Plans::loadSubscriber($subscription_id);
            $subsPrefix = $subscription->ID.'_axisubs_subscribe_';
            if (empty($subscription)){
                $errors[] = 'Subscription not Found - '.$subscription_id;
                $this->_log($errors);
            }
            // prepare some data
            $validate_ipn = $this->getConfigData('validate_ipn', 1);
            if($validate_ipn) {
                if($subscription && ($subscription->ID == $subscription_id) ) {
                    // validate the IPN info
                    $validation_result = $this->_validateIPN($data, $subscription);
                    if (!empty($validation_result))
                    {
                        // ipn Validation failed
                        $data['ipn_validation_results'] = $validation_result;
                        $errors[] = $validation_result;
                        $this->_log($errors);
                    }

                }
            }

            // process the Payment based on its type
            if ( !empty($data['txn_type']) )
            {
                $known_txn_response_types = ['web_accept', 'subscr_signup', 'subscr_payment', 'subscr_eot'];

                $environment = $this->getConfigData('sandbox', 1);
                // set Payment plugin variables
                if($environment){
                    $merchant_email = trim($this->getConfigData('sandbox_email'));
                }else{
                    $merchant_email = trim($this->getConfigData('merchant_email'));
                }

                // is the recipient correct?
                if (empty ( $data ['receiver_email'] ) || strtolower( $data ['receiver_email'] ) != strtolower( trim ( $merchant_email ) )) {
                    $errors [] = 'Receiver email does not matches';
                    $this->_log($errors);
                }

                // for recurring subscription a recurring profile is created and a confirmation is got for signup
                if ($data['txn_type'] == 'subscr_signup') {
                    // just update the transaction record with profile id and mark pending
                    $transaction_data = array(
                        'payment_processor' => $this->_folder,
                        'subscription_profile_id' => $data ['subscr_id'],
                        'transaction_currency' => $data['mc_currency'],
                        'postpayment' => $data['transaction_details']
                    );
                    if($subscription->meta[$subsPrefix.'status'] == 'ORDER_PAGE'){
                        $planObject->paymentPending($subscription->ID, $transaction_data);
                    }

                    $returnResult['status'] = 200;
                    $returnResult['message'] = 'Subscription created successfully';
                    return $returnResult;
                }

                if ($data['txn_type'] == 'subscr_payment') {
                    /**
                     * Check if the current subscription record already has a successful transaction object within that date range
                     * if a new or second transaction record comes in, expire the active subscription and create a new subscription for this renewal
                     * associate the txn id with newly created record and activate the subscription
                     * */
                    $current_trans_id = $data['txn_id'];
                    // decide the appropriate subscription record to be processed
                    $next_subscription = $planObject->getNextRenewal( $subscription->ID , $current_trans_id);

                    if ( $next_subscription ) {
                        $subscription = Plans::loadSubscriber($next_subscription);
                        $subsPrefix = $subscription->ID.'_axisubs_subscribe_';
                    }

                }

                // Recurring or Non-recurring subscription Payment confirmation
                if ( in_array($data['txn_type'], array('web_accept', 'subscr_payment' ) ) ) {
                    // a subscription Payment has been done
                    if( !empty($subscription->ID) ) {
                        // check the Payment status
                        if (empty ( $data ['payment_status'] ) || ($data ['payment_status'] != 'Completed' && $data ['payment_status'] != 'Pending')) {
                            $errors [] = 'Invalid Status - '.$data ['payment_status'];
                            $this->_log($errors);
                        }

                        if ( $data['txn_type'] == 'subscr_payment' ) {
                            $gross = $subscription->meta[$subsPrefix.'price']; // TODO : Vefify this
                        }else{
                            $gross = $subscription->meta[$subsPrefix.'total_price']; // TODO : Vefify this
                        }

                        $mc_gross = floatval($data['mc_gross']);

                        //TODO: check the first time Payment and the setup fee with the gross amount
                        if ($mc_gross > 0)
                        {
                            // A positive value means "Payment". The prices MUST match!
                            // Important: NEVER, EVER compare two floating point values for equality.
                            $isValid = ($gross - $mc_gross) < 0.05;
                            if(!$isValid) {
                                $errors[] = 'Payment amount does not matches';
                                $this->_log($errors);
                            }
                        }
                        if(isset($data ['subscr_id'])){
                            $subscr_id = $data ['subscr_id'];
                        } else {
                            $subscr_id = '';
                        }
                        if(isset($subscription->meta[$subsPrefix.'postpayment'])){
                            $postpayment = $subscription->meta[$subsPrefix.'postpayment'].$data['transaction_details'];
                        } else {
                            $postpayment = $data['transaction_details'];
                        }
                        $transaction_data = array(
                            'payment_processor' => $this->_folder,
                            'transaction_ref_id' => $data ['txn_id'],
                            'subscription_profile_id' => $subscr_id,
                            'transaction_amount' => $mc_gross,
                            'transaction_currency' => $data['mc_currency'],
                            'prepayment' => "",
                            'postpayment' => $postpayment,
                            'authorize' => "",
                            'params' => "",
                            'processor_status' =>$data ['payment_status']
                        );

                        if (count ( $errors )) {
                            $planObject->paymentFailed($subscription->ID, $transaction_data);
                        }elseif (strtoupper($data ['payment_status']) == 'PENDING') {
                            $planObject->paymentPending($subscription->ID, $transaction_data);
                        }elseif(strtoupper($data ['payment_status']) == 'COMPLETED') {
                            $planObject->paymentCompleted($subscription->ID, $transaction_data);
                        }
                    }else{
                        $errors[] = 'Invalid subscription Id';
                        $this->_log($errors);
                    }
                }

                if (  !in_array($data['txn_type'], $known_txn_response_types )  ) {
                    // other methods not supported right now
                    $errors[] = "Invalid transaction type: ".$data['txn_type'];
                    $this->_log($errors);
                }
            }
        } else {
            $errors[] = "Invalid Request: ";
            $this->_log($errors);
        }

        if (count($errors) > 0) {
            $returnResult['status'] = 0;
            $returnResult['message'] = 'Thank you for payment. Your transaction is being processed.';
        } else {
            $returnResult['status'] = 200;
            $returnResult['message'] = 'Payment made successfully';
        }

        return $returnResult;
    }

    /**
     * Validates the IPN data
     *
     * @param array $data
     * @return string Empty string if data is valid and an error message otherwise
     * @access protected
     */
    function _validateIPN( $data, $order)
    {
        $paypal_url = $this->getPostURL($this->getConfigData('sandbox', 1));
        $request = 'cmd=_notify-validate';
        foreach ($data as $key => $value) {
            $request .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
            //$request .= '&' . $key . '=' . urlencode($value);
        }

        $curl = curl_init($paypal_url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);

        if (!$response) {
            $this->_log('CURL failed ' . curl_error($curl) . '(' . curl_errno($curl) . ')');
        }

        $this->_log('IPN Validation REQUEST: ' . $request);
        $this->_log('IPN Validation RESPONSE: ' . $response);

        if ((strcmp($response, 'VERIFIED') == 0 || strcmp($response, 'UNVERIFIED') == 0)) {
            return '';
        }elseif (strcmp ($response, 'INVALID') == 0) {
            return 'IPN Validation failed - invalid';
        }
        return '';
    }

    public function getPostURL($sandbox){
        $url = $sandbox ? 'www.sandbox.paypal.com' : 'www.paypal.com';
        $url = 'https://' . $url . '/cgi-bin/webscr';
        return $url;
    }

    /**
     * Simple logger OVERRIDEEN FOR TETS PURPOSES
     *
     * @param string $text
     * @param string $type
     * @return void
     */
    function _log($text, $type = 'message')
    {
        if (is_array($text) || is_object($text)) {
            $text = json_encode($text);
        }

        $isLog = $this->getConfigData('debug', 1);
        if ($isLog) {
            $file = AXISUBS_APP_PAYPAL_PLUGIN_PATH."logs/payment_log.txt";
            $date = date("Y-m-d H:i:s");
            if(is_writable($file)) {
                $f = fopen($file, 'a');
                fwrite($f, "\n\n" . $date);
                fwrite($f, "\n" . $type . ': ' . $text);
                fclose($f);
            }
        }
    }

    /**
     * Formatts the Payment data for storing
     *
     * @param array $data
     * @return string
     */
    function _getFormattedTransactionDetails( $data )
    {
        return json_encode($data);
    }

    /**
     * Cancel subscription from paypal
     * */
    public function cancelPaypalSubscription($subscription)
    {
        if(isset($subscription->ID)){
            $subscriptionPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
            $payment_processor = $subscription->meta[$subscriptionPrefix.'payment_processor'];
            if($payment_processor == $this->_folder){
                $subscription_profile_id = isset($subscription->meta[$subscriptionPrefix.'subscription_profile_id'])? $subscription->meta[$subscriptionPrefix.'subscription_profile_id'] : '';
                if($subscription_profile_id != ''){
                    $api = $this->hasAccessToCancel();
                    if(is_array($api)){
                        $result = $this->changePaypalSubscriptionStatus($subscription_profile_id, 'Cancel', $api);
                        $subscription = Post::where('post_type', 'axisubs_subscribe')->find((int)$subscription->ID);
                        if(!empty($subscription)){
                            $postpaymentKey = $subscriptionPrefix.'postpayment';
                            $oldPostpayment = $subscription->meta->$postpaymentKey;
                            if(is_array($result) || is_object($result)){
                                $result = json_encode($result);
                            }
                            $subscription->meta->$postpaymentKey = $oldPostpayment.$result;
                            $subscription->save();
                        }
                        $this->_log($result);
                    }
                }
            }
        }
    }

    /**
     * Performs an Express Checkout NVP API operation as passed in $action.
     *
     * Although the PayPal Standard API provides no facility for cancelling a subscription, the PayPal
     * Express Checkout  NVP API can be used.
     */
    protected function changePaypalSubscriptionStatus($profile_id, $action, $api) {
        $api_request = 'USER=' . urlencode($api['api_username'])
            .  '&PWD=' . urlencode($api['api_password'])
            .  '&SIGNATURE=' . urlencode($api['api_signature'])
            .  '&VERSION=76.0'
            .  '&METHOD=ManageRecurringPaymentsProfileStatus'
            .  '&PROFILEID=' . urlencode( $profile_id )
            .  '&ACTION=' . urlencode( $action )
            .  '&NOTE=' . urlencode(esc_html__('Profile cancelled at store', 'axis-subscriptions'));

        $ch = curl_init();
        if($api['api_sandbox']){
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp' ); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
        } else {
            curl_setopt( $ch, CURLOPT_URL, 'https://api-3t.paypal.com/nvp' );
        }
        curl_setopt( $ch, CURLOPT_VERBOSE, 1 );

        // Uncomment these to turn off server and peer verification
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );

        // Set the API parameters for this transaction
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $api_request );

        // Request response from PayPal
        $response = curl_exec( $ch );

        // If no response was received from PayPal there is no point parsing the response
        if( ! $response )
            die( 'Calling PayPal to change_subscription_status failed: ' . curl_error( $ch ) . '(' . curl_errno( $ch ) . ')' );

        curl_close( $ch );

        // An associative array is more usable than a parameter string
        parse_str( $response, $parsed_response );

        return $parsed_response;
    }

    /**
     * Check has access to cancel subscription
     * */
    public function hasAccessToCancel(){
        $sandbox = $this->getConfigData('sandbox', 1);
        if($sandbox){
            $api_username = $this->getConfigData('sandbox_api_username', '');
            $api_password = $this->getConfigData('sandbox_api_password', '');
            $api_signature = $this->getConfigData('sandbox_api_signature', '');
        } else {
            $api_username = $this->getConfigData('api_username', '');
            $api_password = $this->getConfigData('api_password', '');
            $api_signature = $this->getConfigData('api_signature', '');
        }
        if($api_username == '' || $api_password == '' || $api_signature == ''){
            return false;
        } else {
            $api['api_username'] = $api_username;
            $api['api_password'] = $api_password;
            $api['api_signature'] = $api_signature;
            $api['api_sandbox'] = $sandbox;
            return $api;
        }
    }
}