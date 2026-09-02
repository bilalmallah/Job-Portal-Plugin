<?php
/**
 * CareerHub - Shared portal chrome (sidebar + header).
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}

function cwcp_portal_menu() {

    return array(
        'dashboard'    => array('label' => 'Dashboard',    'icon' => 'fa-gauge-high',      'url' => cwcp_dashboard_url()),
        'profile'      => array('label' => 'My Profile',   'icon' => 'fa-id-card',         'url' => cwcp_profile_url()),
        'education'    => array('label' => 'Education',    'icon' => 'fa-graduation-cap',  'url' => cwcp_education_url()),
        'experience'   => array('label' => 'Experience',   'icon' => 'fa-briefcase',       'url' => cwcp_experience_url()),
        'skills'       => array('label' => 'Skills',       'icon' => 'fa-lightbulb',       'url' => cwcp_skills_url()),
        'resume'       => array('label' => 'Resume',       'icon' => 'fa-file-lines',      'url' => cwcp_resume_url()),
        'jobs'         => array('label' => 'Browse Jobs',  'icon' => 'fa-magnifying-glass','url' => cwcp_jobs_url()),
        'applied_jobs' => array('label' => 'Applied Jobs', 'icon' => 'fa-paper-plane',     'url' => cwcp_applied_jobs_url()),
        'saved_jobs'   => array('label' => 'Saved Jobs',   'icon' => 'fa-bookmark',        'url' => cwcp_saved_jobs_url()),
    );
}

/**
 * Opens the portal shell. Every candidate screen calls this, renders its
 * content and then calls cwcp_portal_close().
 */
function cwcp_portal_open($active = '', $title = '', $subtitle = '', $actions = '') {

    $user = wp_get_current_user();

    $completeness = cwcp_profile_completeness($user->ID);

    ob_start();
    ?>
    <div class="cwcp-page cwcp-scope cwcp-portal">
        <div class="cwcp-container">

            <div class="cwcp-portal-grid">

                <aside class="cwcp-sidebar">

                    <div class="cwcp-sidebar-user">

                        <div class="cwcp-avatar"><?php echo get_avatar($user->ID, 64); ?></div>

                        <div class="cwcp-sidebar-user-info">
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <span><?php echo esc_html($user->user_email); ?></span>
                        </div>
                    </div>

                    <div class="cwcp-progress-block">
                        <div class="cwcp-progress-label">
                            <span>Profile completeness</span>
                            <strong><?php echo esc_html($completeness['percent']); ?>%</strong>
                        </div>
                        <div class="cwcp-progress">
                            <span class="cwcp-progress-bar <?php echo $completeness['is_complete'] ? 'is-complete' : ''; ?>"
                                  style="width: <?php echo esc_attr($completeness['percent']); ?>%"></span>
                        </div>
                        <?php if (!$completeness['is_complete']) : ?>
                            <a class="cwcp-progress-link" href="<?php echo esc_url(cwcp_profile_url()); ?>">
                                Complete your account
                            </a>
                        <?php endif; ?>
                    </div>

                    <nav class="cwcp-sidebar-nav">
                        <?php foreach (cwcp_portal_menu() as $key => $item) : ?>
                            <a class="cwcp-sidebar-link <?php echo $active === $key ? 'is-active' : ''; ?>"
                               href="<?php echo esc_url($item['url']); ?>">
                                <i class="fa-solid <?php echo esc_attr($item['icon']); ?>"></i>
                                <span><?php echo esc_html($item['label']); ?></span>
                            </a>
                        <?php endforeach; ?>

                        <a class="cwcp-sidebar-link cwcp-sidebar-logout" href="<?php echo esc_url(cwcp_logout_url()); ?>">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </a>
                    </nav>

                </aside>

                <main class="cwcp-portal-main">

                    <?php if ($title) : ?>
                        <div class="cwcp-page-header">
                            <div>
                                <h1><?php echo esc_html($title); ?></h1>
                                <?php if ($subtitle) : ?>
                                    <p class="cwcp-text-muted"><?php echo esc_html($subtitle); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($actions) : ?>
                                <div class="cwcp-page-header-actions">
                                    <?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

                    <?php if (!$completeness['is_complete'] && 'profile' !== $active) : ?>
                        <div class="cwcp-alert cwcp-alert-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>
                                Your account is incomplete. Add the missing information
                                (<?php echo esc_html(implode(', ', array_slice($completeness['missing_labels'], 0, 4))); ?><?php echo count($completeness['missing_labels']) > 4 ? '…' : ''; ?>)
                                before applying for jobs.
                                <a href="<?php echo esc_url(cwcp_profile_url()); ?>">Complete now</a>
                            </span>
                        </div>
                    <?php endif; ?>
    <?php

    return ob_get_clean();
}

function cwcp_portal_close() {

    return '</main></div></div></div>';
}

/**
 * Wraps any public (non-portal) screen such as the jobs listing for guests.
 */
function cwcp_public_open($title = '', $subtitle = '') {

    ob_start();
    ?>
    <div class="cwcp-page cwcp-scope cwcp-public">
        <div class="cwcp-container">
            <?php if ($title) : ?>
                <div class="cwcp-page-header">
                    <div>
                        <h1><?php echo esc_html($title); ?></h1>
                        <?php if ($subtitle) : ?>
                            <p class="cwcp-text-muted"><?php echo esc_html($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
    <?php

    return ob_get_clean();
}

function cwcp_public_close() {

    return '</div></div>';
}
