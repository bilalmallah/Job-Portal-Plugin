<?php
/**
 * CareerHub - Elementor integration.
 *
 * Adds a "CareerHub" widget category and one widget per portal screen, so the
 * forms, job listing and candidate screens can be dropped into any Elementor
 * layout and styled from the panel instead of the shortcode.
 *
 * Nothing here loads unless Elementor is active: the widget classes extend
 * \Elementor\Widget_Base, so they are only required from inside Elementor's own
 * registration hook, by which point that class definitely exists.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimum Elementor that ships the register()/categories API used below.
 */
define('CWCP_ELEMENTOR_MIN_VERSION', '3.5.0');

function cwcp_elementor_is_active() {

    return did_action('elementor/loaded')
        && defined('ELEMENTOR_VERSION')
        && version_compare(ELEMENTOR_VERSION, CWCP_ELEMENTOR_MIN_VERSION, '>=');
}


/*
|--------------------------------------------------------------------------
| Widget Category
|--------------------------------------------------------------------------
*/

function cwcp_elementor_register_category($elements_manager) {

    $elements_manager->add_category(
        'careerhub',
        array(
            'title' => 'CareerHub',
            'icon'  => 'fa fa-briefcase',
        )
    );
}

add_action('elementor/elements/categories_registered', 'cwcp_elementor_register_category');


/*
|--------------------------------------------------------------------------
| Widgets
|--------------------------------------------------------------------------
*/

function cwcp_elementor_register_widgets($widgets_manager) {

    if (!cwcp_elementor_is_active()) {
        return;
    }

    require_once CWCP_PATH . 'includes/elementor/class-cwcp-elementor-widget.php';
    require_once CWCP_PATH . 'includes/elementor/widgets.php';

    foreach (cwcp_elementor_widget_classes() as $class) {

        if (!class_exists($class)) {
            continue;
        }

        $widgets_manager->register(new $class());
    }
}

add_action('elementor/widgets/register', 'cwcp_elementor_register_widgets');


/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
|
| The portal stylesheets are already enqueued site wide at priority 100; this
| one only neutralises the standalone page chrome when a screen is rendered
| inside an Elementor widget, so it must load after them.
|
*/

function cwcp_elementor_enqueue_assets() {

    if (!cwcp_elementor_is_active()) {
        return;
    }

    $css = CWCP_PATH . 'assets/css/elementor.css';

    if (!file_exists($css)) {
        return;
    }

    wp_enqueue_style(
        'cwcp-elementor',
        CWCP_URL . 'assets/css/elementor.css',
        array('cwcp-portal'),
        filemtime($css)
    );
}

add_action('wp_enqueue_scripts', 'cwcp_elementor_enqueue_assets', 120);
