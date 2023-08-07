<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 12:52 PM
 */
namespace Axisubs\Controllers\Admin;

use Axisubs\Helper\Common;
use Axisubs\Helper\PlanTypes;
use Axisubs\Models\Site\Plans;
use Herbert\Framework\Http;
use Herbert\Framework\Notifier;
use Axisubs\Helper\Pagination;
use Axisubs\Models\Admin\Customers;
use Axisubs\Controllers\Controller;
use Axisubs\Helper\Currency;
use Axisubs\Helper\PaymentPlugins;
use Axisubs\Helper\Duration;
use Axisubs\Helper\Apps;

class Plan extends Controller
{
    public $_controller = 'Plan';

    /**
     * Default page
     * */
    public function index()
    {
        $currency = new Currency();
        $currencyData['code'] = $currency->getCurrencyCode();
        $currencyData['currency'] = $currency->getCurrency();
        $http = Http::capture();
        $pagetitle = 'Plans';
        Plans::populateStates($http->all());
        // Load Listing layout
        $items = Plans::getItems();
        $duration = new Duration();
        $unitInWords = $duration->getDurationInFormatInArray();

        //pre process the plan
        $data['additional_buttons'] = '';
        $data['unitInWords'] = $unitInWords;
        $data['filter_name'] = Plans::$_filter_name;
        $model = $this->getModel('Plans', 'Site');
        $model->preProcessBackendPlanListing($items, $data);
        $data['plan_types'] = PlanTypes::getPlanTypeInArray();
        $data['plugin_url'] = AXISUBS_PLUGIN_URL;
        $data['site_url'] = get_site_url();
        $pagination = new Pagination(Plans::$_start, Plans::$_limit, Plans::$_total);
        $data['total_items'] = Plans::$_total;
        $paginationD['limitbox'] = $pagination->getLimitBox();
        $paginationD['links'] = $pagination->getPaginationLinks();
        return view('@Axisubs/Admin/plans/list.twig', compact('pagetitle', 'items', 'paginationD', 'currencyData', 'data'));
    }

    /**
     * Delete Plan
     * */
    public function delete(){
        $http = Http::capture();
        if ($http->get('id')) {
            $result = Plans::deletePlan($http->get('id'));
            if ($result) {
                Notifier::success('Deleted successfully');
            } else {
                Notifier::error('Failed to delete');
            }
        }
        return $this->index();
    }

    /**
     * New / Edit Plan
     * */
    public function edit($id = 0){
        $item = array();
        $http = Http::capture();
        if(!$id){
            $id = $http->get('id');
        }
        $role_names = wp_roles()->role_names;
        $site_url = get_site_url();
        $pagetitle = 'Add new plan';
        $plan_description = '';
        if ($id) {
            $item = Plans::loadPlan($id);
            if (!empty($item)) {
                $pagetitle = 'Edit Plan';
                $planPrefix = $item->ID.'_'.$item->post_type.'_';
                if(isset($item->meta[$planPrefix.'description'])){
                    $plan_description = $item->meta[$planPrefix.'description'];
                }
                if(isset($item->meta[$planPrefix.'type'])){
                    $data['plan_type_text'] = PlanTypes::getPlanTypeText($item->meta[$planPrefix.'type']);
                }
            }
        }
        if($http->get('install')){
            Notifier::success('You have successfully configured Axis Subscriptions! Now you can create a Plan.');
        }
        wp_enqueue_media();
        $payment = new PaymentPlugins();
        $data['plan_description_editor'] = Common::getEditor('axisubs[plans][description]', 'axisub_plan_description', $plan_description);
        $data['plugin_url'] = AXISUBS_PLUGIN_URL;
        $data['payment_plugins'] = $payment->getAllPaymentApps();
        $model = $this->getModel('Plans', 'Site');
        $model->preProcessBackendPlanEdit($item, $data);
        return view('@Axisubs/Admin/plan/edit.twig', compact('pagetitle', 'item', 'role_names', 'site_url', 'data'));
    }

    /**
     * Save Plan
     * */
    public function save($close = 0){
        $http = Http::capture();
        $role_names = wp_roles()->role_names;
        $site_url = get_site_url();

        $axisubPost = $http->get('axisubs');
        if (isset($axisubPost['plans'])) {
            $result = Plans::savePlans($http->all());
            $pagetitle = 'Edit Plan';
            $data['plugin_url'] = AXISUBS_PLUGIN_URL;
            if ($result) {
                Notifier::success('Saved successfully');           
                $item = Plans::loadPlan($result);
                $apps = new Apps();
                $apps->loadHtml('afterSavePlan', array($item));
                if($close){
                    return $this->index();
                } else {
                    return $this->edit($result);
                }
            } else {
                $item = $axisubPost['plans'];
                Notifier::error('Failed to save');
                return view('@Axisubs/Admin/plan/edit.twig', compact('pagetitle', 'item', 'role_names', 'site_url'));
            }
        } else {
            return $this->index();
        }
    }

    /**
     * Load plan fields based on plan type through ajax
     * */
    public function loadPlanFields(){
        $http = Http::capture();
        $planType = $http->get('type', '');
        $id = $http->get('id');
        $item = Plans::loadPlan($id);
        if($planType != ''){
            $data = view('@Axisubs/Admin/plan/types/'.$planType.'.twig', compact('item'));
        } else {
            $data = view('@Axisubs/Admin/plan/types/free.twig', compact('item'));
        }
        if($data->getStatusCode() == 200){
            echo $data->getBody();
        } else {
            echo "Something goes wrong!";
        }
    }

    /**
     * change plan status through ajax
     * */
    public function updatePlanStatus(){
        $http = Http::capture();
        $planStatus = $http->get('status');
        $id = $http->get('id', 0);
        if($id){
            $key = 'status';
            $result = Plans::updatePlanStatus($id, $planStatus);
            echo $result;
        }
    }
}