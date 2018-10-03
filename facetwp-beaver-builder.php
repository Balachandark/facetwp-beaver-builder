<?php
/*
Plugin Name: FacetWP - Beaver Builder
Description: FacetWP and Beaver Builder Integration
Version: 1.1.1
Author: FacetWP, LLC
Author URI: https://facetwp.com/
GitHub URI: facetwp/facetwp-beaver-builder
*/

defined( 'ABSPATH' ) or exit;

// setup constants.
define( 'FWPBB_PATH', plugin_dir_path( __FILE__ ) );
define( 'FWPBB_URL', plugin_dir_url( __FILE__ ) );
define( 'FWPBB_VER', '1.1.1' );


class FacetWP_BB_Integration {

    private $grids;
    private $settings;
    private static $instance;


    function __construct() {

        add_action( 'init', array( $this, 'register_modules' ), 30 );
        add_action( 'fl_builder_before_render_module', array( $this, 'catch_grid') );
        add_action( 'wp_footer', array( $this, 'set_scripts' ) );

        add_filter( 'fl_builder_register_settings_form', array( $this, 'add_facetwp_toggle' ), 10, 2 );
        add_filter( 'fl_builder_module_custom_class', array( $this, 'add_template_class' ), 10, 2 );
        add_filter( 'fl_builder_render_settings_field', array( $this, 'add_source' ), 10, 2 );
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
        if ( '' !== $query->get( 'facetwp' ) ) {
            $is_main_query = (bool) $query->get( 'facetwp' );
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
        if ( class_exists( 'FLBuilderModel' ) ) {
            return FLBuilderModel::is_builder_active() ? true : $load;
        }
        return $load;
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
    function add_facetwp_toggle( $form, $id ) {
        $supported = array( 'post-grid', 'pp-content-grid' );

        if ( in_array( $id, $supported ) ) {
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
    function loop_query_args( $args ) {

        // Exit if not the builder
        if ( empty( $args['fl_builder_loop' ] ) ) {
            return $args;
        }

        $settings = $args['settings'];

        $is_enabled = isset( $settings->facetwp ) && 'enable' === $settings->facetwp;
        $source = isset( $settings->data_source ) ? $settings->data_source : '';
        $is_fwp_query = ( 0 === strpos( $source, 'fwp/' ) );

        if ( $is_enabled || $is_fwp_query ) {
            if ( $is_fwp_query ) {

                // Grab the template by name
                $template = FWP()->helper->get_template_by_name( substr( $source, 4 ) );

                if ( false !== $template ) {
                    $args = preg_replace( "/\xC2\xA0/", ' ', $template['query'] );
                    $args = (array) eval( '?>' . $args );
                    $args = array_merge( $args, $args );
                }
            }

            // Set paged and offset
            $prefix = FWP()->helper->get_setting( 'prefix', 'fwp_' );
            $paged_var = isset( $_GET[ $prefix . 'paged' ] ) ? (int) $_GET[ $prefix . 'paged' ] : 1;
            $load_more_var = isset( $_GET[ $prefix . 'load_more' ] ) ? (int) $_GET[ $prefix . 'load_more' ] : false;

            $paged = $load_more_var ? $load_more_var : $paged_var;
            $per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 10;
            $offset = ( 1 < $paged ) ? ( ( $paged - 1 ) * $per_page ) : 0;

            $GLOBALS['wp_the_query']->set( 'page', $paged );
            $GLOBALS['wp_the_query']->set( 'paged', $paged );
            $args['paged'] = $paged;
            $args['offset'] = $offset;

            // Support "Load more"
            if ( isset( FWP()->ajax->is_preload ) && true === FWP()->ajax->is_preload && $load_more_var ) {
                $args['posts_per_page'] = $paged * $per_page;
                $args['offset'] = 0;
            }

            if ( $is_enabled ) {
                $args['facetwp'] = true;
            }
        }

        return $args;
    }


    /**
     * If this is a FacetWP-enabled grid module, store some info
     */
    function catch_grid( $module ) {
        $settings = $module->settings;
        $id = $module->node;

        //echo '<pre>';var_dump($module);echo '</pre>';

        if ( isset( $settings->facetwp ) && 'enable' == $settings->facetwp ) {
            if ( 'post-grid' == $module->slug ) {
                $options = array(
                    'id'            => $id,
                    'layout'        => $settings->layout,
                    'pagination'    => $settings->pagination,
                    'postSpacing'   => $settings->post_spacing,
                    'postWidth'     => $settings->post_width,
                    'matchHeight'   => (int) $settings->match_height,
                );
            }
            elseif ( 'pp-content-grid' == $module->slug ) {
                $options = array(
                    'id'            => $id,
                    'layout'        => $settings->layout,
                    'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                    'perPage'       => $settings->posts_per_page,
                    'fields'        => json_encode( $settings ),
                    'pagination'    => $settings->pagination,
                    'postSpacing'   => $settings->post_spacing,
                    'postColumns'   => $settings->post_grid_count,
                    'matchHeight'   => $settings->match_height,
                    'filters'       => false,
                );

                if ( 'grid' == $settings->layout && 'no' == $settings->match_height ) {
                    $options['masonry'] = 'yes';
                }
            }

            $options['type'] = $settings->type;
            $this->grids[ $id ] = $options;
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
