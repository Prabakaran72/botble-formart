<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 12:52 PM
 */
namespace Axisubs\Controllers\Admin;

use Axisubs\Models\Site\Plans;
use Herbert\Framework\Http;
use Herbert\Framework\Notifier;
use Axisubs\Helper\Pagination;
use Axisubs\Models\Admin\Customers;
use Axisubs\Controllers\Controller;
use Axisubs\Helper\Currency;
use Axisubs\Helper\PaymentPlugins;
use Axisubs\Helper\Duration;
use Axisubs\Helper\Config as HelperConfig;
use Axisubs\Models\Admin\Taxes;
use Axisubs\Helper\Countries;

class Tax extends Controller
{
    public $_controller = 'Tax';

    /**
     * Default page
     * */
    /**
     * Default layout
     * */
    public function index()
    {
        $pagetitle = "Taxes";
        $model = $this->getModel('Taxes');
        $http = Http::capture();
        Taxes::populateStates($http->all());
        $items = $model->getTaxes();
        $pagination = new Pagination(Taxes::$_start, Taxes::$_limit, Taxes::$_total);
        $paginationD['limitbox'] = $pagination->getLimitBox();
        $paginationD['links'] = $pagination->getPaginationLinks();
        $data['countries'] = Countries::getCountries();
        if(count($items)){
            $modelZone = $this->getModel('Zones');
            foreach ($items as $key => $item) {
                if(isset($item->tax_rate_state) && $item->tax_rate_state != ''){
                    $item->province_name = $modelZone->getProvinceName($item->tax_rate_state, $item->tax_rate_country);
                } else {
                    $item->province_name = '';
                }
            }
        }

        $htmlView = view('@Axisubs/Admin/Tax/list.twig', compact('pagetitle', 'items', 'paginationD', 'data'));
        if($htmlView->getStatusCode() == '200'){
            echo $htmlView->getBody();
        } else {
            echo "Something goes wrong";
        }
    }

    /**
     * Edit layout
     * */
    public function edit($id = 0){
        $pagetitle = "Add Tax";
        $http = Http::capture();
        $model = $this->getModel('Taxes');
        $item = array();
        $taxCountry = $taxProvince = '';
        if ($http->get('id') || $id) {
            if($http->get('id')){
                $id = $http->get('id');
            }
            $item = $model->getTax($id);
            if (!empty($item)) {
                $pagetitle = 'Edit Tax';
                $taxProvince = $item->tax_rate_state;
                $taxCountry = $item->tax_rate_country;
            }
        }
        $modelZone = $this->getModel('Zones');
        $data['country'] = Countries::getCountriesSelectBox($taxCountry, 'axisubs[tax][tax_rate_country]', 'axisubs_tax_tax_rate_country', 'required');
        $data['province'] = $modelZone->getProvinceSelectBox($taxCountry, $taxProvince, 'axisubs[tax][tax_rate_state]', 'axisubs_tax_tax_rate_state', '');
        $site_url = get_site_url();
        $data = view('@Axisubs/Admin/Tax/edit.twig', compact('pagetitle', 'item', 'site_url', 'data'));
        if($data->getStatusCode() == '200'){
            echo $data->getBody();
        } else {
            echo "Something goes wrong";
        }
    }

    /**
     * Save Tax
     * */
    public function save(){
        $http = Http::capture();
        $axisubPost = $http->get('axisubs');
        $item = array();
        if (isset($axisubPost['tax'])) {
            $model = $this->getModel('Taxes');
            $result = $model->saveTax($http->all());
            if ($result) {
                Notifier::success('Saved successfully');
                return $this->edit($result);
            }
        }
        Notifier::error('Failed to save');
        return $this->index();
    }

    /**
     * Delete Tax
     * */
    public function delete(){
        $http = Http::capture();
        if ($http->get('id')) {
            $model = $this->getModel('Taxes');
            $result = $model->deleteTax($http->get('id'));
            if ($result) {
                Notifier::success('Deleted successfully');
            } else {
                Notifier::error('Failed to delete');
            }
        }
        return $this->index();
    }

    /**
     * calculate tax
     * */
    public function calculateTax($item){
        $model = $this->getModel('Taxes');
        $model->calculateTaxTotals($item);
        $model-> calculateTaxDiscounts($item);
    }

    /**
     * To display Tax price list
     * */
    public function getTaxPriceList($plan, $additionalData, $subscription, $page){
        if(isset($plan['additionalPrice']['tax_total']) && $plan['additionalPrice']['tax_total'] > 0){
            $tax_details = $plan['additionalPrice']['tax_details'];

            if($tax_details != ''){
                $tax_details = (array)json_decode($tax_details);
                $currency = new Currency();
                $currencyData['code'] = $currency->getCurrencyCode();
                $currencyData['currency'] = $currency->getCurrency();
                $data['page'] = $page;
                $data['currencyData'] = $currencyData;
                $dataHtml = view('@Axisubs/Site/tax/list.twig', compact('tax_details', 'data', 'plan'));
                if($dataHtml->getStatusCode() == '200'){
                    return $dataHtml->getBody();
                } else {
                    // echo "Something goes wrong";
                }
            }
        } else {

        }
    }

    /**
     * To display Tax price list
     * */
    public function getTaxPriceListInSubscription($subscription, $additionalData, $admin){
        $subscriptionMeta = $subscription->meta;
        $subscriptionPrefix = $subscription->ID.'_'.$subscription->post_type.'_';
        if(isset($subscriptionMeta[$subscriptionPrefix.'tax_total'])) {
            if ($subscriptionMeta[$subscriptionPrefix . 'tax_total'] > 0) {
                $tax_details = (array)json_decode($subscriptionMeta[$subscriptionPrefix . 'tax_details']);
                $data['tax_details'] = $tax_details;
            }
        }
        $currency = new Currency();
        $currencyData['code'] = $currency->getCurrencyCode();
        $currencyData['currency'] = $currency->getCurrency();
        $data['currencyData'] = $currencyData;
        if($admin){
            $dataHtml = view('@Axisubs/Admin/Tax/subscribed_tax_price.twig', compact('subscription', 'data'));
        } else {
            $dataHtml = view('@Axisubs/Site/tax/subscribed_tax_price.twig', compact('subscription', 'data'));
        }
        if($dataHtml->getStatusCode() == '200'){
            return $dataHtml->getBody();
        } else {
            // echo "Something goes wrong";
        }
    }

    /**
     * For validating EU Tax
     * */
    public function validateSubscribeFormForTax(&$error, $post){
        $model = $this->getModel('Taxes');
        $model->validateTaxNumber($error, $post);
    }
}