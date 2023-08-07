<?php
/*
 * Plugin Name: Edit Entries for Gravity Forms
 * Description: This plugin lets you edit the entries for the forms created with gravity
 * Author: Bright Plugins
 * Requires PHP: 7.2.0
 * Requires at least: 4.0
 * Tested up to: 6.1
 * Version: 0.1.6
 * Author URI: http://brightplugins.com/
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( class_exists( 'GFForms' ) ) {

	add_action( 'gform_entries_first_column_actions', 'bv_gravity_first_column_actions', 10, 4 );
	/**
	 * @param $form_id
	 * @param $field_id
	 * @param $value
	 * @param $entry
	 */
	function bv_gravity_first_column_actions( $form_id, $field_id, $value, $entry ) {

		$lead_id = $entry['id'];
		$id      = $entry['form_id'];
		echo "| <a href='admin.php?page=gf_entries&view=entry&id=" . esc_attr( $id ) . "&lid=" . esc_attr( $lead_id ) . "&order=ASC&filter&paged=1&pos=0&field_id&operator&edit=1'>Edit</a>";
	}
	/**
	 * Add javscript code into the entry details page
	 *
	 */
	add_action( 'admin_enqueue_scripts', 'bv_edit_entire_gravity_enqueue_admin_script' );

	function bv_edit_entire_gravity_enqueue_admin_script( $hook ) {

		if ( 'forms_page_gf_entries' != $hook ) {
			return;
		}
		if ( isset( $_REQUEST['edit'] ) && $_REQUEST['edit'] == 1 ) {
			wp_add_inline_script( 'gform_gravityforms', "
                        jQuery(document).ready( function () {
                            jQuery('#gform_edit_button').click();
                        } );
                     "
			);
			//document.getElementById("gform_edit_button").click();
		}
	}

}