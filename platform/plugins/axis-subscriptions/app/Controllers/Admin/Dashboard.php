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
use Axisubs\Models\Admin\Config;
use Axisubs\Models\Admin\Subscriptions;
use Axisubs\Controllers\Controller;
use Axisubs\Models\Admin\App;
use Axisubs\Models\Admin\Dashboard as ModelDashboard;
use Axisubs\Helper\Status;
use Axisubs\Helper\Currency;

class Dashboard extends Controller
{
    public $_controller = 'Dashboard';

    /**
     * Default page
     * */
    public function index()
    {
        wp_enqueue_script('postbox');
        $config = Config::getInstance();
        $http = Http::capture();
        $pagetitle = 'Dashboard';
        $data['planCount'] = Plans::getTotal();
        $data['subscriptionCount'] = Subscriptions::getTotal();
        $data['customerCount'] = Customers::getTotal();
        $data['appCount'] = count(App::getAllApps());
        $data['last_five_subscription'] = ModelDashboard::getLastFiveSubscriptions();
        $status = new Status();
        $data['status_codes'] = $status->getAllStatusCodesWithHtml();
        $currency = new Currency();
        $currencyData['code'] = $currency->getCurrencyCode();
        $currencyData['currency'] = $currency->getCurrency();
        $data['last_year_statistics'] = ModelDashboard::getLastYearStatistics();
        $data['this_year_statistics'] = ModelDashboard::getThisYearStatistics();
        $data['last_month_statistics'] = ModelDashboard::getLastMonthStatistics();
        $data['this_month_statistics'] = ModelDashboard::getThisMonthStatistics();
        $data['last_7days_statistics'] = ModelDashboard::getLastSevenDaysStatistics();
        $data['yesterday_statistics'] = ModelDashboard::getYesterdayStatistics();
        $data['today_statistics'] = ModelDashboard::getTodayStatistics();
        $data['total_active_subscriptions'] = ModelDashboard::getTotalActiveSubscriptions();
        $data['plugin_url'] = AXISUBS_PLUGIN_URL;

        $data['last7days_subscriptions'] = ModelDashboard::getLastDaysSubscriptionByDate(7);
        $data['last30days_subscriptions'] = ModelDashboard::getLastDaysSubscriptionByDate(30);
        $data['graph_script'] = $this->processGraph($data['last7days_subscriptions'], $data['last30days_subscriptions']);
        return view('@Axisubs/Admin/dashboard/default.twig', compact('pagetitle', 'data', 'currencyData'));
    }

    public function processGraph($lastWeekData, $lastMonthData){
        $start_date_week = $lastWeekData->startDate;
        $end_date_week = $lastWeekData->endDate;
        $start_date_month = $lastMonthData->startDate;
        $end_date_month = $lastMonthData->endDate;

        $html = '';
        $html .= "if(axisubs_linechart_js == undefined) {
                google.charts.load('current', {'packages': ['corechart']});
                google.charts.setOnLoadCallback(drawChart);
        var axisubs_linechart_js = 1;
        } else {
        google.charts.setOnLoadCallback(drawChart);
        }
        
        function drawChart() {
        var data = google.visualization.arrayToDataTable([
        /*['Year', 'Sales', 'Expenses'],
        ['2004',  1000,      400],
        ['2005',  1170,      460],
        ['2006',  660,       1120],
        ['2007',  1030,      540]*/";
        $date = $start_date_week;
        $firstRow = "['Date'";
        foreach ($lastWeekData->plans as $plan){
            $firstRow .= ", '".$plan->plan_name."'";
        }
        $firstRow .= ']';
        $rows = '';
        while (strtotime($date) <= strtotime($end_date_week)) {
            $rows .= ",['".$date."'";
            $rows .= "";
            foreach ($lastWeekData->plans as $plan){
                if(isset($plan->subscriptions[$date])){
                    $rows .= ", ".$plan->subscriptions[$date]->count."";
                } else {
                    $rows .= ", 0";
                }
            }
            $rows .= ']';
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }
        $html .= $firstRow.$rows;
        $html .= "]);
        
            var options = {
            title: 'Weekly Sales Report',
            curveType: 'function',
            legend: { position: 'bottom' },
            pointSize: 5
            };
            
            var chart = new google.visualization.LineChart(document.getElementById('curve_chart_week'));
            
            chart.draw(data, options);
            
            /*************************** For Monthly Report **********************************************/
            var dataMonth = google.visualization.arrayToDataTable([
            /*['Year', 'Sales', 'Expenses'],
            ['2004',  1000,      400],
            ['2005',  1170,      460],
            ['2006',  660,       1120],
            ['2007',  1030,      540]*/";
        $date = $start_date_month;
        $firstRow = "['Date'";
        foreach ($lastMonthData->plans as $plan){
            $firstRow .= ", '".$plan->plan_name."'";
        }
        $firstRow .= ']';
        $rows = '';
        while (strtotime($date) <= strtotime($end_date_month)) {
            $rows .= ",['".$date."'";
            $rows .= "";
            foreach ($lastMonthData->plans as $plan){
                if(isset($plan->subscriptions[$date])){
                    $rows .= ", ".$plan->subscriptions[$date]->count."";
                } else {
                    $rows .= ", 0";
                }
            }
            $rows .= ']';
            $date = date ("Y-m-d", strtotime("+1 day", strtotime($date)));
        }
        $html .= $firstRow.$rows;
        $html .= "]);

        var optionsMonth = {
        title: 'Monthly Sales Report',
        curveType: 'function',
        legend: { position: 'bottom' },
        pointSize: 5
        };

        var chartMonth = new google.visualization.LineChart(document.getElementById('curve_chart_month'));

        chartMonth.draw(dataMonth, optionsMonth);    
            jQuery(document).ready(function(){
                jQuery('#tab1').addClass('tab-pane fade');
            });
        }        
        ";
        wp_enqueue_script( 'axisubs_dashboard_chart', \Axisubs\Helper::assetUrl('/js/chart.js') );
        wp_add_inline_script('axisubs_dashboard_chart', $html, 'after');
        return '';
    }
}