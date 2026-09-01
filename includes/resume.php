<?php
/**
 * Care Wave Candidate Portal - Resume upload and document handling.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Allowed Document Types
|--------------------------------------------------------------------------
*/

function cwcp_allowed_document_mimes() {

    return array(
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    );
}

/**
 * Uploads go into their own folder so resumes are not mixed with media.
 */
function cwcp_document_upload_dir($dirs) {

    $dirs['subdir'] = '/carewave-documents';
    $dirs['path']   = $dirs['basedir'] . '/carewave-documents';
    $dirs['url']    = $dirs['baseurl'] . '/carewave-documents';

    return $dirs;
}

/**
 * Validates and stores an uploaded document, returning an attachment ID.
 *
 * @param string $field   Name of the file input.
 * @param int    $user_id Owner of the document (0 for guests).
 *
 * @return int|WP_Error
 */
function cwcp_store_document($field, $user_id = 0) {

    if (empty($_FILES[$field]) || !isset($_FILES[$field]['name']) || '' === $_FILES[$field]['name']) {
        return new WP_Error('cwcp_no_file', 'Please choose a file to upload.');
    }

    $file = $_FILES[$field]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

    if (!empty($file['error'])) {
        return new WP_Error('cwcp_upload_error', 'The file could not be uploaded. Please try again.');
    }

    $max_mb = (int) cwcp_setting('resume_max_size', 5);

    if ($max_mb < 1) {
        $max_mb = 5;
    }

    if ((int) $file['size'] > $max_mb * 1024 * 1024) {
        return new WP_Error('cwcp_too_big', 'The file is too large. Maximum allowed size is ' . $max_mb . ' MB.');
    }

    $allowed = cwcp_allowed_document_mimes();

    $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed);

    if (empty($check['ext']) || empty($check['type']) || !in_array($check['type'], $allowed, true)) {
        return new WP_Error('cwcp_bad_type', 'Only PDF, DOC and DOCX files are allowed.');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    add_filter('upload_dir', 'cwcp_document_upload_dir');

    $overrides = array(
        'test_form' => false,
        'mimes'     => $allowed,
    );

    $uploaded = wp_handle_upload($file, $overrides);

    remove_filter('upload_dir', 'cwcp_document_upload_dir');

    if (isset($uploaded['error'])) {
        return new WP_Error('cwcp_upload_failed', $uploaded['error']);
    }

    $attachment_id = wp_insert_attachment(
        array(
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name(basename($uploaded['file'])),
            'post_content'   => '',
            'post_status'    => 'private',
            'post_author'    => $user_id ? $user_id : 0,
        ),
        $uploaded['file']
    );

    if (is_wp_error($attachment_id) || !$attachment_id) {
        return new WP_Error('cwcp_attach_failed', 'The file could not be saved. Please try again.');
    }

    wp_update_attachment_metadata(
        $attachment_id,
        wp_generate_attachment_metadata($attachment_id, $uploaded['file'])
    );

    update_post_meta($attachment_id, '_cwcp_document', 1);

    if ($user_id) {
        update_post_meta($attachment_id, '_cwcp_owner', $user_id);
    }

    return (int) $attachment_id;
}


/*
|--------------------------------------------------------------------------
| Profile Photo
|--------------------------------------------------------------------------
|
| Unlike resumes, a profile photo has to be publicly readable, so it goes to
| the normal uploads folder rather than the protected documents folder.
|
*/

function cwcp_allowed_photo_mimes() {

    return array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
        'gif'          => 'image/gif',
    );
}

function cwcp_photo_max_mb() {

    return 2;
}

/**
 * Validates and stores an uploaded profile photo.
 *
 * @return int|WP_Error Attachment ID.
 */
function cwcp_store_photo($field, $user_id) {

    if (empty($_FILES[$field]) || !isset($_FILES[$field]['name']) || '' === $_FILES[$field]['name']) {
        return new WP_Error('cwcp_no_file', 'Please choose an image to upload.');
    }

    $file = $_FILES[$field]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

    if (!empty($file['error'])) {
        return new WP_Error('cwcp_upload_error', 'The photo could not be uploaded. Please try again.');
    }

    $max = cwcp_photo_max_mb();

    if ((int) $file['size'] > $max * 1024 * 1024) {
        return new WP_Error('cwcp_too_big', 'The photo is too large. Maximum allowed size is ' . $max . ' MB.');
    }

    $allowed = cwcp_allowed_photo_mimes();

    $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed);

    if (empty($check['ext']) || empty($check['type']) || !in_array($check['type'], $allowed, true)) {
        return new WP_Error('cwcp_bad_type', 'Only JPG, PNG, WEBP and GIF images are allowed.');
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $uploaded = wp_handle_upload($file, array('test_form' => false, 'mimes' => $allowed));

    if (isset($uploaded['error'])) {
        return new WP_Error('cwcp_upload_failed', $uploaded['error']);
    }

    $attachment_id = wp_insert_attachment(
        array(
            'post_mime_type' => $uploaded['type'],
            'post_title'     => sanitize_file_name(basename($uploaded['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => $user_id ? $user_id : 0,
        ),
        $uploaded['file']
    );

    if (is_wp_error($attachment_id) || !$attachment_id) {
        return new WP_Error('cwcp_attach_failed', 'The photo could not be saved. Please try again.');
    }

    wp_update_attachment_metadata(
        $attachment_id,
        wp_generate_attachment_metadata($attachment_id, $uploaded['file'])
    );

    update_post_meta($attachment_id, '_cwcp_photo', 1);
    update_post_meta($attachment_id, '_cwcp_owner', $user_id);

    return (int) $attachment_id;
}

function cwcp_get_photo_id($user_id) {

    $id = (int) get_user_meta($user_id, 'cwcp_photo_id', true);

    return ($id && get_post($id)) ? $id : 0;
}

function cwcp_get_photo_url($user_id, $size = 'thumbnail') {

    $id = cwcp_get_photo_id($user_id);

    if (!$id) {
        return '';
    }

    $url = wp_get_attachment_image_url($id, $size);

    return $url ? $url : '';
}

function cwcp_delete_photo($user_id) {

    $id = cwcp_get_photo_id($user_id);

    if ($id && (int) get_post_meta($id, '_cwcp_owner', true) === (int) $user_id) {
        wp_delete_attachment($id, true);
    }

    delete_user_meta($user_id, 'cwcp_photo_id');
}

/**
 * The uploaded photo becomes the user's avatar everywhere WordPress asks for
 * one - the portal sidebar, the admin candidate screens, comments.
 */
function cwcp_filter_avatar_data($args, $id_or_email) {

    $user_id = 0;

    if (is_numeric($id_or_email)) {

        $user_id = (int) $id_or_email;

    } elseif ($id_or_email instanceof WP_User) {

        $user_id = (int) $id_or_email->ID;

    } elseif ($id_or_email instanceof WP_Post) {

        $user_id = (int) $id_or_email->post_author;

    } elseif ($id_or_email instanceof WP_Comment) {

        $user_id = (int) $id_or_email->user_id;

    } elseif (is_string($id_or_email) && is_email($id_or_email)) {

        $user = get_user_by('email', $id_or_email);

        $user_id = $user ? (int) $user->ID : 0;
    }

    if (!$user_id) {
        return $args;
    }

    $size = isset($args['size']) ? (int) $args['size'] : 96;

    $url = cwcp_get_photo_url($user_id, $size > 150 ? 'medium' : 'thumbnail');

    if ($url) {

        $args['url']          = $url;
        $args['found_avatar'] = true;
    }

    return $args;
}

add_filter('pre_get_avatar_data', 'cwcp_filter_avatar_data', 10, 2);


/*
|--------------------------------------------------------------------------
| Secure Document Delivery
|--------------------------------------------------------------------------
|
| Resumes and tender documents are never linked to directly. Every link goes
| through this endpoint, which streams the file only to its owner or to a
| portal manager.
|
*/

function cwcp_document_url($attachment_id) {

    $attachment_id = (int) $attachment_id;

    if (!$attachment_id) {
        return '';
    }

    return add_query_arg('cwcp_doc', $attachment_id, home_url('/'));
}

function cwcp_can_read_document($attachment_id) {

    if (cwcp_can_manage()) {
        return true;
    }

    $owner = (int) get_post_meta($attachment_id, '_cwcp_owner', true);

    return $owner && get_current_user_id() === $owner;
}

function cwcp_serve_document() {

    if (!isset($_GET['cwcp_doc'])) {
        return;
    }

    $attachment_id = (int) $_GET['cwcp_doc'];

    $attachment = $attachment_id ? get_post($attachment_id) : null;

    if (!$attachment || 'attachment' !== $attachment->post_type) {
        wp_die('Document not found.', 'Not found', array('response' => 404));
    }

    if (!cwcp_can_read_document($attachment_id)) {
        wp_die('You do not have permission to view this document.', 'Access denied', array('response' => 403));
    }

    $path = get_attached_file($attachment_id);

    if (!$path || !file_exists($path)) {
        wp_die('The file is missing.', 'Not found', array('response' => 404));
    }

    $mime = get_post_mime_type($attachment_id);

    nocache_headers();

    header('Content-Type: ' . ($mime ? $mime : 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header(
        'Content-Disposition: ' . ('application/pdf' === $mime ? 'inline' : 'attachment')
        . '; filename="' . basename($path) . '"'
    );

    readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions

    exit;
}

add_action('template_redirect', 'cwcp_serve_document', 1);


/**
 * A resume that a submitted application points at is kept on file, so the
 * hiring team always sees the CV the candidate actually applied with.
 */
function cwcp_resume_in_use($attachment_id) {

    global $wpdb;

    $table = cwcp_table('applications');

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE resume_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL
            (int) $attachment_id
        )
    );
}


/*
|--------------------------------------------------------------------------
| Resume Actions
|--------------------------------------------------------------------------
*/

function cwcp_handle_resume_actions() {

    if (!isset($_POST['cwcp_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['cwcp_action']));

    if (!in_array($action, array('upload_resume', 'delete_resume'), true)) {
        return;
    }

    if (
        !isset($_POST['cwcp_resume_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_resume_nonce'])), 'cwcp_resume')
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_resume_url());
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        cwcp_redirect(cwcp_login_url());
    }

    if ('delete_resume' === $action) {

        $resume_id = (int) get_user_meta($user_id, 'cwcp_resume_id', true);

        if (
            $resume_id &&
            (int) get_post_meta($resume_id, '_cwcp_owner', true) === $user_id &&
            !cwcp_resume_in_use($resume_id)
        ) {
            wp_delete_attachment($resume_id, true);
        }

        delete_user_meta($user_id, 'cwcp_resume_id');

        cwcp_add_notice('Resume removed. Upload a new one to keep applying for jobs.', 'warning');
        cwcp_redirect(cwcp_resume_url());
    }

    $attachment_id = cwcp_store_document('resume', $user_id);

    if (is_wp_error($attachment_id)) {

        cwcp_add_notice($attachment_id->get_error_message(), 'error');
        cwcp_redirect(cwcp_resume_url());
    }

    /*
     * Remove the previous resume so old files do not pile up.
     */

    $previous = (int) get_user_meta($user_id, 'cwcp_resume_id', true);

    if ($previous && $previous !== $attachment_id && !cwcp_resume_in_use($previous)) {
        wp_delete_attachment($previous, true);
    }

    update_user_meta($user_id, 'cwcp_resume_id', $attachment_id);
    update_user_meta($user_id, 'cwcp_resume_updated', current_time('mysql'));

    cwcp_add_notice('Resume uploaded successfully.', 'success');
    cwcp_redirect(cwcp_resume_url());
}

add_action('template_redirect', 'cwcp_handle_resume_actions', 5);


/*
|--------------------------------------------------------------------------
| Resume Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_resume_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();

    $resume_id = (int) get_user_meta($user_id, 'cwcp_resume_id', true);

    $has_resume = $resume_id && get_post($resume_id);

    $max_mb = (int) cwcp_setting('resume_max_size', 5);

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'resume',
        'Resume',
        'Upload your latest CV. It is attached to every application you submit.'
    );
    ?>

    <div class="cwcp-card cwcp-pad cwcp-mb-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-file-lines"></i></span>
            <h2>Current Resume</h2>
        </div>

        <?php if ($has_resume) : ?>

            <div class="cwcp-file-row">

                <div class="cwcp-file-icon"><i class="fa-solid fa-file-pdf"></i></div>

                <div class="cwcp-file-meta">
                    <strong><?php echo esc_html(get_the_title($resume_id)); ?></strong>
                    <span class="cwcp-text-muted">
                        Uploaded <?php echo esc_html(cwcp_format_date(get_post_field('post_date', $resume_id))); ?>
                        &middot; <?php echo esc_html(size_format((int) filesize(get_attached_file($resume_id)))); ?>
                    </span>
                </div>

                <div class="cwcp-file-actions">
                    <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_document_url($resume_id)); ?>" target="_blank" rel="noopener">
                        <i class="fa-solid fa-eye"></i> View
                    </a>

                    <form method="post" class="cwcp-inline-form"
                          onsubmit="return confirm('Remove your resume? You will not be able to apply until you upload a new one.');">
                        <?php wp_nonce_field('cwcp_resume', 'cwcp_resume_nonce'); ?>
                        <input type="hidden" name="cwcp_action" value="delete_resume" />
                        <button type="submit" class="cwcp-btn-danger"><i class="fa-solid fa-trash"></i> Remove</button>
                    </form>
                </div>
            </div>

        <?php else : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
                <h3>No resume uploaded</h3>
                <p>Your account stays incomplete until a resume is uploaded.</p>
            </div>

        <?php endif; ?>

    </div>

    <div class="cwcp-card cwcp-pad">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-upload"></i></span>
            <h2><?php echo $has_resume ? 'Replace Resume' : 'Upload Resume'; ?></h2>
        </div>

        <form method="post" enctype="multipart/form-data" class="cwcp-form">

            <?php wp_nonce_field('cwcp_resume', 'cwcp_resume_nonce'); ?>
            <input type="hidden" name="cwcp_action" value="upload_resume" />

            <div class="cwcp-form-group">
                <label class="cwcp-form-label" for="cwcp-resume-file">Resume File <span class="cwcp-req">*</span></label>
                <input class="cwcp-form-input" type="file" id="cwcp-resume-file" name="resume"
                       accept=".pdf,.doc,.docx" required />
                <small class="cwcp-help">PDF, DOC or DOCX. Maximum <?php echo esc_html($max_mb); ?> MB.</small>
            </div>

            <button type="submit" class="cwcp-btn-primary">
                <i class="fa-solid fa-upload"></i> <?php echo $has_resume ? 'Replace Resume' : 'Upload Resume'; ?>
            </button>

        </form>
    </div>

    <?php

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_resume', 'cwcp_resume_shortcode');


/*
|--------------------------------------------------------------------------
| Keep Private Documents Private
|--------------------------------------------------------------------------
|
| Only the owner and portal managers may open a stored document through the
| attachment page.
|
*/

function cwcp_protect_document_attachment() {

    if (!is_attachment()) {
        return;
    }

    $post_id = get_queried_object_id();

    if (!get_post_meta($post_id, '_cwcp_document', true)) {
        return;
    }

    $owner = (int) get_post_meta($post_id, '_cwcp_owner', true);

    if (cwcp_can_manage() || ($owner && get_current_user_id() === $owner)) {
        return;
    }

    wp_die('You do not have permission to view this document.', 'Access denied', array('response' => 403));
}

add_action('template_redirect', 'cwcp_protect_document_attachment');
