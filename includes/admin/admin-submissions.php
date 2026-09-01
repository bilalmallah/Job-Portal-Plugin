<?php
/**
 * Care Wave Candidate Portal - Admin screen for volunteer, internship,
 * field facilitator and tender submissions.
 *
 * @package CareWaveCandidatePortal
 */

if (!defined('ABSPATH')) {
    exit;
}

function cwcp_render_submissions_page() {

    if (!cwcp_can_manage()) {
        wp_die('You do not have permission to access this page.');
    }

    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

    if ('view' === $action) {

        cwcp_render_submission_detail(isset($_GET['id']) ? (int) $_GET['id'] : 0);

        return;
    }

    $type       = isset($_GET['form_type']) ? sanitize_key(wp_unslash($_GET['form_type'])) : '';
    $status     = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
    $search     = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $related_id = isset($_GET['related_id']) ? (int) $_GET['related_id'] : 0;
    $paged      = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $per_page   = 25;

    $types = cwcp_form_types();

    if ($type && !isset($types[$type])) {
        $type = '';
    }

    $args = array(
        'form_type'  => $type,
        'status'     => $status,
        'search'     => $search,
        'related_id' => $related_id,
        'paged'      => $paged,
        'per_page'   => $per_page,
    );

    $submissions = cwcp_get_submissions($args);

    $total = cwcp_count_submissions($args);

    $pages = (int) ceil($total / $per_page);

    ?>
    <div class="wrap cwcp-admin">

        <h1 class="wp-heading-inline">Form Submissions</h1>

        <a class="page-title-action"
           href="<?php echo esc_url(
               wp_nonce_url(
                   add_query_arg(
                       array(
                           'page'              => 'cwcp-submissions',
                           'cwcp_admin_action' => 'export_submissions',
                           'form_type'         => $type,
                       ),
                       admin_url('admin.php')
                   ),
                   'cwcp_export_submissions'
               )
           ); ?>">Export CSV</a>

        <hr class="wp-header-end" />

        <ul class="subsubsub">
            <li>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-submissions')); ?>"
                   class="<?php echo '' === $type ? 'current' : ''; ?>">
                    All <span class="count">(<?php echo esc_html(cwcp_count_submissions()); ?>)</span>
                </a> |
            </li>
            <?php
            $type_keys = array_keys($types);
            $last      = end($type_keys);
            ?>
            <?php foreach ($types as $key => $label) : ?>
                <li>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-submissions&form_type=' . $key)); ?>"
                       class="<?php echo $type === $key ? 'current' : ''; ?>">
                        <?php echo esc_html($label); ?>
                        <span class="count">(<?php echo esc_html(cwcp_count_submissions(array('form_type' => $key))); ?>)</span>
                    </a><?php echo $key !== $last ? ' |' : ''; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <form method="get" class="cwcp-admin-filters">

            <input type="hidden" name="page" value="cwcp-submissions" />
            <input type="hidden" name="form_type" value="<?php echo esc_attr($type); ?>" />

            <select name="status">
                <option value="">All statuses</option>
                <?php echo cwcp_select_options(cwcp_submission_statuses(), $status); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Name, email or mobile…" />

            <button type="submit" class="button">Filter</button>

            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=cwcp-submissions&form_type=' . $type)); ?>">Reset</a>

        </form>

        <p class="cwcp-admin-count"><?php echo esc_html($total); ?> submission(s).</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Form</th>
                    <th>Contact</th>
                    <th>Related To</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Document</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

                <?php if (empty($submissions)) : ?>
                    <tr><td colspan="8">No submissions found.</td></tr>
                <?php endif; ?>

                <?php foreach ($submissions as $submission) : ?>

                    <?php
                    $view_url = add_query_arg(
                        array('page' => 'cwcp-submissions', 'action' => 'view', 'id' => $submission['id']),
                        admin_url('admin.php')
                    );
                    ?>

                    <tr>
                        <td><strong><a href="<?php echo esc_url($view_url); ?>"><?php echo esc_html($submission['full_name']); ?></a></strong></td>
                        <td><?php echo esc_html(isset($types[$submission['form_type']]) ? $types[$submission['form_type']] : $submission['form_type']); ?></td>
                        <td>
                            <?php echo esc_html($submission['email']); ?><br />
                            <small><?php echo esc_html($submission['mobile']); ?></small>
                        </td>
                        <td>
                            <?php
                            echo $submission['related_id']
                                ? esc_html(get_the_title($submission['related_id']))
                                : '&mdash;';
                            ?>
                        </td>
                        <td><?php echo esc_html(cwcp_format_date($submission['created_at'], 'd M Y')); ?></td>
                        <td>
                            <span class="cwcp-admin-pill status-<?php echo esc_attr($submission['status']); ?>">
                                <?php echo esc_html(ucfirst($submission['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($submission['attachment_id'] && get_post($submission['attachment_id'])) : ?>
                                <a class="button button-small" target="_blank" rel="noopener"
                                   href="<?php echo esc_url(cwcp_document_url($submission['attachment_id'])); ?>">Open</a>
                            <?php else : ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td><a class="button button-small" href="<?php echo esc_url($view_url); ?>">View</a></td>
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

function cwcp_render_submission_detail($id) {

    $submission = cwcp_get_submission($id);

    if (!$submission) {

        echo '<div class="wrap"><h1>Submission not found</h1></div>';

        return;
    }

    $data = cwcp_get_submission_data($submission);

    $schema = cwcp_form_schema($submission['form_type']);

    $types = cwcp_form_types();

    ?>
    <div class="wrap cwcp-admin">

        <h1 class="wp-heading-inline">
            <?php echo esc_html(isset($types[$submission['form_type']]) ? $types[$submission['form_type']] : 'Submission'); ?>
            #<?php echo esc_html($submission['id']); ?>
        </h1>

        <a class="page-title-action"
           href="<?php echo esc_url(admin_url('admin.php?page=cwcp-submissions&form_type=' . $submission['form_type'])); ?>">
            Back to list
        </a>

        <hr class="wp-header-end" />

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Submission updated.</p></div>
        <?php endif; ?>

        <div class="cwcp-admin-columns">

            <div class="cwcp-admin-box">

                <h2>Submitted Details</h2>

                <table class="widefat striped">
                    <tbody>
                        <?php if ($schema) : ?>

                            <?php foreach ($schema as $key => $field) : ?>
                                <?php if ('file' === $field['type']) : ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <tr>
                                    <th style="width:220px;"><?php echo esc_html($field['label']); ?></th>
                                    <td><?php echo nl2br(esc_html(isset($data[$key]) ? $data[$key] : '')); ?></td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else : ?>

                            <?php foreach ($data as $key => $value) : ?>
                                <tr>
                                    <th style="width:220px;"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                                    <td><?php echo nl2br(esc_html($value)); ?></td>
                                </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                        <tr>
                            <th>Submitted On</th>
                            <td><?php echo esc_html(cwcp_format_date($submission['created_at'], 'd M Y H:i')); ?></td>
                        </tr>

                        <?php if ($submission['related_id']) : ?>
                            <tr>
                                <th>Related Tender</th>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($submission['related_id'])); ?>">
                                        <?php echo esc_html(get_the_title($submission['related_id'])); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($submission['user_id']) : ?>
                            <tr>
                                <th>Portal Account</th>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=cwcp-candidates&action=view&user_id=' . $submission['user_id'])); ?>">
                                        View candidate profile
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <th>IP Address</th>
                            <td><?php echo esc_html($submission['ip']); ?></td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="cwcp-admin-box cwcp-admin-side">

                <h2>Document</h2>

                <?php if ($submission['attachment_id'] && get_post($submission['attachment_id'])) : ?>
                    <p>
                        <a class="button button-primary" target="_blank" rel="noopener"
                           href="<?php echo esc_url(cwcp_document_url($submission['attachment_id'])); ?>">Download</a>
                    </p>
                    <?php if ('application/pdf' === get_post_mime_type($submission['attachment_id'])) : ?>
                        <iframe class="cwcp-resume-preview"
                                src="<?php echo esc_url(cwcp_document_url($submission['attachment_id'])); ?>"
                                title="Document preview"></iframe>
                    <?php endif; ?>
                <?php else : ?>
                    <p>No document attached.</p>
                <?php endif; ?>

                <h2>Status &amp; Notes</h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>">

                    <?php wp_nonce_field('cwcp_update_submission_' . $submission['id']); ?>

                    <input type="hidden" name="page" value="cwcp-submissions" />
                    <input type="hidden" name="cwcp_admin_action" value="update_submission" />
                    <input type="hidden" name="id" value="<?php echo esc_attr($submission['id']); ?>" />

                    <p>
                        <select name="status" class="widefat">
                            <?php echo cwcp_select_options(cwcp_submission_statuses(), $submission['status']); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        </select>
                    </p>

                    <p>
                        <textarea name="admin_notes" rows="6" class="widefat"
                                  placeholder="Internal notes"><?php echo esc_textarea((string) $submission['admin_notes']); ?></textarea>
                    </p>

                    <p><button type="submit" class="button button-primary">Save</button></p>

                </form>

                <p>
                    <a class="button" href="<?php echo esc_url('mailto:' . $submission['email']); ?>">Email applicant</a>
                </p>

            </div>

        </div>

    </div>
    <?php
}
