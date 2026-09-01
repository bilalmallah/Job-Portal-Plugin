<?php
/**
 * CareerHub - Jobs and Tenders post types.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Job Post Type
|--------------------------------------------------------------------------
*/

function cwcp_register_job_post_type() {

    $labels = array(
        'name'               => 'Jobs',
        'singular_name'      => 'Job',
        'add_new'            => 'Add Job',
        'add_new_item'       => 'Add New Job',
        'edit_item'          => 'Edit Job',
        'new_item'           => 'New Job',
        'view_item'          => 'View Job',
        'search_items'       => 'Search Jobs',
        'not_found'          => 'No jobs found',
        'not_found_in_trash' => 'No jobs found in trash',
        'all_items'          => 'All Jobs',
        'menu_name'          => 'Jobs',
    );

    register_post_type(
        'cw_job',
        array(
            'labels'          => $labels,
            'public'          => true,
            'show_ui'         => true,
            'show_in_menu'    => false, // Added under the CareerHub menu.
            'show_in_rest'    => true,
            'has_archive'     => 'jobs',
            'rewrite'         => array('slug' => 'job', 'with_front' => false),
            'menu_icon'       => 'dashicons-portfolio',
            'supports'        => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );
}

add_action('init', 'cwcp_register_job_post_type', 6);


/*
|--------------------------------------------------------------------------
| Tender / Donation Post Type
|--------------------------------------------------------------------------
*/

function cwcp_register_tender_post_type() {

    $labels = array(
        'name'          => 'Tenders',
        'singular_name' => 'Tender',
        'add_new'       => 'Add Tender',
        'add_new_item'  => 'Add New Tender',
        'edit_item'     => 'Edit Tender',
        'all_items'     => 'Tenders & Donations',
        'menu_name'     => 'Tenders',
    );

    register_post_type(
        'cw_tender',
        array(
            'labels'          => $labels,
            'public'          => true,
            'show_ui'         => true,
            'show_in_menu'    => false,
            'show_in_rest'    => true,
            'has_archive'     => 'tenders-list',
            'rewrite'         => array('slug' => 'tender', 'with_front' => false),
            'menu_icon'       => 'dashicons-media-document',
            'supports'        => array('title', 'editor', 'thumbnail'),
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );
}

add_action('init', 'cwcp_register_tender_post_type', 6);


/*
|--------------------------------------------------------------------------
| Taxonomies
|--------------------------------------------------------------------------
*/

function cwcp_register_job_taxonomies() {

    register_taxonomy(
        'cw_job_category',
        array('cw_job'),
        array(
            'labels'            => array(
                'name'          => 'Job Categories',
                'singular_name' => 'Job Category',
                'menu_name'     => 'Categories',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_menu'      => false,
            'rewrite'           => array('slug' => 'job-category'),
        )
    );

    register_taxonomy(
        'cw_job_type',
        array('cw_job'),
        array(
            'labels'            => array(
                'name'          => 'Job Types',
                'singular_name' => 'Job Type',
                'menu_name'     => 'Types',
            ),
            'hierarchical'      => true,
            'public'            => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_menu'      => false,
            'rewrite'           => array('slug' => 'job-type'),
        )
    );
}

add_action('init', 'cwcp_register_job_taxonomies', 7);


/*
|--------------------------------------------------------------------------
| Job Meta Fields
|--------------------------------------------------------------------------
*/

function cwcp_job_meta_fields() {

    return array(
        'cwcp_job_location'    => array('label' => 'Location / Duty Station', 'type' => 'text'),
        'cwcp_job_province'    => array('label' => 'Province',               'type' => 'select', 'options' => 'provinces'),
        'cwcp_job_positions'   => array('label' => 'No. of Positions',       'type' => 'number'),
        'cwcp_job_salary'      => array('label' => 'Salary / Package',       'type' => 'text'),
        'cwcp_job_experience'  => array('label' => 'Experience Required',    'type' => 'text'),
        'cwcp_job_education'   => array('label' => 'Education Required',     'type' => 'text'),
        'cwcp_job_gender'      => array('label' => 'Preferred Gender',       'type' => 'select', 'options' => 'gender_pref'),
        'cwcp_job_deadline'    => array('label' => 'Application Deadline',   'type' => 'date'),
        'cwcp_job_status'      => array('label' => 'Job Status',             'type' => 'select', 'options' => 'job_status'),
    );
}

function cwcp_job_meta_options($set) {

    switch ($set) {

        case 'provinces':
            return cwcp_provinces();

        case 'gender_pref':
            return array_merge(array('Any' => 'Any'), cwcp_genders());

        case 'job_status':
            return array(
                'open'   => 'Open (accepting applications)',
                'closed' => 'Closed',
            );
    }

    return array();
}

function cwcp_add_job_meta_boxes() {

    add_meta_box(
        'cwcp_job_details',
        'Job Details',
        'cwcp_render_job_meta_box',
        'cw_job',
        'normal',
        'high'
    );

    add_meta_box(
        'cwcp_job_applications',
        'Applications',
        'cwcp_render_job_applications_box',
        'cw_job',
        'side',
        'default'
    );

    add_meta_box(
        'cwcp_tender_details',
        'Tender Details',
        'cwcp_render_tender_meta_box',
        'cw_tender',
        'normal',
        'high'
    );
}

add_action('add_meta_boxes', 'cwcp_add_job_meta_boxes');

function cwcp_render_job_meta_box($post) {

    wp_nonce_field('cwcp_save_job_meta', 'cwcp_job_meta_nonce');

    echo '<table class="form-table cwcp-meta-table">';

    foreach (cwcp_job_meta_fields() as $key => $field) {

        $value = get_post_meta($post->ID, $key, true);

        if ('cwcp_job_status' === $key && '' === $value) {
            $value = 'open';
        }

        echo '<tr>';
        echo '<th><label for="' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th>';
        echo '<td>';

        if ('select' === $field['type']) {

            echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" class="regular-text">';
            echo cwcp_select_options(cwcp_job_meta_options($field['options']), $value, '-- Select --'); // phpcs:ignore WordPress.Security.EscapeOutput
            echo '</select>';

        } else {

            echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" '
                . 'value="' . esc_attr($value) . '" class="regular-text" />';
        }

        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';

    echo '<p class="description">The job description itself goes in the main content editor above. '
        . 'Categories and types are set in the boxes on the right.</p>';
}

function cwcp_render_tender_meta_box($post) {

    wp_nonce_field('cwcp_save_job_meta', 'cwcp_job_meta_nonce');

    $fields = array(
        'cwcp_tender_reference' => array('label' => 'Reference No.',   'type' => 'text'),
        'cwcp_tender_type'      => array('label' => 'Type',            'type' => 'select'),
        'cwcp_tender_deadline'  => array('label' => 'Closing Date',    'type' => 'date'),
        'cwcp_tender_amount'    => array('label' => 'Estimated Value / Target Amount', 'type' => 'text'),
        'cwcp_tender_document'  => array('label' => 'Document URL (optional)', 'type' => 'url'),
        'cwcp_tender_status'    => array('label' => 'Status',          'type' => 'select'),
    );

    echo '<table class="form-table cwcp-meta-table">';

    foreach ($fields as $key => $field) {

        $value = get_post_meta($post->ID, $key, true);

        echo '<tr>';
        echo '<th><label for="' . esc_attr($key) . '">' . esc_html($field['label']) . '</label></th>';
        echo '<td>';

        if ('cwcp_tender_type' === $key) {

            $options = array(
                'tender'   => 'Tender',
                'donation' => 'Donation Appeal',
            );

            echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($key) . '">';
            echo cwcp_select_options($options, $value ? $value : 'tender'); // phpcs:ignore WordPress.Security.EscapeOutput
            echo '</select>';

        } elseif ('cwcp_tender_status' === $key) {

            $options = array(
                'open'   => 'Open',
                'closed' => 'Closed',
            );

            echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($key) . '">';
            echo cwcp_select_options($options, $value ? $value : 'open'); // phpcs:ignore WordPress.Security.EscapeOutput
            echo '</select>';

        } else {

            echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" '
                . 'value="' . esc_attr($value) . '" class="regular-text" />';
        }

        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function cwcp_render_job_applications_box($post) {

    $count = cwcp_count_applications(array('job_id' => $post->ID));

    echo '<p><strong>' . esc_html($count) . '</strong> application(s) received.</p>';

    echo '<a class="button button-primary" href="'
        . esc_url(admin_url('admin.php?page=cwcp-applications&job_id=' . $post->ID))
        . '">View Applications</a>';
}

function cwcp_save_job_meta($post_id) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (
        !isset($_POST['cwcp_job_meta_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_job_meta_nonce'])), 'cwcp_save_job_meta')
    ) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $keys = array_keys(cwcp_job_meta_fields());

    $keys = array_merge(
        $keys,
        array(
            'cwcp_tender_reference',
            'cwcp_tender_type',
            'cwcp_tender_deadline',
            'cwcp_tender_amount',
            'cwcp_tender_document',
            'cwcp_tender_status',
        )
    );

    foreach ($keys as $key) {

        if (!isset($_POST[$key])) {
            continue;
        }

        $value = sanitize_text_field(wp_unslash($_POST[$key]));

        if ('cwcp_tender_document' === $key) {
            $value = esc_url_raw($value);
        }

        update_post_meta($post_id, $key, $value);
    }
}

add_action('save_post_cw_job', 'cwcp_save_job_meta');
add_action('save_post_cw_tender', 'cwcp_save_job_meta');


/*
|--------------------------------------------------------------------------
| Admin Columns
|--------------------------------------------------------------------------
*/

function cwcp_job_admin_columns($columns) {

    $new = array();

    foreach ($columns as $key => $label) {

        $new[$key] = $label;

        if ('title' === $key) {
            $new['cwcp_status']       = 'Status';
            $new['cwcp_deadline']     = 'Deadline';
            $new['cwcp_applications'] = 'Applications';
        }
    }

    return $new;
}

add_filter('manage_cw_job_posts_columns', 'cwcp_job_admin_columns');

function cwcp_job_admin_column_content($column, $post_id) {

    if ('cwcp_status' === $column) {

        $status = get_post_meta($post_id, 'cwcp_job_status', true);

        $is_open = cwcp_job_is_open($post_id);

        echo '<span class="cwcp-admin-pill ' . ($is_open ? 'is-open' : 'is-closed') . '">'
            . esc_html($is_open ? 'Open' : 'Closed')
            . '</span>';

        if ('closed' === $status) {
            echo ' <small>(manually closed)</small>';
        }
    }

    if ('cwcp_deadline' === $column) {

        $deadline = get_post_meta($post_id, 'cwcp_job_deadline', true);

        echo $deadline ? esc_html(cwcp_format_date($deadline)) : '&mdash;';
    }

    if ('cwcp_applications' === $column) {

        $count = cwcp_count_applications(array('job_id' => $post_id));

        echo '<a href="' . esc_url(admin_url('admin.php?page=cwcp-applications&job_id=' . $post_id)) . '">'
            . esc_html($count) . '</a>';
    }
}

add_action('manage_cw_job_posts_custom_column', 'cwcp_job_admin_column_content', 10, 2);

function cwcp_tender_admin_columns($columns) {

    $new = array();

    foreach ($columns as $key => $label) {

        $new[$key] = $label;

        if ('title' === $key) {
            $new['cwcp_tender_type']     = 'Type';
            $new['cwcp_tender_deadline'] = 'Closing Date';
            $new['cwcp_tender_subs']     = 'Submissions';
        }
    }

    return $new;
}

add_filter('manage_cw_tender_posts_columns', 'cwcp_tender_admin_columns');

function cwcp_tender_admin_column_content($column, $post_id) {

    if ('cwcp_tender_type' === $column) {

        $type = get_post_meta($post_id, 'cwcp_tender_type', true);

        echo esc_html('donation' === $type ? 'Donation Appeal' : 'Tender');
    }

    if ('cwcp_tender_deadline' === $column) {

        $deadline = get_post_meta($post_id, 'cwcp_tender_deadline', true);

        echo $deadline ? esc_html(cwcp_format_date($deadline)) : '&mdash;';
    }

    if ('cwcp_tender_subs' === $column) {

        $count = cwcp_count_submissions(array('form_type' => 'tender', 'related_id' => $post_id));

        echo '<a href="' . esc_url(admin_url('admin.php?page=cwcp-submissions&form_type=tender&related_id=' . $post_id)) . '">'
            . esc_html($count) . '</a>';
    }
}

add_action('manage_cw_tender_posts_custom_column', 'cwcp_tender_admin_column_content', 10, 2);


/*
|--------------------------------------------------------------------------
| Job Helpers
|--------------------------------------------------------------------------
*/

function cwcp_job_meta($job_id, $key, $fallback = '') {

    $value = get_post_meta($job_id, 'cwcp_job_' . $key, true);

    return '' === $value ? $fallback : $value;
}

/**
 * A job accepts applications while it is published, not manually closed and
 * the deadline (if any) has not passed.
 */
function cwcp_job_is_open($job_id) {

    if ('publish' !== get_post_status($job_id)) {
        return false;
    }

    if ('closed' === get_post_meta($job_id, 'cwcp_job_status', true)) {
        return false;
    }

    $deadline = get_post_meta($job_id, 'cwcp_job_deadline', true);

    if ($deadline && cwcp_is_valid_date($deadline)) {

        $end = strtotime($deadline . ' 23:59:59');

        if ($end && $end < current_time('timestamp')) {
            return false;
        }
    }

    return true;
}

function cwcp_tender_is_open($tender_id) {

    if ('publish' !== get_post_status($tender_id)) {
        return false;
    }

    if ('closed' === get_post_meta($tender_id, 'cwcp_tender_status', true)) {
        return false;
    }

    $deadline = get_post_meta($tender_id, 'cwcp_tender_deadline', true);

    if ($deadline && cwcp_is_valid_date($deadline)) {

        $end = strtotime($deadline . ' 23:59:59');

        if ($end && $end < current_time('timestamp')) {
            return false;
        }
    }

    return true;
}

function cwcp_job_terms_list($job_id, $taxonomy) {

    $terms = get_the_terms($job_id, $taxonomy);

    if (!$terms || is_wp_error($terms)) {
        return '';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

/**
 * Single job URL. Falls back to the jobs listing page with a job_id query arg
 * when the theme does not have a single template for the CPT.
 */
function cwcp_job_url($job_id) {

    return get_permalink($job_id);
}

function cwcp_job_apply_url($job_id) {

    return add_query_arg('job_id', (int) $job_id, cwcp_jobs_url());
}
