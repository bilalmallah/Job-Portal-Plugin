<?php
/**
 * CareerHub - Candidate skills.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


function cwcp_get_skills($user_id) {

    global $wpdb;

    $table = cwcp_table('skills');

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id
        ),
        ARRAY_A
    );
}


/*
|--------------------------------------------------------------------------
| Form Handling
|--------------------------------------------------------------------------
*/

function cwcp_handle_skill_actions() {

    if (!isset($_REQUEST['cwcp_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_REQUEST['cwcp_action']));

    if (!in_array($action, array('add_skill', 'delete_skill'), true)) {
        return;
    }

    $user_id = get_current_user_id();

    if (!$user_id) {
        cwcp_redirect(cwcp_login_url());
    }

    global $wpdb;

    $table = cwcp_table('skills');

    if ('delete_skill' === $action) {

        $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        if (
            !isset($_REQUEST['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'cwcp_delete_skill_' . $id)
        ) {
            cwcp_add_notice('Security check failed.', 'error');
            cwcp_redirect(cwcp_skills_url());
        }

        $wpdb->delete($table, array('id' => $id, 'user_id' => $user_id), array('%d', '%d'));

        cwcp_add_notice('Skill removed.', 'success');
        cwcp_redirect(cwcp_skills_url());
    }

    if (
        !isset($_POST['cwcp_skill_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cwcp_skill_nonce'])), 'cwcp_add_skill')
    ) {
        cwcp_add_notice('Security check failed.', 'error');
        cwcp_redirect(cwcp_skills_url());
    }

    $skill_name  = isset($_POST['skill_name']) ? sanitize_text_field(wp_unslash($_POST['skill_name'])) : '';
    $skill_level = isset($_POST['skill_level']) ? sanitize_text_field(wp_unslash($_POST['skill_level'])) : '';

    if ('' === $skill_name) {

        cwcp_add_notice('Please enter a skill.', 'error');
        cwcp_redirect(cwcp_skills_url());
    }

    $levels = cwcp_skill_levels();

    if (!isset($levels[$skill_level])) {
        $skill_level = 'Intermediate';
    }

    /*
     * Avoid duplicates.
     */

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND skill_name = %s", // phpcs:ignore WordPress.DB.PreparedSQL
            $user_id,
            $skill_name
        )
    );

    if ($exists) {

        $wpdb->update($table, array('skill_level' => $skill_level), array('id' => $exists));

        cwcp_add_notice('Skill level updated.', 'success');
        cwcp_redirect(cwcp_skills_url());
    }

    $wpdb->insert(
        $table,
        array(
            'user_id'     => $user_id,
            'skill_name'  => $skill_name,
            'skill_level' => $skill_level,
            'created_at'  => current_time('mysql'),
        )
    );

    cwcp_add_notice('Skill added.', 'success');
    cwcp_redirect(cwcp_skills_url());
}

add_action('template_redirect', 'cwcp_handle_skill_actions', 5);


/*
|--------------------------------------------------------------------------
| Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_skills_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();

    $skills = cwcp_get_skills($user_id);

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'skills',
        'Skills',
        'List the skills that make you a good fit for our roles.'
    );
    ?>

    <div class="cwcp-card cwcp-pad cwcp-mb-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-lightbulb"></i></span>
            <h2>Add a Skill</h2>
        </div>

        <form method="post" class="cwcp-form">

            <?php wp_nonce_field('cwcp_add_skill', 'cwcp_skill_nonce'); ?>
            <input type="hidden" name="cwcp_action" value="add_skill" />

            <div class="cwcp-grid cwcp-grid-2">

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-skill-name">Skill <span class="cwcp-req">*</span></label>
                    <input class="cwcp-form-input" type="text" id="cwcp-skill-name" name="skill_name"
                           placeholder="Community mobilization, MS Excel, First Aid…" required />
                </div>

                <div class="cwcp-form-group">
                    <label class="cwcp-form-label" for="cwcp-skill-level">Level</label>
                    <select class="cwcp-form-input" id="cwcp-skill-level" name="skill_level">
                        <?php echo cwcp_select_options(cwcp_skill_levels(), 'Intermediate'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    </select>
                </div>

            </div>

            <div class="cwcp-form-actions">
                <button type="submit" class="cwcp-btn-primary"><i class="fa-solid fa-plus"></i> Add Skill</button>
            </div>

        </form>
    </div>

    <div class="cwcp-card cwcp-pad">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-tags"></i></span>
            <h2>Your Skills (<?php echo count($skills); ?>)</h2>
        </div>

        <?php if (empty($skills)) : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-lightbulb"></i></div>
                <h3>No skills added yet</h3>
            </div>

        <?php else : ?>

            <div class="cwcp-skill-list">
                <?php foreach ($skills as $skill) : ?>
                    <span class="cwcp-skill-chip">
                        <strong><?php echo esc_html($skill['skill_name']); ?></strong>
                        <em><?php echo esc_html($skill['skill_level']); ?></em>
                        <a title="Remove"
                           onclick="return confirm('Remove this skill?');"
                           href="<?php echo esc_url(
                               wp_nonce_url(
                                   add_query_arg(
                                       array('cwcp_action' => 'delete_skill', 'id' => $skill['id']),
                                       cwcp_skills_url()
                                   ),
                                   'cwcp_delete_skill_' . $skill['id']
                               )
                           ); ?>"><i class="fa-solid fa-xmark"></i></a>
                    </span>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>

    <?php

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

cwcp_add_shortcode('skills', 'cwcp_skills_shortcode');
