<?php
/**
 * CareerHub - Setup wizard.
 *
 * Runs once when the plugin is first activated, and stays reachable from the
 * CareerHub menu afterwards. It asks two questions:
 *
 *   1. Should the portal pages be created automatically from shortcodes, or
 *      will they be built by hand with the Elementor widgets?
 *   2. Which colour theme should the portal use?
 *
 * Before this existed, activation silently created seventeen pages whether the
 * site wanted them or not.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

function cwcp_setup_is_complete() {

    return (bool) get_option('cwcp_setup_complete', 0);
}

/**
 * 'auto'   - pages are generated from cwcp_page_map() and hold a shortcode.
 * 'manual' - the site builds its own pages with the Elementor widgets, so
 *            nothing is created and nothing is recreated on upgrade.
 */
function cwcp_page_mode() {

    return 'manual' === get_option('cwcp_page_mode', 'auto') ? 'manual' : 'auto';
}

function cwcp_setup_url($step = 1) {

    return admin_url('admin.php?page=cwcp-setup&step=' . (int) $step);
}


/*
|--------------------------------------------------------------------------
| Redirect On First Activation
|--------------------------------------------------------------------------
*/

function cwcp_setup_maybe_redirect() {

    if (!get_transient('cwcp_setup_redirect')) {
        return;
    }

    delete_transient('cwcp_setup_redirect');

    /* Bulk or network activation must not hijack the screen. */

    if (isset($_GET['activate-multi']) || is_network_admin()) {
        return;
    }

    if (!cwcp_can_manage()) {
        return;
    }

    wp_safe_redirect(cwcp_setup_url(1));

    exit;
}

add_action('admin_init', 'cwcp_setup_maybe_redirect');


/*
|--------------------------------------------------------------------------
| Step Handlers
|--------------------------------------------------------------------------
*/

/**
 * Step 1 - how the pages get built.
 *
 * @return array Page keys created this run, for the summary on step 3.
 */
function cwcp_setup_handle_pages() {

    check_admin_referer('cwcp_setup_pages', 'cwcp_setup_nonce');

    $mode = isset($_POST['page_mode']) ? sanitize_key(wp_unslash($_POST['page_mode'])) : 'auto';

    $mode = 'manual' === $mode ? 'manual' : 'auto';

    update_option('cwcp_page_mode', $mode);

    if ('auto' !== $mode) {

        update_option('cwcp_setup_created', array());

        return array();
    }

    $created = cwcp_install_pages();

    update_option('cwcp_setup_created', $created);

    return $created;
}

/**
 * Step 2 - the colour theme.
 */
function cwcp_setup_handle_theme() {

    check_admin_referer('cwcp_setup_theme', 'cwcp_setup_nonce');

    $settings = cwcp_get_settings();

    $preset_key = isset($_POST['preset']) ? sanitize_key(wp_unslash($_POST['preset'])) : '';

    $presets = cwcp_color_presets();

    $colors = isset($settings['colors']) && is_array($settings['colors']) ? $settings['colors'] : array();

    /* Start from the defaults so a half filled option row cannot survive. */

    foreach (cwcp_color_fields() as $key => $field) {

        if (empty($colors[$key]) || !sanitize_hex_color($colors[$key])) {
            $colors[$key] = $field['default'];
        }
    }

    if (isset($presets[$preset_key])) {

        foreach ($presets[$preset_key]['colors'] as $key => $hex) {
            $colors[$key] = $hex;
        }
    }

    /* An explicit brand colour beats the preset it was picked next to. */

    $custom = isset($_POST['custom_primary']) ? sanitize_hex_color(wp_unslash($_POST['custom_primary'])) : '';

    if ($custom) {
        $colors['primary'] = $custom;
    }

    $settings['colors'] = $colors;

    $corners = isset($_POST['corners']) ? sanitize_key(wp_unslash($_POST['corners'])) : 'rounded';

    $settings['corners'] = array_key_exists($corners, cwcp_corner_styles()) ? $corners : 'rounded';

    update_option('cwcp_settings', $settings);
    update_option('cwcp_theme_preset', isset($presets[$preset_key]) ? $preset_key : '');
    update_option('cwcp_setup_complete', 1);
}


/*
|--------------------------------------------------------------------------
| Screen
|--------------------------------------------------------------------------
*/

function cwcp_render_setup_page() {

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    $step = isset($_GET['step']) ? max(1, min(3, (int) $_GET['step'])) : 1;

    if (isset($_POST['cwcp_setup_nonce'])) {

        $posted = isset($_POST['setup_step']) ? (int) $_POST['setup_step'] : 0;

        if (1 === $posted) {

            cwcp_setup_handle_pages();

            wp_safe_redirect(cwcp_setup_url(2));

            exit;
        }

        if (2 === $posted) {

            cwcp_setup_handle_theme();

            wp_safe_redirect(cwcp_setup_url(3));

            exit;
        }
    }

    ?>
    <div class="wrap cwcp-admin cwcp-setup">

        <?php cwcp_setup_header($step); ?>

        <?php
        if (1 === $step) {
            cwcp_setup_step_pages();
        } elseif (2 === $step) {
            cwcp_setup_step_theme();
        } else {
            cwcp_setup_step_done();
        }
        ?>

        <?php cwcp_setup_footer(); ?>

    </div>
    <?php
}


/*
|--------------------------------------------------------------------------
| Chrome
|--------------------------------------------------------------------------
*/

function cwcp_setup_header($step) {

    $steps = array(
        1 => 'Pages',
        2 => 'Theme',
        3 => 'Finish',
    );

    $logo = cwcp_brand_logo_url();

    ?>
    <div class="cwcp-setup-head">

        <?php if ($logo) : ?>
            <img class="cwcp-setup-logo" src="<?php echo esc_url($logo); ?>" alt="CareerHub" />
        <?php else : ?>
            <div class="cwcp-setup-logo cwcp-setup-logo-fallback"><span class="dashicons dashicons-groups"></span></div>
        <?php endif; ?>

        <div class="cwcp-setup-head-text">
            <h1>CareerHub Setup</h1>
            <p>Two questions and the portal is ready.</p>
        </div>

    </div>

    <ol class="cwcp-setup-steps">
        <?php foreach ($steps as $number => $label) : ?>
            <li class="<?php echo $number === $step ? 'is-active' : ($number < $step ? 'is-done' : ''); ?>">
                <span class="cwcp-setup-step-number"><?php echo (int) $number; ?></span>
                <span class="cwcp-setup-step-label"><?php echo esc_html($label); ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php
}

function cwcp_setup_footer() {

    $author_logo = cwcp_brand_author_logo_url();

    ?>
    <div class="cwcp-setup-foot">
        <?php if ($author_logo) : ?>
            <img class="cwcp-setup-author-logo" src="<?php echo esc_url($author_logo); ?>" alt="BM Infinity Tech Solutions" />
        <?php endif; ?>
        <span>CareerHub <?php echo esc_html(CWCP_VERSION); ?> &middot; by BM Infinity Tech Solutions</span>
    </div>
    <?php
}


/*
|--------------------------------------------------------------------------
| Step 1 - Pages
|--------------------------------------------------------------------------
*/

function cwcp_setup_step_pages() {

    $map = cwcp_page_map();

    $elementor = cwcp_elementor_is_active();

    ?>
    <form method="post" class="cwcp-setup-card">

        <?php wp_nonce_field('cwcp_setup_pages', 'cwcp_setup_nonce'); ?>
        <input type="hidden" name="setup_step" value="1" />

        <h2>How should the portal pages be built?</h2>

        <p class="cwcp-setup-lead">
            The portal needs <?php echo count($map); ?> pages - login, registration, the candidate
            screens, the job listing and the application forms. Pick how they get made.
        </p>

        <div class="cwcp-setup-choices">

            <label class="cwcp-setup-choice">

                <input type="radio" name="page_mode" value="auto" checked="checked" />

                <span class="cwcp-setup-choice-body">
                    <span class="cwcp-setup-choice-icon dashicons dashicons-superhero"></span>
                    <strong>Create everything for me</strong>
                    <span class="cwcp-setup-choice-tag">Recommended</span>
                    <span class="cwcp-setup-choice-text">
                        Builds all <?php echo count($map); ?> pages now, each holding one shortcode.
                        Nothing to wire up - the menu links, redirects and emails all point at
                        them straight away. You can still restyle any page later.
                    </span>
                </span>
            </label>

            <label class="cwcp-setup-choice">

                <input type="radio" name="page_mode" value="manual" />

                <span class="cwcp-setup-choice-body">
                    <span class="cwcp-setup-choice-icon dashicons dashicons-art"></span>
                    <strong>I will build them in Elementor</strong>
                    <?php if (!$elementor) : ?>
                        <span class="cwcp-setup-choice-tag cwcp-setup-choice-tag-warn">Elementor not active</span>
                    <?php endif; ?>
                    <span class="cwcp-setup-choice-text">
                        Creates nothing. You add each screen yourself with the CareerHub widgets
                        in Elementor and design it in the panel. Use the slugs listed on the last
                        step so the portal's own links keep working.
                    </span>
                </span>
            </label>

        </div>

        <?php if (!$elementor) : ?>
            <p class="cwcp-setup-note">
                <span class="dashicons dashicons-info"></span>
                Elementor is not active on this site. The manual option still works, but the
                CareerHub widgets only appear in the panel once Elementor is installed.
            </p>
        <?php endif; ?>

        <details class="cwcp-setup-details">

            <summary>See the <?php echo count($map); ?> pages</summary>

            <table class="widefat striped">
                <thead>
                    <tr><th>Page</th><th>Slug</th><th>Shortcode</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($map as $page) : ?>
                        <tr>
                            <td><?php echo esc_html($page['title']); ?></td>
                            <td><code><?php echo esc_html($page['slug']); ?></code></td>
                            <td><code>[<?php echo esc_html($page['shortcode']); ?>]</code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </details>

        <p class="cwcp-setup-actions">
            <button type="submit" class="button button-primary button-hero">Continue</button>
        </p>

    </form>
    <?php
}


/*
|--------------------------------------------------------------------------
| Step 2 - Theme
|--------------------------------------------------------------------------
*/

function cwcp_setup_step_theme() {

    $settings = cwcp_get_settings();

    $current_preset = get_option('cwcp_theme_preset', 'carewave');

    $current_corners = isset($settings['corners']) ? $settings['corners'] : 'rounded';

    ?>
    <form method="post" class="cwcp-setup-card">

        <?php wp_nonce_field('cwcp_setup_theme', 'cwcp_setup_nonce'); ?>
        <input type="hidden" name="setup_step" value="2" />

        <h2>Pick a theme</h2>

        <p class="cwcp-setup-lead">
            This sets the portal's palette everywhere - buttons, links, badges and progress
            bars. Every colour stays editable in <em>Settings</em>, and any Elementor widget
            can override it for one section.
        </p>

        <div class="cwcp-setup-presets">

            <?php foreach (cwcp_color_presets() as $key => $preset) : ?>

                <label class="cwcp-setup-preset">

                    <input type="radio" name="preset" value="<?php echo esc_attr($key); ?>"
                        <?php checked($current_preset, $key); ?> />

                    <span class="cwcp-setup-preset-body">

                        <span class="cwcp-setup-swatches">
                            <?php foreach ($preset['colors'] as $hex) : ?>
                                <span class="cwcp-setup-swatch" style="background: <?php echo esc_attr($hex); ?>"></span>
                            <?php endforeach; ?>
                        </span>

                        <span class="cwcp-setup-preset-label"><?php echo esc_html($preset['label']); ?></span>
                    </span>

                </label>

            <?php endforeach; ?>

        </div>

        <div class="cwcp-setup-field">

            <label for="cwcp-custom-primary"><strong>Or set your own brand colour</strong></label>

            <input type="color" id="cwcp-custom-primary" name="custom_primary" value="" />

            <p class="description">Leave this alone to keep the preset's colour.</p>

        </div>

        <div class="cwcp-setup-field">

            <strong>Corners</strong>

            <div class="cwcp-setup-corners">
                <?php foreach (cwcp_corner_styles() as $key => $corner) : ?>
                    <label class="cwcp-setup-corner">
                        <input type="radio" name="corners" value="<?php echo esc_attr($key); ?>"
                            <?php checked($current_corners, $key); ?> />
                        <span class="cwcp-setup-corner-box" style="border-radius: <?php echo esc_attr($corner['large']); ?>"></span>
                        <span><?php echo esc_html($corner['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

        </div>

        <p class="cwcp-setup-actions">
            <a class="button" href="<?php echo esc_url(cwcp_setup_url(1)); ?>">Back</a>
            <button type="submit" class="button button-primary button-hero">Save and finish</button>
        </p>

    </form>
    <?php
}


/*
|--------------------------------------------------------------------------
| Step 3 - Done
|--------------------------------------------------------------------------
*/

function cwcp_setup_step_done() {

    $mode = cwcp_page_mode();

    $map = cwcp_page_map();

    $created = get_option('cwcp_setup_created', array());

    if (!is_array($created)) {
        $created = array();
    }

    ?>
    <div class="cwcp-setup-card">

        <h2><span class="dashicons dashicons-yes-alt"></span> CareerHub is ready</h2>

        <?php if ('auto' === $mode) : ?>

            <p class="cwcp-setup-lead">
                <?php if ($created) : ?>
                    Created <?php echo count($created); ?> new
                    page<?php echo count($created) === 1 ? '' : 's'; ?>.
                    Pages that already existed were left alone.
                <?php else : ?>
                    All <?php echo count($map); ?> pages were already in place, so nothing was changed.
                <?php endif; ?>
            </p>

            <table class="widefat striped cwcp-setup-table">
                <thead>
                    <tr><th>Page</th><th>Status</th><th>View</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($map as $key => $page) : ?>
                        <?php $page_id = cwcp_get_page_id($key); ?>
                        <tr>
                            <td><strong><?php echo esc_html($page['title']); ?></strong></td>
                            <td>
                                <?php if (in_array($key, $created, true)) : ?>
                                    <span class="cwcp-setup-pill cwcp-setup-pill-new">Created</span>
                                <?php elseif ($page_id) : ?>
                                    <span class="cwcp-setup-pill">Already there</span>
                                <?php else : ?>
                                    <span class="cwcp-setup-pill cwcp-setup-pill-warn">Missing</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($page_id) : ?>
                                    <a href="<?php echo esc_url(get_permalink($page_id)); ?>" target="_blank" rel="noopener">Open</a>
                                    &middot;
                                    <a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>">Edit</a>
                                <?php else : ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else : ?>

            <p class="cwcp-setup-lead">
                No pages were created. Build each screen yourself: add a page at the slug below,
                edit it with Elementor, then drop in the matching widget from the
                <strong>CareerHub</strong> section of the widget panel.
            </p>

            <p class="cwcp-setup-note">
                <span class="dashicons dashicons-info"></span>
                The slug matters. Portal links, login redirects and emails fall back to these
                paths, so a page at a different slug will not be linked to.
            </p>

            <table class="widefat striped cwcp-setup-table">
                <thead>
                    <tr><th>Screen</th><th>Page slug</th><th>Elementor widget</th><th>Or shortcode</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($map as $key => $page) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($page['title']); ?></strong></td>
                            <td><code><?php echo esc_html($page['slug']); ?></code></td>
                            <td><?php echo esc_html(cwcp_setup_widget_label($key)); ?></td>
                            <td><code>[<?php echo esc_html($page['shortcode']); ?>]</code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

        <p class="cwcp-setup-actions">
            <a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-overview')); ?>">
                Go to CareerHub
            </a>
            <a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=cw_job')); ?>">Post your first job</a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-settings')); ?>">Settings</a>
        </p>

    </div>
    <?php
}

/**
 * Page key to the Elementor widget that renders the same screen.
 */
function cwcp_setup_widget_label($page_key) {

    $widgets = array(
        'login'             => 'Candidate Login',
        'register'          => 'Candidate Registration',
        'lost_password'     => 'Forgot Password',
        'reset_password'    => 'Reset Password',
        'dashboard'         => 'Candidate Dashboard',
        'profile'           => 'Candidate Profile',
        'education'         => 'Education',
        'experience'        => 'Experience',
        'skills'            => 'Skills',
        'resume'            => 'Resume',
        'applied_jobs'      => 'Applied Jobs',
        'saved_jobs'        => 'Saved Jobs',
        'jobs'              => 'Job Listing',
        'volunteer'         => 'Volunteer Form',
        'internship'        => 'Internship Form',
        'field_facilitator' => 'Field Facilitator Form',
        'tenders'           => 'Tenders & Appeals',
    );

    return isset($widgets[$page_key]) ? $widgets[$page_key] : '-';
}
