<?php
/**
 * Plugin Name: CareerHub
 * Plugin URI: https://github.com/
 * Description: Complete job portal for WordPress - candidate registration and login, profiles with CNIC and district details, education, experience, skills and resume, one click job applications, plus volunteer, internship, field facilitator and tender forms with a full admin back office.
 * Version: 2.3.0
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * Author: Care Wave Foundation
 * Text Domain: careerhub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Plugin Constants
|--------------------------------------------------------------------------
*/

define('CWCP_VERSION', '2.3.0');
define('CWCP_FILE', __FILE__);
define('CWCP_PATH', plugin_dir_path(__FILE__));
define('CWCP_URL', plugin_dir_url(__FILE__));


/*
|--------------------------------------------------------------------------
| Load Modules
|--------------------------------------------------------------------------
|
| Order matters: helpers first, then data layer, then screens.
|
*/

$cwcp_modules = array(
    'helpers.php',
    'activation.php',
    'appearance.php',
    'cpt.php',
    'layout.php',
    'auth.php',
    'profile.php',
    'resume.php',
    'education.php',
    'experience.php',
    'skills.php',
    'applications.php',
    'jobs.php',
    'dashboard.php',
    'forms.php',
    'emails.php',
    'elementor.php',
);

foreach ($cwcp_modules as $cwcp_module) {

    $cwcp_file = CWCP_PATH . 'includes/' . $cwcp_module;

    if (file_exists($cwcp_file)) {
        require_once $cwcp_file;
    }
}

if (is_admin()) {

    $cwcp_admin_modules = array(
        'admin-menu.php',
        'admin-applications.php',
        'admin-candidates.php',
        'admin-submissions.php',
        'admin-settings.php',
        'admin-setup.php',
    );

    foreach ($cwcp_admin_modules as $cwcp_admin_module) {

        $cwcp_admin_file = CWCP_PATH . 'includes/admin/' . $cwcp_admin_module;

        if (file_exists($cwcp_admin_file)) {
            require_once $cwcp_admin_file;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Activation / Deactivation
|--------------------------------------------------------------------------
*/

register_activation_hook(__FILE__, 'cwcp_activate_plugin');
register_deactivation_hook(__FILE__, 'cwcp_deactivate_plugin');


/*
|--------------------------------------------------------------------------
| Frontend Assets
|--------------------------------------------------------------------------
*/

function cwcp_enqueue_assets() {

    wp_enqueue_style(
        'cwcp-font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        array(),
        '6.5.2'
    );

    $global_css = CWCP_PATH . 'assets/css/global.css';

    if (file_exists($global_css)) {

        wp_enqueue_style(
            'cwcp-global',
            CWCP_URL . 'assets/css/global.css',
            array('cwcp-font-awesome'),
            filemtime($global_css)
        );
    }

    $portal_css = CWCP_PATH . 'assets/css/portal.css';

    if (file_exists($portal_css)) {

        wp_enqueue_style(
            'cwcp-portal',
            CWCP_URL . 'assets/css/portal.css',
            array('cwcp-global'),
            filemtime($portal_css)
        );
    }

    $portal_js = CWCP_PATH . 'assets/js/portal.js';

    if (file_exists($portal_js)) {

        wp_enqueue_script(
            'cwcp-portal',
            CWCP_URL . 'assets/js/portal.js',
            array(),
            filemtime($portal_js),
            true
        );

        wp_localize_script(
            'cwcp-portal',
            'cwcpPortal',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('cwcp_portal'),
            )
        );
    }
}

/*
 * Priority 100: themes enqueue at the default priority, and a plugin's hooks
 * are registered first, so without this the theme's stylesheet would print
 * after the portal's and win every equal specificity match.
 */

add_action('wp_enqueue_scripts', 'cwcp_enqueue_assets', 100);


/*
|--------------------------------------------------------------------------
| Admin Assets
|--------------------------------------------------------------------------
*/

function cwcp_enqueue_admin_assets($hook) {

    $admin_css = CWCP_PATH . 'assets/css/admin.css';

    if (file_exists($admin_css)) {

        wp_enqueue_style(
            'cwcp-admin',
            CWCP_URL . 'assets/css/admin.css',
            array(),
            filemtime($admin_css)
        );
    }

    /*
     * The colour picker is only needed on the portal settings screen.
     */

    if (false === strpos((string) $hook, 'cwcp-settings')) {
        return;
    }

    wp_enqueue_style('wp-color-picker');

    $admin_js = CWCP_PATH . 'assets/js/admin.js';

    if (file_exists($admin_js)) {

        wp_enqueue_script(
            'cwcp-admin',
            CWCP_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            filemtime($admin_js),
            true
        );
    }
}

add_action('admin_enqueue_scripts', 'cwcp_enqueue_admin_assets');


/*
|--------------------------------------------------------------------------
| Plugin Action Links
|--------------------------------------------------------------------------
*/

function cwcp_plugin_action_links($links) {

    $custom = array(
        '<a href="' . esc_url(admin_url('admin.php?page=cwcp-overview')) . '">Portal</a>',
        '<a href="' . esc_url(admin_url('admin.php?page=cwcp-settings')) . '">Settings</a>',
    );

    return array_merge($custom, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cwcp_plugin_action_links');
