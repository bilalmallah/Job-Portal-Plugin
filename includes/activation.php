<?php
/**
 * Care Wave Candidate Portal - Installation, database schema and upgrades.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CWCP_DB_VERSION', '1.0.3');


/*
|--------------------------------------------------------------------------
| Roles & Capabilities
|--------------------------------------------------------------------------
*/

function cwcp_register_roles() {

    if (!get_role('carewave_candidate')) {

        add_role(
            'carewave_candidate',
            'Care Wave Candidate',
            array(
                'read' => true,
            )
        );
    }

    /*
     * Administrators and editors manage the portal.
     */

    $admin = get_role('administrator');

    if ($admin && !$admin->has_cap('cwcp_manage_portal')) {
        $admin->add_cap('cwcp_manage_portal');
    }

    $editor = get_role('editor');

    if ($editor && !$editor->has_cap('cwcp_manage_portal')) {
        $editor->add_cap('cwcp_manage_portal');
    }
}

add_action('init', 'cwcp_register_roles', 5);


/*
|--------------------------------------------------------------------------
| Database Schema
|--------------------------------------------------------------------------
*/

function cwcp_install_tables() {

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();

    $applications = cwcp_table('applications');
    $education    = cwcp_table('education');
    $experience   = cwcp_table('experience');
    $skills       = cwcp_table('skills');
    $submissions  = cwcp_table('submissions');
    $saved_jobs   = cwcp_table('saved_jobs');

    $sql = array();

    $sql[] = "CREATE TABLE {$applications} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        job_id bigint(20) unsigned NOT NULL,
        status varchar(30) NOT NULL DEFAULT 'new',
        cover_note text NULL,
        resume_id bigint(20) unsigned NOT NULL DEFAULT 0,
        snapshot longtext NULL,
        admin_notes text NULL,
        applied_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        UNIQUE KEY user_job (user_id,job_id),
        KEY job_id (job_id),
        KEY status (status)
    ) {$charset};";

    $sql[] = "CREATE TABLE {$education} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        degree_level varchar(60) NOT NULL DEFAULT '',
        degree_title varchar(190) NOT NULL DEFAULT '',
        institute varchar(190) NOT NULL DEFAULT '',
        board_university varchar(190) NOT NULL DEFAULT '',
        passing_year varchar(10) NOT NULL DEFAULT '',
        obtained_marks varchar(20) NOT NULL DEFAULT '',
        total_marks varchar(20) NOT NULL DEFAULT '',
        grade varchar(30) NOT NULL DEFAULT '',
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) {$charset};";

    $sql[] = "CREATE TABLE {$experience} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        organization varchar(190) NOT NULL DEFAULT '',
        designation varchar(190) NOT NULL DEFAULT '',
        job_city varchar(120) NOT NULL DEFAULT '',
        start_date date NULL,
        end_date date NULL,
        currently_working tinyint(1) NOT NULL DEFAULT 0,
        responsibilities text NULL,
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) {$charset};";

    $sql[] = "CREATE TABLE {$skills} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        skill_name varchar(120) NOT NULL DEFAULT '',
        skill_level varchar(30) NOT NULL DEFAULT '',
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) {$charset};";

    $sql[] = "CREATE TABLE {$submissions} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        form_type varchar(40) NOT NULL DEFAULT '',
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        related_id bigint(20) unsigned NOT NULL DEFAULT 0,
        full_name varchar(190) NOT NULL DEFAULT '',
        email varchar(190) NOT NULL DEFAULT '',
        mobile varchar(30) NOT NULL DEFAULT '',
        data longtext NULL,
        attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
        status varchar(30) NOT NULL DEFAULT 'new',
        admin_notes text NULL,
        ip varchar(45) NOT NULL DEFAULT '',
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        KEY form_type (form_type),
        KEY related_id (related_id),
        KEY status (status)
    ) {$charset};";

    $sql[] = "CREATE TABLE {$saved_jobs} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        job_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (id),
        UNIQUE KEY user_job (user_id,job_id)
    ) {$charset};";

    foreach ($sql as $statement) {
        dbDelta($statement);
    }

    update_option('cwcp_db_version', CWCP_DB_VERSION);
}


/*
|--------------------------------------------------------------------------
| Portal Pages
|--------------------------------------------------------------------------
*/

function cwcp_install_pages() {

    $stored = get_option('cwcp_pages', array());

    if (!is_array($stored)) {
        $stored = array();
    }

    foreach (cwcp_page_map() as $key => $page) {

        /*
         * Keep an existing page if it is still there.
         */

        if (
            isset($stored[$key]) &&
            get_post($stored[$key]) &&
            'trash' !== get_post_status($stored[$key])
        ) {
            continue;
        }

        $existing = get_page_by_path($page['slug']);

        if ($existing) {

            $stored[$key] = (int) $existing->ID;

            continue;
        }

        $page_id = wp_insert_post(
            array(
                'post_title'     => $page['title'],
                'post_name'      => $page['slug'],
                'post_content'   => '[' . $page['shortcode'] . ']',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            )
        );

        if ($page_id && !is_wp_error($page_id)) {
            $stored[$key] = (int) $page_id;
        }
    }

    update_option('cwcp_pages', $stored);
}


/*
|--------------------------------------------------------------------------
| Default Job Categories
|--------------------------------------------------------------------------
*/

function cwcp_install_default_terms() {

    $categories = array(
        'Programme / Projects',
        'Health',
        'Education',
        'Monitoring & Evaluation',
        'Finance & Admin',
        'Human Resource',
        'Information Technology',
        'Communications',
        'Logistics & Procurement',
        'Community Mobilization',
    );

    foreach ($categories as $category) {

        if (!term_exists($category, 'cw_job_category')) {
            wp_insert_term($category, 'cw_job_category');
        }
    }

    $types = array(
        'Full Time',
        'Part Time',
        'Contract',
        'Internship',
        'Volunteer',
        'Consultancy',
    );

    foreach ($types as $type) {

        if (!term_exists($type, 'cw_job_type')) {
            wp_insert_term($type, 'cw_job_type');
        }
    }
}


/*
|--------------------------------------------------------------------------
| Uploads Protection
|--------------------------------------------------------------------------
|
| Resumes and tender documents live in their own uploads sub-folder that
| is not browsable through directory listing.
|
*/

function cwcp_protect_uploads() {

    $uploads = wp_upload_dir();

    if (empty($uploads['basedir'])) {
        return;
    }

    $dir = trailingslashit($uploads['basedir']) . 'carewave-documents';

    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }

    $index = trailingslashit($dir) . 'index.php';

    if (!file_exists($index)) {
        file_put_contents($index, "<?php\n// Silence is golden.\n");
    }

    $htaccess = trailingslashit($dir) . '.htaccess';

    if (!file_exists($htaccess)) {

        /*
         * Direct file access is denied on Apache. Documents are delivered by
         * cwcp_serve_document() after an ownership / capability check.
         */

        $rules = "Options -Indexes\n"
            . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";

        file_put_contents($htaccess, $rules); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }
}


/*
|--------------------------------------------------------------------------
| Activation / Deactivation
|--------------------------------------------------------------------------
*/

function cwcp_activate_plugin() {

    cwcp_register_roles();

    cwcp_install_tables();

    cwcp_register_job_post_type();
    cwcp_register_tender_post_type();
    cwcp_register_job_taxonomies();

    cwcp_install_default_terms();

    cwcp_install_pages();

    cwcp_protect_uploads();

    if (false === get_option('cwcp_settings')) {
        update_option('cwcp_settings', cwcp_default_settings());
    }

    flush_rewrite_rules();
}

function cwcp_deactivate_plugin() {

    /*
     * Candidate accounts, applications and submissions are intentionally
     * left untouched.
     */

    flush_rewrite_rules();
}


/*
|--------------------------------------------------------------------------
| Silent Upgrade
|--------------------------------------------------------------------------
|
| Runs when plugin files are updated without re-activating the plugin.
|
*/

function cwcp_maybe_upgrade() {

    if (get_option('cwcp_db_version') === CWCP_DB_VERSION) {
        return;
    }

    cwcp_install_tables();

    cwcp_install_pages();

    cwcp_protect_uploads();
}

add_action('admin_init', 'cwcp_maybe_upgrade');
