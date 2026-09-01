<?php
/**
 * Care Wave Candidate Portal - Settings screen.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}

function cwcp_render_settings_page() {

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    $saved = false;

    if (isset($_POST['cwcp_settings_nonce'])) {

        check_admin_referer('cwcp_save_settings', 'cwcp_settings_nonce');

        $settings = array(
            'company_name'     => isset($_POST['company_name']) ? sanitize_text_field(wp_unslash($_POST['company_name'])) : '',
            'admin_email'      => isset($_POST['admin_email']) ? sanitize_email(wp_unslash($_POST['admin_email'])) : '',
            'notify_admin'     => !empty($_POST['notify_admin']) ? 1 : 0,
            'notify_candidate' => !empty($_POST['notify_candidate']) ? 1 : 0,
            'resume_max_size'  => isset($_POST['resume_max_size']) ? max(1, (int) $_POST['resume_max_size']) : 5,
            'style_login'      => !empty($_POST['style_login']) ? 1 : 0,
            'hide_pages_from_menus' => !empty($_POST['hide_pages_from_menus']) ? 1 : 0,
        );

        /*
         * Colours. An invalid or empty value falls back to the built in
         * default rather than being stored.
         */

        $colors = array();

        foreach (cwcp_color_fields() as $key => $field) {

            $raw = isset($_POST['color_' . $key]) ? sanitize_text_field(wp_unslash($_POST['color_' . $key])) : '';

            $hex = sanitize_hex_color($raw);

            $colors[$key] = $hex ? $hex : $field['default'];
        }

        $settings['colors'] = $colors;

        $corners = isset($_POST['corners']) ? sanitize_key(wp_unslash($_POST['corners'])) : 'rounded';

        $settings['corners'] = array_key_exists($corners, cwcp_corner_styles()) ? $corners : 'rounded';

        update_option('cwcp_settings', $settings);

        if (!empty($_POST['recreate_pages'])) {
            cwcp_install_pages();
        }

        $saved = true;
    }

    $settings = cwcp_get_settings();

    ?>
    <div class="wrap cwcp-admin">

        <h1>Portal Settings</h1>

        <?php if ($saved) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <form method="post">

            <?php wp_nonce_field('cwcp_save_settings', 'cwcp_settings_nonce'); ?>

            <table class="form-table">
                <tbody>

                    <tr>
                        <th><label for="cwcp-company-name">Organization Name</label></th>
                        <td>
                            <input type="text" class="regular-text" id="cwcp-company-name" name="company_name"
                                   value="<?php echo esc_attr($settings['company_name']); ?>" />
                            <p class="description">Shown in the header of every portal email.</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="cwcp-admin-email">Notification Email</label></th>
                        <td>
                            <input type="email" class="regular-text" id="cwcp-admin-email" name="admin_email"
                                   value="<?php echo esc_attr($settings['admin_email']); ?>" />
                            <p class="description">Where new applications and form submissions are announced.</p>
                        </td>
                    </tr>

                    <tr>
                        <th>Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="notify_admin" value="1" <?php checked(1, (int) $settings['notify_admin']); ?> />
                                Notify the organization on new applications and submissions
                            </label><br />
                            <label>
                                <input type="checkbox" name="notify_candidate" value="1" <?php checked(1, (int) $settings['notify_candidate']); ?> />
                                Notify candidates (welcome, application received, status updates)
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="cwcp-resume-size">Max Upload Size (MB)</label></th>
                        <td>
                            <input type="number" min="1" max="20" id="cwcp-resume-size" name="resume_max_size"
                                   value="<?php echo esc_attr($settings['resume_max_size']); ?>" />
                            <p class="description">Applies to resumes and tender documents. PDF, DOC and DOCX only.</p>
                        </td>
                    </tr>

                    <tr>
                        <th>Portal Pages</th>
                        <td>
                            <label>
                                <input type="checkbox" name="recreate_pages" value="1" />
                                Recreate any missing portal pages
                            </label>
                            <p class="description">Use this if a page was deleted by mistake.</p>

                            <p style="margin-top:12px;">
                                <label>
                                    <input type="checkbox" name="hide_pages_from_menus" value="1"
                                        <?php checked(1, (int) cwcp_setting('hide_pages_from_menus', 1)); ?> />
                                    Keep candidate-only pages out of automatic theme menus
                                </label>
                            </p>
                            <p class="description">
                                Themes that print an automatic page list (the Page List block in most block
                                themes) would otherwise show Dashboard, My Profile, Education, Experience,
                                Skills, Resume, Applied Jobs, Saved Jobs and the password pages in your header.
                                Candidates reach those from the portal sidebar. Pages you add to a menu by hand
                                are never hidden.
                            </p>
                        </td>
                    </tr>

                </tbody>
            </table>

            <h2>Colours</h2>

            <p class="description" style="max-width:760px;">
                Match the portal to your theme. These colours are used on every portal screen,
                on the plugin's own admin pages and - when the option below is on - on the
                WordPress login screen.
            </p>

            <?php $colors = cwcp_get_colors(); ?>

            <div class="cwcp-color-presets">

                <strong>Quick schemes:</strong>

                <?php foreach (cwcp_color_presets() as $key => $preset) : ?>
                    <button type="button" class="button cwcp-preset"
                            data-colors="<?php echo esc_attr(wp_json_encode($preset['colors'])); ?>">
                        <span class="cwcp-swatch" style="background: <?php echo esc_attr($preset['colors']['primary']); ?>"></span>
                        <?php echo esc_html($preset['label']); ?>
                    </button>
                <?php endforeach; ?>

            </div>

            <?php $theme_palette = cwcp_theme_palette(); ?>

            <?php if ($theme_palette) : ?>
                <div class="cwcp-color-presets">

                    <strong>From your theme:</strong>

                    <?php foreach ($theme_palette as $hex => $name) : ?>
                        <button type="button" class="button cwcp-theme-color"
                                title="<?php echo esc_attr($name . ' (' . $hex . ')'); ?>"
                                data-color="<?php echo esc_attr($hex); ?>">
                            <span class="cwcp-swatch" style="background: <?php echo esc_attr($hex); ?>"></span>
                            <?php echo esc_html($name); ?>
                        </button>
                    <?php endforeach; ?>

                    <p class="description">
                        Colours declared by your active theme. Click one to use it as the primary colour.
                    </p>

                </div>
            <?php endif; ?>

            <table class="form-table">
                <tbody>

                    <?php foreach (cwcp_color_fields() as $key => $field) : ?>
                        <tr>
                            <th>
                                <label for="cwcp-color-<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($field['label']); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                       class="cwcp-color-field"
                                       id="cwcp-color-<?php echo esc_attr($key); ?>"
                                       name="color_<?php echo esc_attr($key); ?>"
                                       value="<?php echo esc_attr($colors[$key]); ?>"
                                       data-default-color="<?php echo esc_attr($field['default']); ?>"
                                       data-color-key="<?php echo esc_attr($key); ?>" />
                                <p class="description"><?php echo esc_html($field['description']); ?></p>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <tr>
                        <th><label for="cwcp-corners">Corner Style</label></th>
                        <td>
                            <select name="corners" id="cwcp-corners">
                                <?php
                                $corner_options = array();

                                foreach (cwcp_corner_styles() as $corner_key => $corner) {
                                    $corner_options[$corner_key] = $corner['label'];
                                }

                                echo cwcp_select_options($corner_options, cwcp_setting('corners', 'rounded')); // phpcs:ignore WordPress.Security.EscapeOutput
                                ?>
                            </select>
                            <p class="description">How rounded cards, buttons and inputs should be.</p>
                        </td>
                    </tr>

                    <tr>
                        <th>WordPress Login Screen</th>
                        <td>
                            <label>
                                <input type="checkbox" name="style_login" value="1" <?php checked(1, (int) cwcp_setting('style_login', 1)); ?> />
                                Style <code>wp-login.php</code> with these colours and the site logo
                            </label>
                            <p class="description">
                                Candidates can log in from the portal or from the WordPress login page.
                                With this on, both look the same and the WordPress screen links back to
                                the candidate login and registration pages.
                                <a href="<?php echo esc_url(wp_login_url()); ?>" target="_blank" rel="noopener">Preview login screen</a>
                            </p>
                        </td>
                    </tr>

                </tbody>
            </table>

            <h2>Preview</h2>

            <div class="cwcp-color-preview">

                <div class="cwcp-preview-card">
                    <span class="cwcp-preview-title">Programme Officer</span>
                    <span class="cwcp-preview-muted">Lahore &middot; Full Time &middot; Apply before 30 Sep</span>
                    <span class="cwcp-preview-actions">
                        <span class="cwcp-preview-btn">Easy Apply</span>
                        <span class="cwcp-preview-badge cwcp-preview-success">Shortlisted</span>
                        <span class="cwcp-preview-badge cwcp-preview-warning">Incomplete</span>
                        <span class="cwcp-preview-badge cwcp-preview-danger">Not Selected</span>
                    </span>
                </div>

                <p class="description">Live preview. Save to apply the colours to the portal.</p>

            </div>

            <?php submit_button('Save Settings'); ?>

        </form>

        <h2>Shortcode Reference</h2>

        <table class="widefat striped">
            <thead>
                <tr><th>Shortcode</th><th>What it renders</th><th>Page</th></tr>
            </thead>
            <tbody>
                <?php foreach (cwcp_page_map() as $key => $page) : ?>
                    <tr>
                        <td><code>[<?php echo esc_html($page['shortcode']); ?>]</code></td>
                        <td><?php echo esc_html($page['title']); ?></td>
                        <td>
                            <?php $page_id = cwcp_get_page_id($key); ?>
                            <?php if ($page_id) : ?>
                                <a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>">Edit</a> |
                                <a href="<?php echo esc_url(get_permalink($page_id)); ?>" target="_blank" rel="noopener">View</a>
                            <?php else : ?>
                                <em>Not created</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Database</h2>

        <p>
            Portal data lives in these tables:
            <code><?php echo esc_html(cwcp_table('applications')); ?></code>,
            <code><?php echo esc_html(cwcp_table('education')); ?></code>,
            <code><?php echo esc_html(cwcp_table('experience')); ?></code>,
            <code><?php echo esc_html(cwcp_table('skills')); ?></code>,
            <code><?php echo esc_html(cwcp_table('saved_jobs')); ?></code>,
            <code><?php echo esc_html(cwcp_table('submissions')); ?></code>.
            Deactivating the plugin never deletes them.
        </p>

    </div>
    <?php
}
