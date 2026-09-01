<?php
/**
 * CareerHub - Education history.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Data Access
|--------------------------------------------------------------------------
*/

function cwcp_get_education($user_id) {

    global $wpdb;

    $table = cwcp_table('education');

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY passing_year DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id
        ),
        ARRAY_A
    );
}

function cwcp_get_education_row($id, $user_id = 0) {

    global $wpdb;

    $table = cwcp_table('education');

    if ($user_id) {

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $id, $user_id), // phpcs:ignore WordPress.DB.PreparedSQL
            ARRAY_A
        );
    }

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), // phpcs:ignore WordPress.DB.PreparedSQL
        ARRAY_A
    );
}


/*
|--------------------------------------------------------------------------
| Form Handling
|--------------------------------------------------------------------------
*/

function cwcp_handle_education_actions() {

    if (!isset($_REQUEST['cwcp_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_REQUEST['cwcp_action']));

    if (!in_array($action, array('save_education', 'delete_education'), true)) {
        return;
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        cwcp_redirect(cwcp_login_url());
    }

    global $wpdb;

    $table = cwcp_table('education');

    if ('delete_education' === $action) {

        $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (
            !isset($_REQUEST['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'cwcp_delete_education_' . $id)
        ) {
            cwcp_add_notice('Security check failed.', 'error');
            cwcp_redirect(cwcp_education_url());
        }

        $wpdb->delete($table, array('id' => $id, 'user_id' => $user_id), array('%d', '%d'));

        cwcp_add_notice('Education record deleted.', 'success');
        cwcp_redirect(cwcp_education_url());
    }

    if (
        !isset($_POST['cwcp_education_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_education_nonce'])), 'cwcp_save_education')
    ) {
        cwcp_add_notice('Security check failed.', 'error');
        cwcp_redirect(cwcp_education_url());
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $data = array(
        'degree_level'     => isset($_POST['degree_level']) ? sanitize_text_field(wp_unslash($_POST['degree_level'])) : '',
        'degree_title'     => isset($_POST['degree_title']) ? sanitize_text_field(wp_unslash($_POST['degree_title'])) : '',
        'institute'        => isset($_POST['institute']) ? sanitize_text_field(wp_unslash($_POST['institute'])) : '',
        'board_university' => isset($_POST['board_university']) ? sanitize_text_field(wp_unslash($_POST['board_university'])) : '',
        'passing_year'     => isset($_POST['passing_year']) ? sanitize_text_field(wp_unslash($_POST['passing_year'])) : '',
        'obtained_marks'   => isset($_POST['obtained_marks']) ? sanitize_text_field(wp_unslash($_POST['obtained_marks'])) : '',
        'total_marks'      => isset($_POST['total_marks']) ? sanitize_text_field(wp_unslash($_POST['total_marks'])) : '',
        'grade'            => isset($_POST['grade']) ? sanitize_text_field(wp_unslash($_POST['grade'])) : '',
    );

    $errors = array();

    if ('' === $data['degree_level']) {
        $errors[] = 'Please select a degree level.';
    }

    if ('' === $data['degree_title']) {
        $errors[] = 'Degree title is required.';
    }

    if ('' === $data['institute']) {
        $errors[] = 'Institute name is required.';
    }

    $year = (int) $data['passing_year'];

    if ($year < 1950 || $year > (int) gmdate('Y') + 8) {
        $errors[] = 'Please enter a valid passing year.';
    }

    if ($errors) {

        foreach ($errors as $error) {
            cwcp_add_notice($error, 'error');
        }

        cwcp_redirect(cwcp_education_url());
    }

    if ($id && cwcp_get_education_row($id, $user_id)) {

        $wpdb->update($table, $data, array('id' => $id, 'user_id' => $user_id));

        cwcp_add_notice('Education record updated.', 'success');

    } else {

        $data['user_id']    = $user_id;
        $data['created_at'] = current_time('mysql');

        $wpdb->insert($table, $data);

        cwcp_add_notice('Education record added.', 'success');
    }

    cwcp_redirect(cwcp_education_url());
}

add_action('template_redirect', 'cwcp_handle_education_actions', 5);


/*
|--------------------------------------------------------------------------
| Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_education_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();

    $records = cwcp_get_education($user_id);

    $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

    $editing = $edit_id ? cwcp_get_education_row($edit_id, $user_id) : null;

    $value = function ($key) use ($editing) {
        return $editing && isset($editing[$key]) ? $editing[$key] : '';
    };

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'education',
        'Education',
        'Add every degree, diploma or certification you have completed.'
    );
    ?>

    <div class="cwcp-card cwcp-pad cwcp-mb-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-graduation-cap"></i></span>
            <h2><?php echo $editing ? 'Edit Education' : 'Add Education'; ?></h2>
        </div>

        <form method="post" class="cwcp-form">

            <?php wp_nonce_field('cwcp_save_education', 'cwcp_education_nonce'); ?>
            <input type="hidden" name="cwcp_action" value="save_education" />
            <input type="hidden" name="id" value="<?php echo esc_attr($value('id')); ?>" />

            <div class="cwcp-grid cwcp-grid-2">

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-degree-level">Degree Level <span class="cwcp-req">*</span></label>
                    <select class="cwcp-form-input" id="cwcp-degree-level" name="degree_level" required>
                        <?php echo cwcp_select_options(cwcp_degree_levels(), $value('degree_level'), '-- Select --'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    </select>
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-degree-title">Degree Title <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="text" id="cwcp-degree-title" name="degree_title"
                           placeholder="BS Computer Science" value="<?php echo esc_attr($value('degree_title')); ?>" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-institute">Institute / College <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="text" id="cwcp-institute" name="institute"
                           value="<?php echo esc_attr($value('institute')); ?>" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-board">Board / University</label>
                    <input class="cwcp-form-input" type="text" id="cwcp-board" name="board_university"
                           value="<?php echo esc_attr($value('board_university')); ?>" />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-passing-year">Passing Year <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="number" id="cwcp-passing-year" name="passing_year"
                           min="1950" max="<?php echo esc_attr((int) gmdate('Y') + 8); ?>"
                           value="<?php echo esc_attr($value('passing_year')); ?>" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-grade">Grade / CGPA</label>
                    <input class="cwcp-form-input" type="text" id="cwcp-grade" name="grade"
                           placeholder="A / 3.4" value="<?php echo esc_attr($value('grade')); ?>" />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-obtained">Obtained Marks</label>
                    <input class="cwcp-form-input" type="text" id="cwcp-obtained" name="obtained_marks"
                           value="<?php echo esc_attr($value('obtained_marks')); ?>" />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-total">Total Marks</label>
                    <input class="cwcp-form-input" type="text" id="cwcp-total" name="total_marks"
                           value="<?php echo esc_attr($value('total_marks')); ?>" />
                </div>

            </div>

            <div class="cwcp-form-actions">
                <button type="submit" class="cwcp-btn-primary">
                    <i class="fa-solid fa-<?php echo $editing ? 'floppy-disk' : 'plus'; ?>"></i>
                    <?php echo $editing ? 'Update Record' : 'Add Education'; ?>
                </button>

                <?php if ($editing) : ?>
                    <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_education_url()); ?>">Cancel</a>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <div class="cwcp-card cwcp-pad">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-list"></i></span>
            <h2>Your Education (<?php echo count($records); ?>)</h2>
        </div>

        <?php if (empty($records)) : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h3>No education added yet</h3>
                <p>Use the form above to add your qualifications.</p>
            </div>

        <?php else : ?>

            <div class="cwcp-table-wrapper">
                <table class="cwcp-table">
                    <thead>
                        <tr>
                            <th>Degree</th>
                            <th>Institute</th>
                            <th>Year</th>
                            <th>Grade</th>
                            <th class="cwcp-text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($record['degree_title']); ?></strong><br />
                                    <small class="cwcp-text-muted"><?php echo esc_html($record['degree_level']); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($record['institute']); ?>
                                    <?php if ($record['board_university']) : ?>
                                        <br /><small class="cwcp-text-muted"><?php echo esc_html($record['board_university']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($record['passing_year']); ?></td>
                                <td>
                                    <?php
                                    echo esc_html($record['grade']);

                                    if ($record['obtained_marks'] && $record['total_marks']) {
                                        echo '<br /><small class="cwcp-text-muted">'
                                            . esc_html($record['obtained_marks'] . ' / ' . $record['total_marks'])
                                            . '</small>';
                                    }
                                    ?>
                                </td>
                                <td class="cwcp-text-center cwcp-row-actions">
                                    <a class="cwcp-icon-btn" title="Edit"
                                       href="<?php echo esc_url(add_query_arg('edit', $record['id'], cwcp_education_url())); ?>">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <a class="cwcp-icon-btn cwcp-icon-btn-danger" title="Delete"
                                       onclick="return confirm('Delete this education record?');"
                                       href="<?php echo esc_url(
                                           wp_nonce_url(
                                               add_query_arg(
                                                   array('cwcp_action' => 'delete_education', 'id' => $record['id']),
                                                   cwcp_education_url()
                                               ),
                                               'cwcp_delete_education_' . $record['id']
                                           )
                                       ); ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

    <?php

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

cwcp_add_shortcode('education', 'cwcp_education_shortcode');
