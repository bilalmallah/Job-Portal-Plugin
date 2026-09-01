<?php
/**
 * Care Wave Candidate Portal - Registration, login, logout and password reset.
 *
 * All form processing happens on template_redirect so that a redirect can be
 * issued before any output is sent.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Form Router
|--------------------------------------------------------------------------
*/

function cwcp_handle_auth_forms() {

    if (!isset($_POST['cwcp_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['cwcp_action']));

    switch ($action) {

        case 'register':
            cwcp_process_registration();
            break;

        case 'login':
            cwcp_process_login();
            break;

        case 'lost_password':
            cwcp_process_lost_password();
            break;

        case 'reset_password':
            cwcp_process_reset_password();
            break;
    }
}

add_action('template_redirect', 'cwcp_handle_auth_forms', 5);


/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

function cwcp_process_registration() {

    if (
        !isset($_POST['cwcp_register_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_register_nonce'])), 'cwcp_register')
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_registration_url());
    }

    if (is_user_logged_in()) {
        cwcp_redirect(cwcp_dashboard_url());
    }

    /* Honeypot: real users never fill this in. */
    if (!empty($_POST['cwcp_website'])) {
        cwcp_redirect(cwcp_registration_url());
    }

    if (cwcp_is_throttled('register', 10, 3600)) {
        cwcp_add_notice('Too many attempts from this device. Please try again later.', 'error');
        cwcp_redirect(cwcp_registration_url());
    }

    $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
    $last_name  = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
    $email      = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $mobile_raw = isset($_POST['mobile']) ? sanitize_text_field(wp_unslash($_POST['mobile'])) : '';
    $password   = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $confirm    = isset($_POST['confirm_password']) ? (string) wp_unslash($_POST['confirm_password']) : '';
    $terms      = !empty($_POST['terms']);

    $errors = array();

    if ('' === $first_name) {
        $errors[] = 'First name is required.';
    }

    if ('' === $last_name) {
        $errors[] = 'Last name is required.';
    }

    if (!is_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (email_exists($email)) {
        $errors[] = 'An account with this email address already exists. Please log in instead.';
    }

    $mobile = cwcp_normalize_mobile($mobile_raw);

    if ('' === $mobile) {
        $errors[] = 'Please enter a valid mobile number (for example 03001234567).';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Password and confirm password do not match.';
    }

    if (!$terms) {
        $errors[] = 'You must accept the terms and conditions.';
    }

    if ($errors) {

        foreach ($errors as $error) {
            cwcp_add_notice($error, 'error');
        }

        set_transient(
            'cwcp_register_old_' . cwcp_notice_key(),
            compact('first_name', 'last_name', 'email', 'mobile_raw'),
            300
        );

        cwcp_redirect(cwcp_registration_url());
    }

    /*
     * Build a unique username from the email address.
     */

    $base = sanitize_user(current(explode('@', $email)), true);

    if ('' === $base) {
        $base = 'candidate';
    }

    $username = $base;
    $counter  = 1;

    while (username_exists($username)) {

        $username = $base . $counter;

        $counter++;
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {

        cwcp_add_notice($user_id->get_error_message(), 'error');
        cwcp_redirect(cwcp_registration_url());
    }

    $user = new WP_User($user_id);

    $user->set_role('carewave_candidate');

    wp_update_user(
        array(
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'nickname'     => trim($first_name . ' ' . $last_name),
        )
    );

    update_user_meta($user_id, 'cwcp_full_name', trim($first_name . ' ' . $last_name));
    update_user_meta($user_id, 'cwcp_mobile', $mobile);
    update_user_meta($user_id, 'cwcp_registered_at', current_time('mysql'));

    do_action('cwcp_candidate_registered', $user_id);

    /*
     * Log the candidate straight in and send them to the profile page so the
     * account can be completed.
     */

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    cwcp_add_notice(
        'Welcome to ' . esc_html(cwcp_setting('company_name')) . '! Please complete your profile to start applying for jobs.',
        'success'
    );

    cwcp_redirect(cwcp_profile_url());
}


/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

function cwcp_process_login() {

    if (
        !isset($_POST['cwcp_login_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_login_nonce'])), 'cwcp_login')
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_login_url());
    }

    if (cwcp_is_throttled('login', 15, 900)) {
        cwcp_add_notice('Too many login attempts. Please wait a few minutes and try again.', 'error');
        cwcp_redirect(cwcp_login_url());
    }

    $login    = isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $remember = !empty($_POST['remember']);

    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : '';

    if ('' === $login || '' === $password) {

        cwcp_add_notice('Please enter both your email/username and password.', 'error');
        cwcp_redirect(cwcp_login_url());
    }

    /*
     * Allow logging in with an email address.
     */

    if (is_email($login)) {

        $user_by_email = get_user_by('email', $login);

        if ($user_by_email) {
            $login = $user_by_email->user_login;
        }
    }

    $user = wp_signon(
        array(
            'user_login'    => $login,
            'user_password' => $password,
            'remember'      => $remember,
        ),
        is_ssl()
    );

    if (is_wp_error($user)) {

        cwcp_add_notice('Invalid email/username or password. Please try again.', 'error');
        cwcp_redirect(cwcp_login_url());
    }

    wp_set_current_user($user->ID);

    update_user_meta($user->ID, 'cwcp_last_login', current_time('mysql'));

    if ($redirect_to) {
        cwcp_redirect($redirect_to);
    }

    if (cwcp_is_candidate($user->ID)) {
        cwcp_redirect(cwcp_dashboard_url());
    }

    cwcp_redirect(admin_url());
}


/*
|--------------------------------------------------------------------------
| Lost Password
|--------------------------------------------------------------------------
*/

function cwcp_process_lost_password() {

    if (
        !isset($_POST['cwcp_lost_password_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_lost_password_nonce'])), 'cwcp_lost_password')
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_lost_password_url());
    }

    if (cwcp_is_throttled('lost_password', 8, 900)) {
        cwcp_add_notice('Too many requests. Please try again later.', 'error');
        cwcp_redirect(cwcp_lost_password_url());
    }

    $login = isset($_POST['user_login']) ? sanitize_text_field(wp_unslash($_POST['user_login'])) : '';

    /*
     * The same confirmation is shown whether or not the account exists, so
     * the form cannot be used to discover registered email addresses.
     */

    $generic = 'If an account exists for that email address, we have sent a password reset link to it. Please also check your spam folder.';

    $user = is_email($login) ? get_user_by('email', $login) : get_user_by('login', $login);

    if (!$user) {

        cwcp_add_notice($generic, 'info');
        cwcp_redirect(cwcp_lost_password_url());
    }

    $key = get_password_reset_key($user);

    if (is_wp_error($key)) {

        cwcp_add_notice('We could not generate a reset link. Please contact us for help.', 'error');
        cwcp_redirect(cwcp_lost_password_url());
    }

    $reset_url = add_query_arg(
        array(
            'key'   => $key,
            'login' => rawurlencode($user->user_login),
        ),
        cwcp_page_url('reset_password')
    );

    cwcp_send_password_reset_email($user, $reset_url);

    cwcp_add_notice($generic, 'success');
    cwcp_redirect(cwcp_lost_password_url());
}


/*
|--------------------------------------------------------------------------
| Reset Password
|--------------------------------------------------------------------------
*/

function cwcp_process_reset_password() {

    if (
        !isset($_POST['cwcp_reset_password_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_reset_password_nonce'])), 'cwcp_reset_password')
    ) {
        cwcp_add_notice('Security check failed. Please try again.', 'error');
        cwcp_redirect(cwcp_page_url('reset_password'));
    }

    $key      = isset($_POST['reset_key']) ? sanitize_text_field(wp_unslash($_POST['reset_key'])) : '';
    $login    = isset($_POST['reset_login']) ? sanitize_text_field(wp_unslash($_POST['reset_login'])) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $confirm  = isset($_POST['confirm_password']) ? (string) wp_unslash($_POST['confirm_password']) : '';

    $back = add_query_arg(
        array(
            'key'   => $key,
            'login' => rawurlencode($login),
        ),
        cwcp_page_url('reset_password')
    );

    $user = check_password_reset_key($key, $login);

    if (is_wp_error($user)) {

        cwcp_add_notice('This password reset link has expired or is invalid. Please request a new one.', 'error');
        cwcp_redirect(cwcp_lost_password_url());
    }

    if (strlen($password) < 8) {

        cwcp_add_notice('Password must be at least 8 characters long.', 'error');
        cwcp_redirect($back);
    }

    if ($password !== $confirm) {

        cwcp_add_notice('Password and confirm password do not match.', 'error');
        cwcp_redirect($back);
    }

    reset_password($user, $password);

    cwcp_add_notice('Your password has been updated. You can now log in with your new password.', 'success');
    cwcp_redirect(cwcp_login_url());
}


/*
|--------------------------------------------------------------------------
| Registration Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_registration_shortcode() {

    if (is_user_logged_in()) {

        return '<div class="cwcp-page cwcp-scope cwcp-auth-page alignwide"><div class="cwcp-container">'
            . '<div class="cwcp-card cwcp-auth-card cwcp-pad">'
            . '<div class="cwcp-empty"><div class="cwcp-empty-icon"><i class="fa-solid fa-circle-check"></i></div>'
            . '<h3>You are already logged in</h3>'
            . '<p>You already have an account with us.</p>'
            . '<a class="cwcp-btn-primary" href="' . esc_url(cwcp_dashboard_url()) . '">'
            . '<i class="fa-solid fa-gauge-high"></i> Go to Dashboard</a>'
            . '</div></div></div></div>';
    }

    $old = get_transient('cwcp_register_old_' . cwcp_notice_key());

    if (!is_array($old)) {
        $old = array();
    }

    delete_transient('cwcp_register_old_' . cwcp_notice_key());

    $value = function ($key) use ($old) {
        return isset($old[$key]) ? esc_attr($old[$key]) : '';
    };

    ob_start();
    ?>
    <div class="cwcp-page cwcp-scope cwcp-auth-page alignwide">
        <div class="cwcp-container cwcp-container-narrow">

            <div class="cwcp-auth-head">
                <div class="cwcp-icon-box"><i class="fa-solid fa-user-plus"></i></div>
                <h1>Create your candidate account</h1>
                <p>Register once, then apply to any job with a single click.</p>
            </div>

            <?php echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

            <div class="cwcp-card cwcp-pad">
                <form method="post" class="cwcp-form" novalidate>

                    <?php wp_nonce_field('cwcp_register', 'cwcp_register_nonce'); ?>
                    <input type="hidden" name="cwcp_action" value="register" />

                    <div class="cwcp-hp-field">
                        <label>Website</label>
                        <input type="text" name="cwcp_website" tabindex="-1" autocomplete="off" />
                    </div>

                    <div class="cwcp-grid cwcp-grid-2">

                        <div class="cwcp-form-group">
                            <label class="cwcp-form-label" for="cwcp-first-name">First Name <span class="cwcp-req">*</span></label>
                            <input class="cwcp-form-input" type="text" id="cwcp-first-name" name="first_name" value="<?php echo $value('first_name'); // phpcs:ignore ?>" required />
                        </div>

                        <div class="cwcp-form-group">
                            <label class="cwcp-form-label" for="cwcp-last-name">Last Name <span class="cwcp-req">*</span></label>
                            <input class="cwcp-form-input" type="text" id="cwcp-last-name" name="last_name" value="<?php echo $value('last_name'); // phpcs:ignore ?>" required />
                        </div>

                        <div class="cwcp-form-group">
                            <label class="cwcp-form-label" for="cwcp-email">Email Address <span class="cwcp-req">*</span></label>
                            <input class="cwcp-form-input" type="email" id="cwcp-email" name="email" value="<?php echo $value('email'); // phpcs:ignore ?>" required />
                        </div>

                        <div class="cwcp-form-group">
                            <label class="cwcp-form-label" for="cwcp-mobile">Mobile Number <span class="cwcp-req">*</span></label>
                            <input class="cwcp-form-input" type="tel" id="cwcp-mobile" name="mobile" placeholder="03001234567" value="<?php echo $value('mobile_raw'); // phpcs:ignore ?>" required />
                        </div>

                        <div class="cwcp-form-group">
                            <label class="cwcp-form-label" for="cwcp-password">Password <span class="cwcp-req">*</span></label>
                            <div class="cwcp-password-wrap">
                                <input class="cwcp-form-input" type="password" id="cwcp-password" name="password" minlength="8" required />
                                <button type="button" class="cwcp-password-toggle" data-target="cwcp-password" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <small class="cwcp-help">Minimum 8 characters.</small>
                        </div>

                        <div class="cwcp-form-group">
                            <label class="cwcp-form-label" for="cwcp-confirm-password">Confirm Password <span class="cwcp-req">*</span></label>
                            <div class="cwcp-password-wrap">
                                <input class="cwcp-form-input" type="password" id="cwcp-confirm-password" name="confirm_password" minlength="8" required />
                                <button type="button" class="cwcp-password-toggle" data-target="cwcp-confirm-password" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="cwcp-form-group cwcp-checkbox-group">
                        <label>
                            <input type="checkbox" name="terms" value="1" required />
                            I agree to the terms and conditions and to the processing of my data for recruitment purposes.
                        </label>
                    </div>

                    <button type="submit" class="cwcp-btn-primary cwcp-btn-block">
                        <i class="fa-solid fa-user-plus"></i> Create Account
                    </button>

                    <p class="cwcp-auth-alt">
                        Already registered?
                        <a href="<?php echo esc_url(cwcp_login_url()); ?>">Login here</a>
                    </p>

                </form>
            </div>

        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('carewave_register', 'cwcp_registration_shortcode');
add_shortcode('carewave_candidate_registration', 'cwcp_registration_shortcode');


/*
|--------------------------------------------------------------------------
| Login Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_login_shortcode() {

    if (is_user_logged_in()) {

        $user = wp_get_current_user();

        return '<div class="cwcp-page cwcp-scope cwcp-auth-page alignwide"><div class="cwcp-container cwcp-container-narrow">'
            . '<div class="cwcp-card cwcp-pad">'
            . '<div class="cwcp-empty"><div class="cwcp-empty-icon"><i class="fa-solid fa-user-check"></i></div>'
            . '<h3>You are logged in as ' . esc_html($user->display_name) . '</h3>'
            . '<div class="cwcp-flex cwcp-flex-center cwcp-gap">'
            . '<a class="cwcp-btn-primary" href="' . esc_url(cwcp_dashboard_url()) . '"><i class="fa-solid fa-gauge-high"></i> Dashboard</a> '
            . '<a class="cwcp-btn-secondary" href="' . esc_url(cwcp_logout_url()) . '"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>'
            . '</div></div></div></div></div>';
    }

    $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';

    ob_start();
    ?>
    <div class="cwcp-page cwcp-scope cwcp-auth-page alignwide">
        <div class="cwcp-container cwcp-container-narrow">

            <div class="cwcp-auth-head">
                <div class="cwcp-icon-box"><i class="fa-solid fa-right-to-bracket"></i></div>
                <h1>Candidate Login</h1>
                <p>Log in to manage your profile and applications.</p>
            </div>

            <?php echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

            <div class="cwcp-card cwcp-pad">
                <form method="post" class="cwcp-form" novalidate>

                    <?php wp_nonce_field('cwcp_login', 'cwcp_login_nonce'); ?>
                    <input type="hidden" name="cwcp_action" value="login" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>" />

                    <div class="cwcp-form-group">
                        <label class="cwcp-form-label" for="cwcp-login-username">Email Address <span class="cwcp-req">*</span></label>
                        <input class="cwcp-form-input" type="text" id="cwcp-login-username" name="username" required />
                    </div>

                    <div class="cwcp-form-group">
                        <label class="cwcp-form-label" for="cwcp-login-password">Password <span class="cwcp-req">*</span></label>
                        <div class="cwcp-password-wrap">
                            <input class="cwcp-form-input" type="password" id="cwcp-login-password" name="password" required />
                            <button type="button" class="cwcp-password-toggle" data-target="cwcp-login-password" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="cwcp-flex cwcp-flex-between cwcp-mb-20">
                        <label class="cwcp-inline-check">
                            <input type="checkbox" name="remember" value="1" checked /> Remember me
                        </label>
                        <a href="<?php echo esc_url(cwcp_lost_password_url()); ?>">Forgot password?</a>
                    </div>

                    <button type="submit" class="cwcp-btn-primary cwcp-btn-block">
                        <i class="fa-solid fa-right-to-bracket"></i> Login
                    </button>

                    <p class="cwcp-auth-alt">
                        Do not have an account?
                        <a href="<?php echo esc_url(cwcp_registration_url()); ?>">Register now</a>
                    </p>

                </form>
            </div>

        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('carewave_login', 'cwcp_login_shortcode');


/*
|--------------------------------------------------------------------------
| Lost Password Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_lost_password_shortcode() {

    ob_start();
    ?>
    <div class="cwcp-page cwcp-scope cwcp-auth-page alignwide">
        <div class="cwcp-container cwcp-container-narrow">

            <div class="cwcp-auth-head">
                <div class="cwcp-icon-box"><i class="fa-solid fa-key"></i></div>
                <h1>Forgot your password?</h1>
                <p>Enter your registered email address and we will send you a reset link.</p>
            </div>

            <?php echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

            <div class="cwcp-card cwcp-pad">
                <form method="post" class="cwcp-form" novalidate>

                    <?php wp_nonce_field('cwcp_lost_password', 'cwcp_lost_password_nonce'); ?>
                    <input type="hidden" name="cwcp_action" value="lost_password" />

                    <div class="cwcp-form-group">
                        <label class="cwcp-form-label" for="cwcp-lost-email">Email Address <span class="cwcp-req">*</span></label>
                        <input class="cwcp-form-input" type="email" id="cwcp-lost-email" name="user_login" required />
                    </div>

                    <button type="submit" class="cwcp-btn-primary cwcp-btn-block">
                        <i class="fa-solid fa-paper-plane"></i> Send Reset Link
                    </button>

                    <p class="cwcp-auth-alt">
                        Remembered it?
                        <a href="<?php echo esc_url(cwcp_login_url()); ?>">Back to login</a>
                    </p>

                </form>
            </div>

        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('carewave_lost_password', 'cwcp_lost_password_shortcode');


/*
|--------------------------------------------------------------------------
| Reset Password Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_reset_password_shortcode() {

    $key   = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
    $login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

    ob_start();

    echo '<div class="cwcp-page cwcp-scope cwcp-auth-page alignwide"><div class="cwcp-container cwcp-container-narrow">';

    echo '<div class="cwcp-auth-head">'
        . '<div class="cwcp-icon-box"><i class="fa-solid fa-lock"></i></div>'
        . '<h1>Choose a new password</h1></div>';

    echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput

    $user = ($key && $login) ? check_password_reset_key($key, $login) : new WP_Error('missing', 'missing');

    if (is_wp_error($user)) {

        echo '<div class="cwcp-card cwcp-pad"><div class="cwcp-empty">'
            . '<div class="cwcp-empty-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>'
            . '<h3>This reset link is invalid or has expired</h3>'
            . '<p>Please request a new password reset link.</p>'
            . '<a class="cwcp-btn-primary" href="' . esc_url(cwcp_lost_password_url()) . '">Request New Link</a>'
            . '</div></div>';

    } else {
        ?>
        <div class="cwcp-card cwcp-pad">
            <form method="post" class="cwcp-form" novalidate>

                <?php wp_nonce_field('cwcp_reset_password', 'cwcp_reset_password_nonce'); ?>
                <input type="hidden" name="cwcp_action" value="reset_password" />
                <input type="hidden" name="reset_key" value="<?php echo esc_attr($key); ?>" />
                <input type="hidden" name="reset_login" value="<?php echo esc_attr($login); ?>" />

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-new-password">New Password <span class="cwcp-req">*</span></label>
                    <div class="cwcp-password-wrap">
                        <input class="cwcp-form-input" type="password" id="cwcp-new-password" name="password" minlength="8" required />
                        <button type="button" class="cwcp-password-toggle" data-target="cwcp-new-password" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <small class="cwcp-help">Minimum 8 characters.</small>
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-confirm-new-password">Confirm New Password <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="password" id="cwcp-confirm-new-password" name="confirm_password" minlength="8" required />
                </div>

                <button type="submit" class="cwcp-btn-primary cwcp-btn-block">
                    <i class="fa-solid fa-floppy-disk"></i> Save New Password
                </button>

            </form>
        </div>
        <?php
    }

    echo '</div></div>';

    return ob_get_clean();
}

add_shortcode('carewave_reset_password', 'cwcp_reset_password_shortcode');


/*
|--------------------------------------------------------------------------
| Candidate Logout Link Handler
|--------------------------------------------------------------------------
*/

function cwcp_logout_redirect($redirect_to, $requested, $user) {

    if ($user instanceof WP_User && cwcp_is_candidate($user->ID)) {
        return cwcp_login_url();
    }

    return $redirect_to;
}

add_filter('logout_redirect', 'cwcp_logout_redirect', 10, 3);


/*
|--------------------------------------------------------------------------
| Send Candidates To The Portal, Not wp-admin
|--------------------------------------------------------------------------
*/

function cwcp_login_redirect($redirect_to, $requested, $user) {

    if ($user instanceof WP_User && cwcp_is_candidate($user->ID)) {
        return cwcp_dashboard_url();
    }

    return $redirect_to;
}

add_filter('login_redirect', 'cwcp_login_redirect', 10, 3);

function cwcp_block_candidate_admin_access() {

    if (!is_admin() || wp_doing_ajax()) {
        return;
    }

    if (!is_user_logged_in() || !cwcp_is_candidate()) {
        return;
    }

    cwcp_redirect(cwcp_dashboard_url());
}

add_action('admin_init', 'cwcp_block_candidate_admin_access');

function cwcp_candidate_admin_bar($show) {

    if (is_user_logged_in() && cwcp_is_candidate()) {
        return false;
    }

    return $show;
}

add_filter('show_admin_bar', 'cwcp_candidate_admin_bar');


/*
|--------------------------------------------------------------------------
| Guard: pages that require a logged in candidate
|--------------------------------------------------------------------------
*/

function cwcp_require_login_notice() {

    $redirect = add_query_arg(
        'redirect_to',
        rawurlencode(cwcp_current_page_url()),
        cwcp_login_url()
    );

    return '<div class="cwcp-page cwcp-scope alignwide"><div class="cwcp-container cwcp-container-narrow">'
        . '<div class="cwcp-card cwcp-pad"><div class="cwcp-empty">'
        . '<div class="cwcp-empty-icon"><i class="fa-solid fa-lock"></i></div>'
        . '<h3>Please log in to continue</h3>'
        . '<p>This section is only available to registered candidates.</p>'
        . '<div class="cwcp-flex cwcp-flex-center cwcp-gap">'
        . '<a class="cwcp-btn-primary" href="' . esc_url($redirect) . '"><i class="fa-solid fa-right-to-bracket"></i> Login</a> '
        . '<a class="cwcp-btn-secondary" href="' . esc_url(cwcp_registration_url()) . '"><i class="fa-solid fa-user-plus"></i> Register</a>'
        . '</div></div></div></div></div>';
}

function cwcp_current_page_url() {

    $scheme = is_ssl() ? 'https://' : 'http://';

    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $uri  = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

    if (!$host) {
        return home_url('/');
    }

    return $scheme . $host . $uri;
}
