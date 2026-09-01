<?php
/**
 * Care Wave Candidate Portal - Colour scheme.
 *
 * One palette, chosen by the administrator, drives the whole portal, the
 * plugin's admin screens and the WordPress login page. Every colour is stored
 * as a hex value and printed as a CSS custom property, so nothing has to be
 * recompiled when it changes.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Palette Definition
|--------------------------------------------------------------------------
|
| The defaults mirror the values in assets/css/global.css, so an untouched
| installation looks exactly as it did before any colour was picked.
|
*/

function cwcp_color_fields() {

    return array(

        'primary' => array(
            'label'       => 'Primary / Brand',
            'default'     => '#1d4ed8',
            'description' => 'Buttons, links, active menu items and progress bars.',
        ),

        'text' => array(
            'label'       => 'Body Text',
            'default'     => '#172033',
            'description' => 'Headings and body copy.',
        ),

        'muted' => array(
            'label'       => 'Muted Text',
            'default'     => '#7b8495',
            'description' => 'Secondary labels, meta lines and help text.',
        ),

        'background' => array(
            'label'       => 'Page Background',
            'default'     => '#f7f8fc',
            'description' => 'The area behind the cards.',
        ),

        'surface' => array(
            'label'       => 'Card Background',
            'default'     => '#ffffff',
            'description' => 'Cards, tables and form panels.',
        ),

        'border' => array(
            'label'       => 'Borders',
            'default'     => '#e6e9ef',
            'description' => 'Card outlines, table rules and input borders.',
        ),

        'success' => array(
            'label'       => 'Success',
            'default'     => '#16a34a',
            'description' => 'Completed steps, shortlisted and hired badges.',
        ),

        'warning' => array(
            'label'       => 'Warning',
            'default'     => '#ea8b19',
            'description' => 'Incomplete account notices and pending statuses.',
        ),

        'danger' => array(
            'label'       => 'Danger',
            'default'     => '#dc2626',
            'description' => 'Errors, delete actions and rejected applications.',
        ),
    );
}

function cwcp_corner_styles() {

    return array(
        'sharp'   => array('label' => 'Square',  'radius' => '3px',  'large' => '4px'),
        'rounded' => array('label' => 'Rounded', 'radius' => '7px',  'large' => '10px'),
        'soft'    => array('label' => 'Extra rounded', 'radius' => '12px', 'large' => '18px'),
    );
}

/**
 * Ready made schemes, offered as one click presets in the settings screen.
 */
function cwcp_color_presets() {

    return array(

        'carewave' => array(
            'label'  => 'Care Wave Blue',
            'colors' => array('primary' => '#1d4ed8', 'success' => '#16a34a', 'warning' => '#ea8b19', 'danger' => '#dc2626'),
        ),

        'teal' => array(
            'label'  => 'Teal',
            'colors' => array('primary' => '#0d9488', 'success' => '#15803d', 'warning' => '#d97706', 'danger' => '#dc2626'),
        ),

        'green' => array(
            'label'  => 'Forest Green',
            'colors' => array('primary' => '#15803d', 'success' => '#16a34a', 'warning' => '#ca8a04', 'danger' => '#b91c1c'),
        ),

        'maroon' => array(
            'label'  => 'Maroon',
            'colors' => array('primary' => '#9d174d', 'success' => '#15803d', 'warning' => '#d97706', 'danger' => '#b91c1c'),
        ),

        'charcoal' => array(
            'label'  => 'Charcoal',
            'colors' => array('primary' => '#334155', 'success' => '#16a34a', 'warning' => '#ea8b19', 'danger' => '#dc2626'),
        ),
    );
}


/*
|--------------------------------------------------------------------------
| Stored Values
|--------------------------------------------------------------------------
*/

function cwcp_get_colors() {

    $saved = cwcp_setting('colors', array());

    if (!is_array($saved)) {
        $saved = array();
    }

    $colors = array();

    foreach (cwcp_color_fields() as $key => $field) {

        $value = isset($saved[$key]) ? sanitize_hex_color($saved[$key]) : '';

        $colors[$key] = $value ? $value : $field['default'];
    }

    return $colors;
}

function cwcp_color($key) {

    $colors = cwcp_get_colors();

    return isset($colors[$key]) ? $colors[$key] : '';
}

function cwcp_corner_style() {

    $corners = cwcp_corner_styles();

    $chosen = cwcp_setting('corners', 'rounded');

    return isset($corners[$chosen]) ? $corners[$chosen] : $corners['rounded'];
}


/*
|--------------------------------------------------------------------------
| Colour Maths
|--------------------------------------------------------------------------
|
| The hover and tint variants are derived from the chosen colours, so an
| administrator only picks nine values instead of twenty.
|
*/

function cwcp_hex_to_rgb($hex) {

    $hex = ltrim((string) $hex, '#');

    if (3 === strlen($hex)) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    if (6 !== strlen($hex) || !ctype_xdigit($hex)) {
        return array(0, 0, 0);
    }

    return array(
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    );
}

function cwcp_rgb_to_hex($rgb) {

    return sprintf(
        '#%02x%02x%02x',
        max(0, min(255, (int) round($rgb[0]))),
        max(0, min(255, (int) round($rgb[1]))),
        max(0, min(255, (int) round($rgb[2])))
    );
}

/**
 * Mixes a colour towards black (negative) or white (positive).
 *
 * @param string $hex    Base colour.
 * @param float  $amount -1 to 1.
 */
function cwcp_mix($hex, $amount) {

    $rgb = cwcp_hex_to_rgb($hex);

    $target = $amount >= 0 ? 255 : 0;

    $weight = abs($amount);

    foreach ($rgb as $i => $channel) {
        $rgb[$i] = $channel + (($target - $channel) * $weight);
    }

    return cwcp_rgb_to_hex($rgb);
}

function cwcp_rgba($hex, $alpha) {

    $rgb = cwcp_hex_to_rgb($hex);

    return 'rgba(' . (int) $rgb[0] . ', ' . (int) $rgb[1] . ', ' . (int) $rgb[2] . ', ' . $alpha . ')';
}

/**
 * Readable text colour for a filled button or badge.
 */
function cwcp_contrast_color($hex) {

    $rgb = cwcp_hex_to_rgb($hex);

    $luminance = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;

    return $luminance > 0.6 ? '#172033' : '#ffffff';
}


/*
|--------------------------------------------------------------------------
| CSS Custom Properties
|--------------------------------------------------------------------------
*/

function cwcp_color_variables() {

    $c = cwcp_get_colors();

    $corners = cwcp_corner_style();

    $vars = array(
        '--cwcp-primary'        => $c['primary'],
        '--cwcp-primary-dark'   => cwcp_mix($c['primary'], -0.18),
        '--cwcp-primary-light'  => cwcp_mix($c['primary'], 0.92),
        '--cwcp-on-primary'     => cwcp_contrast_color($c['primary']),

        '--cwcp-success'        => $c['success'],
        '--cwcp-success-light'  => cwcp_mix($c['success'], 0.92),

        '--cwcp-danger'         => $c['danger'],
        '--cwcp-danger-light'   => cwcp_mix($c['danger'], 0.93),

        '--cwcp-warning'        => $c['warning'],
        '--cwcp-warning-light'  => cwcp_mix($c['warning'], 0.9),

        '--cwcp-text'           => $c['text'],
        '--cwcp-text-dark'      => cwcp_mix($c['text'], -0.08),
        '--cwcp-text-muted'     => $c['muted'],
        '--cwcp-text-light'     => cwcp_mix($c['muted'], 0.25),

        '--cwcp-border'         => $c['border'],
        '--cwcp-border-light'   => cwcp_mix($c['border'], 0.4),

        '--cwcp-background'     => $c['background'],
        '--cwcp-white'          => $c['surface'],

        '--cwcp-radius'         => $corners['radius'],
        '--cwcp-radius-large'   => $corners['large'],

        '--cwcp-shadow'         => '0 8px 30px ' . cwcp_rgba($c['text'], 0.06),
    );

    return $vars;
}

function cwcp_color_css() {

    $css = ':root{';

    foreach (cwcp_color_variables() as $name => $value) {
        $css .= $name . ':' . $value . ';';
    }

    $css .= '}';

    /*
     * A few rules in global.css hard code the original blue and white, so they
     * are re-pointed at the chosen palette here.
     */

    $primary  = cwcp_color('primary');
    $on_brand = cwcp_contrast_color($primary);

    $css .= '.cwcp-btn-primary,.cwcp-btn-primary:hover,.cwcp-sidebar-link.is-active,'
        . '.cwcp-pill.is-active,.cwcp-pagination .page-numbers.current,'
        . '.cwcp-pagination .page-numbers:hover{color:' . $on_brand . ' !important;}';

    $css .= '.cwcp-form-input:focus{box-shadow:0 0 0 3px ' . cwcp_rgba($primary, 0.12) . ';}';

    $css .= '.cwcp-timeline-dot{box-shadow:0 0 0 2px ' . cwcp_rgba($primary, 0.15) . ';}';

    $css .= '.cwcp-job-card:hover{border-color:' . cwcp_mix($primary, 0.6) . ';}';

    $css .= '.cwcp-apply-box{border-color:' . cwcp_mix($primary, 0.6) . ';}';

    return $css;
}

/**
 * Attaches the palette to the portal stylesheet.
 */
function cwcp_enqueue_color_variables() {

    if (wp_style_is('cwcp-portal', 'enqueued')) {

        wp_add_inline_style('cwcp-portal', cwcp_color_css());

    } elseif (wp_style_is('cwcp-global', 'enqueued')) {

        wp_add_inline_style('cwcp-global', cwcp_color_css());
    }
}

add_action('wp_enqueue_scripts', 'cwcp_enqueue_color_variables', 20);

/**
 * The plugin's own admin screens follow the same palette.
 */
function cwcp_enqueue_admin_color_variables() {

    if (wp_style_is('cwcp-admin', 'enqueued')) {
        wp_add_inline_style('cwcp-admin', cwcp_color_css());
    }
}

add_action('admin_enqueue_scripts', 'cwcp_enqueue_admin_color_variables', 20);


/*
|--------------------------------------------------------------------------
| WordPress Login Screen
|--------------------------------------------------------------------------
|
| Candidates can log in either through the portal login page or through
| wp-login.php. When branding is enabled the WordPress screen is restyled with
| the same palette and the site logo, so both routes look like one site.
|
*/

function cwcp_login_branding_enabled() {

    return (bool) cwcp_setting('style_login', 1);
}

function cwcp_login_logo_url() {

    $logo_id = get_theme_mod('custom_logo');

    if ($logo_id) {

        $url = wp_get_attachment_image_url($logo_id, 'medium');

        if ($url) {
            return $url;
        }
    }

    return '';
}

function cwcp_login_styles() {

    if (!cwcp_login_branding_enabled()) {
        return;
    }

    $primary  = cwcp_color('primary');
    $on_brand = cwcp_contrast_color($primary);

    $corners = cwcp_corner_style();

    $logo = cwcp_login_logo_url();

    ?>
    <style id="cwcp-login-styles">
        body.login {
            background: <?php echo esc_html(cwcp_color('background')); ?>;
            color: <?php echo esc_html(cwcp_color('text')); ?>;
        }

        <?php if ($logo) : ?>
        #login h1 a,
        .login h1 a {
            background-image: url("<?php echo esc_url($logo); ?>");
            background-size: contain;
            background-position: center center;
            width: 100%;
            height: 72px;
            margin-bottom: 18px;
        }
        <?php else : ?>
        #login h1 a,
        .login h1 a {
            color: <?php echo esc_html($primary); ?>;
        }
        <?php endif; ?>

        #login {
            padding-top: 6%;
        }

        .login form {
            border: 1px solid <?php echo esc_html(cwcp_color('border')); ?>;
            border-radius: <?php echo esc_html($corners['large']); ?>;
            background: <?php echo esc_html(cwcp_color('surface')); ?>;
            box-shadow: 0 8px 30px <?php echo esc_html(cwcp_rgba(cwcp_color('text'), 0.06)); ?>;
        }

        .login label,
        .login form .input,
        .login input[type="text"],
        .login input[type="password"] {
            color: <?php echo esc_html(cwcp_color('text')); ?>;
        }

        .login form .input,
        .login input[type="text"],
        .login input[type="password"] {
            border: 1px solid <?php echo esc_html(cwcp_color('border')); ?>;
            border-radius: <?php echo esc_html($corners['radius']); ?>;
        }

        .login form .input:focus,
        .login input[type="text"]:focus,
        .login input[type="password"]:focus,
        .login input[type="checkbox"]:focus {
            border-color: <?php echo esc_html($primary); ?>;
            box-shadow: 0 0 0 3px <?php echo esc_html(cwcp_rgba($primary, 0.12)); ?>;
            outline: none;
        }

        .login .button-primary,
        .wp-core-ui .button-primary {
            border: 0;
            border-radius: <?php echo esc_html($corners['radius']); ?>;
            background: <?php echo esc_html($primary); ?>;
            color: <?php echo esc_html($on_brand); ?>;
            text-shadow: none;
            box-shadow: none;
        }

        .login .button-primary:hover,
        .login .button-primary:focus,
        .wp-core-ui .button-primary:hover {
            background: <?php echo esc_html(cwcp_mix($primary, -0.18)); ?>;
            color: <?php echo esc_html($on_brand); ?>;
        }

        .login #nav a,
        .login #backtoblog a,
        .login .privacy-policy-page-link a {
            color: <?php echo esc_html(cwcp_color('muted')); ?>;
        }

        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: <?php echo esc_html($primary); ?>;
        }

        .login #login_error,
        .login .message,
        .login .success {
            border-left-color: <?php echo esc_html($primary); ?>;
            border-radius: <?php echo esc_html($corners['radius']); ?>;
        }

        .login #login_error {
            border-left-color: <?php echo esc_html(cwcp_color('danger')); ?>;
        }

        .login .cwcp-login-portal-link {
            display: block;
            margin-top: 16px;
            color: <?php echo esc_html(cwcp_color('muted')); ?>;
            font-size: 13px;
            text-align: center;
        }

        .login .cwcp-login-portal-link a {
            color: <?php echo esc_html($primary); ?>;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
    <?php
}

add_action('login_enqueue_scripts', 'cwcp_login_styles');

/**
 * The logo links back to the site, not to wordpress.org.
 */
function cwcp_login_header_url($url) {

    return cwcp_login_branding_enabled() ? home_url('/') : $url;
}

add_filter('login_headerurl', 'cwcp_login_header_url');

function cwcp_login_header_text($text) {

    return cwcp_login_branding_enabled() ? cwcp_setting('company_name', get_bloginfo('name')) : $text;
}

add_filter('login_headertext', 'cwcp_login_header_text');

/**
 * Points candidates at the portal from the WordPress login screen.
 */
function cwcp_login_footer_links() {

    if (!cwcp_login_branding_enabled()) {
        return;
    }

    echo '<p class="cwcp-login-portal-link">Applying for a job? '
        . '<a href="' . esc_url(cwcp_login_url()) . '">Candidate login</a>'
        . ' &middot; <a href="' . esc_url(cwcp_registration_url()) . '">Register</a></p>';
}

add_action('login_footer', 'cwcp_login_footer_links');


/*
|--------------------------------------------------------------------------
| Theme Menus
|--------------------------------------------------------------------------
|
| Activation creates a page per portal screen. Themes that print an automatic
| page list - the core Page List block used by most block themes, or
| wp_list_pages() in a classic theme - would otherwise dump all of them into
| the header navigation. The candidate only screens are therefore hidden from
| those automatic lists; they are reached from the portal sidebar instead.
| Pages added to a real menu by hand are never affected.
|
*/

function cwcp_private_page_keys() {

    return array(
        'dashboard',
        'profile',
        'education',
        'experience',
        'skills',
        'resume',
        'applied_jobs',
        'saved_jobs',
        'lost_password',
        'reset_password',
    );
}

function cwcp_hidden_page_ids() {

    if (!cwcp_setting('hide_pages_from_menus', 1)) {
        return array();
    }

    $ids = array();

    foreach (cwcp_private_page_keys() as $key) {

        $id = cwcp_get_page_id($key);

        if ($id) {
            $ids[] = (int) $id;
        }
    }

    return $ids;
}

function cwcp_filter_get_pages($pages, $args = array()) {

    if (is_admin() || empty($pages) || !is_array($pages)) {
        return $pages;
    }

    $hidden = cwcp_hidden_page_ids();

    if (!$hidden) {
        return $pages;
    }

    foreach ($pages as $index => $page) {

        $id = is_object($page) ? (int) $page->ID : (int) $page;

        if (in_array($id, $hidden, true)) {
            unset($pages[$index]);
        }
    }

    return array_values($pages);
}

add_filter('get_pages', 'cwcp_filter_get_pages', 10, 2);

function cwcp_filter_list_pages_excludes($excludes) {

    if (is_admin()) {
        return $excludes;
    }

    return array_merge((array) $excludes, cwcp_hidden_page_ids());
}

add_filter('wp_list_pages_excludes', 'cwcp_filter_list_pages_excludes');


/*
|--------------------------------------------------------------------------
| Theme Palette Detection
|--------------------------------------------------------------------------
|
| Block themes expose a palette through theme.json and classic themes through
| add_theme_support('editor-color-palette'). Either way the administrator can
| click a swatch instead of typing a hex value.
|
*/

function cwcp_theme_palette() {

    $palette = array();

    if (function_exists('wp_get_global_settings')) {

        $settings = wp_get_global_settings(array('color', 'palette'));

        if (is_array($settings)) {

            foreach (array('theme', 'custom', 'default') as $origin) {

                if (empty($settings[$origin]) || !is_array($settings[$origin])) {
                    continue;
                }

                foreach ($settings[$origin] as $entry) {

                    if (empty($entry['color'])) {
                        continue;
                    }

                    $hex = sanitize_hex_color($entry['color']);

                    if ($hex) {
                        $palette[$hex] = isset($entry['name']) ? $entry['name'] : $hex;
                    }
                }

                /* Theme colours are the most relevant; stop once found. */
                if ($palette) {
                    break;
                }
            }
        }
    }

    if (!$palette) {

        $support = get_theme_support('editor-color-palette');

        if (!empty($support[0]) && is_array($support[0])) {

            foreach ($support[0] as $entry) {

                if (empty($entry['color'])) {
                    continue;
                }

                $hex = sanitize_hex_color($entry['color']);

                if ($hex) {
                    $palette[$hex] = isset($entry['name']) ? $entry['name'] : $hex;
                }
            }
        }
    }

    return $palette;
}
