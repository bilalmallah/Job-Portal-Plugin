<?php
/**
 * Care Wave Candidate Portal - Volunteer, internship, field facilitator and
 * tender / donation forms.
 *
 * All four share one schema driven renderer, one validator and one storage
 * table, so adding another form later only means adding a schema.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Form Schemas
|--------------------------------------------------------------------------
*/

function cwcp_form_schema($type) {

    $identity = array(
        'full_name'   => array('label' => 'Full Name', 'type' => 'text', 'required' => true),
        'father_name' => array('label' => 'Father Name', 'type' => 'text', 'required' => true),
        'email'       => array('label' => 'Email Address', 'type' => 'email', 'required' => true),
        'mobile'      => array('label' => 'Mobile Number', 'type' => 'tel', 'required' => true, 'placeholder' => '03001234567'),
        'cnic'        => array('label' => 'CNIC', 'type' => 'text', 'required' => true, 'placeholder' => '35202-1234567-1'),
        'dob'         => array('label' => 'Date of Birth', 'type' => 'date', 'required' => true),
        'gender'      => array('label' => 'Gender', 'type' => 'select', 'required' => true, 'options' => 'genders'),
    );

    $location = array(
        'province' => array('label' => 'Province', 'type' => 'select', 'required' => true, 'options' => 'provinces'),
        'district' => array('label' => 'District', 'type' => 'select', 'required' => true, 'options' => 'districts'),
        'address'  => array('label' => 'Address', 'type' => 'textarea', 'required' => true),
    );

    switch ($type) {

        case 'volunteer':
            return array_merge(
                $identity,
                $location,
                array(
                    'qualification'   => array('label' => 'Highest Qualification', 'type' => 'text', 'required' => true),
                    'occupation'      => array('label' => 'Current Occupation', 'type' => 'text', 'required' => false),
                    'interest_area'   => array(
                        'label'    => 'Area of Interest',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'interests',
                    ),
                    'availability'    => array(
                        'label'    => 'Availability',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'availability',
                    ),
                    'hours_per_week'  => array('label' => 'Hours Available Per Week', 'type' => 'number', 'required' => false),
                    'past_experience' => array('label' => 'Previous Volunteer Experience', 'type' => 'textarea', 'required' => false),
                    'motivation'      => array('label' => 'Why do you want to volunteer with us?', 'type' => 'textarea', 'required' => true),
                    'emergency_name'  => array('label' => 'Emergency Contact Name', 'type' => 'text', 'required' => true),
                    'emergency_phone' => array('label' => 'Emergency Contact Number', 'type' => 'tel', 'required' => true),
                    'attachment'      => array('label' => 'CV (optional)', 'type' => 'file', 'required' => false),
                )
            );

        case 'internship':
            return array_merge(
                $identity,
                $location,
                array(
                    'university'      => array('label' => 'University / Institute', 'type' => 'text', 'required' => true),
                    'degree_program'  => array('label' => 'Degree Program', 'type' => 'text', 'required' => true),
                    'semester'        => array('label' => 'Current Semester / Year', 'type' => 'text', 'required' => true),
                    'cgpa'            => array('label' => 'CGPA / Percentage', 'type' => 'text', 'required' => false),
                    'department'      => array(
                        'label'    => 'Preferred Department',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'interests',
                    ),
                    'duration'        => array(
                        'label'    => 'Internship Duration',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'durations',
                    ),
                    'start_date'      => array('label' => 'Available From', 'type' => 'date', 'required' => true),
                    'is_credit'       => array(
                        'label'    => 'Is this a degree requirement?',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'yes_no',
                    ),
                    'motivation'      => array('label' => 'What do you hope to learn with us?', 'type' => 'textarea', 'required' => true),
                    'attachment'      => array('label' => 'CV / Resume', 'type' => 'file', 'required' => true),
                )
            );

        case 'field_facilitator':
            return array_merge(
                $identity,
                $location,
                array(
                    'union_council'    => array('label' => 'Union Council / Tehsil', 'type' => 'text', 'required' => true),
                    'qualification'    => array('label' => 'Highest Qualification', 'type' => 'text', 'required' => true),
                    'experience_years' => array('label' => 'Years of Field Experience', 'type' => 'number', 'required' => true),
                    'sectors'          => array(
                        'label'    => 'Sector Experience',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'interests',
                    ),
                    'languages'        => array('label' => 'Languages Spoken', 'type' => 'text', 'required' => true, 'placeholder' => 'Urdu, Sindhi, Pashto…'),
                    'has_bike'         => array(
                        'label'    => 'Do you own a bike / vehicle?',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'yes_no',
                    ),
                    'can_travel'       => array(
                        'label'    => 'Willing to travel to remote areas?',
                        'type'     => 'select',
                        'required' => true,
                        'options'  => 'yes_no',
                    ),
                    'expected_stipend' => array('label' => 'Expected Monthly Stipend (PKR)', 'type' => 'text', 'required' => false),
                    'reference'        => array('label' => 'Reference (name and contact)', 'type' => 'text', 'required' => false),
                    'attachment'       => array('label' => 'CV / Resume', 'type' => 'file', 'required' => true),
                )
            );

        case 'tender':
            return array(
                'organization'   => array('label' => 'Company / Organization Name', 'type' => 'text', 'required' => true),
                'full_name'      => array('label' => 'Contact Person', 'type' => 'text', 'required' => true),
                'email'          => array('label' => 'Email Address', 'type' => 'email', 'required' => true),
                'mobile'         => array('label' => 'Mobile Number', 'type' => 'tel', 'required' => true, 'placeholder' => '03001234567'),
                'ntn'            => array('label' => 'NTN / Registration No.', 'type' => 'text', 'required' => false),
                'address'        => array('label' => 'Office Address', 'type' => 'textarea', 'required' => true),
                'bid_amount'     => array('label' => 'Bid / Pledge Amount (PKR)', 'type' => 'text', 'required' => false),
                'message'        => array('label' => 'Message / Proposal Summary', 'type' => 'textarea', 'required' => true),
                'attachment'     => array('label' => 'Proposal Document (PDF/DOC)', 'type' => 'file', 'required' => true),
            );
    }

    return array();
}

function cwcp_form_option_set($set, $context = array()) {

    switch ($set) {

        case 'genders':
            return cwcp_genders();

        case 'provinces':
            return cwcp_provinces();

        case 'districts':
            $province = isset($context['province']) ? $context['province'] : '';

            $list = cwcp_districts($province);

            return $list ? array_combine($list, $list) : array();

        case 'availability':
            return array(
                'Weekdays'  => 'Weekdays',
                'Weekends'  => 'Weekends',
                'Both'      => 'Both',
                'Flexible'  => 'Flexible',
            );

        case 'durations':
            return array(
                '6 weeks'  => '6 weeks',
                '2 months' => '2 months',
                '3 months' => '3 months',
                '6 months' => '6 months',
            );

        case 'yes_no':
            return array('Yes' => 'Yes', 'No' => 'No');

        case 'interests':
            $terms = get_terms(array('taxonomy' => 'cw_job_category', 'hide_empty' => false));

            $options = array();

            if (!is_wp_error($terms)) {

                foreach ($terms as $term) {
                    $options[$term->name] = $term->name;
                }
            }

            $options['Other'] = 'Other';

            return $options;
    }

    return array();
}


/*
|--------------------------------------------------------------------------
| Submission Storage
|--------------------------------------------------------------------------
*/

function cwcp_insert_submission($form_type, $values, $attachment_id = 0, $related_id = 0) {

    global $wpdb;

    $inserted = $wpdb->insert(
        cwcp_table('submissions'),
        array(
            'form_type'     => $form_type,
            'user_id'       => get_current_user_id(),
            'related_id'    => (int) $related_id,
            'full_name'     => isset($values['full_name']) ? $values['full_name'] : '',
            'email'         => isset($values['email']) ? $values['email'] : '',
            'mobile'        => isset($values['mobile']) ? $values['mobile'] : '',
            'data'          => wp_json_encode($values),
            'attachment_id' => (int) $attachment_id,
            'status'        => 'new',
            'ip'            => cwcp_get_ip(),
            'created_at'    => current_time('mysql'),
        ),
        array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
    );

    if (!$inserted) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

function cwcp_get_submission($id) {

    global $wpdb;

    $table = cwcp_table('submissions');

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), // phpcs:ignore WordPress.DB.PreparedSQL
        ARRAY_A
    );
}

function cwcp_submissions_where($args) {

    global $wpdb;

    $where  = array('1=1');
    $params = array();

    if (!empty($args['form_type'])) {
        $where[]  = 'form_type = %s';
        $params[] = $args['form_type'];
    }

    if (!empty($args['related_id'])) {
        $where[]  = 'related_id = %d';
        $params[] = (int) $args['related_id'];
    }

    if (!empty($args['status'])) {
        $where[]  = 'status = %s';
        $params[] = $args['status'];
    }

    if (!empty($args['search'])) {

        $like = '%' . $wpdb->esc_like($args['search']) . '%';

        $where[]  = '(full_name LIKE %s OR email LIKE %s OR mobile LIKE %s)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    return array(
        'sql'    => implode(' AND ', $where),
        'params' => $params,
    );
}

function cwcp_get_submissions($args = array()) {

    global $wpdb;

    $args = wp_parse_args(
        $args,
        array(
            'form_type'  => '',
            'related_id' => 0,
            'status'     => '',
            'search'     => '',
            'per_page'   => 20,
            'paged'      => 1,
        )
    );

    $table = cwcp_table('submissions');

    $where = cwcp_submissions_where($args);

    $per_page = max(1, (int) $args['per_page']);
    $offset   = max(0, ((int) $args['paged'] - 1) * $per_page);

    $sql = "SELECT * FROM {$table} WHERE {$where['sql']} ORDER BY created_at DESC LIMIT %d OFFSET %d";

    $params = array_merge($where['params'], array($per_page, $offset));

    return $wpdb->get_results(
        $wpdb->prepare($sql, $params), // phpcs:ignore WordPress.DB.PreparedSQL
        ARRAY_A
    );
}

function cwcp_count_submissions($args = array()) {

    global $wpdb;

    $args = wp_parse_args(
        $args,
        array(
            'form_type'  => '',
            'related_id' => 0,
            'status'     => '',
            'search'     => '',
        )
    );

    $table = cwcp_table('submissions');

    $where = cwcp_submissions_where($args);

    $sql = "SELECT COUNT(id) FROM {$table} WHERE {$where['sql']}";

    if ($where['params']) {
        $sql = $wpdb->prepare($sql, $where['params']); // phpcs:ignore WordPress.DB.PreparedSQL
    }

    return (int) $wpdb->get_var($sql); // phpcs:ignore WordPress.DB.PreparedSQL
}

function cwcp_update_submission_status($id, $status, $notes = null) {

    global $wpdb;

    $allowed = array('new', 'reviewed', 'shortlisted', 'approved', 'rejected');

    if (!in_array($status, $allowed, true)) {
        return false;
    }

    $data = array('status' => $status);

    if (null !== $notes) {
        $data['admin_notes'] = $notes;
    }

    return (bool) $wpdb->update(cwcp_table('submissions'), $data, array('id' => (int) $id));
}

function cwcp_submission_statuses() {

    return array(
        'new'         => 'New',
        'reviewed'    => 'Reviewed',
        'shortlisted' => 'Shortlisted',
        'approved'    => 'Approved',
        'rejected'    => 'Not Selected',
    );
}

function cwcp_get_submission_data($submission) {

    if (empty($submission['data'])) {
        return array();
    }

    $data = json_decode($submission['data'], true);

    return is_array($data) ? $data : array();
}


/*
|--------------------------------------------------------------------------
| Submission Handler
|--------------------------------------------------------------------------
*/

function cwcp_handle_form_submission() {

    if (!isset($_POST['cwcp_action']) || 'submit_form' !== sanitize_key(wp_unslash($_POST['cwcp_action']))) {
        return;
    }

    $type = isset($_POST['form_type']) ? sanitize_key(wp_unslash($_POST['form_type'])) : '';

    $schema = cwcp_form_schema($type);

    if (empty($schema)) {
        return;
    }

    $related_id = isset($_POST['related_id']) ? (int) $_POST['related_id'] : 0;

    $back = cwcp_form_page_url($type, $related_id);

    if (
        !isset($_POST['cwcp_form_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_form_nonce'])), 'cwcp_form_' . $type)
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect($back);
    }

    /* Honeypot. */
    if (!empty($_POST['cwcp_website'])) {
        cwcp_redirect($back);
    }

    if (cwcp_is_throttled('form_' . $type, 6, 1800)) {
        cwcp_add_notice('You have submitted this form several times already. Please contact us directly.', 'error');
        cwcp_redirect($back);
    }

    if ('tender' === $type) {

        if (!$related_id || 'cw_tender' !== get_post_type($related_id)) {

            cwcp_add_notice('Please choose a tender to apply against.', 'error');
            cwcp_redirect($back);
        }

        if (!cwcp_tender_is_open($related_id)) {

            cwcp_add_notice('This tender is closed and no longer accepts submissions.', 'error');
            cwcp_redirect($back);
        }
    }

    $errors = array();
    $values = array();

    foreach ($schema as $key => $field) {

        if ('file' === $field['type']) {
            continue;
        }

        $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';

        if ('textarea' === $field['type']) {
            $value = sanitize_textarea_field($raw);
        } elseif ('email' === $field['type']) {
            $value = sanitize_email($raw);
        } else {
            $value = sanitize_text_field($raw);
        }

        if ('cnic' === $key && '' !== $value) {

            $normalized = cwcp_normalize_cnic($value);

            if ('' === $normalized) {
                $errors[] = 'Please enter a valid 13 digit CNIC.';
            } else {
                $value = $normalized;
            }
        }

        if (in_array($key, array('mobile', 'emergency_phone'), true) && '' !== $value) {

            $normalized = cwcp_normalize_mobile($value);

            if ('' === $normalized) {
                $errors[] = $field['label'] . ' must be a valid mobile number (for example 03001234567).';
            } else {
                $value = $normalized;
            }
        }

        if ('email' === $key && '' !== $value && !is_email($value)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ('dob' === $key && '' !== $value) {

            if (!cwcp_is_valid_date($value)) {

                $errors[] = 'Please enter a valid date of birth.';

            } elseif (cwcp_calculate_age($value) < 16) {

                $errors[] = 'Applicants must be at least 16 years old.';
            }
        }

        if (!empty($field['required']) && '' === trim((string) $value)) {
            $errors[] = $field['label'] . ' is required.';
        }

        $values[$key] = $value;
    }

    /*
     * Optional / required document.
     */

    $attachment_id = 0;

    $has_file = !empty($_FILES['attachment']['name']);

    $file_field = isset($schema['attachment']) ? $schema['attachment'] : null;

    if ($file_field && $has_file) {

        $stored = cwcp_store_document('attachment', get_current_user_id());

        if (is_wp_error($stored)) {
            $errors[] = $stored->get_error_message();
        } else {
            $attachment_id = $stored;
        }

    } elseif ($file_field && !empty($file_field['required'])) {

        $errors[] = $file_field['label'] . ' is required.';
    }

    if ($errors) {

        foreach (array_unique($errors) as $error) {
            cwcp_add_notice($error, 'error');
        }

        set_transient('cwcp_form_old_' . $type . '_' . cwcp_notice_key(), $values, 300);

        cwcp_redirect($back);
    }

    $submission_id = cwcp_insert_submission($type, $values, $attachment_id, $related_id);

    if (!$submission_id) {

        cwcp_add_notice('We could not save your submission. Please try again.', 'error');
        cwcp_redirect($back);
    }

    do_action('cwcp_form_submitted', $submission_id, $type, $values);

    $labels = cwcp_form_types();

    cwcp_add_notice(
        'Thank you! Your ' . esc_html(strtolower($labels[$type])) . ' has been received. Our team will contact you if you are shortlisted.',
        'success'
    );

    cwcp_redirect(add_query_arg('submitted', 1, $back));
}

add_action('template_redirect', 'cwcp_handle_form_submission', 5);

function cwcp_form_page_url($type, $related_id = 0) {

    if ('tender' === $type) {

        return $related_id
            ? add_query_arg('tender_id', $related_id, cwcp_page_url('tenders'))
            : cwcp_page_url('tenders');
    }

    return cwcp_page_url($type);
}


/*
|--------------------------------------------------------------------------
| Generic Form Renderer
|--------------------------------------------------------------------------
*/

function cwcp_render_form($type, $related_id = 0, $intro = '') {

    $schema = cwcp_form_schema($type);

    if (empty($schema)) {
        return '';
    }

    $old = get_transient('cwcp_form_old_' . $type . '_' . cwcp_notice_key());

    if (is_array($old)) {
        delete_transient('cwcp_form_old_' . $type . '_' . cwcp_notice_key());
    } else {
        $old = array();
    }

    /*
     * Logged in candidates get their known details pre-filled.
     */

    $prefill = array();

    if (is_user_logged_in()) {

        $profile = cwcp_get_profile(get_current_user_id());

        $prefill = array(
            'full_name'   => $profile['full_name'],
            'father_name' => $profile['father_name'],
            'email'       => $profile['email'],
            'mobile'      => $profile['mobile'],
            'cnic'        => $profile['cnic'],
            'dob'         => $profile['dob'],
            'gender'      => $profile['gender'],
            'province'    => $profile['province'],
            'district'    => $profile['district'],
            'address'     => $profile['address'],
        );
    }

    $get_value = function ($key) use ($old, $prefill) {

        if (isset($old[$key])) {
            return $old[$key];
        }

        return isset($prefill[$key]) ? $prefill[$key] : '';
    };

    $has_file = false;

    foreach ($schema as $field) {

        if ('file' === $field['type']) {
            $has_file = true;
        }
    }

    ob_start();
    ?>
    <form method="post" class="cwcp-form cwcp-public-form" <?php echo $has_file ? 'enctype="multipart/form-data"' : ''; ?>>

        <?php wp_nonce_field('cwcp_form_' . $type, 'cwcp_form_nonce'); ?>
        <input type="hidden" name="cwcp_action" value="submit_form" />
        <input type="hidden" name="form_type" value="<?php echo esc_attr($type); ?>" />
        <input type="hidden" name="related_id" value="<?php echo esc_attr($related_id); ?>" />

        <div class="cwcp-hp-field">
            <label>Website</label>
            <input type="text" name="cwcp_website" tabindex="-1" autocomplete="off" />
        </div>

        <?php if ($intro) : ?>
            <p class="cwcp-form-intro"><?php echo esc_html($intro); ?></p>
        <?php endif; ?>

        <div class="cwcp-grid cwcp-grid-2">

            <?php foreach ($schema as $key => $field) : ?>

                <?php
                $value = $get_value($key);

                $wide = in_array($field['type'], array('textarea'), true);
                ?>

                <div class="cwcp-form-group <?php echo $wide ? 'cwcp-col-span-2' : ''; ?>">

                    <label class="cwcp-form-label" for="cwcp-<?php echo esc_attr($type . '-' . $key); ?>">
                        <?php echo esc_html($field['label']); ?>
                        <?php if (!empty($field['required'])) : ?>
                            <span class="cwcp-req">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ('select' === $field['type']) : ?>

                        <select class="cwcp-form-input"
                                id="cwcp-<?php echo esc_attr($type . '-' . $key); ?>"
                                name="<?php echo esc_attr($key); ?>"
                                <?php echo 'province' === $key ? 'data-cwcp-province="1"' : ''; ?>
                                <?php echo 'district' === $key ? 'data-cwcp-district="1"' : ''; ?>
                                <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                            <?php
                            $options = cwcp_form_option_set(
                                $field['options'],
                                array('province' => $get_value('province'))
                            );

                            echo cwcp_select_options($options, $value, '-- Select --'); // phpcs:ignore WordPress.Security.EscapeOutput
                            ?>
                        </select>

                    <?php elseif ('textarea' === $field['type']) : ?>

                        <textarea class="cwcp-form-input cwcp-textarea"
                                  id="cwcp-<?php echo esc_attr($type . '-' . $key); ?>"
                                  name="<?php echo esc_attr($key); ?>"
                                  rows="4"
                                  <?php echo !empty($field['required']) ? 'required' : ''; ?>><?php echo esc_textarea($value); ?></textarea>

                    <?php elseif ('file' === $field['type']) : ?>

                        <input class="cwcp-form-input"
                               type="file"
                               id="cwcp-<?php echo esc_attr($type . '-' . $key); ?>"
                               name="attachment"
                               accept=".pdf,.doc,.docx"
                               <?php echo !empty($field['required']) ? 'required' : ''; ?> />
                        <small class="cwcp-help">PDF, DOC or DOCX. Maximum <?php echo esc_html((int) cwcp_setting('resume_max_size', 5)); ?> MB.</small>

                    <?php else : ?>

                        <input class="cwcp-form-input"
                               type="<?php echo esc_attr($field['type']); ?>"
                               id="cwcp-<?php echo esc_attr($type . '-' . $key); ?>"
                               name="<?php echo esc_attr($key); ?>"
                               value="<?php echo esc_attr($value); ?>"
                               <?php echo isset($field['placeholder']) ? 'placeholder="' . esc_attr($field['placeholder']) . '"' : ''; ?>
                               <?php echo 'date' === $field['type'] && 'dob' === $key ? 'max="' . esc_attr(gmdate('Y-m-d')) . '"' : ''; ?>
                               <?php echo !empty($field['required']) ? 'required' : ''; ?> />

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="cwcp-form-group cwcp-checkbox-group">
            <label class="cwcp-inline-check">
                <input type="checkbox" required />
                I confirm the information provided is accurate and complete.
            </label>
        </div>

        <button type="submit" class="cwcp-btn-primary cwcp-btn-lg">
            <i class="fa-solid fa-paper-plane"></i> Submit Application
        </button>

    </form>
    <?php

    return ob_get_clean();
}


/*
|--------------------------------------------------------------------------
| Form Shortcodes
|--------------------------------------------------------------------------
*/

function cwcp_volunteer_form_shortcode() {

    ob_start();

    echo cwcp_public_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'Volunteer Registration',
        'Join our volunteer roster and support communities across Pakistan.'
    );

    echo '<div class="cwcp-card cwcp-pad">';
    echo cwcp_render_form('volunteer', 0, 'Complete the form below to join our volunteer database.'); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div>';

    echo cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_volunteer_form', 'cwcp_volunteer_form_shortcode');

function cwcp_internship_form_shortcode() {

    ob_start();

    echo cwcp_public_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'Internship Application',
        'Learn with our programme teams and gain field experience.'
    );

    echo '<div class="cwcp-card cwcp-pad">';
    echo cwcp_render_form('internship', 0, 'Tell us about your studies and what you would like to learn.'); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div>';

    echo cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_internship_form', 'cwcp_internship_form_shortcode');

function cwcp_field_facilitator_form_shortcode() {

    ob_start();

    echo cwcp_public_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'Field Facilitator Application',
        'Work with us in the field on health, education and relief projects.'
    );

    echo '<div class="cwcp-card cwcp-pad">';
    echo cwcp_render_form('field_facilitator', 0, 'Field facilitators are hired district wise. Please give accurate location details.'); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div>';

    echo cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_field_facilitator_form', 'cwcp_field_facilitator_form_shortcode');


/*
|--------------------------------------------------------------------------
| Tenders Listing + Submission
|--------------------------------------------------------------------------
*/

function cwcp_tenders_shortcode() {

    $tender_id = isset($_GET['tender_id']) ? (int) $_GET['tender_id'] : 0;

    ob_start();

    if ($tender_id && 'cw_tender' === get_post_type($tender_id)) {

        $tender = get_post($tender_id);

        $is_open = cwcp_tender_is_open($tender_id);

        $type = get_post_meta($tender_id, 'cwcp_tender_type', true);

        echo cwcp_public_open( // phpcs:ignore WordPress.Security.EscapeOutput
            $tender->post_title,
            'donation' === $type ? 'Donation appeal' : 'Tender notice'
        );

        echo '<a class="cwcp-back-link" href="' . esc_url(cwcp_page_url('tenders')) . '">'
            . '<i class="fa-solid fa-arrow-left"></i> Back to all tenders</a>';

        $rows = array_filter(
            array(
                'Reference No.' => get_post_meta($tender_id, 'cwcp_tender_reference', true),
                'Closing Date'  => cwcp_format_date(get_post_meta($tender_id, 'cwcp_tender_deadline', true)),
                'Value'         => get_post_meta($tender_id, 'cwcp_tender_amount', true),
                'Status'        => $is_open ? 'Open' : 'Closed',
            )
        );

        echo '<div class="cwcp-card cwcp-pad cwcp-mb-25">';
        echo '<div class="cwcp-detail-grid">';

        foreach ($rows as $label => $value) {
            echo '<div class="cwcp-detail-item">'
                . '<span class="cwcp-detail-label">' . esc_html($label) . '</span>'
                . '<span class="cwcp-detail-value">' . esc_html($value) . '</span>'
                . '</div>';
        }

        echo '</div>';

        echo '<div class="cwcp-job-description">' . wp_kses_post(wpautop($tender->post_content)) . '</div>';

        $document = get_post_meta($tender_id, 'cwcp_tender_document', true);

        if ($document) {
            echo '<a class="cwcp-btn-secondary" target="_blank" rel="noopener" href="' . esc_url($document) . '">'
                . '<i class="fa-solid fa-download"></i> Download Documents</a>';
        }

        echo '</div>';

        echo '<div class="cwcp-card cwcp-pad">';

        if ($is_open) {

            echo '<div class="cwcp-section-header">'
                . '<span class="cwcp-section-header-icon"><i class="fa-solid fa-file-signature"></i></span>'
                . '<h2>Submit Your ' . esc_html('donation' === $type ? 'Pledge' : 'Bid') . '</h2></div>';

            echo cwcp_render_form('tender', $tender_id); // phpcs:ignore WordPress.Security.EscapeOutput

        } else {

            echo '<div class="cwcp-empty">'
                . '<div class="cwcp-empty-icon"><i class="fa-solid fa-lock"></i></div>'
                . '<h3>This tender is closed</h3>'
                . '<p>Submissions are no longer accepted.</p></div>';
        }

        echo '</div>';

        echo cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput

        return ob_get_clean();
    }

    /*
     * Listing.
     */

    $query = new WP_Query(
        array(
            'post_type'      => 'cw_tender',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
        )
    );

    echo cwcp_public_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'Tenders & Donation Appeals',
        'Current procurement notices and appeals open for submissions.'
    );

    if ($query->have_posts()) {

        echo '<div class="cwcp-job-list">';

        while ($query->have_posts()) {

            $query->the_post();

            $id      = get_the_ID();
            $is_open = cwcp_tender_is_open($id);
            $type    = get_post_meta($id, 'cwcp_tender_type', true);

            ?>
            <article class="cwcp-job-card">

                <div class="cwcp-job-card-head">
                    <div>
                        <h3 class="cwcp-job-title">
                            <a href="<?php echo esc_url(add_query_arg('tender_id', $id, cwcp_page_url('tenders'))); ?>">
                                <?php echo esc_html(get_the_title()); ?>
                            </a>
                        </h3>
                        <div class="cwcp-job-tags">
                            <span class="cwcp-badge cwcp-badge-neutral">
                                <?php echo esc_html('donation' === $type ? 'Donation Appeal' : 'Tender'); ?>
                            </span>
                            <span class="cwcp-badge <?php echo $is_open ? 'cwcp-badge-success' : 'cwcp-badge-danger'; ?>">
                                <?php echo $is_open ? 'Open' : 'Closed'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="cwcp-job-meta">
                    <?php $reference = get_post_meta($id, 'cwcp_tender_reference', true); ?>
                    <?php if ($reference) : ?>
                        <span><i class="fa-solid fa-hashtag"></i> <?php echo esc_html($reference); ?></span>
                    <?php endif; ?>

                    <?php $deadline = get_post_meta($id, 'cwcp_tender_deadline', true); ?>
                    <?php if ($deadline) : ?>
                        <span><i class="fa-regular fa-calendar-xmark"></i> Closes <?php echo esc_html(cwcp_format_date($deadline)); ?></span>
                    <?php endif; ?>
                </div>

                <p class="cwcp-job-excerpt">
                    <?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_the_content()), 28)); ?>
                </p>

                <div class="cwcp-job-card-actions">
                    <a class="cwcp-btn-primary" href="<?php echo esc_url(add_query_arg('tender_id', $id, cwcp_page_url('tenders'))); ?>">
                        <i class="fa-solid fa-eye"></i> View &amp; Submit
                    </a>
                </div>

            </article>
            <?php
        }

        echo '</div>';

        wp_reset_postdata();

    } else {

        echo '<div class="cwcp-card cwcp-pad"><div class="cwcp-empty">'
            . '<div class="cwcp-empty-icon"><i class="fa-solid fa-file-signature"></i></div>'
            . '<h3>No tenders published right now</h3>'
            . '<p>Please check back later.</p></div></div>';
    }

    echo cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_tenders', 'cwcp_tenders_shortcode');
