<?php
/*
Plugin Name: FacetWP - Beaver Builder
Description: FacetWP and Beaver Builder Integration
Version: 1.0.3
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-beaver-builder
*/

defined( 'ABSPATH' ) or exit;

// setup constants.
define( 'FWPBB_PATH', plugin_dir_path( __FILE__ ) );
define( 'FWPBB_URL', plugin_dir_url( __FILE__ ) );
define( 'FWPBB_VER', '1.0.3' );


class FacetWP_BB_Integration {

    private $grids;
    private $settings;
    private static $instance;


    function __construct() {

        add_action( 'init', array( $this, 'register_modules' ), 30 );
        add_action( 'fl_builder_loop_before_query', array( $this, 'store_module_settings' ) );
        add_action( 'fl_builder_before_render_module', array( $this, 'catch_grid') );
        add_action( 'wp_footer', array( $this, 'set_scripts' ) );

        add_filter( 'fl_builder_module_custom_class', array( $this, 'add_template_class' ), 10, 2 );
        add_filter( 'fl_builder_render_settings_field', array( $this, 'add_source' ), 10, 2 );
        add_filter( 'fl_builder_render_module_settings', array( $this, 'add_facetwp_toggle' ), 10, 2 );
        add_filter( 'fl_builder_loop_query_args', array( $this, 'loop_query_args' ) );
        add_filter( 'facetwp_is_main_query', array( $this, 'is_main_query' ), 10, 2 );
        add_filter( 'facetwp_load_assets', array( $this, 'load_assets' ) );
    }


    public static function init() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    function register_modules() {
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
     * Use the current query?
     */
    function is_main_query( $is_main_query, $query ) {
        if ( isset( $query->query_vars['facetwp'] ) ) {
            $is_main_query = (bool) $query->query_vars['facetwp'];
        }

        if ( 'fl-builder-template' == $query->get( 'post_type' ) ) {
            $is_main_query = false;
        }

        return $is_main_query;
    }


    /**
     * Load assets for BB builder preview
     */
    function load_assets( $load ) {
        return FLBuilderModel::is_builder_active() ? true : $load;
    }


    /**
     * Add FacetWP templates to the "data source" dropdown
     */
    function add_source( $field, $name ) {
        if ( 'data_source' === $name ) {
            $templates = FWP()->helper->get_templates();

            foreach ( $templates as $template ) {
                $field['options'][ 'fwp/' . $template['name'] ] = 'FacetWP: ' . $template['label'];
            }
        }

        return $field;
    }


    /**
     * Add a FacetWP toggle for post grid modules
     */
    function add_facetwp_toggle( $form, $instance ) {
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
     * Add the FacetWP template CSS class if needed
     */
    function add_template_class( $class, $module ) {
        if ( isset( $module->settings->facetwp ) && 'enable' === $module->settings->facetwp ) {
            $class .= ' facetwp-template facetwp-bb-module';
        }

        return $class;
    }


    /**
     * Override query arguments
     * Source: "custom_query" or "fwp/<template_name>"
     */
    function loop_query_args( $query_vars ) {

        // Exit if not the builder
        if ( empty( $query_vars['fl_builder_loop' ] ) ) {
            return $query_vars;
        }

        $is_enabled = isset( $this->settings->facetwp ) && 'enable' === $this->settings->facetwp;
        $source = isset( $this->settings->data_source ) ? $this->settings->data_source : '';
        $is_fwp_query = ( 0 === strpos( $source, 'fwp/' ) );

        if ( $is_enabled || $is_fwp_query ) {
            if ( $is_fwp_query ) {

                // Grab the template by name
                $template = FWP()->helper->get_template_by_name( substr( $source, 4 ) );

                if ( false !== $template ) {
                    $args = preg_replace( "/\xC2\xA0/", ' ', $template['query'] );
                    $args = (array) eval( '?>' . $args );
                    $query_vars = array_merge( $query_vars, $args );
                }
            }

            // Set paged and offset
            $prefix = FWP()->helper->get_setting( 'prefix', 'fwp_' );
            $paged = isset( $_GET[ $prefix . 'paged' ] ) ? (int) $_GET[ $prefix . 'paged' ] : 1;
            $per_page = isset( $query_vars['posts_per_page'] ) ? (int) $query_vars['posts_per_page'] : 10;
            $offset = ( 1 < $paged ) ? ( ( $paged - 1 ) * $per_page ) : 0;

            $GLOBALS['wp_the_query']->set( 'page', $paged );
            $GLOBALS['wp_the_query']->set( 'paged', $paged );
            $query_vars['paged'] = $paged;
            $query_vars['offset'] = $offset;

            if ( $is_enabled ) {
                $query_vars['facetwp'] = true;
            }
        }

        return $query_vars;
    }


    /**
     * Use this hook since the "fl_builder_loop_query_args" hook
     * doesn't pass the $settings object
     */
    function store_module_settings( $settings ) {
        $this->settings = $settings;
    }


    /**
     * If this is a FacetWP-enabled grid module, store some info
     */
    function catch_grid( $module ) {
        $settings = $module->settings;
        $id = $module->node;

        if ( isset( $settings->facetwp ) && 'enable' == $settings->facetwp ) {
            $this->grids[ $id ] = array(
                'id'          => $id,
                'layout'      => $settings->layout,
                'pagination'  => $settings->pagination,
                'postSpacing' => $settings->post_spacing,
                'postWidth'   => $settings->post_width,
                'matchHeight' => (int) $settings->match_height,
            );
        }
    }


    /**
     * Load assets
     */
    function set_scripts() {
        if ( ! empty( $this->grids ) ) {
            wp_enqueue_script( 'facetwp-bb', FWPBB_URL . 'js/front.js', array( 'jquery' ), FWPBB_VER );
            wp_localize_script( 'facetwp-bb', 'FWPBB', array(
                'post_id' => get_queried_object_id(),
                'modules' => $this->grids,
            ) );
        }
    }
}


FacetWP_BB_Integration::init();
