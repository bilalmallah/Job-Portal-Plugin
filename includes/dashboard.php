<?php
/**
 * CareerHub - Candidate dashboard.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}

function cwcp_dashboard_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();
    $user    = wp_get_current_user();

    $completeness = cwcp_profile_completeness($user_id);

    $total_applications = cwcp_count_applications(array('user_id' => $user_id));

    $shortlisted = cwcp_count_applications(array('user_id' => $user_id, 'status' => 'shortlisted'))
        + cwcp_count_applications(array('user_id' => $user_id, 'status' => 'interview'));

    $hired = cwcp_count_applications(array('user_id' => $user_id, 'status' => 'hired'));

    $saved_count = count(cwcp_get_saved_jobs($user_id));

    $recent = cwcp_get_applications(array('user_id' => $user_id, 'per_page' => 5));

    $latest_jobs = cwcp_query_jobs(array('per_page' => 4));

    $extra_items = array('resume', 'education', 'experience');

    $checklist = array(
        'Personal details'         => !array_diff($completeness['missing'], $extra_items),
        'Education added'          => !in_array('education', $completeness['missing'], true),
        'Work experience answered' => !in_array('experience', $completeness['missing'], true),
        'Resume uploaded'          => !in_array('resume', $completeness['missing'], true),
        'Skills added (optional)'  => (bool) cwcp_get_skills($user_id),
        'Profile photo (optional)' => (bool) cwcp_get_photo_id($user_id),
    );

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'dashboard',
        'Welcome back, ' . $user->display_name,
        'Here is what is happening with your applications.'
    );
    ?>

    <div class="cwcp-stat-grid">

        <div class="cwcp-stat-card">
            <span class="cwcp-stat-icon cwcp-stat-blue"><i class="fa-solid fa-paper-plane"></i></span>
            <div>
                <strong><?php echo esc_html($total_applications); ?></strong>
                <span>Applications</span>
            </div>
        </div>

        <div class="cwcp-stat-card">
            <span class="cwcp-stat-icon cwcp-stat-amber"><i class="fa-solid fa-star"></i></span>
            <div>
                <strong><?php echo esc_html($shortlisted); ?></strong>
                <span>Shortlisted / Interview</span>
            </div>
        </div>

        <div class="cwcp-stat-card">
            <span class="cwcp-stat-icon cwcp-stat-green"><i class="fa-solid fa-handshake"></i></span>
            <div>
                <strong><?php echo esc_html($hired); ?></strong>
                <span>Offers</span>
            </div>
        </div>

        <div class="cwcp-stat-card">
            <span class="cwcp-stat-icon cwcp-stat-purple"><i class="fa-solid fa-bookmark"></i></span>
            <div>
                <strong><?php echo esc_html($saved_count); ?></strong>
                <span>Saved Jobs</span>
            </div>
        </div>

    </div>

    <div class="cwcp-grid cwcp-grid-2 cwcp-gap-25">

        <div class="cwcp-card cwcp-pad">

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-list-check"></i></span>
                <h2>Account Checklist</h2>
            </div>

            <div class="cwcp-progress-block cwcp-mb-20">
                <div class="cwcp-progress-label">
                    <span>Overall completeness</span>
                    <strong><?php echo esc_html($completeness['percent']); ?>%</strong>
                </div>
                <div class="cwcp-progress">
                    <span class="cwcp-progress-bar <?php echo $completeness['is_complete'] ? 'is-complete' : ''; ?>"
                          style="width: <?php echo esc_attr($completeness['percent']); ?>%"></span>
                </div>
            </div>

            <ul class="cwcp-checklist">
                <?php foreach ($checklist as $label => $done) : ?>
                    <li class="<?php echo $done ? 'is-done' : ''; ?>">
                        <i class="fa-solid <?php echo $done ? 'fa-circle-check' : 'fa-circle'; ?>"></i>
                        <?php echo esc_html($label); ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (!$completeness['is_complete']) : ?>
                <a class="cwcp-btn-primary" href="<?php echo esc_url(cwcp_profile_url()); ?>">
                    <i class="fa-solid fa-id-card"></i> Complete My Account
                </a>
            <?php else : ?>
                <a class="cwcp-btn-primary" href="<?php echo esc_url(cwcp_jobs_url()); ?>">
                    <i class="fa-solid fa-magnifying-glass"></i> Find Jobs
                </a>
            <?php endif; ?>

        </div>

        <div class="cwcp-card cwcp-pad">

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                <h2>Recent Applications</h2>
            </div>

            <?php if (empty($recent)) : ?>

                <div class="cwcp-empty">
                    <div class="cwcp-empty-icon"><i class="fa-solid fa-paper-plane"></i></div>
                    <h3>Nothing yet</h3>
                    <p>Your applications will appear here.</p>
                </div>

            <?php else : ?>

                <ul class="cwcp-activity-list">
                    <?php foreach ($recent as $application) : ?>
                        <li>
                            <div>
                                <a href="<?php echo esc_url(get_permalink($application['job_id'])); ?>">
                                    <strong><?php echo esc_html($application['job_title'] ? $application['job_title'] : 'Job removed'); ?></strong>
                                </a>
                                <small class="cwcp-text-muted"><?php echo esc_html(cwcp_format_date($application['applied_at'])); ?></small>
                            </div>
                            <span class="cwcp-badge <?php echo esc_attr(cwcp_status_badge_class($application['status'])); ?>">
                                <?php echo esc_html(cwcp_status_label($application['status'])); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_applied_jobs_url()); ?>">
                    View all applications
                </a>

            <?php endif; ?>

        </div>

    </div>

    <div class="cwcp-card cwcp-pad cwcp-mt-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-briefcase"></i></span>
            <h2>Latest Openings</h2>
        </div>

        <?php if ($latest_jobs->have_posts()) : ?>

            <div class="cwcp-job-list">
                <?php
                while ($latest_jobs->have_posts()) {

                    $latest_jobs->the_post();

                    echo cwcp_render_job_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput
                }

                wp_reset_postdata();
                ?>
            </div>

            <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_jobs_url()); ?>">See all jobs</a>

        <?php else : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                <h3>No open positions right now</h3>
                <p>Please check back soon.</p>
            </div>

        <?php endif; ?>

    </div>

    <div class="cwcp-card cwcp-pad cwcp-mt-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
            <h2>Other Ways to Join Us</h2>
        </div>

        <div class="cwcp-quick-links">

            <a class="cwcp-quick-link" href="<?php echo esc_url(cwcp_page_url('volunteer')); ?>">
                <i class="fa-solid fa-hands-helping"></i>
                <span>Volunteer with us</span>
            </a>

            <a class="cwcp-quick-link" href="<?php echo esc_url(cwcp_page_url('internship')); ?>">
                <i class="fa-solid fa-user-graduate"></i>
                <span>Apply for an internship</span>
            </a>

            <a class="cwcp-quick-link" href="<?php echo esc_url(cwcp_page_url('field_facilitator')); ?>">
                <i class="fa-solid fa-people-carry-box"></i>
                <span>Field facilitator roster</span>
            </a>

            <a class="cwcp-quick-link" href="<?php echo esc_url(cwcp_page_url('tenders')); ?>">
                <i class="fa-solid fa-file-signature"></i>
                <span>Tenders &amp; donations</span>
            </a>

        </div>

    </div>

    <?php

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

cwcp_add_shortcode('dashboard', 'cwcp_dashboard_shortcode');
