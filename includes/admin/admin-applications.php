<?php
/**
 * CareerHub - Admin applications screen.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Admin Actions
|--------------------------------------------------------------------------
*/

function cwcp_handle_admin_application_actions() {

    if (!isset($_REQUEST['cwcp_admin_action'])) {
        return;
    }

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to do this.');
    }

    $action = sanitize_key(wp_unslash($_REQUEST['cwcp_admin_action']));

    switch ($action) {

        case 'update_application':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

            check_admin_referer('cwcp_update_application_' . $id);

            $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
            $notes  = isset($_POST['admin_notes']) ? sanitize_textarea_field(wp_unslash($_POST['admin_notes'])) : '';

            cwcp_update_application_status($id, $status, $notes);

            wp_safe_redirect(
                add_query_arg(
                    array('page' => 'cwcp-applications', 'action' => 'view', 'id' => $id, 'updated' => 1),
                    admin_url('admin.php')
                )
            );
            exit;

        case 'delete_application':
            $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

            check_admin_referer('cwcp_delete_application_' . $id);

            cwcp_delete_application($id);

            wp_safe_redirect(
                add_query_arg(array('page' => 'cwcp-applications', 'deleted' => 1), admin_url('admin.php'))
            );
            exit;

        case 'export_applications':
            check_admin_referer('cwcp_export_applications');

            cwcp_export_applications_csv();
            exit;

        case 'update_submission':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

            check_admin_referer('cwcp_update_submission_' . $id);

            $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
            $notes  = isset($_POST['admin_notes']) ? sanitize_textarea_field(wp_unslash($_POST['admin_notes'])) : '';

            cwcp_update_submission_status($id, $status, $notes);

            wp_safe_redirect(
                add_query_arg(
                    array('page' => 'cwcp-submissions', 'action' => 'view', 'id' => $id, 'updated' => 1),
                    admin_url('admin.php')
                )
            );
            exit;

        case 'export_submissions':
            check_admin_referer('cwcp_export_submissions');

            cwcp_export_submissions_csv();
            exit;
    }
}

add_action('admin_init', 'cwcp_handle_admin_application_actions');


/*
|--------------------------------------------------------------------------
| CSV Exports
|--------------------------------------------------------------------------
*/

function cwcp_send_csv_headers($filename) {

    nocache_headers();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
}

function cwcp_export_applications_csv() {

    $job_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';

    $applications = cwcp_get_applications(
        array(
            'job_id'   => $job_id,
            'status'   => $status,
            'per_page' => 5000,
        )
    );

    cwcp_send_csv_headers('careerhub-applications-' . gmdate('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    $profile_fields = cwcp_profile_fields();

    $header = array('Application ID', 'Job', 'Applied On', 'Status');

    foreach ($profile_fields as $field) {
        $header[] = $field['label'];
    }

    $header[] = 'Total Experience';
    $header[] = 'Education';
    $header[] = 'Skills';
    $header[] = 'Resume URL';
    $header[] = 'Cover Note';

    fputcsv($output, $header);

    foreach ($applications as $application) {

        $snapshot = cwcp_get_application_snapshot($application);

        $profile = isset($snapshot['profile']) ? $snapshot['profile'] : cwcp_get_profile($application['user_id']);

        $row = array(
            $application['id'],
            $application['job_title'],
            $application['applied_at'],
            cwcp_status_label($application['status']),
        );

        foreach ($profile_fields as $key => $field) {
            $row[] = isset($profile[$key]) ? $profile[$key] : '';
        }

        $row[] = isset($snapshot['experience_months'])
            ? cwcp_format_experience((int) $snapshot['experience_months'])
            : cwcp_format_experience(cwcp_total_experience_months($application['user_id']));

        $education = isset($snapshot['education']) ? $snapshot['education'] : cwcp_get_education($application['user_id']);

        $education_text = array();

        foreach ((array) $education as $record) {
            $education_text[] = $record['degree_title'] . ' (' . $record['institute'] . ', ' . $record['passing_year'] . ')';
        }

        $row[] = implode(' | ', $education_text);

        $skills = isset($snapshot['skills']) ? $snapshot['skills'] : cwcp_get_skills($application['user_id']);

        $skill_text = array();

        foreach ((array) $skills as $skill) {
            $skill_text[] = $skill['skill_name'] . ' (' . $skill['skill_level'] . ')';
        }

        $row[] = implode(', ', $skill_text);

        $row[] = $application['resume_id'] ? cwcp_document_url($application['resume_id']) : '';
        $row[] = $application['cover_note'];

        fputcsv($output, $row);
    }

    fclose($output);
}

function cwcp_export_submissions_csv() {

    $type = isset($_GET['form_type']) ? sanitize_key(wp_unslash($_GET['form_type'])) : '';

    $submissions = cwcp_get_submissions(
        array(
            'form_type' => $type,
            'per_page'  => 5000,
        )
    );

    cwcp_send_csv_headers('carewave-' . ($type ? $type : 'submissions') . '-' . gmdate('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    $schema = $type ? cwcp_form_schema($type) : array();

    $header = array('ID', 'Form', 'Submitted On', 'Status');

    if ($schema) {

        foreach ($schema as $key => $field) {

            if ('file' === $field['type']) {
                continue;
            }

            $header[] = $field['label'];
        }
    } else {
        $header[] = 'Name';
        $header[] = 'Email';
        $header[] = 'Mobile';
    }

    $header[] = 'Attachment URL';

    fputcsv($output, $header);

    foreach ($submissions as $submission) {

        $data = cwcp_get_submission_data($submission);

        $row = array(
            $submission['id'],
            $submission['form_type'],
            $submission['created_at'],
            $submission['status'],
        );

        if ($schema) {

            foreach ($schema as $key => $field) {

                if ('file' === $field['type']) {
                    continue;
                }

                $row[] = isset($data[$key]) ? $data[$key] : '';
            }

        } else {

            $row[] = $submission['full_name'];
            $row[] = $submission['email'];
            $row[] = $submission['mobile'];
        }

        $row[] = $submission['attachment_id'] ? cwcp_document_url($submission['attachment_id']) : '';

        fputcsv($output, $row);
    }

    fclose($output);
}


/*
|--------------------------------------------------------------------------
| Applications Screen
|--------------------------------------------------------------------------
*/

function cwcp_render_applications_page() {

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

    if ('view' === $action) {

        cwcp_render_application_detail(isset($_GET['id']) ? (int) $_GET['id'] : 0);

        return;
    }

    $job_id   = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
    $status   = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
    $search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $paged    = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $per_page = 20;

    $args = array(
        'job_id'   => $job_id,
        'status'   => $status,
        'search'   => $search,
        'paged'    => $paged,
        'per_page' => $per_page,
    );

    $applications = cwcp_get_applications($args);

    $total = cwcp_count_applications($args);

    $pages = (int) ceil($total / $per_page);

    $jobs = get_posts(
        array(
            'post_type'      => 'cw_job',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    ?>
    <div class="wrap cwcp-admin">

        <h1 class="wp-heading-inline">Applications</h1>

        <a class="page-title-action"
           href="<?php echo esc_url(
               wp_nonce_url(
                   add_query_arg(
                       array(
                           'page'              => 'cwcp-applications',
                           'cwcp_admin_action' => 'export_applications',
                           'job_id'            => $job_id,
                           'status'            => $status,
                       ),
                       admin_url('admin.php')
                   ),
                   'cwcp_export_applications'
               )
           ); ?>">Export CSV</a>

        <hr class="wp-header-end" />

        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Application deleted.</p></div>
        <?php endif; ?>

        <form method="get" class="cwcp-admin-filters">

            <input type="hidden" name="page" value="cwcp-applications" />

            <select name="job_id">
                <option value="">All jobs</option>
                <?php foreach ($jobs as $job) : ?>
                    <option value="<?php echo esc_attr($job->ID); ?>" <?php selected($job_id, $job->ID); ?>>
                        <?php echo esc_html($job->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status">
                <option value="">All statuses</option>
                <?php echo cwcp_select_options(cwcp_application_statuses(), $status); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Candidate or job…" />

            <button type="submit" class="button">Filter</button>

            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications')); ?>">Reset</a>

        </form>

        <p class="cwcp-admin-count"><?php echo esc_html($total); ?> application(s) found.</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Contact</th>
                    <th>Job</th>
                    <th>Applied</th>
                    <th>Status</th>
                    <th>Resume</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php if (empty($applications)) : ?>
                    <tr><td colspan="7">No applications found.</td></tr>
                <?php endif; ?>

                <?php foreach ($applications as $application) : ?>

                    <?php
                    $view_url = add_query_arg(
                        array('page' => 'cwcp-applications', 'action' => 'view', 'id' => $application['id']),
                        admin_url('admin.php')
                    );

                    $profile = cwcp_get_profile($application['user_id']);
                    ?>

                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url($view_url); ?>">
                                <?php echo esc_html($profile['full_name'] ? $profile['full_name'] : $application['display_name']); ?>
                            </a></strong>
                            <div class="row-actions">
                                <span>CNIC: <?php echo esc_html($profile['cnic']); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php echo esc_html($application['user_email']); ?><br />
                            <small><?php echo esc_html($profile['mobile']); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html($application['job_title']); ?>
                        </td>
                        <td><?php echo esc_html(cwcp_format_date($application['applied_at'], 'd M Y')); ?></td>
                        <td>
                            <span class="cwcp-admin-pill status-<?php echo esc_attr($application['status']); ?>">
                                <?php echo esc_html(cwcp_status_label($application['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($application['resume_id'] && get_post($application['resume_id'])) : ?>
                                <a class="button button-small" target="_blank" rel="noopener"
                                   href="<?php echo esc_url(cwcp_document_url($application['resume_id'])); ?>">
                                    Download
                                </a>
                            <?php else : ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url($view_url); ?>">View</a>
                            <a class="button button-small button-link-delete"
                               onclick="return confirm('Delete this application permanently?');"
                               href="<?php echo esc_url(
                                   wp_nonce_url(
                                       add_query_arg(
                                           array(
                                               'page'              => 'cwcp-applications',
                                               'cwcp_admin_action' => 'delete_application',
                                               'id'                => $application['id'],
                                           ),
                                           admin_url('admin.php')
                                       ),
                                       'cwcp_delete_application_' . $application['id']
                                   )
                               ); ?>">Delete</a>
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


/*
|--------------------------------------------------------------------------
| Application Detail
|--------------------------------------------------------------------------
*/

function cwcp_render_application_detail($application_id) {

    $application = cwcp_get_application($application_id);

    if (!$application) {

        echo '<div class="wrap"><h1>Application not found</h1>'
            . '<a class="button" href="' . esc_url(admin_url('admin.php?page=cwcp-applications')) . '">Back to applications</a></div>';

        return;
    }

    $snapshot = cwcp_get_application_snapshot($application);

    $user_id = (int) $application['user_id'];

    $profile    = isset($snapshot['profile']) ? $snapshot['profile'] : cwcp_get_profile($user_id);
    $education  = isset($snapshot['education']) ? $snapshot['education'] : cwcp_get_education($user_id);
    $experience = isset($snapshot['experience']) ? $snapshot['experience'] : cwcp_get_experience($user_id);
    $skills     = isset($snapshot['skills']) ? $snapshot['skills'] : cwcp_get_skills($user_id);

    $resume_id = (int) $application['resume_id'];

    ?>
    <div class="wrap cwcp-admin">

        <h1 class="wp-heading-inline">Application #<?php echo esc_html($application['id']); ?></h1>

        <a class="page-title-action" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-applications')); ?>">
            Back to list
        </a>

        <hr class="wp-header-end" />

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Application updated. The candidate has been notified by email.</p></div>
        <?php endif; ?>

        <div class="cwcp-admin-columns">

            <div class="cwcp-admin-box">

                <h2>Applied For</h2>

                <p>
                    <strong><?php echo esc_html(get_the_title($application['job_id'])); ?></strong><br />
                    Applied on <?php echo esc_html(cwcp_format_date($application['applied_at'], 'd M Y H:i')); ?>
                </p>

                <?php if ($application['cover_note']) : ?>
                    <h3>Message from candidate</h3>
                    <p class="cwcp-admin-note"><?php echo nl2br(esc_html($application['cover_note'])); ?></p>
                <?php endif; ?>

                <h2>Candidate Details</h2>

                <table class="widefat striped">
                    <tbody>
                        <?php foreach (cwcp_profile_fields() as $key => $field) : ?>
                            <tr>
                                <th style="width:200px;"><?php echo esc_html($field['label']); ?></th>
                                <td><?php echo esc_html(isset($profile[$key]) ? $profile[$key] : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <th>Age</th>
                            <td><?php echo esc_html(cwcp_calculate_age(isset($profile['dob']) ? $profile['dob'] : '')); ?> years</td>
                        </tr>
                        <tr>
                            <th>Total Experience</th>
                            <td>
                                <?php
                                echo esc_html(
                                    cwcp_format_experience(
                                        isset($snapshot['experience_months'])
                                            ? (int) $snapshot['experience_months']
                                            : cwcp_total_experience_months($user_id)
                                    )
                                );
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2>Education</h2>

                <?php if (empty($education)) : ?>
                    <p>No education records.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr><th>Degree</th><th>Institute</th><th>Year</th><th>Grade</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($education as $record) : ?>
                                <tr>
                                    <td><?php echo esc_html($record['degree_title'] . ' (' . $record['degree_level'] . ')'); ?></td>
                                    <td><?php echo esc_html($record['institute'] . ' ' . $record['board_university']); ?></td>
                                    <td><?php echo esc_html($record['passing_year']); ?></td>
                                    <td>
                                        <?php
                                        echo esc_html($record['grade']);

                                        if ($record['obtained_marks']) {
                                            echo ' (' . esc_html($record['obtained_marks'] . '/' . $record['total_marks']) . ')';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h2>Experience</h2>

                <?php if (empty($experience)) : ?>
                    <p>No experience records.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr><th>Designation</th><th>Organization</th><th>Period</th><th>Responsibilities</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($experience as $record) : ?>
                                <tr>
                                    <td><?php echo esc_html($record['designation']); ?></td>
                                    <td><?php echo esc_html($record['organization'] . ' ' . $record['job_city']); ?></td>
                                    <td>
                                        <?php echo esc_html(cwcp_format_date($record['start_date'], 'M Y')); ?> &ndash;
                                        <?php echo esc_html(!empty($record['currently_working']) ? 'Present' : cwcp_format_date($record['end_date'], 'M Y')); ?>
                                    </td>
                                    <td><?php echo esc_html(wp_trim_words((string) $record['responsibilities'], 30)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h2>Skills</h2>

                <?php if (empty($skills)) : ?>
                    <p>No skills listed.</p>
                <?php else : ?>
                    <p>
                        <?php foreach ($skills as $skill) : ?>
                            <span class="cwcp-admin-pill">
                                <?php echo esc_html($skill['skill_name'] . ' - ' . $skill['skill_level']); ?>
                            </span>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

            </div>

            <div class="cwcp-admin-box cwcp-admin-side">

                <h2>Resume</h2>

                <?php if ($resume_id && get_post($resume_id)) : ?>

                    <p>
                        <a class="button button-primary" target="_blank" rel="noopener"
                           href="<?php echo esc_url(cwcp_document_url($resume_id)); ?>">
                            Download Resume
                        </a>
                    </p>

                    <?php if ('application/pdf' === get_post_mime_type($resume_id)) : ?>
                        <iframe class="cwcp-resume-preview"
                                src="<?php echo esc_url(cwcp_document_url($resume_id)); ?>"
                                title="Resume preview"></iframe>
                    <?php endif; ?>

                <?php else : ?>
                    <p>No resume attached to this application.</p>
                <?php endif; ?>

                <h2>Status &amp; Notes</h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>">

                    <?php wp_nonce_field('cwcp_update_application_' . $application['id']); ?>

                    <input type="hidden" name="page" value="cwcp-applications" />
                    <input type="hidden" name="cwcp_admin_action" value="update_application" />
                    <input type="hidden" name="id" value="<?php echo esc_attr($application['id']); ?>" />

                    <p>
                        <label for="cwcp-status"><strong>Status</strong></label><br />
                        <select name="status" id="cwcp-status" class="widefat">
                            <?php echo cwcp_select_options(cwcp_application_statuses(), $application['status']); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        </select>
                    </p>

                    <p>
                        <label for="cwcp-admin-notes"><strong>Internal notes</strong></label><br />
                        <textarea name="admin_notes" id="cwcp-admin-notes" rows="6" class="widefat"><?php
                            echo esc_textarea((string) $application['admin_notes']);
                        ?></textarea>
                    </p>

                    <p><button type="submit" class="button button-primary">Save &amp; Notify Candidate</button></p>

                </form>

                <h2>Candidate Account</h2>

                <p>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-candidates&action=view&user_id=' . $user_id)); ?>">
                        View full candidate profile
                    </a>
                </p>

                <p>
                    <a class="button" href="<?php echo esc_url('mailto:' . (isset($profile['email']) ? $profile['email'] : '')); ?>">
                        Email candidate
                    </a>
                </p>

            </div>

        </div>

    </div>
    <?php
}
