<?php
/**
 * Created by PhpStorm.
 * User: aron-destiny
 * Date: 12/7/16
 * Time: 12:52 PM
 */
namespace Axisubs\Controllers\Admin;

use Axisubs\Helper\Common;
use Herbert\Framework\Http;
use Herbert\Framework\Notifier;
use Axisubs\Controllers\Controller;
use Axisubs\Models\Admin\Config as ModelConfig;
use Axisubs\Helper\Countries;

class InstallationWizard extends Controller{
    
    public $_controller = 'InstallationWizard';

    /**
     * Default page
     * */
    public function index()
    {
        $config = ModelConfig::all();
        $model = $this->getModel('InstallationWizard');
        $configProvince = $configCountry = '';
        if(!empty($config)){
            $configPrefix = $config->ID.'_'.$config->post_type.'_';
            $configProvince = $config->meta[$configPrefix.'region'];
            $configCountry =$config->meta[$configPrefix.'country'];
        }
        $modelZone = $this->getModel('Zones');
        $data['country'] = Countries::getCountriesSelectBox($configCountry, 'axisubs[config][country]', 'axisubs_config_country', 'required');
        $data['province'] = $modelZone->getProvinceSelectBox($configCountry, $configProvince, 'axisubs[config][region]', 'axisubs_config_region');
        $data['site_url'] = get_site_url();
        $data['plugin_url'] = AXISUBS_PLUGIN_URL;
        $data['plugin_details'] = get_plugin_data(AXISUBS_PLUGIN_PATH.'plugin.php');
        $paypalConfig = $model->getPaypalConfig();
        $pagetitle = "Installation Wizard";
        return view('@Axisubs/Admin/installationwizard/list.twig', compact('pagetitle', 'data', 'config', 'paypalConfig'));
    }
    
    /**
     * Save config through ajax
     * */
    public function saveConfig(){
        $http = Http::capture();
        $axisubPost = $http->get('axisubs');
        if (isset($axisubPost['config'])) {
            $result = ModelConfig::saveConfig($http->all());
            if ($result) {
                if(isset($axisubPost['config']['enable_tax'])){
                    Common::setInstallWizard(3);
                } else {
                    Common::setInstallWizard(1);
                }
                echo 1;
            } else {
                echo 0;
            }
        }
    }

    /**
     * Save config through ajax
     * */
    public function savePaypalConfig(){
        $http = Http::capture();
        $axisubPost = $http->get('axisubs');
        if (isset($axisubPost['payment'])) {
            $model = $this->getModel();
            $result = $model->savePaypalConfig($http->all());
            if ($result) {
                Common::setInstallWizard(2);
                echo 1;
            } else {
                echo 0;
            }
        }
    }

    /**
     * To set step through ajax
     * */
    public function updateInstallWizardStatus(){
        $http = Http::capture();
        $step = $http->get('step', 1);
        Common::setInstallWizard($step);
        echo 1;
    }
}