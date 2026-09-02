<?php
/**
 * CareerHub - Shared helpers.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Database Table Names
|--------------------------------------------------------------------------
*/

function cwcp_table($name) {

    global $wpdb;

    $allowed = array(
        'applications',
        'education',
        'experience',
        'skills',
        'submissions',
        'saved_jobs',
    );

    if (!in_array($name, $allowed, true)) {
        return '';
    }

    return $wpdb->prefix . 'cwcp_' . $name;
}


/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

function cwcp_default_settings() {

    $colors = array();

    if (function_exists('cwcp_color_fields')) {

        foreach (cwcp_color_fields() as $key => $field) {
            $colors[$key] = $field['default'];
        }
    }

    return array(
        'admin_email'          => get_option('admin_email'),
        'notify_admin'         => 1,
        'notify_candidate'     => 1,
        'company_name'         => get_bloginfo('name'),
        'resume_max_size'      => 5,
        'colors'               => $colors,
        'corners'              => 'rounded',
        'style_login'          => 1,
        'hide_pages_from_menus' => 1,
    );
}

function cwcp_get_settings() {

    $saved = get_option('cwcp_settings', array());

    if (!is_array($saved)) {
        $saved = array();
    }

    return wp_parse_args($saved, cwcp_default_settings());
}

function cwcp_setting($key, $fallback = '') {

    $settings = cwcp_get_settings();

    return isset($settings[$key]) ? $settings[$key] : $fallback;
}


/*
|--------------------------------------------------------------------------
| Shortcode Registration
|--------------------------------------------------------------------------
|
| The plugin shipped first as "Care Wave Candidate Portal" with carewave_
| prefixed shortcodes. Every screen is registered under both the current
| careerhub_ name and the original one, so pages written against either name
| keep working.
|
*/

function cwcp_add_shortcode($base, $callback) {

    add_shortcode('careerhub_' . $base, $callback);

    /* Legacy name, kept for existing pages. */
    add_shortcode('carewave_' . $base, $callback);
}


/*
|--------------------------------------------------------------------------
| Portal Pages
|--------------------------------------------------------------------------
|
| Every portal screen is a WordPress page holding one shortcode. The page
| IDs are stored on activation so links keep working if slugs change.
|
*/

function cwcp_page_map() {

    return array(
        'login'             => array('title' => 'Login',                  'slug' => 'login',                  'shortcode' => 'careerhub_login'),
        'register'          => array('title' => 'Registration',           'slug' => 'registration',           'shortcode' => 'careerhub_register'),
        'lost_password'     => array('title' => 'Forgot Password',        'slug' => 'forgot-password',        'shortcode' => 'careerhub_lost_password'),
        'reset_password'    => array('title' => 'Reset Password',         'slug' => 'reset-password',         'shortcode' => 'careerhub_reset_password'),
        'dashboard'         => array('title' => 'Candidate Dashboard',    'slug' => 'candidate-dashboard',    'shortcode' => 'careerhub_dashboard'),
        'profile'           => array('title' => 'My Profile',             'slug' => 'candidate-profile',      'shortcode' => 'careerhub_profile'),
        'education'         => array('title' => 'Education',              'slug' => 'candidate-education',    'shortcode' => 'careerhub_education'),
        'experience'        => array('title' => 'Experience',             'slug' => 'candidate-experience',   'shortcode' => 'careerhub_experience'),
        'skills'            => array('title' => 'Skills',                 'slug' => 'candidate-skills',       'shortcode' => 'careerhub_skills'),
        'resume'            => array('title' => 'Resume',                 'slug' => 'candidate-resume',       'shortcode' => 'careerhub_resume'),
        'applied_jobs'      => array('title' => 'Applied Jobs',           'slug' => 'applied-jobs',           'shortcode' => 'careerhub_applied_jobs'),
        'saved_jobs'        => array('title' => 'Saved Jobs',             'slug' => 'saved-jobs',             'shortcode' => 'careerhub_saved_jobs'),
        'jobs'              => array('title' => 'Jobs',                   'slug' => 'jobs-listing',           'shortcode' => 'careerhub_jobs'),
        'volunteer'         => array('title' => 'Volunteer Form',         'slug' => 'volunteer-form',         'shortcode' => 'careerhub_volunteer_form'),
        'internship'        => array('title' => 'Internship Form',        'slug' => 'internship-form',        'shortcode' => 'careerhub_internship_form'),
        'field_facilitator' => array('title' => 'Field Facilitator Form', 'slug' => 'field-facilitator-form', 'shortcode' => 'careerhub_field_facilitator_form'),
        'tenders'           => array('title' => 'Tenders & Donations',    'slug' => 'tenders',                'shortcode' => 'careerhub_tenders'),
    );
}

function cwcp_get_page_id($key) {

    $pages = get_option('cwcp_pages', array());

    return isset($pages[$key]) ? (int) $pages[$key] : 0;
}

function cwcp_page_url($key, $args = array()) {

    $page_id = cwcp_get_page_id($key);

    if ($page_id && 'publish' === get_post_status($page_id)) {

        $url = get_permalink($page_id);

    } else {

        $map = cwcp_page_map();

        $slug = isset($map[$key]['slug']) ? $map[$key]['slug'] : $key;

        $url = home_url('/' . $slug . '/');
    }

    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }

    return $url;
}

/* Convenience wrappers used across the portal. */

function cwcp_login_url()        { return cwcp_page_url('login'); }
function cwcp_registration_url() { return cwcp_page_url('register'); }
function cwcp_lost_password_url(){ return cwcp_page_url('lost_password'); }
function cwcp_dashboard_url()    { return cwcp_page_url('dashboard'); }
function cwcp_profile_url()      { return cwcp_page_url('profile'); }
function cwcp_resume_url()       { return cwcp_page_url('resume'); }
function cwcp_education_url()    { return cwcp_page_url('education'); }
function cwcp_experience_url()   { return cwcp_page_url('experience'); }
function cwcp_skills_url()       { return cwcp_page_url('skills'); }
function cwcp_applied_jobs_url() { return cwcp_page_url('applied_jobs'); }
function cwcp_saved_jobs_url()   { return cwcp_page_url('saved_jobs'); }
function cwcp_jobs_url()         { return cwcp_page_url('jobs'); }
function cwcp_logout_url()       { return wp_logout_url(cwcp_login_url()); }


/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/

function cwcp_is_candidate($user_id = 0) {

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return false;
    }

    return in_array('carewave_candidate', (array) $user->roles, true);
}

function cwcp_can_manage() {

    return current_user_can('manage_options') || current_user_can('cwcp_manage_portal');
}


/*
|--------------------------------------------------------------------------
| Reference Data
|--------------------------------------------------------------------------
*/

function cwcp_provinces() {

    return array(
        'Punjab'                      => 'Punjab',
        'Sindh'                       => 'Sindh',
        'Khyber Pakhtunkhwa'          => 'Khyber Pakhtunkhwa',
        'Balochistan'                 => 'Balochistan',
        'Gilgit-Baltistan'            => 'Gilgit-Baltistan',
        'Azad Jammu and Kashmir'      => 'Azad Jammu and Kashmir',
        'Islamabad Capital Territory' => 'Islamabad Capital Territory',
    );
}

function cwcp_districts($province = '') {

    $districts = array(

        'Punjab' => array(
            'Attock', 'Bahawalnagar', 'Bahawalpur', 'Bhakkar', 'Chakwal', 'Chiniot',
            'Dera Ghazi Khan', 'Faisalabad', 'Gujranwala', 'Gujrat', 'Hafizabad',
            'Jhang', 'Jhelum', 'Kasur', 'Khanewal', 'Khushab', 'Lahore', 'Layyah',
            'Lodhran', 'Mandi Bahauddin', 'Mianwali', 'Multan', 'Muzaffargarh',
            'Nankana Sahib', 'Narowal', 'Okara', 'Pakpattan', 'Rahim Yar Khan',
            'Rajanpur', 'Rawalpindi', 'Sahiwal', 'Sargodha', 'Sheikhupura',
            'Sialkot', 'Toba Tek Singh', 'Vehari',
        ),

        'Sindh' => array(
            'Badin', 'Dadu', 'Ghotki', 'Hyderabad', 'Jacobabad', 'Jamshoro',
            'Karachi Central', 'Karachi East', 'Karachi South', 'Karachi West',
            'Kashmore', 'Keamari', 'Khairpur', 'Korangi', 'Larkana', 'Malir',
            'Matiari', 'Mirpur Khas', 'Naushahro Feroze', 'Sanghar',
            'Shaheed Benazirabad', 'Shikarpur', 'Sujawal', 'Sukkur',
            'Tando Allahyar', 'Tando Muhammad Khan', 'Tharparkar', 'Thatta',
            'Umerkot',
        ),

        'Khyber Pakhtunkhwa' => array(
            'Abbottabad', 'Bajaur', 'Bannu', 'Battagram', 'Buner', 'Charsadda',
            'Chitral Lower', 'Chitral Upper', 'Dera Ismail Khan', 'Hangu',
            'Haripur', 'Karak', 'Khyber', 'Kohat', 'Kohistan Lower',
            'Kohistan Upper', 'Kurram', 'Lakki Marwat', 'Malakand', 'Mansehra',
            'Mardan', 'Mohmand', 'North Waziristan', 'Nowshera', 'Orakzai',
            'Peshawar', 'Shangla', 'South Waziristan', 'Swabi', 'Swat', 'Tank',
            'Torghar',
        ),

        'Balochistan' => array(
            'Awaran', 'Barkhan', 'Chagai', 'Chaman', 'Dera Bugti', 'Duki',
            'Gwadar', 'Harnai', 'Hub', 'Jafarabad', 'Jhal Magsi', 'Kachhi',
            'Kalat', 'Kech', 'Kharan', 'Khuzdar', 'Killa Abdullah',
            'Killa Saifullah', 'Kohlu', 'Lasbela', 'Loralai', 'Mastung',
            'Musakhel', 'Nasirabad', 'Nushki', 'Panjgur', 'Pishin', 'Quetta',
            'Sherani', 'Sibi', 'Sohbatpur', 'Washuk', 'Zhob', 'Ziarat',
        ),

        'Gilgit-Baltistan' => array(
            'Astore', 'Diamer', 'Ghanche', 'Ghizer', 'Gilgit', 'Hunza',
            'Kharmang', 'Nagar', 'Shigar', 'Skardu',
        ),

        'Azad Jammu and Kashmir' => array(
            'Bagh', 'Bhimber', 'Hattian Bala', 'Haveli', 'Kotli', 'Mirpur',
            'Muzaffarabad', 'Neelum', 'Poonch', 'Sudhanoti',
        ),

        'Islamabad Capital Territory' => array(
            'Islamabad',
        ),
    );

    if ($province) {
        return isset($districts[$province]) ? $districts[$province] : array();
    }

    return $districts;
}

/**
 * Districts as a value => label list for a select field.
 *
 * cwcp_districts() with no province returns the whole province map, so the
 * empty case is answered with an empty list here rather than combining arrays.
 */
function cwcp_district_options($province) {

    if (!$province) {
        return array();
    }

    $districts = cwcp_districts($province);

    if (!$districts) {
        return array();
    }

    return array_combine($districts, $districts);
}

function cwcp_religions() {

    return array(
        'Islam'             => 'Islam',
        'Christianity'      => 'Christianity',
        'Hinduism'          => 'Hinduism',
        'Sikhism'           => 'Sikhism',
        'Parsi'             => 'Parsi',
        'Other'             => 'Other',
        'Prefer not to say' => 'Prefer not to say',
    );
}

function cwcp_marital_statuses() {

    return array(
        'Single'   => 'Single',
        'Married'  => 'Married',
        'Divorced' => 'Divorced',
        'Widowed'  => 'Widowed',
    );
}

function cwcp_genders() {

    return array(
        'Male'   => 'Male',
        'Female' => 'Female',
        'Other'  => 'Other',
    );
}

function cwcp_degree_levels() {

    return array(
        'Matric'        => 'Matric / O-Level',
        'Intermediate'  => 'Intermediate / A-Level',
        'Diploma'       => 'Diploma',
        'Bachelors'     => 'Bachelors',
        'Masters'       => 'Masters',
        'MPhil'         => 'MPhil',
        'PhD'           => 'PhD',
        'Certification' => 'Certification',
    );
}

function cwcp_skill_levels() {

    return array(
        'Beginner'     => 'Beginner',
        'Intermediate' => 'Intermediate',
        'Advanced'     => 'Advanced',
        'Expert'       => 'Expert',
    );
}

function cwcp_application_statuses() {

    return array(
        'new'         => 'New',
        'reviewed'    => 'Under Review',
        'shortlisted' => 'Shortlisted',
        'interview'   => 'Interview',
        'hired'       => 'Hired',
        'rejected'    => 'Not Selected',
    );
}

function cwcp_status_label($status) {

    $statuses = cwcp_application_statuses();

    return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
}

function cwcp_status_badge_class($status) {

    switch ($status) {

        case 'hired':
        case 'shortlisted':
            return 'cwcp-badge-success';

        case 'rejected':
            return 'cwcp-badge-danger';

        case 'interview':
        case 'reviewed':
            return 'cwcp-badge-warning';

        default:
            return 'cwcp-badge-primary';
    }
}

function cwcp_form_types() {

    return array(
        'volunteer'         => 'Volunteer Application',
        'internship'        => 'Internship Application',
        'field_facilitator' => 'Field Facilitator Application',
        'tender'            => 'Tender / Donation Submission',
    );
}


/*
|--------------------------------------------------------------------------
| Validation Helpers
|--------------------------------------------------------------------------
*/

/**
 * Pakistani CNIC: 13 digits, stored and displayed as 00000-0000000-0.
 */
function cwcp_normalize_cnic($cnic) {

    $digits = preg_replace('/\D/', '', (string) $cnic);

    if (13 !== strlen($digits)) {
        return '';
    }

    return substr($digits, 0, 5) . '-' . substr($digits, 5, 7) . '-' . substr($digits, 12, 1);
}

function cwcp_is_valid_cnic($cnic) {

    return '' !== cwcp_normalize_cnic($cnic);
}

/**
 * Mobile numbers are stored as 03xxxxxxxxx.
 */
function cwcp_normalize_mobile($mobile) {

    $digits = preg_replace('/\D/', '', (string) $mobile);

    if (0 === strpos($digits, '92') && 12 === strlen($digits)) {
        $digits = '0' . substr($digits, 2);
    }

    if (11 === strlen($digits) && 0 === strpos($digits, '03')) {
        return $digits;
    }

    if (10 === strlen($digits) && 0 === strpos($digits, '3')) {
        return '0' . $digits;
    }

    return '';
}

function cwcp_is_valid_mobile($mobile) {

    return '' !== cwcp_normalize_mobile($mobile);
}

function cwcp_is_valid_date($date) {

    if (!$date) {
        return false;
    }

    $parts = explode('-', $date);

    if (3 !== count($parts)) {
        return false;
    }

    return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
}

function cwcp_calculate_age($dob) {

    if (!cwcp_is_valid_date($dob)) {
        return 0;
    }

    try {

        $birth = new DateTime($dob);
        $now   = new DateTime('today');

    } catch (Exception $e) {
        return 0;
    }

    return (int) $birth->diff($now)->y;
}


/*
|--------------------------------------------------------------------------
| Notices (survive a redirect)
|--------------------------------------------------------------------------
*/

function cwcp_notice_key() {

    $user_id = get_current_user_id();

    if ($user_id) {
        return 'u' . $user_id;
    }

    return 'g' . md5(
        (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '') .
        (isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '')
    );
}

function cwcp_add_notice($message, $type = 'success') {

    $notices = get_transient('cwcp_notices_' . cwcp_notice_key());

    if (!is_array($notices)) {
        $notices = array();
    }

    $notices[] = array(
        'message' => $message,
        'type'    => $type,
    );

    set_transient('cwcp_notices_' . cwcp_notice_key(), $notices, 300);
}

function cwcp_render_notices() {

    $notices = get_transient('cwcp_notices_' . cwcp_notice_key());

    if (!is_array($notices) || empty($notices)) {
        return '';
    }

    delete_transient('cwcp_notices_' . cwcp_notice_key());

    $html = '';

    foreach ($notices as $notice) {

        $type = in_array($notice['type'], array('success', 'error', 'warning', 'info'), true)
            ? $notice['type']
            : 'info';

        $icon = 'success' === $type
            ? 'fa-circle-check'
            : ('error' === $type
                ? 'fa-circle-exclamation'
                : ('warning' === $type ? 'fa-triangle-exclamation' : 'fa-circle-info'));

        $html .= '<div class="cwcp-alert cwcp-alert-' . esc_attr($type) . '">'
            . '<i class="fa-solid ' . esc_attr($icon) . '"></i>'
            . '<span>' . wp_kses_post($notice['message']) . '</span>'
            . '</div>';
    }

    return $html;
}


/*
|--------------------------------------------------------------------------
| Misc
|--------------------------------------------------------------------------
*/

function cwcp_redirect($url) {

    wp_safe_redirect($url);
    exit;
}

function cwcp_get_ip() {

    if (isset($_SERVER['REMOTE_ADDR'])) {
        return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    return '';
}

/**
 * Simple per-IP throttle for public forms.
 */
function cwcp_is_throttled($action, $limit = 8, $window = 900) {

    $key = 'cwcp_thr_' . md5($action . '|' . cwcp_get_ip());

    $count = (int) get_transient($key);

    if ($count >= $limit) {
        return true;
    }

    set_transient($key, $count + 1, $window);

    return false;
}

function cwcp_format_date($date, $format = '') {

    if (!$date || '0000-00-00' === substr((string) $date, 0, 10)) {
        return '';
    }

    if (!$format) {
        $format = get_option('date_format');
    }

    $time = strtotime($date);

    return $time ? date_i18n($format, $time) : '';
}

/**
 * Renders a <select> field for the portal forms.
 */
function cwcp_select_options($options, $selected = '', $placeholder = '') {

    $html = '';

    if ('' !== $placeholder) {
        $html .= '<option value="">' . esc_html($placeholder) . '</option>';
    }

    foreach ($options as $value => $label) {

        if (is_int($value)) {
            $value = $label;
        }

        $html .= '<option value="' . esc_attr($value) . '" ' . selected($selected, $value, false) . '>'
            . esc_html($label) . '</option>';
    }

    return $html;
}


/*
|--------------------------------------------------------------------------
| Branding
|--------------------------------------------------------------------------
|
| The two logos are optional files. Every caller degrades to text or a
| dashicon when they are absent, so the plugin never ships a broken image.
|
*/

function cwcp_brand_logo_file($name) {

    $relative = 'assets/images/' . $name;

    return file_exists(CWCP_PATH . $relative) ? CWCP_URL . $relative : '';
}

/**
 * The product mark shown on the setup wizard, the admin menu and the
 * overview screen.
 */
function cwcp_brand_logo_url() {

    return cwcp_brand_logo_file('careerhub-logo.png');
}

/**
 * The maker's mark, shown once in the setup wizard footer.
 */
function cwcp_brand_author_logo_url() {

    return cwcp_brand_logo_file('bm-infinity-logo.png');
}

function cwcp_brand_logo_img($class = 'cwcp-brand-logo', $alt = 'CareerHub') {

    $url = cwcp_brand_logo_url();

    if (!$url) {
        return '';
    }

    return '<img class="' . esc_attr($class) . '" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" />';
}

/**
 * Data URI for the admin menu icon, so WordPress does not have to fetch it
 * on every page load. Falls back to a dashicon when the file is missing.
 */
function cwcp_brand_menu_icon() {

    $url = cwcp_brand_logo_url();

    return $url ? $url : 'dashicons-groups';
}
