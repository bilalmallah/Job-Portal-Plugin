<?php
/**
 * Care Wave Candidate Portal - Work experience history.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Data Access
|--------------------------------------------------------------------------
*/

function cwcp_get_experience($user_id) {

    global $wpdb;

    $table = cwcp_table('experience');

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY currently_working DESC, start_date DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id
        ),
        ARRAY_A
    );
}

function cwcp_get_experience_row($id, $user_id = 0) {

    global $wpdb;

    $table = cwcp_table('experience');

    if ($user_id) {

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $id, $user_id), // phpcs:ignore WordPress.DB.PreparedSQL
            ARRAY_A
        );
    }

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), // phpcs:ignore WordPress.DB.PreparedSQL
        ARRAY_A
    );
}

/**
 * Total experience in months, used on the dashboard and in the admin view.
 */
function cwcp_total_experience_months($user_id) {

    $months = 0;

    foreach (cwcp_get_experience($user_id) as $row) {

        if (empty($row['start_date'])) {
            continue;
        }

        $start = strtotime($row['start_date']);

        $end = !empty($row['currently_working']) || empty($row['end_date'])
            ? current_time('timestamp')
            : strtotime($row['end_date']);

        if (!$start || !$end || $end < $start) {
            continue;
        }

        $months += max(0, (int) round(($end - $start) / (30.44 * DAY_IN_SECONDS)));
    }

    return $months;
}

function cwcp_format_experience($months) {

    if ($months < 1) {
        return 'Fresh';
    }

    $years     = (int) floor($months / 12);
    $remainder = $months % 12;

    $parts = array();

    if ($years) {
        $parts[] = $years . ' year' . (1 === $years ? '' : 's');
    }

    if ($remainder) {
        $parts[] = $remainder . ' month' . (1 === $remainder ? '' : 's');
    }

    return implode(' ', $parts);
}


/*
|--------------------------------------------------------------------------
| Form Handling
|--------------------------------------------------------------------------
*/

function cwcp_handle_experience_actions() {

    if (!isset($_REQUEST['cwcp_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_REQUEST['cwcp_action']));

    if (!in_array($action, array('save_experience', 'delete_experience', 'set_no_experience'), true)) {
        return;
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        cwcp_redirect(cwcp_login_url());
    }

    global $wpdb;

    $table = cwcp_table('experience');

    /*
     * Fresh candidates answer the work history question instead of adding a
     * record, so their account can still reach 100%.
     */

    if ('set_no_experience' === $action) {

        if (
            !isset($_POST['cwcp_no_experience_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_no_experience_nonce'])), 'cwcp_no_experience')
        ) {
            cwcp_add_notice('Security check failed.', 'error');
            cwcp_redirect(cwcp_experience_url());
        }

        if (!empty($_POST['no_experience'])) {

            update_user_meta($user_id, 'cwcp_no_experience', 1);

            cwcp_add_notice('Saved. You are listed as a fresh candidate with no work experience yet.', 'success');

        } else {

            delete_user_meta($user_id, 'cwcp_no_experience');

            cwcp_add_notice('Updated. Please add your work experience below.', 'info');
        }

        cwcp_redirect(cwcp_experience_url());
    }

    if ('delete_experience' === $action) {

        $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (
            !isset($_REQUEST['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'cwcp_delete_experience_' . $id)
        ) {
            cwcp_add_notice('Security check failed.', 'error');
            cwcp_redirect(cwcp_experience_url());
        }

        $wpdb->delete($table, array('id' => $id, 'user_id' => $user_id), array('%d', '%d'));

        cwcp_add_notice('Experience record deleted.', 'success');
        cwcp_redirect(cwcp_experience_url());
    }

    if (
        !isset($_POST['cwcp_experience_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_experience_nonce'])), 'cwcp_save_experience')
    ) {
        cwcp_add_notice('Security check failed.', 'error');
        cwcp_redirect(cwcp_experience_url());
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $currently_working = !empty($_POST['currently_working']) ? 1 : 0;

    $data = array(
        'organization'      => isset($_POST['organization']) ? sanitize_text_field(wp_unslash($_POST['organization'])) : '',
        'designation'       => isset($_POST['designation']) ? sanitize_text_field(wp_unslash($_POST['designation'])) : '',
        'job_city'          => isset($_POST['job_city']) ? sanitize_text_field(wp_unslash($_POST['job_city'])) : '',
        'start_date'        => isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '',
        'end_date'          => isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : '',
        'currently_working' => $currently_working,
        'responsibilities'  => isset($_POST['responsibilities']) ? sanitize_textarea_field(wp_unslash($_POST['responsibilities'])) : '',
    );

    $errors = array();

    if ('' === $data['organization']) {
        $errors[] = 'Organization name is required.';
    }

    if ('' === $data['designation']) {
        $errors[] = 'Designation is required.';
    }

    if (!cwcp_is_valid_date($data['start_date'])) {
        $errors[] = 'Please enter a valid start date.';
    }

    if ($currently_working) {

        $data['end_date'] = null;

    } else {

        if (!cwcp_is_valid_date($data['end_date'])) {

            $errors[] = 'Please enter a valid end date, or tick "I currently work here".';

        } elseif (strtotime($data['end_date']) < strtotime($data['start_date'])) {

            $errors[] = 'The end date cannot be before the start date.';
        }
    }

    if ($errors) {

        foreach ($errors as $error) {
            cwcp_add_notice($error, 'error');
        }

        cwcp_redirect(cwcp_experience_url());
    }

    if ($id && cwcp_get_experience_row($id, $user_id)) {

        $wpdb->update($table, $data, array('id' => $id, 'user_id' => $user_id));

        cwcp_add_notice('Experience record updated.', 'success');

    } else {

        $data['user_id']    = $user_id;
        $data['created_at'] = current_time('mysql');

        $wpdb->insert($table, $data);

        delete_user_meta($user_id, 'cwcp_no_experience');

        cwcp_add_notice('Experience record added.', 'success');
    }

    cwcp_redirect(cwcp_experience_url());
}

add_action('template_redirect', 'cwcp_handle_experience_actions', 5);


/*
|--------------------------------------------------------------------------
| Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_experience_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();

    $records = cwcp_get_experience($user_id);

    $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

    $editing = $edit_id ? cwcp_get_experience_row($edit_id, $user_id) : null;

    $value = function ($key) use ($editing) {
        return $editing && isset($editing[$key]) ? $editing[$key] : '';
    };

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'experience',
        'Work Experience',
        'Total experience: ' . cwcp_format_experience(cwcp_total_experience_months($user_id))
    );
    ?>

    <?php $no_experience = (bool) get_user_meta($user_id, 'cwcp_no_experience', true); ?>

    <?php if (empty($records)) : ?>

        <div class="cwcp-card cwcp-pad cwcp-mb-25">

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-seedling"></i></span>
                <h2>No work experience yet?</h2>
            </div>

            <p class="cwcp-text-muted">
                Fresh graduates can tick this instead of adding a record. Your account still counts as
                complete, and you can add experience later at any time.
            </p>

            <form method="post" class="cwcp-form">

                <?php wp_nonce_field('cwcp_no_experience', 'cwcp_no_experience_nonce'); ?>
                <input type="hidden" name="cwcp_action" value="set_no_experience" />

                <label class="cwcp-inline-check">
                    <input type="checkbox" name="no_experience" value="1" <?php checked(true, $no_experience); ?> />
                    I am a fresh candidate and have no work experience yet
                </label>

                <div class="cwcp-form-actions">
                    <button type="submit" class="cwcp-btn-secondary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Answer
                    </button>
                </div>

            </form>

        </div>

    <?php endif; ?>

    <div class="cwcp-card cwcp-pad cwcp-mb-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-briefcase"></i></span>
            <h2><?php echo $editing ? 'Edit Experience' : 'Add Experience'; ?></h2>
        </div>

        <form method="post" class="cwcp-form">

            <?php wp_nonce_field('cwcp_save_experience', 'cwcp_experience_nonce'); ?>
            <input type="hidden" name="cwcp_action" value="save_experience" />
            <input type="hidden" name="id" value="<?php echo esc_attr($value('id')); ?>" />

            <div class="cwcp-grid cwcp-grid-2">

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-organization">Organization <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="text" id="cwcp-organization" name="organization"
                           value="<?php echo esc_attr($value('organization')); ?>" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-designation">Designation <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="text" id="cwcp-designation" name="designation"
                           value="<?php echo esc_attr($value('designation')); ?>" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-job-city">City</label>
                    <input class="cwcp-form-input" type="text" id="cwcp-job-city" name="job_city"
                           value="<?php echo esc_attr($value('job_city')); ?>" />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-start-date">Start Date <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="date" id="cwcp-start-date" name="start_date"
                           value="<?php echo esc_attr($value('start_date')); ?>" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-end-date">End Date</label>
                    <input class="cwcp-form-input" type="date" id="cwcp-end-date" name="end_date"
                           value="<?php echo esc_attr($value('end_date')); ?>"
                           <?php echo !empty($value('currently_working')) ? 'disabled' : ''; ?> />
                </div>

                <div class="cwcp-form-group cwcp-checkbox-group">
                    <label class="cwcp-inline-check">
                        <input type="checkbox" name="currently_working" value="1" id="cwcp-currently-working"
                               <?php checked(1, (int) $value('currently_working')); ?> />
                        I currently work here
                    </label>
                </div>

                <div class="cwcp-form-group cwcp-col-span-2">
                    <label class="cwcp-form-label" for="cwcp-responsibilities">Key Responsibilities</label>
                    <textarea class="cwcp-form-input cwcp-textarea" id="cwcp-responsibilities" name="responsibilities"
                              rows="4"><?php echo esc_textarea($value('responsibilities')); ?></textarea>
                </div>

            </div>

            <div class="cwcp-form-actions">
                <button type="submit" class="cwcp-btn-primary">
                    <i class="fa-solid fa-<?php echo $editing ? 'floppy-disk' : 'plus'; ?>"></i>
                    <?php echo $editing ? 'Update Record' : 'Add Experience'; ?>
                </button>

                <?php if ($editing) : ?>
                    <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_experience_url()); ?>">Cancel</a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <div class="cwcp-card cwcp-pad">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-timeline"></i></span>
            <h2>Your Experience (<?php echo count($records); ?>)</h2>
        </div>

        <?php if (empty($records)) : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-briefcase"></i></div>
                <h3>No experience added yet</h3>
                <p>Fresh graduates can skip this section.</p>
            </div>

        <?php else : ?>

            <div class="cwcp-timeline">
                <?php foreach ($records as $record) : ?>

                    <div class="cwcp-timeline-item">

                        <div class="cwcp-timeline-dot"></div>

                        <div class="cwcp-timeline-body">

                            <div class="cwcp-flex cwcp-flex-between cwcp-flex-wrap">
                                <div>
                                    <strong><?php echo esc_html($record['designation']); ?></strong>
                                    <span class="cwcp-text-muted"> at <?php echo esc_html($record['organization']); ?></span>
                                </div>

                                <div class="cwcp-row-actions">
                                    <a class="cwcp-icon-btn" title="Edit"
                                       href="<?php echo esc_url(add_query_arg('edit', $record['id'], cwcp_experience_url())); ?>">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a class="cwcp-icon-btn cwcp-icon-btn-danger" title="Delete"
                                       onclick="return confirm('Delete this experience record?');"
                                       href="<?php echo esc_url(
                                           wp_nonce_url(
                                               add_query_arg(
                                                   array('cwcp_action' => 'delete_experience', 'id' => $record['id']),
                                                   cwcp_experience_url()
                                               ),
                                               'cwcp_delete_experience_' . $record['id']
                                           )
                                       ); ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="cwcp-timeline-meta">
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo esc_html(cwcp_format_date($record['start_date'], 'M Y')); ?>
                                &ndash;
                                <?php
                                echo !empty($record['currently_working'])
                                    ? 'Present'
                                    : esc_html(cwcp_format_date($record['end_date'], 'M Y'));
                                ?>

                                <?php if ($record['job_city']) : ?>
                                    &nbsp;&middot;&nbsp;<i class="fa-solid fa-location-dot"></i>
                                    <?php echo esc_html($record['job_city']); ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($record['responsibilities']) : ?>
                                <p class="cwcp-timeline-text"><?php echo nl2br(esc_html($record['responsibilities'])); ?></p>
                            <?php endif; ?>

                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>

    <?php

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_experience', 'cwcp_experience_shortcode');
