<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 12:52 PM
 */
namespace Axisubs\Controllers\Admin;

use Axisubs\Models\Admin\Config as ModelConfig;
use Herbert\Framework\Http;
use Herbert\Framework\Notifier;
use Axisubs\Controllers\Controller;
use Axisubs\Helper\Countries;
use Axisubs\Models\Site\Plans;

class Config extends Controller
{
    public $_controller = 'Config';

    /**
     * Default page
     * */
    public function index()
    {
        $all = ModelConfig::all();
        $configProvince = $configCountry = '';
        $selected_plans = array();
        if(!empty($all)){
            $configPrefix = $all->ID.'_'.$all->post_type.'_';
            $configProvince = $all->meta[$configPrefix.'region'];
            $configCountry =$all->meta[$configPrefix.'country'];
            if(isset($all->meta[$configPrefix.'plans_for_graph_report'])){
                $selected_plans = $all->meta[$configPrefix.'plans_for_graph_report'];
            } else {
                $selected_plans = '';
            }
            if(isset($selected_plans)){
                if(is_array($selected_plans)){
                    $selected_plans = $selected_plans;
                } else if($selected_plans != ''){
                    $selected_plans = explode(',', $selected_plans);
                }
            }
        }

        $plans = Plans::getItems();

        $modelZone = $this->getModel('Zones');
        $data['country'] = Countries::getCountriesSelectBox($configCountry, 'axisubs[config][country]', 'axisubs_config_country', 'required');
        $data['province'] = $modelZone->getProvinceSelectBox($configCountry, $configProvince, 'axisubs[config][region]', 'axisubs_config_region');
        $pagetitle = "Configuration";
        $site_url = get_site_url();
        return view('@Axisubs/Admin/config/edit.twig', compact('all', 'pagetitle', 'data', 'site_url', 'selected_plans', 'plans'));
    }

    /**
     * Save
     * */
    public function save()
    {
        $http = Http::capture();
        $axisubPost = $http->get('axisubs');
        if (isset($axisubPost['config'])) {
            $result = ModelConfig::saveConfig($http->all());
            if ($result) {
                Notifier::success('Saved successfully');
            } else {
                Notifier::error('Failed to save');
            }
        }
        return $this->index();
    }
}