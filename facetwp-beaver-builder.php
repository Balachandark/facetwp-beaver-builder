<?php
/*
Plugin Name: FacetWP - Beaver Builder
Description: FacetWP and Beaver Builder Integration
Version: 1.0
Author: FacetWP, LLC
Author URI: https://facetwp.com/
License: GPLv2 or later
*/

defined( 'ABSPATH' ) or exit;

// setup constants
define( 'FWPBB_PATH', plugin_dir_path( __FILE__ ) );
define( 'FWPBB_URL', plugin_dir_url( __FILE__ ) );
define( 'FWPBB_VER', '1.0' );
define( 'FWPBB_BASENAME', plugin_basename( __FILE__ ) );


add_action( 'init', function () {
	if ( class_exists( 'FLBuilderModule' ) ) {
		include_once FWPBB_PATH . 'modules/template/class-template.php';
		include_once FWPBB_PATH . 'modules/facet/class-facet.php';
		include_once FWPBB_PATH . 'modules/pager/class-pager.php';
		include_once FWPBB_PATH . 'modules/counts/class-counts.php';
		include_once FWPBB_PATH . 'modules/per-page/class-per-page.php';
		include_once FWPBB_PATH . 'modules/selections/class-selections.php';
		include_once FWPBB_PATH . 'modules/sort/class-sort.php';
	}
}, 30 );


add_filter( 'fl_builder_render_settings_field', function ( $field, $name ) {
	if ( 'data_source' === $name ) {
		$templates = FWP()->helper->get_templates();
		foreach ( $templates as $template ) {
			$field['options'][ 'fwp/' . $template['name'] ] = 'FacetWP: ' . $template['label'];
		}

	}

	return $field;
}, 10, 2 );

add_filter( 'fl_builder_loop_query', function ( $query, $settings ) {

	if ( 'fwp/' === substr( $settings->data_source, 0, 4 ) ) {
		$source    = substr( $settings->data_source, 4 );
		$templates = FWP()->helper->get_templates();
		foreach ( $templates as $template ) {
			if ( $template['name'] === $source ) {
				// remove UTF-8 non-breaking spaces.
				$query_args = preg_replace( "/\xC2\xA0/", ' ', $template['query'] );
				$query_args = (array) eval( '?>' . $query_args );

				return new WP_Query( $query_args );
			}
		}
	}

	return $query;
}, 10, 2 );