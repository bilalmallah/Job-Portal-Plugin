<?php
/**
 * Care Wave Candidate Portal - Admin menu and overview screen.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}

function cwcp_admin_menu() {

    $cap = 'cwcp_manage_portal';

    add_menu_page(
        'Care Wave Portal',
        'Care Wave',
        $cap,
        'cwcp-overview',
        'cwcp_render_overview_page',
        'dashicons-groups',
        26
    );

    add_submenu_page('cwcp-overview', 'Overview', 'Overview', $cap, 'cwcp-overview', 'cwcp_render_overview_page');

    add_submenu_page(
        'cwcp-overview',
        'Applications',
        'Applications',
        $cap,
        'cwcp-applications',
        'cwcp_render_applications_page'
    );

    add_submenu_page(
        'cwcp-overview',
        'Candidates',
        'Candidates',
        $cap,
        'cwcp-candidates',
        'cwcp_render_candidates_page'
    );

    add_submenu_page('cwcp-overview', 'Jobs', 'Jobs', $cap, 'edit.php?post_type=cw_job');
    add_submenu_page('cwcp-overview', 'Add New Job', 'Add New Job', $cap, 'post-new.php?post_type=cw_job');

    add_submenu_page(
        'cwcp-overview',
        'Job Categories',
        'Job Categories',
        $cap,
        'edit-tags.php?taxonomy=cw_job_category&post_type=cw_job'
    );

    add_submenu_page(
        'cwcp-overview',
        'Job Types',
        'Job Types',
        $cap,
        'edit-tags.php?taxonomy=cw_job_type&post_type=cw_job'
    );

    add_submenu_page('cwcp-overview', 'Tenders', 'Tenders', $cap, 'edit.php?post_type=cw_tender');

    add_submenu_page(
        'cwcp-overview',
        'Form Submissions',
        'Form Submissions',
        $cap,
        'cwcp-submissions',
        'cwcp_render_submissions_page'
    );

    add_submenu_page(
        'cwcp-overview',
        'Portal Settings',
        'Settings',
        $cap,
        'cwcp-settings',
        'cwcp_render_settings_page'
    );
}

add_action('admin_menu', 'cwcp_admin_menu');

/**
 * Keeps the Care Wave menu highlighted while editing a job or tender.
 */
function cwcp_admin_parent_file($parent_file) {

    global $current_screen;

    if ($current_screen && in_array($current_screen->post_type, array('cw_job', 'cw_tender'), true)) {
        return 'cwcp-overview';
    }

    return $parent_file;
}

add_filter('parent_file', 'cwcp_admin_parent_file');


/*
|--------------------------------------------------------------------------
| Overview
|--------------------------------------------------------------------------
*/

function cwcp_render_overview_page() {

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    $status_counts = cwcp_count_applications_by_status();

    $total_applications = array_sum($status_counts);

    $jobs_total = wp_count_posts('cw_job');

    $open_jobs = 0;

    $job_ids = get_posts(
        array(
            'post_type'      => 'cw_job',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    foreach ($job_ids as $job_id) {

        if (cwcp_job_is_open($job_id)) {
            $open_jobs++;
        }
    }

    $candidates = count_users();

    $candidate_count = isset($candidates['avail_roles']['carewave_candidate'])
        ? (int) $candidates['avail_roles']['carewave_candidate']
        : 0;

    $recent = cwcp_get_applications(array('per_page' => 8));

    $submission_counts = array();

    foreach (cwcp_form_types() as $type => $label) {
        $submission_counts[$type] = cwcp_count_submissions(array('form_type' => $type));
    }

    ?>
    <div class="wrap cwcp-admin">

        <h1>Care Wave Portal Overview</h1>

        <div class="cwcp-admin-stats">

            <div class="cwcp-admin-stat">
                <span class="cwcp-admin-stat-label">Total Applications</span>
                <strong><?php echo esc_html($total_applications); ?></strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications')); ?>">View all</a>
            </div>

            <div class="cwcp-admin-stat">
                <span class="cwcp-admin-stat-label">New / Unreviewed</span>
                <strong><?php echo esc_html($status_counts['new']); ?></strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications&status=new')); ?>">Review now</a>
            </div>

            <div class="cwcp-admin-stat">
                <span class="cwcp-admin-stat-label">Open Jobs</span>
                <strong><?php echo esc_html($open_jobs); ?></strong>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=cw_job')); ?>">Manage jobs</a>
            </div>

            <div class="cwcp-admin-stat">
                <span class="cwcp-admin-stat-label">Registered Candidates</span>
                <strong><?php echo esc_html($candidate_count); ?></strong>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-candidates')); ?>">View candidates</a>
            </div>

        </div>

        <div class="cwcp-admin-columns">

            <div class="cwcp-admin-box">

                <h2>Applications by Status</h2>

                <table class="widefat striped">
                    <tbody>
                        <?php foreach (cwcp_application_statuses() as $key => $label) : ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td style="width:80px;text-align:right;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications&status=' . $key)); ?>">
                                        <strong><?php echo esc_html($status_counts[$key]); ?></strong>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2 style="margin-top:24px;">Form Submissions</h2>

                <table class="widefat striped">
                    <tbody>
                        <?php foreach (cwcp_form_types() as $type => $label) : ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td style="width:80px;text-align:right;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-submissions&form_type=' . $type)); ?>">
                                        <strong><?php echo esc_html($submission_counts[$type]); ?></strong>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

            <div class="cwcp-admin-box">

                <h2>Latest Applications</h2>

                <?php if (empty($recent)) : ?>

                    <p>No applications received yet.</p>

                <?php else : ?>

                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Job</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $application) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications&action=view&id=' . $application['id'])); ?>">
                                            <?php echo esc_html($application['display_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($application['job_title']); ?></td>
                                    <td><?php echo esc_html(cwcp_format_date($application['applied_at'])); ?></td>
                                    <td><?php echo esc_html(cwcp_status_label($application['status'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            </div>

        </div>

        <div class="cwcp-admin-box" style="margin-top:20px;">

            <h2>Portal Pages</h2>

            <p>These pages were created automatically. Add them to your menu so visitors can reach them.</p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Screen</th>
                        <th>Shortcode</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (cwcp_page_map() as $key => $page) : ?>
                        <tr>
                            <td><?php echo esc_html($page['title']); ?></td>
                            <td><code>[<?php echo esc_html($page['shortcode']); ?>]</code></td>
                            <td><a href="<?php echo esc_url(cwcp_page_url($key)); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html(cwcp_page_url($key)); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    </div>
    <?php
}
