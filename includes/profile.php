<?php
/**
 * Care Wave Candidate Portal - Candidate profile and account completeness.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Profile Field Definition
|--------------------------------------------------------------------------
|
| One definition drives the frontend form, validation, the completeness
| calculation, the admin detail screen and the CSV export.
|
*/

function cwcp_profile_fields() {

    return array(

        'full_name' => array(
            'label'    => 'Full Name',
            'type'     => 'text',
            'required' => true,
            'section'  => 'personal',
        ),

        'father_name' => array(
            'label'    => 'Father Name',
            'type'     => 'text',
            'required' => true,
            'section'  => 'personal',
        ),

        'email' => array(
            'label'    => 'Email Address',
            'type'     => 'email',
            'required' => true,
            'section'  => 'personal',
        ),

        'mobile' => array(
            'label'       => 'Mobile Number',
            'type'        => 'tel',
            'required'    => true,
            'placeholder' => '03001234567',
            'section'     => 'personal',
        ),

        'cnic' => array(
            'label'       => 'CNIC',
            'type'        => 'text',
            'required'    => true,
            'placeholder' => '35202-1234567-1',
            'section'     => 'personal',
        ),

        'dob' => array(
            'label'    => 'Date of Birth',
            'type'     => 'date',
            'required' => true,
            'section'  => 'personal',
        ),

        'gender' => array(
            'label'    => 'Gender',
            'type'     => 'select',
            'required' => true,
            'options'  => 'genders',
            'section'  => 'personal',
        ),

        'religion' => array(
            'label'    => 'Religion',
            'type'     => 'select',
            'required' => true,
            'options'  => 'religions',
            'section'  => 'personal',
        ),

        'marital_status' => array(
            'label'    => 'Marital Status',
            'type'     => 'select',
            'required' => true,
            'options'  => 'marital',
            'section'  => 'personal',
        ),

        'province' => array(
            'label'    => 'Province',
            'type'     => 'select',
            'required' => true,
            'options'  => 'provinces',
            'section'  => 'location',
        ),

        'district' => array(
            'label'    => 'District',
            'type'     => 'select',
            'required' => true,
            'options'  => 'districts',
            'section'  => 'location',
        ),

        'address' => array(
            'label'    => 'Postal Address',
            'type'     => 'textarea',
            'required' => true,
            'section'  => 'location',
        ),

        'current_position' => array(
            'label'    => 'Current Position (if any)',
            'type'     => 'text',
            'required' => false,
            'section'  => 'professional',
        ),

        'current_organization' => array(
            'label'    => 'Current Organization (if any)',
            'type'     => 'text',
            'required' => false,
            'section'  => 'professional',
        ),

        'expected_salary' => array(
            'label'    => 'Expected Salary (PKR)',
            'type'     => 'text',
            'required' => false,
            'section'  => 'professional',
        ),

        'linkedin' => array(
            'label'    => 'LinkedIn Profile',
            'type'     => 'url',
            'required' => false,
            'section'  => 'professional',
        ),

        'about' => array(
            'label'    => 'About / Career Objective',
            'type'     => 'textarea',
            'required' => false,
            'section'  => 'professional',
        ),
    );
}

function cwcp_profile_sections() {

    return array(
        'personal'     => array('title' => 'Personal Information', 'icon' => 'fa-user'),
        'location'     => array('title' => 'Address',              'icon' => 'fa-location-dot'),
        'professional' => array('title' => 'Professional Details', 'icon' => 'fa-briefcase'),
    );
}

function cwcp_field_options($set, $user_id = 0) {

    switch ($set) {

        case 'genders':
            return cwcp_genders();

        case 'religions':
            return cwcp_religions();

        case 'marital':
            return cwcp_marital_statuses();

        case 'provinces':
            return cwcp_provinces();

        case 'districts':
            $province = cwcp_get_profile_value($user_id, 'province');

            $districts = cwcp_districts($province);

            return array_combine($districts, $districts) ?: array();
    }

    return array();
}


/*
|--------------------------------------------------------------------------
| Reading / Writing Profile Values
|--------------------------------------------------------------------------
*/

function cwcp_get_profile_value($user_id, $key) {

    if (!$user_id) {
        return '';
    }

    if ('email' === $key) {

        $user = get_userdata($user_id);

        return $user ? $user->user_email : '';
    }

    $value = get_user_meta($user_id, 'cwcp_' . $key, true);

    if ('full_name' === $key && '' === $value) {

        $user = get_userdata($user_id);

        return $user ? trim($user->first_name . ' ' . $user->last_name) : '';
    }

    return is_string($value) ? $value : '';
}

function cwcp_get_profile($user_id) {

    $profile = array();

    foreach (cwcp_profile_fields() as $key => $field) {
        $profile[$key] = cwcp_get_profile_value($user_id, $key);
    }

    $profile['resume_id']  = (int) get_user_meta($user_id, 'cwcp_resume_id', true);
    $profile['resume_url'] = $profile['resume_id'] ? cwcp_document_url($profile['resume_id']) : '';

    return $profile;
}


/*
|--------------------------------------------------------------------------
| Completeness
|--------------------------------------------------------------------------
|
| An account is complete when every required profile field is filled in, a
| resume is uploaded, at least one education record exists and the work
| history is answered. Only complete accounts can apply for jobs.
|
*/

function cwcp_profile_completeness($user_id) {

    $missing        = array();
    $missing_labels = array();

    $total    = 0;
    $filled   = 0;

    foreach (cwcp_profile_fields() as $key => $field) {

        if (empty($field['required'])) {
            continue;
        }

        $total++;

        if ('' !== trim((string) cwcp_get_profile_value($user_id, $key))) {

            $filled++;

        } else {

            $missing[]        = $key;
            $missing_labels[] = $field['label'];
        }
    }

    /*
     * Beyond the profile fields an account also needs a resume, at least one
     * education record and a work history. A candidate with no work history
     * completes that item by ticking "no experience yet" on the experience
     * screen, so fresh graduates can still reach 100%.
     */

    $total++;

    $resume_id = (int) get_user_meta($user_id, 'cwcp_resume_id', true);

    if ($resume_id && get_post($resume_id)) {

        $filled++;

    } else {

        $missing[]        = 'resume';
        $missing_labels[] = 'Resume';
    }

    $total++;

    if (cwcp_get_education($user_id)) {

        $filled++;

    } else {

        $missing[]        = 'education';
        $missing_labels[] = 'Education';
    }

    $total++;

    if (cwcp_get_experience($user_id) || get_user_meta($user_id, 'cwcp_no_experience', true)) {

        $filled++;

    } else {

        $missing[]        = 'experience';
        $missing_labels[] = 'Work experience';
    }

    $percent = $total ? (int) round(($filled / $total) * 100) : 0;

    return array(
        'percent'        => $percent,
        'filled'         => $filled,
        'total'          => $total,
        'missing'        => $missing,
        'missing_labels' => $missing_labels,
        'is_complete'    => empty($missing),
    );
}

function cwcp_is_profile_complete($user_id = 0) {

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $completeness = cwcp_profile_completeness($user_id);

    return $completeness['is_complete'];
}


/*
|--------------------------------------------------------------------------
| Save Profile
|--------------------------------------------------------------------------
*/

/**
 * Sanitizes and validates a submitted profile.
 *
 * Shared by the candidate profile screen and the admin candidate editor so
 * both enforce identical rules.
 *
 * @param array $input   Raw input, usually $_POST.
 * @param int   $user_id Account being edited.
 *
 * @return array {
 *     @type array $values Clean values, keyed like cwcp_profile_fields().
 *     @type array $errors Human readable error messages.
 * }
 */
function cwcp_validate_profile_input($input, $user_id) {

    $errors = array();
    $values = array();

    foreach (cwcp_profile_fields() as $key => $field) {

        $raw = isset($input[$key]) ? wp_unslash($input[$key]) : '';

        if ('textarea' === $field['type']) {
            $value = sanitize_textarea_field($raw);
        } elseif ('email' === $field['type']) {
            $value = sanitize_email($raw);
        } elseif ('url' === $field['type']) {
            $value = esc_url_raw($raw);
        } else {
            $value = sanitize_text_field($raw);
        }

        /*
         * Field specific validation.
         */

        if ('cnic' === $key && '' !== $value) {

            $normalized = cwcp_normalize_cnic($value);

            if ('' === $normalized) {
                $errors[] = 'Please enter a valid 13 digit CNIC (for example 35202-1234567-1).';
            } else {
                $value = $normalized;
            }
        }

        if ('mobile' === $key && '' !== $value) {

            $normalized = cwcp_normalize_mobile($value);

            if ('' === $normalized) {
                $errors[] = 'Please enter a valid mobile number (for example 03001234567).';
            } else {
                $value = $normalized;
            }
        }

        if ('dob' === $key && '' !== $value) {

            if (!cwcp_is_valid_date($value)) {

                $errors[] = 'Please enter a valid date of birth.';

            } else {

                $age = cwcp_calculate_age($value);

                if ($age < 16) {
                    $errors[] = 'The candidate must be at least 16 years old.';
                }

                if ($age > 80) {
                    $errors[] = 'Please check the date of birth you entered.';
                }
            }
        }

        if ('email' === $key && '' !== $value) {

            if (!is_email($value)) {

                $errors[] = 'Please enter a valid email address.';

            } else {

                $existing = email_exists($value);

                if ($existing && (int) $existing !== $user_id) {
                    $errors[] = 'That email address is already used by another account.';
                }
            }
        }

        if (!empty($field['required']) && '' === trim((string) $value)) {
            $errors[] = $field['label'] . ' is required.';
        }

        $values[$key] = $value;
    }

    /*
     * District must belong to the chosen province.
     */

    if (!empty($values['province']) && !empty($values['district'])) {

        $districts = cwcp_districts($values['province']);

        if ($districts && !in_array($values['district'], $districts, true)) {
            $errors[] = 'The selected district does not belong to the selected province.';
        }
    }

    return array(
        'values' => $values,
        'errors' => array_values(array_unique($errors)),
    );
}

/**
 * Writes validated profile values to the account.
 */
function cwcp_save_profile_values($user_id, $values) {

    foreach ($values as $key => $value) {

        if ('email' === $key) {
            continue;
        }

        update_user_meta($user_id, 'cwcp_' . $key, $value);
    }

    $name_parts = preg_split('/\s+/', trim($values['full_name']));

    $first_name = array_shift($name_parts);
    $last_name  = $name_parts ? implode(' ', $name_parts) : '';

    wp_update_user(
        array(
            'ID'           => $user_id,
            'user_email'   => $values['email'],
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $values['full_name'],
        )
    );

    do_action('cwcp_profile_saved', $user_id, $values);
}

function cwcp_handle_profile_save() {

    if (!isset($_POST['cwcp_action']) || 'save_profile' !== sanitize_key(wp_unslash($_POST['cwcp_action']))) {
        return;
    }

    if (
        !isset($_POST['cwcp_profile_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_profile_nonce'])), 'cwcp_save_profile')
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_profile_url());
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        cwcp_redirect(cwcp_login_url());
    }

    $checked = cwcp_validate_profile_input($_POST, $user_id); // phpcs:ignore WordPress.Security.NonceVerification

    $values = $checked['values'];

    if ($checked['errors']) {

        foreach ($checked['errors'] as $error) {
            cwcp_add_notice($error, 'error');
        }

        set_transient('cwcp_profile_old_' . $user_id, $values, 300);

        cwcp_redirect(cwcp_profile_url());
    }

    cwcp_save_profile_values($user_id, $values);

    /*
     * Profile photo. Handled after the field save so a rejected image never
     * costs the candidate the rest of the form.
     */

    if (!empty($_POST['remove_photo'])) {

        cwcp_delete_photo($user_id);

        cwcp_add_notice('Profile photo removed.', 'info');
    }

    if (!empty($_FILES['profile_photo']['name'])) {

        $photo_id = cwcp_store_photo('profile_photo', $user_id);

        if (is_wp_error($photo_id)) {

            cwcp_add_notice($photo_id->get_error_message(), 'error');

        } else {

            $previous = cwcp_get_photo_id($user_id);

            if ($previous && $previous !== $photo_id) {
                cwcp_delete_photo($user_id);
            }

            update_user_meta($user_id, 'cwcp_photo_id', $photo_id);
        }
    }

    /*
     * "Save and manage resume" keeps the candidate moving without losing the
     * form they just filled in.
     */

    $after_save = isset($_POST['cwcp_after_save']) ? sanitize_key(wp_unslash($_POST['cwcp_after_save'])) : '';

    $completeness = cwcp_profile_completeness($user_id);

    if ($completeness['is_complete']) {

        cwcp_add_notice('Profile saved. Your account is complete - you can now apply for jobs.', 'success');

    } else {

        cwcp_add_notice(
            'Profile saved. Still missing: ' . esc_html(implode(', ', $completeness['missing_labels'])) . '.',
            'warning'
        );
    }

    if ('resume' === $after_save) {
        cwcp_redirect(cwcp_resume_url());
    }

    cwcp_redirect(cwcp_profile_url());
}

add_action('template_redirect', 'cwcp_handle_profile_save', 5);


/*
|--------------------------------------------------------------------------
| Profile Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_profile_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();

    $old = get_transient('cwcp_profile_old_' . $user_id);

    if (is_array($old)) {
        delete_transient('cwcp_profile_old_' . $user_id);
    } else {
        $old = array();
    }

    $completeness = cwcp_profile_completeness($user_id);

    $sections = cwcp_profile_sections();
    $fields   = cwcp_profile_fields();

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'profile',
        'My Profile',
        'These details are shared with the hiring team every time you apply.'
    );
    ?>

    <?php if (!$completeness['is_complete']) : ?>
        <div class="cwcp-alert cwcp-alert-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                <strong>Account incomplete (<?php echo esc_html($completeness['percent']); ?>%).</strong>
                Please provide: <?php echo esc_html(implode(', ', $completeness['missing_labels'])); ?>.
            </span>
        </div>
    <?php else : ?>
        <div class="cwcp-alert cwcp-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span><strong>Your account is complete.</strong> You can apply for any open job with one click.</span>
        </div>
    <?php endif; ?>

    <form method="post" class="cwcp-form" enctype="multipart/form-data" data-cwcp-dirty-guard="1">

        <?php wp_nonce_field('cwcp_save_profile', 'cwcp_profile_nonce'); ?>
        <input type="hidden" name="cwcp_action" value="save_profile" />

        <?php foreach ($sections as $section_key => $section) : ?>

            <div class="cwcp-card cwcp-pad cwcp-mb-25">

                <div class="cwcp-section-header">
                    <span class="cwcp-section-header-icon"><i class="fa-solid <?php echo esc_attr($section['icon']); ?>"></i></span>
                    <h2><?php echo esc_html($section['title']); ?></h2>
                </div>

                <div class="cwcp-grid cwcp-grid-2">

                    <?php
                    foreach ($fields as $key => $field) :

                        if ($field['section'] !== $section_key) {
                            continue;
                        }

                        $value = isset($old[$key]) ? $old[$key] : cwcp_get_profile_value($user_id, $key);

                        $is_wide = in_array($field['type'], array('textarea'), true);
                        ?>

                        <div class="cwcp-form-group <?php echo $is_wide ? 'cwcp-col-span-2' : ''; ?>">

                            <label class="cwcp-form-label" for="cwcp-<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($field['label']); ?>
                                <?php if (!empty($field['required'])) : ?>
                                    <span class="cwcp-req">*</span>
                                <?php endif; ?>
                            </label>

                            <?php if ('select' === $field['type']) : ?>

                                <select class="cwcp-form-input"
                                        id="cwcp-<?php echo esc_attr($key); ?>"
                                        name="<?php echo esc_attr($key); ?>"
                                        <?php echo 'province' === $key ? 'data-cwcp-province="1"' : ''; ?>
                                        <?php echo 'district' === $key ? 'data-cwcp-district="1"' : ''; ?>
                                        <?php echo !empty($field['required']) ? 'required' : ''; ?>>

                                    <?php
                                    $options = 'districts' === $field['options']
                                        ? cwcp_field_options('districts', $user_id)
                                        : cwcp_field_options($field['options']);

                                    if ('districts' === $field['options'] && isset($old['province'])) {

                                        $list    = cwcp_districts($old['province']);
                                        $options = $list ? array_combine($list, $list) : array();
                                    }

                                    echo cwcp_select_options($options, $value, '-- Select --'); // phpcs:ignore WordPress.Security.EscapeOutput
                                    ?>
                                </select>

                            <?php elseif ('textarea' === $field['type']) : ?>

                                <textarea class="cwcp-form-input cwcp-textarea"
                                          id="cwcp-<?php echo esc_attr($key); ?>"
                                          name="<?php echo esc_attr($key); ?>"
                                          rows="4"
                                          <?php echo !empty($field['required']) ? 'required' : ''; ?>><?php echo esc_textarea($value); ?></textarea>

                            <?php else : ?>

                                <input class="cwcp-form-input"
                                       type="<?php echo esc_attr($field['type']); ?>"
                                       id="cwcp-<?php echo esc_attr($key); ?>"
                                       name="<?php echo esc_attr($key); ?>"
                                       value="<?php echo esc_attr($value); ?>"
                                       <?php echo isset($field['placeholder']) ? 'placeholder="' . esc_attr($field['placeholder']) . '"' : ''; ?>
                                       <?php echo 'date' === $field['type'] ? 'max="' . esc_attr(gmdate('Y-m-d')) . '"' : ''; ?>
                                       <?php echo !empty($field['required']) ? 'required' : ''; ?> />

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>
            </div>

        <?php endforeach; ?>

        <div class="cwcp-card cwcp-pad cwcp-mb-25">

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-camera"></i></span>
                <h2>Profile Photo</h2>
            </div>

            <?php $photo_url = cwcp_get_photo_url($user_id, 'medium'); ?>

            <div class="cwcp-photo-row">

                <div class="cwcp-photo-preview">
                    <?php if ($photo_url) : ?>
                        <img src="<?php echo esc_url($photo_url); ?>" alt="" />
                    <?php else : ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </div>

                <div class="cwcp-photo-fields">

                    <div class="cwcp-form-group">
                        <label class="cwcp-form-label" for="cwcp-profile-photo">
                            <?php echo $photo_url ? 'Replace photo' : 'Upload a photo'; ?>
                        </label>
                        <input class="cwcp-form-input" type="file" id="cwcp-profile-photo"
                               name="profile_photo" accept="image/jpeg,image/png,image/webp,image/gif" />
                        <small class="cwcp-help">
                            JPG, PNG, WEBP or GIF. Maximum <?php echo esc_html(cwcp_photo_max_mb()); ?> MB.
                            A clear head and shoulders photo works best.
                        </small>
                    </div>

                    <?php if ($photo_url) : ?>
                        <label class="cwcp-inline-check">
                            <input type="checkbox" name="remove_photo" value="1" />
                            Remove my current photo
                        </label>
                    <?php endif; ?>

                </div>

            </div>

        </div>

        <div class="cwcp-card cwcp-pad cwcp-mb-25">

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-file-arrow-up"></i></span>
                <h2>Resume</h2>
            </div>

            <?php
            $resume_id = (int) get_user_meta($user_id, 'cwcp_resume_id', true);

            if ($resume_id && get_post($resume_id)) :
                ?>
                <p class="cwcp-flex cwcp-flex-wrap cwcp-gap">
                    <span class="cwcp-badge cwcp-badge-success"><i class="fa-solid fa-check"></i> Uploaded</span>
                    <a href="<?php echo esc_url(cwcp_document_url($resume_id)); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html(get_the_title($resume_id)); ?>
                    </a>
                </p>
            <?php else : ?>
                <p class="cwcp-text-muted">No resume uploaded yet. A resume is required before you can apply.</p>
            <?php endif; ?>

            <button type="submit" class="cwcp-btn-secondary" name="cwcp_after_save" value="resume">
                <i class="fa-solid fa-upload"></i>
                <?php echo $resume_id ? 'Save &amp; Manage Resume' : 'Save &amp; Upload Resume'; ?>
            </button>

            <small class="cwcp-help">
                Your profile is saved before the resume screen opens, so nothing you typed is lost.
            </small>
        </div>

        <div class="cwcp-form-actions">
            <button type="submit" class="cwcp-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Profile
            </button>
        </div>

    </form>

    <?php

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

add_shortcode('carewave_profile', 'cwcp_profile_shortcode');


/*
|--------------------------------------------------------------------------
| District Lookup (AJAX)
|--------------------------------------------------------------------------
*/

function cwcp_ajax_get_districts() {

    check_ajax_referer('cwcp_portal', 'nonce');

    $province = isset($_POST['province']) ? sanitize_text_field(wp_unslash($_POST['province'])) : '';

    wp_send_json_success(
        array(
            'districts' => array_values(cwcp_districts($province)),
        )
    );
}

add_action('wp_ajax_cwcp_get_districts', 'cwcp_ajax_get_districts');
add_action('wp_ajax_nopriv_cwcp_get_districts', 'cwcp_ajax_get_districts');
