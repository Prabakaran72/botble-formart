<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 1:25 PM
 */

namespace Axisubs\Models\Admin;

use Corcel\Post;
use Herbert\Framework\Models\PostMeta;
use Herbert\Framework\Http;
use Axisubs\Helper\AxisubsRedirect;
use Illuminate\Database\Eloquent\Model;
use Axisubs\Helper\Config as HelperConfig;
use Axisubs\Helper\Tax;
use Axisubs\Helper\Common;

class Taxes extends Model
{
    protected $table = 'axisubs_taxrates';
    protected $primaryKey = 'axisubs_taxrate_id';

    protected $hidden = array('tax_rate_priority');
    /**
     * The table associated with the model.
     *
     * @var string
     */

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $_item = array();

    public static $post_type = 'axisubs_tax';

    public static $post_type_items = 'axisubs_taxes';

    public static $post_type_config = 'axisubs_tax_config';

    public static $_total;
    public static $_start;
    public static $_limit;


    public static function populateStates($post){
        if(isset($post['limitstart']) && $post['limitstart']){
            Taxes::$_start = $post['limitstart'];
        } else {
            Taxes::$_start = 0;
        }
        if(isset($post['limit']) && $post['limit']){
            Taxes::$_limit = $post['limit'];
        } else {
            Taxes::$_limit = 10;
        }
    }

    public static function getPaginationStartAndLimit($total = 0){
        Taxes::$_total = $total;
        $balance = Taxes::$_total-(Taxes::$_limit*Taxes::$_start);
        if($balance < Taxes::$_limit){
            $limit = $balance;
        } else {
            $limit = Taxes::$_limit;
        }

        // For getting start date
        $rem = $total % Taxes::$_limit;
        $totalPage = floor($total / Taxes::$_limit);
        if($rem>0){
            $totalPage++;
        }
        $result['start'] = Taxes::$_start;

        $result['start'] += 1;
        if($totalPage <= $result['start']){
            $result['start'] = 0;
        }

//        $result['start'] = Taxes::$_start;
        $result['limit'] = $limit;

        return $result;
    }

    /**
     * Get Taxes
     * */
    public function getTaxes(){
        $totalItem = $this->all();
        //get pagination start and limit
        $pageLimit = Taxes::getPaginationStartAndLimit(count($totalItem));
        //get limited data
        $items = $totalItem->forPage($pageLimit['start'], $pageLimit['limit']);
        return $items;
    }

    /**
     * Get single Tax
     * */
    public function getTax($id){
        $item = $this->all()->where('axisubs_taxrate_id', (int)$id)->first();
        return $item;
    }

    /**
     * Save a tax
     * */
    public function saveTax($post){
        if(isset($post['id']) && $post['id']){
            $model = $this->all()->where('axisubs_taxrate_id', (int)$post['id'])->first();
        } else {
            $model = $this->newInstance();
            $model->tax_rate_priority = 0;
            $model->tax_rate_shipping = 0;
            $model->tax_rate_compound = 0;
            $model->tax_rate_order = 0;
        }

        $model->tax_rate_class = "standard";
        foreach ($post['axisubs']['tax'] as $field => $value){
            $model->$field = $value;
        }

        $result = $model->save();
        if($result){
            return $model->axisubs_taxrate_id;
        } else {
            return false;
        }
    }

    /**
     * Delete Tax
     * */
    public function deleteTax($id){
        if($id){
            $model = $this->all()->where('axisubs_taxrate_id', (int)$id)->first();
            if(!empty($model)){
                return $model->delete();
            } else {
                AxisubsRedirect::redirect('?page=app-index&task=view&p=axisubs-app-taxes');
            }
        } else {
            return false;
        }
    }

    /**
     * Calculate tax
     * */
    public function calculateTaxTotals($item){
        if($item['additionalPrice']){
            $additionalPrice = $item['additionalPrice'];
        } else {
            $additionalPrice = array();
        }
        $tax_class = 'standard';
        $config = HelperConfig::getInstance();
        $enable_tax = $config->get('enable_tax', 0);
        $include_exclude_tax = $config->get('tax_type', 'excluding_tax');
        $enable_tax_info = $config->get('display_tax_info', 1);
        $line_price = $this->getMetaData($item, 'total_price');
        $line_price_org = $line_price;
        $original_price = $this->getMetaData($item, 'original_price');
        //Check tax is applicable
        if($enable_tax){
            $enable_tax = $this->checkTaxIsApplicable($item, $config);
        }
        // Checking tax is enabled
        if(!$enable_tax){
            $line_subtotal 		= $line_price;
            $line_subtotal_tax  = 0;
            $tax_details = '';
        } else if($include_exclude_tax == 'including_tax'){
            $additionalPrice['tax_type'] = 'including_tax';
            $line_price = $original_price;
            // include tax

            // Get base tax rates
            if ( empty( $shop_tax_rates[ $tax_class ] ) ) {
                $shop_tax_rates[ $tax_class ] = Tax::get_base_tax_rates( $tax_class );
            }

            // Get item tax rates
            if ( empty( $tax_rates[ $tax_class ] ) ) {
                $tax_rates[ $tax_class ] = Tax::get_rates( $tax_class );
            }

            $base_tax_rates = $shop_tax_rates[ $tax_class ];
            $item_tax_rates = $tax_rates[ $tax_class ];

            /**
             * ADJUST TAX - Calculations when base tax is not equal to the item tax.
             *
             * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
             * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
             * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
             */
            if ( $item_tax_rates !== $base_tax_rates ) {

                // Work out a new base price without the shop's base tax
                $taxes                 = Tax::calc_tax( $line_price, $base_tax_rates, true, true );

                // Now we have a new item price (excluding TAX)
                $line_subtotal         = $line_price - array_sum( $taxes );

                // Now add modified taxes
                $tax_result            = Tax::calc_tax( $line_subtotal, $item_tax_rates );

                //To display each tax price in front end
                foreach($tax_result as $k => $tax_rates){
                    $item_tax_rates[$k]['price'] = Common::roundPrice($tax_rates);
                }
                $tax_details = $item_tax_rates;

                $line_subtotal_tax     = array_sum( $tax_result );
                //$line_subtotal         = $line_price - $line_subtotal_tax;
                $line_subtotal         = $line_price_org - $line_subtotal_tax;

                /**
                 * Regular tax calculation (customer inside base and the tax class is unmodified.
                 */
            } else {

                // Calc tax normally
                $taxes                 = Tax::calc_tax( $line_price , $item_tax_rates, true );

                //To display each tax price in front end
                foreach($taxes as $k => $tax_rates){
                    $item_tax_rates[$k]['price'] = Common::roundPrice($tax_rates);
                }
                $tax_details = $item_tax_rates;

                $line_subtotal_tax     = array_sum( $taxes );
                //$line_subtotal         = $line_price - array_sum( $taxes );
                $line_subtotal         = $line_price_org - array_sum( $taxes );
            }
        } else {
            $additionalPrice['tax_type'] = 'excluding_tax';
            $line_price = $original_price;
            // exluding tax
            if ( ! empty( $tax_class ) ) {

            }
            $tax_rates[ $tax_class ]  = Tax::get_rates( $tax_class );
            $item_tax_rates        = $tax_rates[ $tax_class ];

            // Base tax for line before discount - we will store this in the order data
            $taxes                 = Tax::calc_tax( $line_price, $item_tax_rates );

            //To display each tax price in front end
            foreach($taxes as $k => $tax_rates){
                $item_tax_rates[$k]['price'] = Common::roundPrice($tax_rates);
            }
            $tax_details = $item_tax_rates;

            $line_subtotal_tax     = array_sum( $taxes );
            $line_subtotal         = $line_price_org;
        }

        // Add to main subtotal $original_price
        $subtotal        	= Common::roundPrice($line_subtotal) + Common::roundPrice($line_subtotal_tax);
        $subtotal_ex_tax 	= Common::roundPrice($line_subtotal);
        $tax 				= Common::roundPrice($line_subtotal_tax);
        $params = '';
        $params['tax_details'] = $tax_details;
        $additionalPrice['tax_total'] = Common::roundPrice($tax);
        $additionalPrice['total_excluding_tax'] = $subtotal_ex_tax;
        $additionalPrice['tax_details'] = json_encode($tax_details);
        $item['additionalPrice'] = $additionalPrice;
        if($enable_tax && $enable_tax_info){
            $item['tax_info_html'] = $this->getTaxInfoHtml($item);
        }
        $this->setMetaData($item, 'total_price', Common::roundPrice($subtotal));
    }

    /**
     * Calculate tax discount
     * */
    public function calculateTaxDiscounts($item){

        if($item['additionalPrice']){
            $additionalPrice = $item['additionalPrice'];
        } else {
            $additionalPrice = array();
        }
        $tax_class = 'standard';
        $config = HelperConfig::getInstance();
        $enable_tax = $config->get('enable_tax', 0);
        $include_exclude_tax = $config->get('tax_type', 'excluding_tax');
        $total_product_linePrice = $this->getMetaData($item, 'total_price');
        if(isset($item['additionalPrice']['discount']) && $item['additionalPrice']['discount'] > 0){
            $line_price = $item['additionalPrice']['discount'];
        } else {
            return ;
        }

        //Check tax is applicable
        if($enable_tax){
            $enable_tax = $this->checkTaxIsApplicable($item, $config);
        }

        if(!$enable_tax) {
            // just return an empty amount
            $line_subtotal 		= $line_price;
            $line_subtotal_tax  = 0;
            $subtotal = $total_product_linePrice;

        } else if($include_exclude_tax == 'including_tax'){
            // include tax

            // Get base tax rates
            if ( empty( $shop_tax_rates[ $tax_class ] ) ) {
                $shop_tax_rates[ $tax_class ] = Tax::get_base_tax_rates( $tax_class );
            }

            // Get item tax rates
            if ( empty( $tax_rates[ $tax_class ] ) ) {
                $tax_rates[ $tax_class ] = Tax::get_rates( $tax_class );
            }

            $base_tax_rates = $shop_tax_rates[ $tax_class ];
            $item_tax_rates = $tax_rates[ $tax_class ];

            /**
             * ADJUST TAX - Calculations when base tax is not equal to the item tax.
             *
             * The non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
             * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
             * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
             */
            if ( $item_tax_rates !== $base_tax_rates ) {

                // Work out a new base price without the shop's base tax
                $taxes                 = Tax::calc_tax( $line_price, $base_tax_rates, true, true );

                // Now we have a new item price (excluding TAX)
                $line_subtotal         = $line_price - array_sum( $taxes );

                // Now add modified taxes
                $tax_result            = Tax::calc_tax( $line_subtotal, $item_tax_rates );
                $line_subtotal_tax     = array_sum( $tax_result );
                $subtotal        	= $total_product_linePrice;
                /**
                 * Regular tax calculation (customer inside base and the tax class is unmodified.
                 */
            } else {

                // Calc tax normally
                $taxes                 = Tax::calc_tax( $line_price , $item_tax_rates, true );
                $line_subtotal_tax     = array_sum( $taxes );
                $line_subtotal         = $line_price - array_sum( $taxes );
                $subtotal        	= $total_product_linePrice;
            }

        } else {
            // exluding tax
            if ( ! empty( $tax_class ) ) {

            }
            $tax_rates[ $tax_class ]  = Tax::get_rates( $tax_class );
            $item_tax_rates        = $tax_rates[ $tax_class ];


            // Base tax for line before discount - we will store this in the order data
            $taxes                 = Tax::calc_tax( $line_price, $item_tax_rates );
            $line_subtotal_tax     = array_sum( $taxes );
            $line_subtotal         = $line_price;
            $subtotal        	= $total_product_linePrice + Common::roundPrice($line_subtotal_tax);
        }

        // Add to main subtotal

        $subtotal_ex_tax 	= $line_subtotal;
        $tax_total 			= $additionalPrice['tax_total']+Common::roundPrice($line_subtotal_tax);
        $additionalPrice['tax_total'] = Common::roundPrice($tax_total);
        $additionalPrice['tax_total_ex_discount_tax'] = Common::roundPrice($subtotal_ex_tax);
        $additionalPrice['discount_tax'] = Common::roundPrice($line_subtotal_tax);
        $item['additionalPrice'] = $additionalPrice;
        $this->setMetaData($item, 'total_price', Common::roundPrice($subtotal));
    }

    /**
     * get meta value
     * */
    protected function getMetaData($item, $field){
        if(isset($item->meta)){
            $data = $item->meta[$field];
        } else {
            $data = $item['meta'][$field];;
        }

        return $data;
    }

    /**
     * set meta value
     * */
    protected function setMetaData($item, $field, $value){
        if(isset($item->meta)){
            $metaArray = $item->meta;
            $metaArray[$field] = $value;
            $item->meta = $metaArray;
        } else {
            $metaArray = $item['meta'];
            $metaArray[$field] = $value;
            $item['meta'] = $metaArray;
        }
    }

    /**
     * check tax is applicable or not
     * */
    function checkTaxIsApplicable($object, $config){
        $main_config = $config;
        $enableTax = 1;
        $enable_eu_vat = $main_config->get('enable_eu_vat', 0);
        if($enable_eu_vat) {
            $no_tax_for_non_eu = $main_config->get('no_tax_for_non_eu', 0);
            $apply_digital_rules = $main_config->get('apply_digital_rules', 0);
            $model = $this->getModel();
            $eu_countries = $model->getEUCountries();
            $country = $main_config->get('country');

            $session = Session();
            $country_id = $session->get('customer_billing_country', $main_config->get('country','') );
            $vat_number = $session->get('customer_billing_vat_number', '' );
            $company = $session->get('customer_billing_company', '' );


            //Rule 1: Home country's individuals and businesses are charged tax
            //Rule 2: EU individuals and businesses with no valid VAT Number are charged tax
            if ($vat_number != '' && $country_id != '') {
                if (in_array($country_id, $eu_countries)) {
                    //Rule 3: EU (non-home country) individuals and businesses with VALID VAT are charged 0 tax
                    if ($country_id != $country) {
                        $validate_vat_number = $this->validateVatNumber($country_id, $vat_number);
                        if ($validate_vat_number) {
                            $enableTax = 0;
                        }
                    }

                }
            }

            // Sub Rule 1: EU individuals (non-home country ) are charged tax
            if ($apply_digital_rules) {
                if (isset($company)) {
                    if ($country_id == $country && $company == '') {
                        $enableTax = 0;
                    }
                } else {
                    if ($country_id == $country) {
                        $enableTax = 0;
                    }
                }
            }

            //Sub Rule 2: Non EU residents are charged 0 percent tax
            if ($no_tax_for_non_eu) {
                if (!in_array($country_id, $eu_countries)) {
                    $enableTax = 0;
                }
            }
        }

        return $enableTax;
    }

    /**
     * Validate Tax number
     * */
    public function validateTaxNumber(&$error, $post){
        $config = HelperConfig::getInstance();
        $enable_tax = $config->get('enable_tax', 0);
        $validate_vat_number = $config->get('validate_vat_number', 1);
        if($enable_tax && $validate_vat_number){
            $subscribe = $post['axisubs']['subscribe'];
            if(isset($subscribe['vat_number']) && $subscribe['vat_number'] != '' && isset($subscribe['country']) && $subscribe['country'] != ''){
                $eu_countries = $this->getEUCountries();
                if (in_array($subscribe['country'], $eu_countries)) {
                    $validate_vat_number = $this->validateVatNumber($subscribe['country'], $subscribe['vat_number']);
                    if (!$validate_vat_number) {
                        $error['status'] = 1;
                        $fields = $error['fields'];
                        $fields['vat_number'] = 'Invalid Tax Number';
                        $error['fields'] = $fields;
                    }
                }
            }
        }
    }

    /**
     * to get EU Countries
     * */
    public function getEUCountries() {

        return array(
            'AT' => 'AT', //Austria
            'BE' => 'BE', //Belgium
            'BG' => 'BG', //Bulgaria
            'CY' => 'CY', //Cyprus
            'CZ' => 'CZ', //Czech Republic
            'HR' => 'HR', //Croatia
            'DK' => 'DK', //Denmark
            'EE' => 'EE', //Estonia
            'FI' => 'FI', //Finland
            'FR' => 'FR', //France
            'FX' => 'FR', //France métropolitaine
            'DE' => 'DE', //Germany
            'GR' => 'EL', //Greece
            'HU' => 'HU', //Hungary
            'IE' => 'IE', //Irland
            'IT' => 'IT', //Italy
            'LV' => 'LV', //Latvia
            'LT' => 'LT', //Lithuania
            'LU' => 'LU', //Luxembourg
            'MT' => 'MT', //Malta
            'NL' => 'NL', //Netherlands
            'PL' => 'PL', //Poland
            'PT' => 'PT', //Portugal
            'RO' => 'RO', //Romania
            'SK' => 'SK', //Slovakia
            'SI' => 'SI',  //Slovania
            'ES' => 'ES', //Spain
            'SE' => 'SE', //Sweden
            'GB' => 'GB' //United Kingdom
        );
    }

    /**
     * Validate Vat Number
     * */
    public function validateVatNumber($country_code, $number){
        $org_number = $number;
        $number = str_replace($country_code, "", $org_number);
        $status = 0;
        if(!class_exists('SoapClient')) {
            require_once(AXISUBS_PLUGIN_PATH.'app/Helper/library/class.euvat.php');
            $vatValidation = new vatValidation( array('debug' => false));
            if($vatValidation->check($country_code, $number)) {
                $status = 1;
            } else {
                $status = 0;
            }
        } else {
            $response = file_get_contents('http://ec.europa.eu/taxation_customs/vies/viesquer.do?ms=' . $country_code . '&iso=' .$country_code. '&vat=' . $number);
            if (preg_match('/\bvalid VAT number\b/i', $response)) {
                $status = 1;
            }

            if (preg_match('/\binvalid VAT number\b/i', $response)) {
                $status = 0;
            }
        }

        return $status;
    }

    /**
     * Tax info
     * */
    public function getTaxInfoHtml($item){
        $taxPercent = 0;
        if(isset($item['additionalPrice']['tax_details'])){
            $taxdetails = json_decode($item['additionalPrice']['tax_details']);
            foreach ($taxdetails as $key => $taxdetail){
                $taxPercent = $taxPercent+$taxdetail->rate;
            }
        }
        $data['taxPercent'] = $taxPercent;
        $item['additionalPrice']['tax_details'];
        $dataHtml = view('@Axisubs/Site/tax/info.twig', compact('item', 'data'));
        if($dataHtml->getStatusCode() == '200'){
            return $dataHtml->getBody();
        } else {
            // echo "Something goes wrong";
        }
    }
}
