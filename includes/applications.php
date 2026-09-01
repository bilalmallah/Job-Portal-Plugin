<?php
/**
 * Care Wave Candidate Portal - Job applications (easy apply) and saved jobs.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Queries
|--------------------------------------------------------------------------
*/

function cwcp_has_applied($user_id, $job_id) {

    global $wpdb;

    $table = cwcp_table('applications');

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND job_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id,
            $job_id
        )
    );
}

function cwcp_get_application($id) {

    global $wpdb;

    $table = cwcp_table('applications');

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), // phpcs:ignore WordPress.DB.PreparedSQL
        ARRAY_A
    );
}

/**
 * Builds the WHERE clause shared by the list and count queries.
 */
function cwcp_applications_where($args) {

    global $wpdb;

    $where  = array('1=1');
    $params = array();

    if (!empty($args['user_id'])) {
        $where[]  = 'a.user_id = %d';
        $params[] = (int) $args['user_id'];
    }

    if (!empty($args['job_id'])) {
        $where[]  = 'a.job_id = %d';
        $params[] = (int) $args['job_id'];
    }

    if (!empty($args['status'])) {
        $where[]  = 'a.status = %s';
        $params[] = $args['status'];
    }

    if (!empty($args['search'])) {

        $like = '%' . $wpdb->esc_like($args['search']) . '%';

        $where[]  = '(u.display_name LIKE %s OR u.user_email LIKE %s OR p.post_title LIKE %s)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    return array(
        'sql'    => implode(' AND ', $where),
        'params' => $params,
    );
}

function cwcp_get_applications($args = array()) {

    global $wpdb;

    $args = wp_parse_args(
        $args,
        array(
            'user_id'  => 0,
            'job_id'   => 0,
            'status'   => '',
            'search'   => '',
            'per_page' => 20,
            'paged'    => 1,
            'orderby'  => 'applied_at',
            'order'    => 'DESC',
        )
    );

    $table = cwcp_table('applications');

    $where = cwcp_applications_where($args);

    $allowed_orderby = array('applied_at', 'status', 'id');

    $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'applied_at';
    $order   = 'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC';

    $per_page = max(1, (int) $args['per_page']);
    $offset   = max(0, ((int) $args['paged'] - 1) * $per_page);

    $sql = "SELECT a.*, u.display_name, u.user_email, p.post_title AS job_title
            FROM {$table} a
            LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
            LEFT JOIN {$wpdb->posts} p ON p.ID = a.job_id
            WHERE {$where['sql']}
            ORDER BY a.{$orderby} {$order}
            LIMIT %d OFFSET %d";

    $params = array_merge($where['params'], array($per_page, $offset));

    return $wpdb->get_results(
        $wpdb->prepare($sql, $params), // phpcs:ignore WordPress.DB.PreparedSQL
        ARRAY_A
    );
}

function cwcp_count_applications($args = array()) {

    global $wpdb;

    $args = wp_parse_args(
        $args,
        array(
            'user_id' => 0,
            'job_id'  => 0,
            'status'  => '',
            'search'  => '',
        )
    );

    $table = cwcp_table('applications');

    $where = cwcp_applications_where($args);

    $sql = "SELECT COUNT(a.id)
            FROM {$table} a
            LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
            LEFT JOIN {$wpdb->posts} p ON p.ID = a.job_id
            WHERE {$where['sql']}";

    if ($where['params']) {
        $sql = $wpdb->prepare($sql, $where['params']); // phpcs:ignore WordPress.DB.PreparedSQL
    }

    return (int) $wpdb->get_var($sql); // phpcs:ignore WordPress.DB.PreparedSQL
}

function cwcp_count_applications_by_status() {

    global $wpdb;

    $table = cwcp_table('applications');

    $rows = $wpdb->get_results("SELECT status, COUNT(id) AS total FROM {$table} GROUP BY status", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL

    $counts = array();

    foreach (cwcp_application_statuses() as $key => $label) {
        $counts[$key] = 0;
    }

    foreach ($rows as $row) {
        $counts[$row['status']] = (int) $row['total'];
    }

    return $counts;
}


/*
|--------------------------------------------------------------------------
| Profile Snapshot
|--------------------------------------------------------------------------
|
| The candidate profile as it was at the moment of applying is stored with
| the application, so later profile edits never rewrite history.
|
*/

function cwcp_build_profile_snapshot($user_id) {

    $profile = cwcp_get_profile($user_id);

    return array(
        'profile'    => $profile,
        'education'  => cwcp_get_education($user_id),
        'experience' => cwcp_get_experience($user_id),
        'skills'     => cwcp_get_skills($user_id),
        'experience_months' => cwcp_total_experience_months($user_id),
        'captured_at'       => current_time('mysql'),
    );
}

function cwcp_get_application_snapshot($application) {

    if (empty($application['snapshot'])) {
        return array();
    }

    $data = json_decode($application['snapshot'], true);

    return is_array($data) ? $data : array();
}


/*
|--------------------------------------------------------------------------
| Applying
|--------------------------------------------------------------------------
*/

/**
 * Runs every guard and creates the application.
 *
 * @return int|WP_Error Application ID on success.
 */
function cwcp_apply_to_job($user_id, $job_id, $cover_note = '') {

    global $wpdb;

    if (!$user_id) {
        return new WP_Error('cwcp_not_logged_in', 'Please log in to apply.');
    }

    $job = get_post($job_id);

    if (!$job || 'cw_job' !== $job->post_type) {
        return new WP_Error('cwcp_no_job', 'This job could not be found.');
    }

    if (!cwcp_job_is_open($job_id)) {
        return new WP_Error('cwcp_job_closed', 'This job is no longer accepting applications.');
    }

    if (!cwcp_is_profile_complete($user_id)) {

        $completeness = cwcp_profile_completeness($user_id);

        return new WP_Error(
            'cwcp_incomplete',
            'Your account is incomplete. Please add: ' . implode(', ', $completeness['missing_labels']) . '.'
        );
    }

    if (cwcp_has_applied($user_id, $job_id)) {
        return new WP_Error('cwcp_duplicate', 'You have already applied for this job.');
    }

    $table = cwcp_table('applications');

    $inserted = $wpdb->insert(
        $table,
        array(
            'user_id'    => $user_id,
            'job_id'     => $job_id,
            'status'     => 'new',
            'cover_note' => $cover_note,
            'resume_id'  => (int) get_user_meta($user_id, 'cwcp_resume_id', true),
            'snapshot'   => wp_json_encode(cwcp_build_profile_snapshot($user_id)),
            'applied_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
    );

    if (!$inserted) {
        return new WP_Error('cwcp_insert_failed', 'We could not submit your application. Please try again.');
    }

    $application_id = (int) $wpdb->insert_id;

    /* Applying supersedes a saved job. */
    cwcp_unsave_job($user_id, $job_id);

    do_action('cwcp_application_submitted', $application_id, $user_id, $job_id);

    return $application_id;
}

/**
 * Fetches an application only if it belongs to the given candidate.
 */
function cwcp_get_user_application($application_id, $user_id) {

    $application = cwcp_get_application($application_id);

    if (!$application || (int) $application['user_id'] !== (int) $user_id) {
        return null;
    }

    return $application;
}

/**
 * A candidate may edit an application until a decision has been made.
 */
function cwcp_application_is_editable($application) {

    if (!$application) {
        return false;
    }

    return !in_array($application['status'], array('hired', 'rejected'), true);
}

/**
 * Updates the candidate's own application: the message to the hiring team and,
 * optionally, a refresh of the attached profile, resume and records.
 */
function cwcp_update_application($application_id, $user_id, $cover_note, $refresh = false) {

    global $wpdb;

    $application = cwcp_get_user_application($application_id, $user_id);

    if (!$application) {
        return new WP_Error('cwcp_not_found', 'That application could not be found.');
    }

    if (!cwcp_application_is_editable($application)) {
        return new WP_Error('cwcp_decided', 'This application has already been decided and can no longer be changed.');
    }

    $data = array(
        'cover_note' => $cover_note,
        'updated_at' => current_time('mysql'),
    );

    if ($refresh) {

        if (!cwcp_is_profile_complete($user_id)) {

            $completeness = cwcp_profile_completeness($user_id);

            return new WP_Error(
                'cwcp_incomplete',
                'Your account is incomplete, so it cannot be re-attached. Please add: '
                . implode(', ', $completeness['missing_labels']) . '.'
            );
        }

        $data['resume_id'] = (int) get_user_meta($user_id, 'cwcp_resume_id', true);
        $data['snapshot']  = wp_json_encode(cwcp_build_profile_snapshot($user_id));
    }

    $wpdb->update(cwcp_table('applications'), $data, array('id' => (int) $application_id), null, array('%d'));

    do_action('cwcp_application_updated', $application_id, $user_id, $refresh);

    return true;
}

function cwcp_handle_edit_application() {

    if (!isset($_POST['cwcp_action']) || 'edit_application' !== sanitize_key(wp_unslash($_POST['cwcp_action']))) {
        return;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if (
        !isset($_POST['cwcp_edit_application_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_edit_application_nonce'])), 'cwcp_edit_application_' . $id)
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_applied_jobs_url());
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        cwcp_redirect(cwcp_login_url());
    }

    $cover_note = isset($_POST['cover_note']) ? sanitize_textarea_field(wp_unslash($_POST['cover_note'])) : '';
    $refresh    = !empty($_POST['refresh_profile']);

    $result = cwcp_update_application($id, $user_id, $cover_note, $refresh);

    if (is_wp_error($result)) {

        cwcp_add_notice($result->get_error_message(), 'error');

        if ('cwcp_incomplete' === $result->get_error_code()) {
            cwcp_redirect(cwcp_profile_url());
        }

        cwcp_redirect(add_query_arg('edit', $id, cwcp_applied_jobs_url()));
    }

    cwcp_add_notice(
        $refresh
            ? 'Application updated. Your latest profile and resume are now attached to it.'
            : 'Application updated.',
        'success'
    );

    cwcp_redirect(cwcp_applied_jobs_url());
}

add_action('template_redirect', 'cwcp_handle_edit_application', 5);

function cwcp_update_application_status($application_id, $status, $notes = null) {

    global $wpdb;

    $statuses = cwcp_application_statuses();

    if (!isset($statuses[$status])) {
        return false;
    }

    $application = cwcp_get_application($application_id);

    if (!$application) {
        return false;
    }

    $data = array(
        'status'     => $status,
        'updated_at' => current_time('mysql'),
    );

    if (null !== $notes) {
        $data['admin_notes'] = $notes;
    }

    $wpdb->update(cwcp_table('applications'), $data, array('id' => $application_id));

    if ($application['status'] !== $status) {
        do_action('cwcp_application_status_changed', $application_id, $status, $application['status']);
    }

    return true;
}

function cwcp_delete_application($application_id) {

    global $wpdb;

    return (bool) $wpdb->delete(cwcp_table('applications'), array('id' => $application_id), array('%d'));
}


/*
|--------------------------------------------------------------------------
| Easy Apply Handlers (POST + AJAX)
|--------------------------------------------------------------------------
*/

function cwcp_handle_apply() {

    if (!isset($_POST['cwcp_action']) || 'apply_job' !== sanitize_key(wp_unslash($_POST['cwcp_action']))) {
        return;
    }

    $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

    if (
        !isset($_POST['cwcp_apply_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_apply_nonce'])), 'cwcp_apply_' . $job_id)
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_jobs_url());
    }

    if (!is_user_logged_in()) {

        cwcp_redirect(
            add_query_arg('redirect_to', rawurlencode(get_permalink($job_id)), cwcp_login_url())
        );
    }

    $cover_note = isset($_POST['cover_note']) ? sanitize_textarea_field(wp_unslash($_POST['cover_note'])) : '';

    $result = cwcp_apply_to_job(get_current_user_id(), $job_id, $cover_note);

    if (is_wp_error($result)) {

        cwcp_add_notice($result->get_error_message(), 'error');

        if ('cwcp_incomplete' === $result->get_error_code()) {
            cwcp_redirect(cwcp_profile_url());
        }

        cwcp_redirect(get_permalink($job_id));
    }

    cwcp_add_notice(
        'Your application for <strong>' . esc_html(get_the_title($job_id)) . '</strong> has been submitted.',
        'success'
    );

    cwcp_redirect(cwcp_applied_jobs_url());
}

add_action('template_redirect', 'cwcp_handle_apply', 5);

function cwcp_ajax_apply() {

    check_ajax_referer('cwcp_portal', 'nonce');

    $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;

    if (!is_user_logged_in()) {

        wp_send_json_error(
            array(
                'message'  => 'Please log in to apply for this job.',
                'redirect' => add_query_arg('redirect_to', rawurlencode(get_permalink($job_id)), cwcp_login_url()),
            )
        );
    }

    $cover_note = isset($_POST['cover_note']) ? sanitize_textarea_field(wp_unslash($_POST['cover_note'])) : '';

    $result = cwcp_apply_to_job(get_current_user_id(), $job_id, $cover_note);

    if (is_wp_error($result)) {

        wp_send_json_error(
            array(
                'message'  => $result->get_error_message(),
                'redirect' => 'cwcp_incomplete' === $result->get_error_code() ? cwcp_profile_url() : '',
            )
        );
    }

    wp_send_json_success(
        array(
            'message' => 'Application submitted. Good luck!',
            'applied' => true,
        )
    );
}

add_action('wp_ajax_cwcp_apply', 'cwcp_ajax_apply');
add_action('wp_ajax_nopriv_cwcp_apply', 'cwcp_ajax_apply');


/*
|--------------------------------------------------------------------------
| Withdraw
|--------------------------------------------------------------------------
*/

function cwcp_handle_withdraw() {

    if (!isset($_REQUEST['cwcp_action']) || 'withdraw_application' !== sanitize_key(wp_unslash($_REQUEST['cwcp_action']))) {
        return;
    }

    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

    if (
        !isset($_REQUEST['_wpnonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'cwcp_withdraw_' . $id)
    ) {
        cwcp_add_notice('Security check failed.', 'error');
        cwcp_redirect(cwcp_applied_jobs_url());
    }

    $application = cwcp_get_application($id);

    if (!$application || (int) $application['user_id'] !== get_current_user_id()) {

        cwcp_add_notice('That application could not be found.', 'error');
        cwcp_redirect(cwcp_applied_jobs_url());
    }

    if (in_array($application['status'], array('hired', 'rejected'), true)) {

        cwcp_add_notice('This application has already been decided and cannot be withdrawn.', 'warning');
        cwcp_redirect(cwcp_applied_jobs_url());
    }

    cwcp_delete_application($id);

    cwcp_add_notice('Application withdrawn.', 'success');
    cwcp_redirect(cwcp_applied_jobs_url());
}

add_action('template_redirect', 'cwcp_handle_withdraw', 5);


/*
|--------------------------------------------------------------------------
| Saved Jobs
|--------------------------------------------------------------------------
*/

function cwcp_is_job_saved($user_id, $job_id) {

    global $wpdb;

    $table = cwcp_table('saved_jobs');

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND job_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id,
            $job_id
        )
    );
}

function cwcp_save_job($user_id, $job_id) {

    global $wpdb;

    if (cwcp_is_job_saved($user_id, $job_id)) {
        return true;
    }

    return (bool) $wpdb->insert(
        cwcp_table('saved_jobs'),
        array(
            'user_id'    => $user_id,
            'job_id'     => $job_id,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%d', '%s')
    );
}

function cwcp_unsave_job($user_id, $job_id) {

    global $wpdb;

    return (bool) $wpdb->delete(
        cwcp_table('saved_jobs'),
        array('user_id' => $user_id, 'job_id' => $job_id),
        array('%d', '%d')
    );
}

function cwcp_get_saved_jobs($user_id) {

    global $wpdb;

    $table = cwcp_table('saved_jobs');

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id
        ),
        ARRAY_A
    );
}

function cwcp_ajax_toggle_saved_job() {

    check_ajax_referer('cwcp_portal', 'nonce');

    if (!is_user_logged_in()) {

        wp_send_json_error(
            array(
                'message'  => 'Please log in to save jobs.',
                'redirect' => cwcp_login_url(),
            )
        );
    }

    $job_id  = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
    $user_id = get_current_user_id();

    if (!$job_id || 'cw_job' !== get_post_type($job_id)) {
        wp_send_json_error(array('message' => 'Invalid job.'));
    }

    if (cwcp_is_job_saved($user_id, $job_id)) {

        cwcp_unsave_job($user_id, $job_id);

        wp_send_json_success(array('saved' => false, 'message' => 'Removed from saved jobs.'));
    }

    cwcp_save_job($user_id, $job_id);

    wp_send_json_success(array('saved' => true, 'message' => 'Job saved.'));
}

add_action('wp_ajax_cwcp_toggle_saved_job', 'cwcp_ajax_toggle_saved_job');
