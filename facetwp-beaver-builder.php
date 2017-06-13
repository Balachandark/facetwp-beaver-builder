<?php

/**
 * Plugin Name: FacetWP - Beaver Builder
 * Description: FacetWP and Beaver Builder Integration
 * Version: 1.0
 * Author: FacetWP, LLC
 * Author URI: https://facetwp.com/
 * License: GPLv2 or later
 **/

defined( 'ABSPATH' ) or exit;

// setup constants.
define( 'FWPBB_PATH', plugin_dir_path( __FILE__ ) );
define( 'FWPBB_URL', plugin_dir_url( __FILE__ ) );
define( 'FWPBB_VER', '1.0' );


/**
 * FacetWP_BB_Integration Main Class
 *
 * @package   fwpmanip
 * @author    David Cramer
 * @license   GPL-2.0+
 * @link
 * @copyright 2016 David Cramer
 */
class FacetWP_BB_Integration {

	/**
	 * Holds instance of the class
	 *
	 * @since   1.0.0
	 * @var     FacetWP_BB_Integration
	 */
	private static $instance;

	/**
	 * Holds the list of used grids.
	 *
	 * @since   1.0.0
	 * @var     array
	 */
	private $grids;

	/**
	 * FacetWP_BB_Integration constructor.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'fwp_bb_modules' ), 30 );
		add_filter( 'fl_builder_render_settings_field', array( $this, 'fwp_bb_add_source' ), 10, 2 );
		add_filter( 'fl_builder_render_module_settings', array( $this, 'fwp_bb_add_facetwp_enable' ), 10, 2 );
		add_filter( 'fl_builder_module_custom_class', array( $this, 'fwp_bb_add_template_class' ), 10, 2 );
		add_filter( 'fl_builder_loop_query', array( $this, 'fwp_bb_swapout_query_source' ), 10, 2 );
		add_filter( 'fl_builder_loop_query_args', array( $this, 'fwp_bb_correct_pager' ) );
		add_filter( 'fl_builder_render_js', array( $this, 'fwp_bb_inject_js' ), 100, 2 );
		add_action( 'wp_footer', array( $this, 'set_scripts' ) );
		add_filter( 'facetwp_load_assets', array( $this, 'is_builder_active' ) );
	}

	/**
	 * Ensure that FacetWP assets load in builder.
	 *
	 * @since 1.0.0
	 * @return  bool
	 */
	public function is_builder_active( $load ) {
		if ( FLBuilderModel::is_builder_active() ) {
			$load = true;
		}

		return $load;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 * @return  FacetWP_BB_Integration  A single instance
	 */
	public static function init() {

		// If the single instance hasn't been set, set it now.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Includes the custom FacetWP Modules.
	 */
	public function fwp_bb_modules() {
		$this->grids = array();
		if ( class_exists( 'FLBuilderModule' ) ) {
			include_once FWPBB_PATH . 'modules/template/class-template.php';
			include_once FWPBB_PATH . 'modules/facet/class-facet.php';
			include_once FWPBB_PATH . 'modules/pager/class-pager.php';
			include_once FWPBB_PATH . 'modules/counts/class-counts.php';
			include_once FWPBB_PATH . 'modules/per-page/class-per-page.php';
			include_once FWPBB_PATH . 'modules/selections/class-selections.php';
			include_once FWPBB_PATH . 'modules/sort/class-sort.php';
		}
	}


	/**
	 * Adds FacetWP as a data source for the Posts Module.
	 *
	 * @param array  $field Field array structure.
	 * @param string $name  The settings name.
	 *
	 * @return mixed
	 */
	public function fwp_bb_add_source( $field, $name ) {
		if ( 'data_source' === $name ) {
			$templates = FWP()->helper->get_templates();
			foreach ( $templates as $template ) {
				$field['options'][ 'fwp/' . $template['name'] ] = 'FacetWP: ' . $template['label'];
			}
		}

		return $field;
	}


	/**
	 * Adds FacetWP enable select box to the Posts Module.
	 *
	 * @param array           $form     The modules settings array.
	 * @param FLBuilderModule $instance The current module object.
	 *
	 * @return mixed
	 */
	public function fwp_bb_add_facetwp_enable( $form, $instance ) {
		if ( 'post-grid' === $instance->slug ) {

			$form['layout']['sections']['general']['fields']['facetwp'] = array(
				'type'    => 'select',
				'label'   => __( 'FacetWP', 'fl-builder' ),
				'default' => 'disable',
				'options' => array(
					'disable' => __( 'Disabled', 'fl-builder' ),
					'enable'  => __( 'Enable', 'fl-builder' ),
				),
			);
		}

		return $form;
	}


	/**
	 * Adds the facetwp-template class to the posts module frontend.
	 *
	 * @param string          $class  The custom class string.
	 * @param FLBuilderModule $module The current module object.
	 *
	 * @return string
	 */
	public function fwp_bb_add_template_class( $class, $module ) {
		if ( isset( $module->settings->facetwp ) && 'enable' === $module->settings->facetwp ) {
			$class .= ' facetwp-template facetwp-bb-module';
		}

		return $class;
	}


	/**
	 * Replaces the Query with the selected FacetWP template.
	 *
	 * @param \WP_Query $query    The current Query object.
	 * @param object    $settings The current modules settings object.
	 *
	 * @return \WP_Query
	 */
	public function fwp_bb_swapout_query_source( $query, $settings ) {
		if ( isset( $settings->data_source ) && 'fwp/' === substr( $settings->data_source, 0, 4 ) ) {
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
	}


	/**
	 * Replaces the Query with the selected FacetWP template.
	 *
	 * @param array $query_vars The current Query settings.
	 *
	 * @return array
	 */
	public function fwp_bb_correct_pager( $query_vars ) {

		if ( ! empty( $query_vars['fl_builder_loop'] ) ) {
			global $paged, $wp_the_query;
			if ( ! empty( $_GET['fwp_per_page'] ) ) {
				$query_vars['posts_per_page'] = $_GET['fwp_per_page'];
			}
			if ( ! empty( $_GET['fwp_paged'] ) ) {
				$paged = (int) $_GET['fwp_paged'];
			}

			if ( ! empty( $paged ) ) {
				$wp_the_query->set( 'page', $paged );
				$query_vars['paged'] = $paged;
				// Get the paged offset.
				$query_vars['offset'] = ( $query_vars['paged'] - 1 ) * $query_vars['posts_per_page'];
				//$query_vars['offset'] = 13;
			}
		}

		return $query_vars;
	}


	/**
	 * Adds the frontend handler scripts for the post module.
	 *
	 * @param string $js    The javascript cache string.
	 * @param array  $nodes The nodes and modules used.
	 *
	 * @return string
	 */
	public function fwp_bb_inject_js( $js, $nodes ) {
		foreach ( $nodes['modules'] as $module ) {
			if ( empty( $module->settings->facetwp ) || 'disable' === $module->settings->facetwp ) {
				continue;
			}
			if ( 'post-grid' === $module->slug ) {
				$this->catch_grid( $module );
			}
			// @todo add captures and support for other requested types.
			//if ( 'other-type' === $module->slug ) {
			//	$this->catch_type( $module );
			//}
		}

		return $js;
	}

	/**
	 * Capture settings for grid module.
	 *
	 * @param \FLBuilderModule $module The grid module object.
	 */
	public function catch_grid( $module ) {
		$settings      = $module->settings;
		$id            = $module->node;
		$this->grids[] = array(
			'id'          => $id,
			'layout'      => $settings->layout,
			'pagination'  => $settings->pagination,
			'postSpacing' => $settings->post_spacing,
			'postWidth'   => $settings->post_width,
			'matchHeight' => (int) $settings->match_height,
		);
	}

	/**
	 * Enqueue and set script configs.
	 */
	public function set_scripts() {
		if ( ! empty( $this->grids ) ) {
			wp_enqueue_script( 'facetwp-bb', FWPBB_URL . 'js/facetwp-bb-frontend.min.js', array( 'jquery' ), FWPBB_VER );
			wp_localize_script( 'facetwp-bb', 'FWPBB', array(
				'post_id' => get_queried_object_id(),
				'modules' => $this->grids,
			) );
		}
	}

}

// init plugin.
FacetWP_BB_Integration::init();
