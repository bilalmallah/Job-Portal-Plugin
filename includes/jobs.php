<?php
/**
 * CareerHub - Job listing, job detail and the apply box.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Job Query
|--------------------------------------------------------------------------
*/

function cwcp_query_jobs($args = array()) {

    $args = wp_parse_args(
        $args,
        array(
            'search'   => '',
            'category' => '',
            'type'     => '',
            'province' => '',
            'per_page' => 10,
            'paged'    => 1,
        )
    );

    $query_args = array(
        'post_type'      => 'cw_job',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $args['per_page'],
        'paged'          => (int) $args['paged'],
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ($args['search']) {
        $query_args['s'] = $args['search'];
    }

    $tax_query = array();

    if ($args['category']) {
        $tax_query[] = array(
            'taxonomy' => 'cw_job_category',
            'field'    => 'slug',
            'terms'    => $args['category'],
        );
    }

    if ($args['type']) {
        $tax_query[] = array(
            'taxonomy' => 'cw_job_type',
            'field'    => 'slug',
            'terms'    => $args['type'],
        );
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }

    if ($tax_query) {
        $query_args['tax_query'] = $tax_query;
    }

    if ($args['province']) {
        $query_args['meta_query'] = array(
            array(
                'key'   => 'cwcp_job_province',
                'value' => $args['province'],
            ),
        );
    }

    return new WP_Query($query_args);
}


/*
|--------------------------------------------------------------------------
| Job Card
|--------------------------------------------------------------------------
*/

function cwcp_render_job_card($job_id) {

    $is_open   = cwcp_job_is_open($job_id);
    $deadline  = cwcp_job_meta($job_id, 'deadline');
    $location  = cwcp_job_meta($job_id, 'location');
    $positions = cwcp_job_meta($job_id, 'positions');

    $user_id = get_current_user_id();

    $applied = $user_id ? cwcp_has_applied($user_id, $job_id) : false;
    $saved   = $user_id ? cwcp_is_job_saved($user_id, $job_id) : false;

    ob_start();
    ?>
    <article class="cwcp-job-card">

        <div class="cwcp-job-card-head">

            <div>
                <h3 class="cwcp-job-title">
                    <a href="<?php echo esc_url(get_permalink($job_id)); ?>"><?php echo esc_html(get_the_title($job_id)); ?></a>
                </h3>

                <div class="cwcp-job-tags">
                    <?php
                    $type = cwcp_job_terms_list($job_id, 'cw_job_type');
                    $cat  = cwcp_job_terms_list($job_id, 'cw_job_category');
                    ?>
                    <?php if ($type) : ?>
                        <span class="cwcp-badge cwcp-badge-primary"><i class="fa-solid fa-clock"></i> <?php echo esc_html($type); ?></span>
                    <?php endif; ?>
                    <?php if ($cat) : ?>
                        <span class="cwcp-badge cwcp-badge-neutral"><i class="fa-solid fa-folder"></i> <?php echo esc_html($cat); ?></span>
                    <?php endif; ?>
                    <?php if (!$is_open) : ?>
                        <span class="cwcp-badge cwcp-badge-danger">Closed</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (is_user_logged_in() && cwcp_is_candidate()) : ?>
                <button type="button"
                        class="cwcp-save-toggle <?php echo $saved ? 'is-saved' : ''; ?>"
                        data-job-id="<?php echo esc_attr($job_id); ?>"
                        title="<?php echo $saved ? 'Remove from saved jobs' : 'Save this job'; ?>">
                    <i class="fa-<?php echo $saved ? 'solid' : 'regular'; ?> fa-bookmark"></i>
                </button>
            <?php endif; ?>

        </div>

        <div class="cwcp-job-meta">

            <?php if ($location) : ?>
                <span><i class="fa-solid fa-location-dot"></i> <?php echo esc_html($location); ?></span>
            <?php endif; ?>

            <?php if ($positions) : ?>
                <span><i class="fa-solid fa-users"></i> <?php echo esc_html($positions); ?> position(s)</span>
            <?php endif; ?>

            <?php if ($deadline) : ?>
                <span><i class="fa-regular fa-calendar-xmark"></i> Apply before <?php echo esc_html(cwcp_format_date($deadline)); ?></span>
            <?php endif; ?>

            <span><i class="fa-regular fa-clock"></i> Posted <?php echo esc_html(human_time_diff(get_post_time('U', true, $job_id), current_time('timestamp', true))); ?> ago</span>
        </div>

        <p class="cwcp-job-excerpt">
            <?php echo esc_html(wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $job_id)), 28)); ?>
        </p>

        <div class="cwcp-job-card-actions">

            <a class="cwcp-btn-secondary" href="<?php echo esc_url(get_permalink($job_id)); ?>">
                <i class="fa-solid fa-eye"></i> View Details
            </a>

            <?php echo cwcp_render_apply_button($job_id, $applied, $is_open); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        </div>

    </article>
    <?php

    return ob_get_clean();
}

/**
 * The one click apply control, used on cards and on the single job page.
 */
function cwcp_render_apply_button($job_id, $applied = null, $is_open = null) {

    $user_id = get_current_user_id();

    if (null === $is_open) {
        $is_open = cwcp_job_is_open($job_id);
    }

    if (null === $applied) {
        $applied = $user_id ? cwcp_has_applied($user_id, $job_id) : false;
    }

    if (!$is_open) {

        return '<span class="cwcp-btn-disabled"><i class="fa-solid fa-lock"></i> Applications Closed</span>';
    }

    if ($applied) {

        return '<span class="cwcp-btn-applied"><i class="fa-solid fa-circle-check"></i> Applied</span>';
    }

    if (!is_user_logged_in()) {

        $login = add_query_arg('redirect_to', rawurlencode(get_permalink($job_id)), cwcp_login_url());

        return '<a class="cwcp-btn-primary" href="' . esc_url($login) . '">'
            . '<i class="fa-solid fa-right-to-bracket"></i> Login to Apply</a>';
    }

    if (!cwcp_is_profile_complete($user_id)) {

        return '<a class="cwcp-btn-warning" href="' . esc_url(cwcp_profile_url()) . '">'
            . '<i class="fa-solid fa-triangle-exclamation"></i> Complete Account to Apply</a>';
    }

    ob_start();
    ?>
    <form method="post" class="cwcp-apply-form cwcp-inline-form">
        <?php wp_nonce_field('cwcp_apply_' . $job_id, 'cwcp_apply_nonce'); ?>
        <input type="hidden" name="cwcp_action" value="apply_job" />
        <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>" />
        <button type="submit" class="cwcp-btn-primary cwcp-apply-btn" data-job-id="<?php echo esc_attr($job_id); ?>">
            <i class="fa-solid fa-bolt"></i> Easy Apply
        </button>
    </form>
    <?php

    return ob_get_clean();
}


/*
|--------------------------------------------------------------------------
| Jobs Listing Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_jobs_shortcode($atts) {

    $atts = shortcode_atts(
        array(
            'per_page' => 10,
        ),
        $atts,
        'carewave_jobs'
    );

    /*
     * A job can also be opened inside the listing page via ?job_id=
     */

    $single_id = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;

    if ($single_id && 'cw_job' === get_post_type($single_id)) {
        return cwcp_render_single_job($single_id, true);
    }

    $search   = isset($_GET['cwcp_s']) ? sanitize_text_field(wp_unslash($_GET['cwcp_s'])) : '';
    $category = isset($_GET['cwcp_cat']) ? sanitize_title(wp_unslash($_GET['cwcp_cat'])) : '';
    $type     = isset($_GET['cwcp_type']) ? sanitize_title(wp_unslash($_GET['cwcp_type'])) : '';
    $province = isset($_GET['cwcp_province']) ? sanitize_text_field(wp_unslash($_GET['cwcp_province'])) : '';
    $paged = isset($_GET['jpage']) ? max(1, (int) $_GET['jpage']) : max(1, (int) get_query_var('paged'));

    $query = cwcp_query_jobs(
        array(
            'search'   => $search,
            'category' => $category,
            'type'     => $type,
            'province' => $province,
            'per_page' => (int) $atts['per_page'],
            'paged'    => $paged,
        )
    );

    $categories = get_terms(array('taxonomy' => 'cw_job_category', 'hide_empty' => false));
    $types      = get_terms(array('taxonomy' => 'cw_job_type', 'hide_empty' => false));

    ob_start();

    $logged_in_candidate = is_user_logged_in() && cwcp_is_candidate();

    echo $logged_in_candidate // phpcs:ignore WordPress.Security.EscapeOutput
        ? cwcp_portal_open('jobs', 'Browse Jobs', 'Find a role and apply with a single click.')
        : cwcp_public_open('Current Openings', 'Browse our latest vacancies and apply online.');
    ?>

    <form method="get" class="cwcp-job-filters cwcp-card cwcp-pad cwcp-mb-25">

        <?php if (!get_option('permalink_structure') && cwcp_get_page_id('jobs')) : ?>
            <input type="hidden" name="page_id" value="<?php echo esc_attr(cwcp_get_page_id('jobs')); ?>" />
        <?php endif; ?>

        <div class="cwcp-grid cwcp-grid-4">

            <div class="cwcp-form-group">
                <label class="cwcp-form-label" for="cwcp-s">Keyword</label>
                <input class="cwcp-form-input" type="search" id="cwcp-s" name="cwcp_s"
                       value="<?php echo esc_attr($search); ?>" placeholder="Job title…" />
            </div>

            <div class="cwcp-form-group">
                <label class="cwcp-form-label" for="cwcp-cat">Category</label>
                <select class="cwcp-form-input" id="cwcp-cat" name="cwcp_cat">
                    <option value="">All categories</option>
                    <?php if (!is_wp_error($categories)) : ?>
                        <?php foreach ($categories as $term) : ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($category, $term->slug); ?>>
                                <?php echo esc_html($term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="cwcp-form-group">
                <label class="cwcp-form-label" for="cwcp-type">Job Type</label>
                <select class="cwcp-form-input" id="cwcp-type" name="cwcp_type">
                    <option value="">All types</option>
                    <?php if (!is_wp_error($types)) : ?>
                        <?php foreach ($types as $term) : ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($type, $term->slug); ?>>
                                <?php echo esc_html($term->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="cwcp-form-group">
                <label class="cwcp-form-label" for="cwcp-province">Province</label>
                <select class="cwcp-form-input" id="cwcp-province" name="cwcp_province">
                    <option value="">All provinces</option>
                    <?php echo cwcp_select_options(cwcp_provinces(), $province); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </select>
            </div>

        </div>

        <div class="cwcp-form-actions">
            <button type="submit" class="cwcp-btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search Jobs</button>
            <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_jobs_url()); ?>">Reset</a>
        </div>

    </form>

    <?php if ($query->have_posts()) : ?>

        <p class="cwcp-result-count">
            <?php echo esc_html($query->found_posts); ?> job(s) found
        </p>

        <div class="cwcp-job-list">
            <?php
            while ($query->have_posts()) {

                $query->the_post();

                echo cwcp_render_job_card(get_the_ID()); // phpcs:ignore WordPress.Security.EscapeOutput
            }

            wp_reset_postdata();
            ?>
        </div>

        <?php
        $pagination = paginate_links(
            array(
                'total'     => $query->max_num_pages,
                'current'   => $paged,
                'format'    => '?jpage=%#%',
                'add_args'  => array_filter(
                    array(
                        'cwcp_s'        => $search,
                        'cwcp_cat'      => $category,
                        'cwcp_type'     => $type,
                        'cwcp_province' => $province,
                    )
                ),
                'prev_text' => '<i class="fa-solid fa-chevron-left"></i>',
                'next_text' => '<i class="fa-solid fa-chevron-right"></i>',
                'type'      => 'array',
            )
        );

        if ($pagination) {
            echo '<div class="cwcp-pagination">' . implode('', $pagination) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
        }
        ?>

    <?php else : ?>

        <div class="cwcp-card cwcp-pad">
            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                <h3>No jobs found</h3>
                <p>Try a different keyword or clear the filters.</p>
            </div>
        </div>

    <?php endif; ?>

    <?php

    echo $logged_in_candidate ? cwcp_portal_close() : cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

cwcp_add_shortcode('jobs', 'cwcp_jobs_shortcode');


/*
|--------------------------------------------------------------------------
| Single Job
|--------------------------------------------------------------------------
*/

function cwcp_render_single_job($job_id, $wrap = false) {

    $job = get_post($job_id);

    if (!$job) {
        return '';
    }

    $is_open = cwcp_job_is_open($job_id);

    ob_start();

    if ($wrap) {
        echo cwcp_public_open(); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    ?>
    <div class="cwcp-single-job">

        <?php if ($wrap) : ?>

            <a class="cwcp-back-link" href="<?php echo esc_url(cwcp_jobs_url()); ?>">
                <i class="fa-solid fa-arrow-left"></i> Back to all jobs
            </a>

            <div class="cwcp-job-hero">

                <h1 class="cwcp-single-job-title"><?php echo esc_html($job->post_title); ?></h1>

                <div class="cwcp-job-tags">
                    <?php $type = cwcp_job_terms_list($job_id, 'cw_job_type'); ?>
                    <?php $category = cwcp_job_terms_list($job_id, 'cw_job_category'); ?>

                    <?php if ($type) : ?>
                        <span class="cwcp-badge cwcp-badge-primary"><i class="fa-solid fa-clock"></i> <?php echo esc_html($type); ?></span>
                    <?php endif; ?>

                    <?php if ($category) : ?>
                        <span class="cwcp-badge cwcp-badge-neutral"><i class="fa-solid fa-folder"></i> <?php echo esc_html($category); ?></span>
                    <?php endif; ?>

                    <span class="cwcp-badge <?php echo $is_open ? 'cwcp-badge-success' : 'cwcp-badge-danger'; ?>">
                        <?php echo $is_open ? 'Open' : 'Closed'; ?>
                    </span>
                </div>

            </div>

        <?php endif; ?>

        <?php echo cwcp_job_layout($job_id, wp_kses_post(wpautop($job->post_content))); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    </div>
    <?php

    if ($wrap) {
        echo cwcp_public_close(); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    return ob_get_clean();
}

function cwcp_job_details_table($job_id, $style = 'grid') {

    $rows = array(
        'Job Type'         => cwcp_job_terms_list($job_id, 'cw_job_type'),
        'Category'         => cwcp_job_terms_list($job_id, 'cw_job_category'),
        'Location'         => cwcp_job_meta($job_id, 'location'),
        'Province'         => cwcp_job_meta($job_id, 'province'),
        'Positions'        => cwcp_job_meta($job_id, 'positions'),
        'Salary / Package' => cwcp_job_meta($job_id, 'salary'),
        'Experience'       => cwcp_job_meta($job_id, 'experience'),
        'Education'        => cwcp_job_meta($job_id, 'education'),
        'Preferred Gender' => cwcp_job_meta($job_id, 'gender'),
        'Deadline'         => cwcp_format_date(cwcp_job_meta($job_id, 'deadline')),
    );

    $rows = array_filter($rows, function ($value) {
        return '' !== trim((string) $value);
    });

    if (empty($rows)) {
        return '';
    }

    ob_start();
    ?>
    <div class="cwcp-card cwcp-pad cwcp-mb-25">

        <div class="cwcp-section-header">
            <span class="cwcp-section-header-icon"><i class="fa-solid fa-circle-info"></i></span>
            <h2>Job Summary</h2>
        </div>

        <?php if ('list' === $style) : ?>

            <ul class="cwcp-detail-list">
                <?php foreach ($rows as $label => $value) : ?>
                    <li>
                        <span class="cwcp-detail-label"><?php echo esc_html($label); ?></span>
                        <span class="cwcp-detail-value"><?php echo esc_html($value); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php else : ?>

            <div class="cwcp-detail-grid">
                <?php foreach ($rows as $label => $value) : ?>
                    <div class="cwcp-detail-item">
                        <span class="cwcp-detail-label"><?php echo esc_html($label); ?></span>
                        <span class="cwcp-detail-value"><?php echo esc_html($value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
    <?php

    return ob_get_clean();
}

/**
 * The two column job layout: description on the left, summary and the apply
 * panel in a rail that follows the reader down the page.
 */
function cwcp_job_layout($job_id, $description_html) {

    $summary = cwcp_job_details_table($job_id, 'list');

    ob_start();
    ?>
    <div class="cwcp-job-layout">

        <div class="cwcp-job-main">

            <div class="cwcp-card cwcp-pad">

                <div class="cwcp-section-header">
                    <span class="cwcp-section-header-icon"><i class="fa-solid fa-file-lines"></i></span>
                    <h2>Job Description</h2>
                </div>

                <div class="cwcp-job-description">
                    <?php echo $description_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>

            </div>

        </div>

        <aside class="cwcp-job-aside">
            <?php echo $summary; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php echo cwcp_job_apply_box($job_id); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        </aside>

    </div>
    <?php

    return ob_get_clean();
}

/**
 * Apply panel shown at the bottom of a job page.
 */
function cwcp_job_apply_box($job_id) {

    $is_open = cwcp_job_is_open($job_id);

    $user_id = get_current_user_id();

    $applied = $user_id ? cwcp_has_applied($user_id, $job_id) : false;

    ob_start();
    ?>
    <div class="cwcp-card cwcp-pad cwcp-apply-box">

        <?php echo cwcp_render_notices(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <?php if ($applied) : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon cwcp-text-success"><i class="fa-solid fa-circle-check"></i></div>
                <h3>You have already applied for this job</h3>
                <p>You can track the status from your applied jobs page.</p>
                <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_applied_jobs_url()); ?>">
                    <i class="fa-solid fa-paper-plane"></i> My Applications
                </a>
            </div>

        <?php elseif (!$is_open) : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-lock"></i></div>
                <h3>Applications are closed for this job</h3>
                <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_jobs_url()); ?>">Browse other jobs</a>
            </div>

        <?php else : ?>

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-bolt"></i></span>
                <h2>Easy Apply</h2>
            </div>

            <?php if (is_user_logged_in() && cwcp_is_profile_complete($user_id)) : ?>

                <p class="cwcp-text-muted">
                    Your saved profile, education, experience, skills and resume are sent with this application.
                    No further details required.
                </p>

                <form method="post" class="cwcp-form cwcp-apply-form">

                    <?php wp_nonce_field('cwcp_apply_' . $job_id, 'cwcp_apply_nonce'); ?>
                    <input type="hidden" name="cwcp_action" value="apply_job" />
                    <input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>" />

                    <div class="cwcp-form-group">
                        <label class="cwcp-form-label" for="cwcp-cover-note">Message to the hiring team (optional)</label>
                        <textarea class="cwcp-form-input cwcp-textarea" id="cwcp-cover-note" name="cover_note" rows="3"
                                  placeholder="Anything you would like us to know…"></textarea>
                    </div>

                    <button type="submit" class="cwcp-btn-primary cwcp-btn-lg">
                        <i class="fa-solid fa-bolt"></i> Apply Now
                    </button>
                </form>

            <?php elseif (is_user_logged_in()) : ?>

                <?php $completeness = cwcp_profile_completeness($user_id); ?>

                <div class="cwcp-alert cwcp-alert-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>
                        Your account is <?php echo esc_html($completeness['percent']); ?>% complete.
                        Add <?php echo esc_html(implode(', ', $completeness['missing_labels'])); ?> to unlock one click apply.
                    </span>
                </div>

                <a class="cwcp-btn-primary" href="<?php echo esc_url(cwcp_profile_url()); ?>">
                    <i class="fa-solid fa-id-card"></i> Complete My Account
                </a>

            <?php else : ?>

                <p class="cwcp-text-muted">Log in or create a free candidate account to apply in one click.</p>

                <div class="cwcp-flex cwcp-gap cwcp-flex-wrap">
                    <a class="cwcp-btn-primary"
                       href="<?php echo esc_url(add_query_arg('redirect_to', rawurlencode(get_permalink($job_id)), cwcp_login_url())); ?>">
                        <i class="fa-solid fa-right-to-bracket"></i> Login &amp; Apply
                    </a>
                    <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_registration_url()); ?>">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </a>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>
    <?php

    return ob_get_clean();
}

/**
 * Appends the summary table and apply box to the theme's single job template.
 */
function cwcp_single_job_content($content) {

    if (!is_singular('cw_job') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $job_id = get_the_ID();

    /*
      * The markup is wrapped in its own scope so the portal typography applies
      * inside it without the theme's body styles leaking in - and without the
      * portal page background being painted over the theme's template.
      */

    return '<div class="cwcp-scope cwcp-single-job alignwide">'
        . cwcp_job_layout($job_id, $content)
        . '</div>';
}

add_filter('the_content', 'cwcp_single_job_content');

/**
 * Wraps single job output in the portal container so the styles apply.
 */
function cwcp_single_job_body_class($classes) {

    if (is_singular('cw_job') || is_singular('cw_tender')) {

        /*
         * A marker only. Layout classes must never land on <body>, or the
         * portal padding and background would repaint the whole theme.
         */

        $classes[] = 'cwcp-single-job-page';
    }

    return $classes;
}

add_filter('body_class', 'cwcp_single_job_body_class');


/*
|--------------------------------------------------------------------------
| Applied Jobs Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_applied_jobs_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $user_id = get_current_user_id();

    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';

    $statuses = cwcp_application_statuses();

    if ($status && !isset($statuses[$status])) {
        $status = '';
    }

    $applications = cwcp_get_applications(
        array(
            'user_id'  => $user_id,
            'status'   => $status,
            'per_page' => 50,
        )
    );

    $total = cwcp_count_applications(array('user_id' => $user_id));

    $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

    $editing = $edit_id ? cwcp_get_user_application($edit_id, $user_id) : null;

    ob_start();

    echo cwcp_portal_open( // phpcs:ignore WordPress.Security.EscapeOutput
        'applied_jobs',
        'Applied Jobs',
        'Track every application you have submitted.'
    );
    ?>

    <?php if ($editing) : ?>

        <div class="cwcp-card cwcp-pad cwcp-mb-25">

            <div class="cwcp-section-header">
                <span class="cwcp-section-header-icon"><i class="fa-solid fa-pen"></i></span>
                <h2>Edit Application</h2>
            </div>

            <?php if (!cwcp_application_is_editable($editing)) : ?>

                <div class="cwcp-alert cwcp-alert-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>This application has already been decided and can no longer be changed.</span>
                </div>

                <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_applied_jobs_url()); ?>">Back to my applications</a>

            <?php else : ?>

                <p class="cwcp-text-muted">
                    You applied for
                    <strong><?php echo esc_html(get_the_title($editing['job_id'])); ?></strong>
                    on <?php echo esc_html(cwcp_format_date($editing['applied_at'])); ?>.
                    Current status: <?php echo esc_html(cwcp_status_label($editing['status'])); ?>.
                </p>

                <form method="post" class="cwcp-form">

                    <?php wp_nonce_field('cwcp_edit_application_' . $editing['id'], 'cwcp_edit_application_nonce'); ?>
                    <input type="hidden" name="cwcp_action" value="edit_application" />
                    <input type="hidden" name="id" value="<?php echo esc_attr($editing['id']); ?>" />

                    <div class="cwcp-form-group">
                        <label class="cwcp-form-label" for="cwcp-edit-cover-note">Message to the hiring team</label>
                        <textarea class="cwcp-form-input cwcp-textarea" id="cwcp-edit-cover-note" name="cover_note"
                                  rows="4"><?php echo esc_textarea((string) $editing['cover_note']); ?></textarea>
                    </div>

                    <div class="cwcp-form-group cwcp-checkbox-group">
                        <label class="cwcp-inline-check">
                            <input type="checkbox" name="refresh_profile" value="1" checked />
                            Also update this application with my latest profile, education, experience, skills and resume
                        </label>
                    </div>

                    <div class="cwcp-form-actions">
                        <button type="submit" class="cwcp-btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                        </button>
                        <a class="cwcp-btn-secondary" href="<?php echo esc_url(cwcp_applied_jobs_url()); ?>">Cancel</a>
                    </div>

                </form>

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <div class="cwcp-filter-pills">
        <a class="cwcp-pill <?php echo '' === $status ? 'is-active' : ''; ?>"
           href="<?php echo esc_url(cwcp_applied_jobs_url()); ?>">All (<?php echo esc_html($total); ?>)</a>

        <?php foreach ($statuses as $key => $label) : ?>
            <a class="cwcp-pill <?php echo $status === $key ? 'is-active' : ''; ?>"
               href="<?php echo esc_url(add_query_arg('status', $key, cwcp_applied_jobs_url())); ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="cwcp-card cwcp-pad">

        <?php if (empty($applications)) : ?>

            <div class="cwcp-empty">
                <div class="cwcp-empty-icon"><i class="fa-solid fa-paper-plane"></i></div>
                <h3>No applications yet</h3>
                <p>Browse the open positions and apply with one click.</p>
                <a class="cwcp-btn-primary" href="<?php echo esc_url(cwcp_jobs_url()); ?>">
                    <i class="fa-solid fa-magnifying-glass"></i> Browse Jobs
                </a>
            </div>

        <?php else : ?>

            <div class="cwcp-table-wrapper">
                <table class="cwcp-table">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th class="cwcp-text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $application) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(get_permalink($application['job_id'])); ?>">
                                            <?php echo esc_html($application['job_title'] ? $application['job_title'] : 'Job removed'); ?>
                                        </a>
                                    </strong>
                                    <?php $location = cwcp_job_meta($application['job_id'], 'location'); ?>
                                    <?php if ($location) : ?>
                                        <br /><small class="cwcp-text-muted"><i class="fa-solid fa-location-dot"></i> <?php echo esc_html($location); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(cwcp_format_date($application['applied_at'])); ?></td>
                                <td>
                                    <span class="cwcp-badge <?php echo esc_attr(cwcp_status_badge_class($application['status'])); ?>">
                                        <?php echo esc_html(cwcp_status_label($application['status'])); ?>
                                    </span>
                                </td>
                                <td class="cwcp-text-center">
                                    <?php if (cwcp_application_is_editable($application)) : ?>
                                        <div class="cwcp-row-actions">
                                            <a class="cwcp-icon-btn" title="Edit application"
                                               href="<?php echo esc_url(add_query_arg('edit', $application['id'], cwcp_applied_jobs_url())); ?>">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <a class="cwcp-icon-btn cwcp-icon-btn-danger" title="Withdraw application"
                                               onclick="return confirm('Withdraw this application?');"
                                               href="<?php echo esc_url(
                                                   wp_nonce_url(
                                                       add_query_arg(
                                                           array('cwcp_action' => 'withdraw_application', 'id' => $application['id']),
                                                           cwcp_applied_jobs_url()
                                                       ),
                                                       'cwcp_withdraw_' . $application['id']
                                                   )
                                               ); ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    <?php else : ?>
                                        <span class="cwcp-text-muted">&mdash;</span>
                                    <?php endif; ?>
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

cwcp_add_shortcode('applied_jobs', 'cwcp_applied_jobs_shortcode');


/*
|--------------------------------------------------------------------------
| Saved Jobs Shortcode
|--------------------------------------------------------------------------
*/

function cwcp_saved_jobs_shortcode() {

    if (!is_user_logged_in()) {
        return cwcp_require_login_notice();
    }

    $saved = cwcp_get_saved_jobs(get_current_user_id());

    ob_start();

    echo cwcp_portal_open('saved_jobs', 'Saved Jobs', 'Jobs you bookmarked for later.'); // phpcs:ignore WordPress.Security.EscapeOutput

    if (empty($saved)) {

        echo '<div class="cwcp-card cwcp-pad"><div class="cwcp-empty">'
            . '<div class="cwcp-empty-icon"><i class="fa-regular fa-bookmark"></i></div>'
            . '<h3>No saved jobs</h3>'
            . '<p>Tap the bookmark icon on any job to save it here.</p>'
            . '<a class="cwcp-btn-primary" href="' . esc_url(cwcp_jobs_url()) . '">Browse Jobs</a>'
            . '</div></div>';

    } else {

        echo '<div class="cwcp-job-list">';

        foreach ($saved as $row) {

            if ('cw_job' !== get_post_type($row['job_id'])) {
                continue;
            }

            echo cwcp_render_job_card((int) $row['job_id']); // phpcs:ignore WordPress.Security.EscapeOutput
        }

        echo '</div>';
    }

    echo cwcp_portal_close(); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
}

cwcp_add_shortcode('saved_jobs', 'cwcp_saved_jobs_shortcode');
