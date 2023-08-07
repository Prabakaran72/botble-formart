<?php namespace Axisubs;

/** @var \Herbert\Framework\Panel $panel */
// For Main menu
$panel->add([
    'type'   => 'panel',
    'as'     => 'dashboard',
    'title'  => '<div style="padding: 0px 8px;">Axis Subscriptions</div>',
    'rename' => 'Dashboard',
    'slug'   => 'dashboard-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Dashboard@execute'
]);

// For Plans menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'plans',
    'title'  => 'Plans',
    'slug'   => 'plans-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Plan@execute'
]);

// For Subscriptions menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'subscriptions',
    'title'  => 'Subscriptions',
    'slug'   => 'subscriptions-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Subscription@execute'
]);

// For Subscriptions menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'customers',
    'title'  => 'Members List',
    'slug'   => 'customers-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Customer@execute'
]);

// For configuration menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'tax',
    'title'  => 'Tax',
    'slug'   => 'tax-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Tax@execute'
]);

// For configuration menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'configuration',
    'title'  => 'Configuration',
    'slug'   => 'config-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Config@execute'
]);

// For App menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'app',
    'title'  => 'Apps',
    'slug'   => 'app-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\App@execute'
]);

// For Short codes menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'shortcodes',
    'title'  => 'Shortcodes',
    'slug'   => 'shortcodes-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Support@shortCodes'
]);

// For Support menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'support',
    'title'  => 'Support',
    'slug'   => 'support-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\Support@execute'
]);

// For Support menu
$panel->add([
    'type'   => 'sub-panel',
    'parent' => 'dashboard',
    'as'     => 'installationwizard',
    'title'  => '',
    'slug'   => 'installationwizard-index',
    'uses'   => __NAMESPACE__ . '\Controllers\Admin\InstallationWizard@execute'
]);
