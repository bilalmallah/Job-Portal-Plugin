<?php
/**
 * Care Wave Candidate Portal - Email notifications.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Mail Helpers
|--------------------------------------------------------------------------
*/

function cwcp_mail($to, $subject, $body_html) {

    $headers = array('Content-Type: text/html; charset=UTF-8');

    return wp_mail($to, $subject, cwcp_email_template($subject, $body_html), $headers);
}

function cwcp_email_template($title, $body_html) {

    $company = cwcp_setting('company_name', get_bloginfo('name'));

    ob_start();
    ?>
    <div style="background:#f4f6fa;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#172033;">
        <div style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e6e9ef;border-radius:10px;overflow:hidden;">

            <div style="background:#1d4ed8;color:#ffffff;padding:18px 24px;font-size:18px;font-weight:bold;">
                <?php echo esc_html($company); ?>
            </div>

            <div style="padding:24px;font-size:15px;line-height:1.6;">
                <h2 style="margin:0 0 16px;font-size:18px;color:#172033;"><?php echo esc_html($title); ?></h2>
                <?php echo wp_kses_post($body_html); ?>
            </div>

            <div style="padding:16px 24px;background:#f8fafc;border-top:1px solid #e6e9ef;font-size:12px;color:#7b8495;">
                This is an automated message from <?php echo esc_html($company); ?>.
                <br />
                <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#1d4ed8;"><?php echo esc_html(home_url('/')); ?></a>
            </div>

        </div>
    </div>
    <?php

    return ob_get_clean();
}

function cwcp_admin_notification_email() {

    $email = cwcp_setting('admin_email', get_option('admin_email'));

    return is_email($email) ? $email : get_option('admin_email');
}

function cwcp_button_html($url, $label) {

    return '<p style="margin:22px 0;">'
        . '<a href="' . esc_url($url) . '" style="background:#1d4ed8;color:#ffffff;text-decoration:none;'
        . 'padding:11px 20px;border-radius:6px;display:inline-block;font-weight:bold;">'
        . esc_html($label) . '</a></p>';
}


/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

function cwcp_email_on_registration($user_id) {

    if (!cwcp_setting('notify_candidate', 1)) {
        return;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return;
    }

    $body = '<p>Dear ' . esc_html($user->display_name) . ',</p>'
        . '<p>Your candidate account has been created. The next step is to complete your profile: '
        . 'personal details, CNIC, address, education, experience and your resume. '
        . 'Once your account is complete you can apply for any open position with a single click.</p>'
        . cwcp_button_html(cwcp_profile_url(), 'Complete My Profile')
        . '<p>Your login email: <strong>' . esc_html($user->user_email) . '</strong></p>';

    cwcp_mail($user->user_email, 'Welcome to ' . cwcp_setting('company_name'), $body);

    if (cwcp_setting('notify_admin', 1)) {

        $admin_body = '<p>A new candidate has registered.</p>'
            . '<ul>'
            . '<li><strong>Name:</strong> ' . esc_html($user->display_name) . '</li>'
            . '<li><strong>Email:</strong> ' . esc_html($user->user_email) . '</li>'
            . '<li><strong>Mobile:</strong> ' . esc_html(get_user_meta($user_id, 'cwcp_mobile', true)) . '</li>'
            . '</ul>'
            . cwcp_button_html(admin_url('admin.php?page=cwcp-candidates'), 'View Candidates');

        cwcp_mail(cwcp_admin_notification_email(), 'New candidate registration', $admin_body);
    }
}

add_action('cwcp_candidate_registered', 'cwcp_email_on_registration');


/*
|--------------------------------------------------------------------------
| Password Reset
|--------------------------------------------------------------------------
*/

function cwcp_send_password_reset_email($user, $reset_url) {

    $body = '<p>Dear ' . esc_html($user->display_name) . ',</p>'
        . '<p>We received a request to reset the password for your candidate account. '
        . 'Click the button below to choose a new password. This link expires in 24 hours.</p>'
        . cwcp_button_html($reset_url, 'Reset My Password')
        . '<p>If you did not request this, you can safely ignore this email.</p>'
        . '<p style="font-size:12px;color:#7b8495;word-break:break-all;">' . esc_url($reset_url) . '</p>';

    return cwcp_mail($user->user_email, 'Reset your password', $body);
}


/*
|--------------------------------------------------------------------------
| Applications
|--------------------------------------------------------------------------
*/

function cwcp_email_on_application($application_id, $user_id, $job_id) {

    $user = get_userdata($user_id);

    if (!$user) {
        return;
    }

    $job_title = get_the_title($job_id);

    if (cwcp_setting('notify_candidate', 1)) {

        $body = '<p>Dear ' . esc_html($user->display_name) . ',</p>'
            . '<p>We have received your application for <strong>' . esc_html($job_title) . '</strong>. '
            . 'Our team reviews every application and will contact you if you are shortlisted.</p>'
            . '<p>Application reference: <strong>#' . esc_html($application_id) . '</strong></p>'
            . cwcp_button_html(cwcp_applied_jobs_url(), 'Track My Applications');

        cwcp_mail($user->user_email, 'Application received - ' . $job_title, $body);
    }

    if (cwcp_setting('notify_admin', 1)) {

        $profile = cwcp_get_profile($user_id);

        $body = '<p>A new application has been submitted.</p>'
            . '<ul>'
            . '<li><strong>Job:</strong> ' . esc_html($job_title) . '</li>'
            . '<li><strong>Candidate:</strong> ' . esc_html($profile['full_name']) . '</li>'
            . '<li><strong>Email:</strong> ' . esc_html($profile['email']) . '</li>'
            . '<li><strong>Mobile:</strong> ' . esc_html($profile['mobile']) . '</li>'
            . '<li><strong>CNIC:</strong> ' . esc_html($profile['cnic']) . '</li>'
            . '<li><strong>District:</strong> ' . esc_html($profile['district'] . ', ' . $profile['province']) . '</li>'
            . '</ul>'
            . cwcp_button_html(
                admin_url('admin.php?page=cwcp-applications&action=view&id=' . $application_id),
                'Open Application'
            );

        cwcp_mail(cwcp_admin_notification_email(), 'New application: ' . $job_title, $body);
    }
}

add_action('cwcp_application_submitted', 'cwcp_email_on_application', 10, 3);

function cwcp_email_on_status_change($application_id, $status, $old_status) {

    if (!cwcp_setting('notify_candidate', 1)) {
        return;
    }

    $application = cwcp_get_application($application_id);

    if (!$application) {
        return;
    }

    $user = get_userdata($application['user_id']);

    if (!$user) {
        return;
    }

    $job_title = get_the_title($application['job_id']);

    $messages = array(
        'reviewed'    => 'Your application is now under review.',
        'shortlisted' => 'Good news - you have been shortlisted. We will contact you with the next steps.',
        'interview'   => 'You have been invited to an interview. Our team will share the schedule with you shortly.',
        'hired'       => 'Congratulations! We would like to offer you this position. Our HR team will be in touch.',
        'rejected'    => 'After careful review we have decided to move forward with other candidates for this role. '
            . 'We encourage you to apply for future openings.',
    );

    if (!isset($messages[$status])) {
        return;
    }

    $body = '<p>Dear ' . esc_html($user->display_name) . ',</p>'
        . '<p>Update on your application for <strong>' . esc_html($job_title) . '</strong>:</p>'
        . '<p style="padding:12px 16px;background:#eef3ff;border-left:3px solid #1d4ed8;">'
        . esc_html($messages[$status]) . '</p>'
        . cwcp_button_html(cwcp_applied_jobs_url(), 'View My Applications');

    cwcp_mail($user->user_email, 'Application update - ' . $job_title, $body);
}

add_action('cwcp_application_status_changed', 'cwcp_email_on_status_change', 10, 3);


/*
|--------------------------------------------------------------------------
| Volunteer / Internship / Facilitator / Tender Forms
|--------------------------------------------------------------------------
*/

function cwcp_email_on_form_submission($submission_id, $type, $values) {

    $labels = cwcp_form_types();

    $label = isset($labels[$type]) ? $labels[$type] : 'Form submission';

    if (!empty($values['email']) && cwcp_setting('notify_candidate', 1)) {

        $body = '<p>Dear ' . esc_html(isset($values['full_name']) ? $values['full_name'] : 'Applicant') . ',</p>'
            . '<p>Thank you for your ' . esc_html(strtolower($label)) . '. '
            . 'Your submission has been recorded with reference <strong>#' . esc_html($submission_id) . '</strong>. '
            . 'Our team will get in touch if your profile matches our current needs.</p>';

        cwcp_mail($values['email'], $label . ' received', $body);
    }

    if (cwcp_setting('notify_admin', 1)) {

        $rows = '';

        foreach ($values as $key => $value) {

            if ('' === $value) {
                continue;
            }

            $rows .= '<li><strong>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ':</strong> '
                . esc_html($value) . '</li>';
        }

        $body = '<p>A new ' . esc_html(strtolower($label)) . ' has been received.</p>'
            . '<ul>' . $rows . '</ul>'
            . cwcp_button_html(
                admin_url('admin.php?page=cwcp-submissions&action=view&id=' . $submission_id),
                'Open Submission'
            );

        cwcp_mail(cwcp_admin_notification_email(), 'New ' . strtolower($label), $body);
    }
}

add_action('cwcp_form_submitted', 'cwcp_email_on_form_submission', 10, 3);
