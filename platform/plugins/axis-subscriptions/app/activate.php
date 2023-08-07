<?php

/** @var  \Herbert\Framework\Application $container */
/** @var  \Herbert\Framework\Http $http */
/** @var  \Herbert\Framework\Router $router */
/** @var  \Herbert\Framework\Enqueue $enqueue */
/** @var  \Herbert\Framework\Panel $panel */
/** @var  \Herbert\Framework\Shortcode $shortcode */
/** @var  \Herbert\Framework\Widget $widget */

use Illuminate\Database\Capsule\Manager as Capsule;
use Axisubs\Models\Admin\Setup;

if (version_compare(phpversion(), '5.6.0', '<')) {
    axisub_trigger_error('This plugin support only in PHP v5.6.0 and higher.', E_USER_ERROR);
}

if (!extension_loaded('mbstring')) {
    axisub_trigger_error('It looks like the mbstring extension is not loaded. This plugin cannot work properly without this extension.', E_USER_ERROR);
}

function axisub_trigger_error($message, $errno) {
    if(isset($_GET['action'])
        && $_GET['action'] == 'error_scrape') {
        echo '<strong>' . $message . '</strong>';
        exit;
    } else {
        trigger_error($message, $errno);
    }
}

Capsule::schema()->dropIfExists('axisubs_zones');
Capsule::schema()->create('axisubs_zones', function($table)
{
    $table->increments('axisubs_zone_id');
    $table->string('country_code');
    $table->string('zone_code');
    $table->string('zone_name');
    $table->integer('enabled');
    $table->integer('ordering');
});

/**
 * For tax rates
 * */
if(!(Capsule::schema()->hasTable('axisubs_taxrates'))){
    Capsule::schema()->create('axisubs_taxrates', function($table)
    {
        $table->increments('axisubs_taxrate_id');
        $table->string('tax_rate_country');
        $table->string('tax_rate_state');
        $table->string('tax_rate');
        $table->string('tax_rate_name');
        $table->integer('tax_rate_priority');
        $table->integer('tax_rate_compound');
        $table->integer('tax_rate_shipping');
        $table->integer('tax_rate_order');
        $table->string('tax_rate_class');
    });
}

/**
 * For tax rates locations
 * */
if(!(Capsule::schema()->hasTable('axisubs_taxratelocations'))){
    Capsule::schema()->create('axisubs_taxratelocations', function($table)
    {
        $table->increments('axisubs_taxratelocation_id');
        $table->string('location_code');
        $table->integer('tax_rate_id');
        $table->string('location_type');
    });
}

//For adding zone data
axisubs_installTables();

//For activating additional plugins
Setup::installAdditionalPlugins();

function axisubs_installTables(){
    global $wpdb;
    $file = AXISUBS_PLUGIN_PATH."sql/zones.sql";
    $f = file_get_contents($file);
    $sql = str_replace("#__", $wpdb->prefix, $f);
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
