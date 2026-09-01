<?php
/**
 * Care Wave Candidate Portal - Admin candidates screen.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Admin Profile Editing
|--------------------------------------------------------------------------
|
| Portal managers can correct a candidate's details (a mistyped CNIC, a wrong
| district) without asking the candidate to log in. The same validation as the
| frontend profile screen is applied.
|
*/

function cwcp_handle_admin_candidate_actions() {

    if (!isset($_POST['cwcp_admin_action']) || 'update_candidate_profile' !== sanitize_key(wp_unslash($_POST['cwcp_admin_action']))) {
        return;
    }

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to do this.');
    }

    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    check_admin_referer('cwcp_update_candidate_profile_' . $user_id);

    if (!$user_id || !get_userdata($user_id)) {
        wp_die('That candidate could not be found.');
    }

    $checked = cwcp_validate_profile_input($_POST, $user_id);

    $base = array(
        'page'    => 'cwcp-candidates',
        'action'  => 'view',
        'user_id' => $user_id,
    );

    if ($checked['errors']) {

        set_transient('cwcp_admin_profile_errors_' . $user_id, $checked['errors'], 60);
        set_transient('cwcp_admin_profile_values_' . $user_id, $checked['values'], 60);

        $base['mode'] = 'edit';

        wp_safe_redirect(add_query_arg($base, admin_url('admin.php')));
        exit;
    }

    cwcp_save_profile_values($user_id, $checked['values']);

    $base['updated'] = 1;

    wp_safe_redirect(add_query_arg($base, admin_url('admin.php')));
    exit;
}

add_action('admin_init', 'cwcp_handle_admin_candidate_actions');

function cwcp_render_candidate_edit_form($user_id, $user) {

    $errors = get_transient('cwcp_admin_profile_errors_' . $user_id);
    $values = get_transient('cwcp_admin_profile_values_' . $user_id);

    delete_transient('cwcp_admin_profile_errors_' . $user_id);
    delete_transient('cwcp_admin_profile_values_' . $user_id);

    $profile = cwcp_get_profile($user_id);

    $back = add_query_arg(
        array('page' => 'cwcp-candidates', 'action' => 'view', 'user_id' => $user_id),
        admin_url('admin.php')
    );

    ?>
    <div class="wrap cwcp-admin">

        <h1 class="wp-heading-inline">Edit Candidate Profile</h1>

        <a class="page-title-action" href="<?php echo esc_url($back); ?>">Cancel</a>

        <hr class="wp-header-end" />

        <?php if (is_array($errors) && $errors) : ?>
            <div class="notice notice-error">
                <p><strong>The profile was not saved:</strong></p>
                <ul style="list-style:disc;margin-left:20px;">
                    <?php foreach ($errors as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="cwcp-admin-box">

            <h2><?php echo esc_html($user->user_email); ?></h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>">

                <?php wp_nonce_field('cwcp_update_candidate_profile_' . $user_id); ?>

                <input type="hidden" name="page" value="cwcp-candidates" />
                <input type="hidden" name="cwcp_admin_action" value="update_candidate_profile" />
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>" />

                <table class="form-table">
                    <tbody>

                    <?php foreach (cwcp_profile_fields() as $key => $field) : ?>

                        <?php
                        $value = is_array($values) && isset($values[$key]) ? $values[$key] : $profile[$key];
                        ?>

                        <tr>
                            <th>
                                <label for="cwcp-admin-<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($field['label']); ?>
                                    <?php if (!empty($field['required'])) : ?>
                                        <span style="color:#b32d2e;">*</span>
                                    <?php endif; ?>
                                </label>
                            </th>
                            <td>
                                <?php if ('select' === $field['type']) : ?>

                                    <select name="<?php echo esc_attr($key); ?>" id="cwcp-admin-<?php echo esc_attr($key); ?>">
                                        <?php
                                        if ('districts' === $field['options']) {

                                            /* Every district is offered; the province match is validated on save. */
                                            $options = array();

                                            foreach (cwcp_districts() as $province => $list) {
                                                foreach ($list as $district) {
                                                    $options[$district] = $district . ' (' . $province . ')';
                                                }
                                            }
                                        } else {
                                            $options = cwcp_field_options($field['options'], $user_id);
                                        }

                                        echo cwcp_select_options($options, $value, '-- Select --'); // phpcs:ignore WordPress.Security.EscapeOutput
                                        ?>
                                    </select>

                                <?php elseif ('textarea' === $field['type']) : ?>

                                    <textarea name="<?php echo esc_attr($key); ?>" id="cwcp-admin-<?php echo esc_attr($key); ?>"
                                              rows="3" class="large-text"><?php echo esc_textarea($value); ?></textarea>

                                <?php else : ?>

                                    <input type="<?php echo esc_attr($field['type']); ?>"
                                           name="<?php echo esc_attr($key); ?>"
                                           id="cwcp-admin-<?php echo esc_attr($key); ?>"
                                           value="<?php echo esc_attr($value); ?>"
                                           class="regular-text"
                                           <?php echo isset($field['placeholder']) ? 'placeholder="' . esc_attr($field['placeholder']) . '"' : ''; ?> />

                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

                <p class="description">
                    Education, experience, skills and the resume are managed by the candidate from the portal.
                </p>

                <?php submit_button('Save Profile'); ?>

            </form>

        </div>

    </div>
    <?php
}

function cwcp_render_candidates_page() {

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

    if ('view' === $action) {

        cwcp_render_candidate_detail(isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0);

        return;
    }

    $search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $province = isset($_GET['province']) ? sanitize_text_field(wp_unslash($_GET['province'])) : '';
    $complete = isset($_GET['complete']) ? sanitize_key(wp_unslash($_GET['complete'])) : '';
    $paged    = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $per_page = 25;

    $args = array(
        'role'    => 'carewave_candidate',
        'number'  => $per_page,
        'paged'   => $paged,
        'orderby' => 'registered',
        'order'   => 'DESC',
    );

    if ($search) {
        $args['search']         = '*' . $search . '*';
        $args['search_columns'] = array('user_login', 'user_email', 'display_name');
    }

    if ($province) {
        $args['meta_query'] = array(
            array(
                'key'   => 'cwcp_province',
                'value' => $province,
            ),
        );
    }

    $query = new WP_User_Query($args);

    $candidates = $query->get_results();

    $total = (int) $query->get_total();

    $pages = (int) ceil($total / $per_page);

    ?>
    <div class="wrap cwcp-admin">

        <h1>Candidates</h1>

        <form method="get" class="cwcp-admin-filters">

            <input type="hidden" name="page" value="cwcp-candidates" />

            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Name or email…" />

            <select name="province">
                <option value="">All provinces</option>
                <?php echo cwcp_select_options(cwcp_provinces(), $province); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </select>

            <select name="complete">
                <option value="">All accounts</option>
                <option value="yes" <?php selected($complete, 'yes'); ?>>Complete only</option>
                <option value="no" <?php selected($complete, 'no'); ?>>Incomplete only</option>
            </select>

            <button type="submit" class="button">Filter</button>

            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-candidates')); ?>">Reset</a>

        </form>

        <p class="cwcp-admin-count"><?php echo esc_html($total); ?> candidate(s).</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>CNIC</th>
                    <th>Location</th>
                    <th>Account</th>
                    <th>Applications</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php if (empty($candidates)) : ?>
                    <tr><td colspan="8">No candidates found.</td></tr>
                <?php endif; ?>

                <?php foreach ($candidates as $candidate) : ?>

                    <?php
                    $completeness = cwcp_profile_completeness($candidate->ID);

                    if ('yes' === $complete && !$completeness['is_complete']) {
                        continue;
                    }

                    if ('no' === $complete && $completeness['is_complete']) {
                        continue;
                    }

                    $profile = cwcp_get_profile($candidate->ID);

                    $view_url = add_query_arg(
                        array('page' => 'cwcp-candidates', 'action' => 'view', 'user_id' => $candidate->ID),
                        admin_url('admin.php')
                    );
                    ?>

                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url($view_url); ?>">
                                <?php echo esc_html($profile['full_name'] ? $profile['full_name'] : $candidate->display_name); ?>
                            </a></strong>
                        </td>
                        <td>
                            <?php echo esc_html($candidate->user_email); ?><br />
                            <small><?php echo esc_html($profile['mobile']); ?></small>
                        </td>
                        <td><?php echo esc_html($profile['cnic']); ?></td>
                        <td>
                            <?php echo esc_html(trim($profile['district'] . ', ' . $profile['province'], ', ')); ?>
                        </td>
                        <td>
                            <span class="cwcp-admin-pill <?php echo $completeness['is_complete'] ? 'is-open' : 'is-closed'; ?>">
                                <?php echo esc_html($completeness['percent']); ?>%
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications&s=' . rawurlencode($candidate->user_email))); ?>">
                                <?php echo esc_html(cwcp_count_applications(array('user_id' => $candidate->ID))); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html(cwcp_format_date($candidate->user_registered, 'd M Y')); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url($view_url); ?>">View</a>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg('mode', 'edit', $view_url)); ?>">Edit</a>
                            <?php
                            $resume_id = (int) get_user_meta($candidate->ID, 'cwcp_resume_id', true);
                            ?>
                            <?php if ($resume_id && get_post($resume_id)) : ?>
                                <a class="button button-small" target="_blank" rel="noopener"
                                   href="<?php echo esc_url(cwcp_document_url($resume_id)); ?>">CV</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>
        </table>

        <?php if ($pages > 1) : ?>
            <div class="tablenav"><div class="tablenav-pages">
                <?php
                echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
                    array(
                        'base'    => add_query_arg('paged', '%#%'),
                        'format'  => '',
                        'current' => $paged,
                        'total'   => $pages,
                    )
                );
                ?>
            </div></div>
        <?php endif; ?>

    </div>
    <?php
}

function cwcp_render_candidate_detail($user_id) {

    $user = get_userdata($user_id);

    if (!$user) {

        echo '<div class="wrap"><h1>Candidate not found</h1></div>';

        return;
    }

    $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : '';

    if ('edit' === $mode) {

        cwcp_render_candidate_edit_form($user_id, $user);

        return;
    }

    $profile      = cwcp_get_profile($user_id);
    $completeness = cwcp_profile_completeness($user_id);
    $education    = cwcp_get_education($user_id);
    $experience   = cwcp_get_experience($user_id);
    $skills       = cwcp_get_skills($user_id);

    $applications = cwcp_get_applications(array('user_id' => $user_id, 'per_page' => 100));

    $resume_id = (int) get_user_meta($user_id, 'cwcp_resume_id', true);

    ?>
    <div class="wrap cwcp-admin">

        <h1 class="wp-heading-inline"><?php echo esc_html($profile['full_name'] ? $profile['full_name'] : $user->display_name); ?></h1>

        <a class="page-title-action"
           href="<?php echo esc_url(
               add_query_arg(
                   array('page' => 'cwcp-candidates', 'action' => 'view', 'user_id' => $user_id, 'mode' => 'edit'),
                   admin_url('admin.php')
               )
           ); ?>">Edit Profile</a>

        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-candidates')); ?>">Back to list</a>

        <hr class="wp-header-end" />

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Candidate profile updated.</p></div>
        <?php endif; ?>

        <div class="cwcp-admin-columns">

            <div class="cwcp-admin-box">

                <h2>Profile <span class="cwcp-admin-pill <?php echo $completeness['is_complete'] ? 'is-open' : 'is-closed'; ?>">
                    <?php echo esc_html($completeness['percent']); ?>% complete</span></h2>

                <?php if (!$completeness['is_complete']) : ?>
                    <p><em>Missing: <?php echo esc_html(implode(', ', $completeness['missing_labels'])); ?></em></p>
                <?php endif; ?>

                <table class="widefat striped">
                    <tbody>
                        <?php foreach (cwcp_profile_fields() as $key => $field) : ?>
                            <tr>
                                <th style="width:200px;"><?php echo esc_html($field['label']); ?></th>
                                <td><?php echo esc_html($profile[$key]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th>Registered</th>
                            <td><?php echo esc_html(cwcp_format_date($user->user_registered, 'd M Y')); ?></td>
                        </tr>
                        <tr>
                            <th>Last login</th>
                            <td><?php echo esc_html(cwcp_format_date(get_user_meta($user_id, 'cwcp_last_login', true), 'd M Y H:i')); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h2>Education</h2>

                <?php if (empty($education)) : ?>
                    <p>No education records.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead><tr><th>Degree</th><th>Institute</th><th>Year</th><th>Grade</th></tr></thead>
                        <tbody>
                            <?php foreach ($education as $record) : ?>
                                <tr>
                                    <td><?php echo esc_html($record['degree_title']); ?></td>
                                    <td><?php echo esc_html($record['institute']); ?></td>
                                    <td><?php echo esc_html($record['passing_year']); ?></td>
                                    <td><?php echo esc_html($record['grade']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h2>Experience (<?php echo esc_html(cwcp_format_experience(cwcp_total_experience_months($user_id))); ?>)</h2>

                <?php if (empty($experience)) : ?>
                    <p>No experience records.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead><tr><th>Designation</th><th>Organization</th><th>Period</th></tr></thead>
                        <tbody>
                            <?php foreach ($experience as $record) : ?>
                                <tr>
                                    <td><?php echo esc_html($record['designation']); ?></td>
                                    <td><?php echo esc_html($record['organization']); ?></td>
                                    <td>
                                        <?php echo esc_html(cwcp_format_date($record['start_date'], 'M Y')); ?> &ndash;
                                        <?php echo esc_html(!empty($record['currently_working']) ? 'Present' : cwcp_format_date($record['end_date'], 'M Y')); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h2>Applications</h2>

                <?php if (empty($applications)) : ?>
                    <p>This candidate has not applied for any job yet.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead><tr><th>Job</th><th>Applied</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($applications as $application) : ?>
                                <tr>
                                    <td><?php echo esc_html($application['job_title']); ?></td>
                                    <td><?php echo esc_html(cwcp_format_date($application['applied_at'], 'd M Y')); ?></td>
                                    <td><?php echo esc_html(cwcp_status_label($application['status'])); ?></td>
                                    <td>
                                        <a class="button button-small"
                                           href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications&action=view&id=' . $application['id'])); ?>">
                                            Open
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>

            <div class="cwcp-admin-box cwcp-admin-side">

                <?php $photo_url = cwcp_get_photo_url($user_id, 'medium'); ?>

                <?php if ($photo_url) : ?>
                    <p style="text-align:center;">
                        <img src="<?php echo esc_url($photo_url); ?>" alt=""
                             style="width:120px;height:120px;object-fit:cover;border-radius:50%;border:1px solid #dcdcde;" />
                    </p>
                <?php endif; ?>

                <h2>Resume</h2>

                <?php if ($resume_id && get_post($resume_id)) : ?>
                    <p>
                        <a class="button button-primary" target="_blank" rel="noopener"
                           href="<?php echo esc_url(cwcp_document_url($resume_id)); ?>">Download Resume</a>
                    </p>
                    <?php if ('application/pdf' === get_post_mime_type($resume_id)) : ?>
                        <iframe class="cwcp-resume-preview"
                                src="<?php echo esc_url(cwcp_document_url($resume_id)); ?>"
                                title="Resume preview"></iframe>
                    <?php endif; ?>
                <?php else : ?>
                    <p>No resume uploaded.</p>
                <?php endif; ?>

                <h2>Skills</h2>

                <?php if (empty($skills)) : ?>
                    <p>No skills listed.</p>
                <?php else : ?>
                    <p>
                        <?php foreach ($skills as $skill) : ?>
                            <span class="cwcp-admin-pill"><?php echo esc_html($skill['skill_name']); ?></span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <h2>Contact</h2>

                <p>
                    <a class="button" href="<?php echo esc_url('mailto:' . $user->user_email); ?>">Email candidate</a>
                    <a class="button" href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user_id)); ?>">Edit WP user</a>
                </p>

            </div>

        </div>

    </div>
    <?php
}
