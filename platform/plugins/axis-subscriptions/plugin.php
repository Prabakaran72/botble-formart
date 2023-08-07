<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Axis Subscriptions
 * Plugin URI:        http://flycart.org/
 * Description:       A Simple Membership & Subscription Plugin.
 * Version:           1.4.3
 * Author:            Flycart
 * Author URI:        http://flycart.org/
 * Text Domain:       axis-subscriptions
 * License:           GPLv3
 */
use Events\Event;
use Axisubs\Helper\SubscriptionMails;
use Axisubs\Controllers\Controller;
//dd(get_option('rewrite_rules'));

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/getherbert/framework/bootstrap/autoload.php';

if (!defined('AXISUBS_PLUGIN_PATH')) {
    define( 'AXISUBS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if (!defined('AXISUBS_PLUGIN_URL')) {
    define( 'AXISUBS_PLUGIN_URL', plugins_url( '', __FILE__ ));
}

if ( is_plugin_active(plugin_basename( __FILE__ )) ) {
    $plugin = new \Herbert\Framework\Base\Plugin(plugin_dir_path( __FILE__ ));
    $herbert->registerPlugin($plugin);
}

add_action('plugins_loaded', 'axis_load_textdomain');
function axis_load_textdomain() {
    load_plugin_textdomain( 'axis-subscriptions', false, dirname( plugin_basename(__FILE__) ) . '/i18n/languages/' );
}

Event::listen('mailPaymentCanceled', 'sendMailPaymentCanceled', '1');
Event::listen('mailSubscriptionExpired', 'sendMailSubscriptionExpired', '1');
Event::listen('mailPaymentCompleted', 'sendMailPaymentCompleted', '1');
Event::listen('mailPaymentFailed', 'sendMailPaymentFailed', '1');
Event::listen('mailSubscriptionActive', 'sendMailSubscriptionActive', '1');
Event::listen('mailPaymentPending', 'sendMailPaymentPending', '1');

Event::listen('query_vars', 'axisubsRewriteQueryVars', '1', 'filter');
function axisubsRewriteQueryVars( $vars )
{
    $vars[] = 'task';
    $vars[] = 'id';
    $vars[] = 'sid';
    $vars[] = 'slug';
    return $vars;
}

//For login users
Event::listen('wp_ajax_axisubs_ajax', 'ajaxRequestAxisubs', '1');
Event::listen('wp_ajax_axisubs_ajax_admin', 'ajaxRequestAxisubsAdmin', '1');
//For non login user trigger
Event::listen('wp_ajax_nopriv_axisubs_ajax', 'ajaxRequestAxisubs', '1');


add_filter('plugin_row_meta',  'axisubs_plugins_links', 10, 2);
function axisubs_plugins_links ($links, $file) {
    $base = plugin_basename(__FILE__);
    if ($file == $base) {
        $links[] = '<a href="http://flycart.org/">Premium Support</a>';
    }
    return $links;
}

/**
 * For ajax site request
 * */
function ajaxRequestAxisubs(){
    $controller = new Controller();
    $controller->ajaxCallSite();
    wp_die();
}

/**
 * For ajax admin request
 * */
function ajaxRequestAxisubsAdmin(){
    $controller = new Controller();
    $controller->ajaxCall();
    wp_die();
}

/**
 * For Rewrite URL
 * */
Event::listen('init', 'rewriteAxisURL', '1');
function rewriteAxisURL(){
    $permalinkPlain = get_option('permalink_structure')? 0: 1;
    global $wp_rewrite;
    flush_rewrite_rules();

    $plans_structure = 'axisplan/%plan%';
    add_rewrite_tag("%plan%", '([^/]+)', "axisubs_plan=");
    add_permastruct('axisubs_single_plan', $plans_structure, false);

    add_rewrite_tag("%task%", '([^/]+)', "task=");
    add_rewrite_tag("%slug%", '([^/]+)', "slug=");
    add_rewrite_tag("%id%", '([^/]+)', "id=");

    $plan_structure = 'axisplan/%plan%/%task%/%slug%/%id%';
    add_permastruct('axisubs_single_plan', $plan_structure, false);

    $subscriptions_structure = 'axisubs/%subscribe%';
    add_rewrite_tag("%subscribe%", '([^/]+)', "axisubs_subscribes=");
    add_permastruct('axisubs_single_subscribe', $subscriptions_structure, false);

    add_rewrite_tag("%sid%", '([^/]+)', "sid=");

    $subscription_structure = 'axisubs/%subscribe%/%task%/%sid%';
    add_permastruct('axisubs_single_subscribe', $subscription_structure, false);
}

/**
 * sendMailPaymentCanceled
 * */
function sendMailPaymentCanceled($subscription_id){
    SubscriptionMails::subscriptionPaymentCanceled($subscription_id);
}

/**
 * sendMailSubscriptionExpired
 * */
function sendMailSubscriptionExpired($subscription_id){
    SubscriptionMails::subscriptionExpired($subscription_id);
}

/**
 * sendMailPaymentCompleted
 * */
function sendMailPaymentCompleted($subscription_id){
    SubscriptionMails::subscriptionPaymentCompleted($subscription_id);
}

/**
 * sendMailPaymentCompleted
 * */
function sendMailPaymentFailed($subscription_id){
    SubscriptionMails::subscriptionPaymentFailed($subscription_id);
}

/**
 * sendMailPaymentCompleted
 * */
function sendMailSubscriptionActive($subscription_id){
    SubscriptionMails::subscriptionActive($subscription_id);
}

/**
 * sendMailPaymentCompleted
 * */
function sendMailPaymentPending($subscription_id){
    SubscriptionMails::subscriptionPending($subscription_id);
}

function filter_single_plan_display($content)
{
    global $post;
    if ($post->post_type == 'axisubs_plan') {
        if ($post->ID) {
            echo do_shortcode("[AxisubsAllPlans post_id=" . $post->ID . "]");
            return;
        }
    }
    return $content;
}

function axisubs_single_plan_template($template)
{
    global $post;
    if ($post->post_type == 'axisubs_plan') {
        $template_name = 'axisubs-single-plan-template.php';

        // Set variable to search in axis-subscriptions folder of theme.
        $template_path = 'axis-subscriptions/templates/';

        // Set default plugin templates path.
        $default_path = plugin_dir_path( __FILE__ ) . 'templates/'; // Path to the template folder
        // Search template file in theme folder.
        $template = locate_template( array(
            $template_path . $template_name,
            $template_name
        ) );
        // Get plugins template file.
        if ( ! $template ) :
            $template = $default_path . $template_name;
        endif;
//        $single_template = plugin_dir_path(__FILE__) . 'templates/axisubs-single-plan-template.php';
    }

    return $template;
//    return $single_template;
}

function filter_single_subscribe_display($content)
{
    global $post;
    if ($post->post_type == 'axisubs_subscribes') {
        if ($post->ID) {
            echo do_shortcode("[AxisubsAllSubscriptions post_id=" . $post->ID . "]");
            return;
        }
    }
    return $content;
}

function axisubs_single_subscribe_template($template)
{
    global $post;
    if ($post->post_type == 'axisubs_subscribes') {
        $template_name = 'axisubs-single-subscribe-template.php';

        // Set variable to search in axis-subscriptions folder of theme.
        $template_path = 'axis-subscriptions/templates/';

        // Set default plugin templates path.
        $default_path = plugin_dir_path( __FILE__ ) . 'templates/'; // Path to the template folder
        // Search template file in theme folder.
        $template = locate_template( array(
            $template_path . $template_name,
            $template_name
        ) );
        // Get plugins template file.
        if ( ! $template ) :
            $template = $default_path . $template_name;
        endif;
    }
    return $template;
}

Event::listen('single_template', 'axisubs_single_plan_template', '', 'filter');
Event::listen('single_template', 'axisubs_single_subscribe_template', '', 'filter');
Event::listen('axisubs_single_plan', 'filter_single_plan_display', '');
Event::listen('axisubs_single_subscribe', 'filter_single_subscribe_display', '');
