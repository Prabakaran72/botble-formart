<?php

namespace Axisubs\Hooks;


use Axisubs\Helper;

class Hooks {

	function __construct() {
		//add_action('save_post', array($this, 'save_post_intent'));
//		/add_action('transition_post_status', array($this, 'transition_intent'), 10, 3 );

		add_action('admin_enqueue_scripts', array($this, 'add_admin_scripts'));
	}


	public function add_admin_scripts($hook) {

		// we need to find ways to improve this loading.
		if(strpos($hook, 'dashboard-index') !== false) {
			$asset_url = Helper::assetUrl('js/chart.js');
			wp_enqueue_script('chartjs', $asset_url, [], false, 'header');
		}

	}

}

new Hooks();