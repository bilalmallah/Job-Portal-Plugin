<?php
/**
 * CareerHub - The Elementor widget classes.
 *
 * Every widget is a thin wrapper around the shortcode callback that already
 * renders that screen, so the widget and the shortcode can never drift apart.
 * All controls live on CWCP_Elementor_Widget.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Public Application Forms
|--------------------------------------------------------------------------
|
| These three accept title / subtitle / intro overrides.
|
*/

class CWCP_Elementor_Widget_Volunteer_Form extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'volunteer-form',
            'title'    => 'Volunteer Form',
            'icon'     => 'eicon-form-horizontal',
            'keywords' => array('volunteer', 'form', 'apply', 'registration'),
            'render'   => 'cwcp_volunteer_form_shortcode',
            'headings' => true,
        );
    }
}

class CWCP_Elementor_Widget_Internship_Form extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'internship-form',
            'title'    => 'Internship Form',
            'icon'     => 'eicon-form-horizontal',
            'keywords' => array('internship', 'intern', 'form', 'student', 'apply'),
            'render'   => 'cwcp_internship_form_shortcode',
            'headings' => true,
        );
    }
}

class CWCP_Elementor_Widget_Field_Facilitator_Form extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'field-facilitator-form',
            'title'    => 'Field Facilitator Form',
            'icon'     => 'eicon-form-horizontal',
            'keywords' => array('field', 'facilitator', 'form', 'apply'),
            'render'   => 'cwcp_field_facilitator_form_shortcode',
            'headings' => true,
        );
    }
}


/*
|--------------------------------------------------------------------------
| Account Screens
|--------------------------------------------------------------------------
*/

class CWCP_Elementor_Widget_Register extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'register',
            'title'    => 'Candidate Registration',
            'icon'     => 'eicon-user-circle-o',
            'keywords' => array('register', 'signup', 'account', 'candidate'),
            'render'   => 'cwcp_registration_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Login extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'login',
            'title'    => 'Candidate Login',
            'icon'     => 'eicon-lock-user',
            'keywords' => array('login', 'signin', 'account'),
            'render'   => 'cwcp_login_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Lost_Password extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'lost-password',
            'title'    => 'Forgot Password',
            'icon'     => 'eicon-lock-user',
            'keywords' => array('password', 'forgot', 'reset', 'lost'),
            'render'   => 'cwcp_lost_password_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Reset_Password extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'reset-password',
            'title'    => 'Reset Password',
            'icon'     => 'eicon-lock-user',
            'keywords' => array('password', 'reset', 'change'),
            'render'   => 'cwcp_reset_password_shortcode',
        );
    }
}


/*
|--------------------------------------------------------------------------
| Candidate Portal Screens
|--------------------------------------------------------------------------
*/

class CWCP_Elementor_Widget_Dashboard extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'dashboard',
            'title'    => 'Candidate Dashboard',
            'icon'     => 'eicon-dashboard',
            'keywords' => array('dashboard', 'account', 'overview'),
            'render'   => 'cwcp_dashboard_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Profile extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'profile',
            'title'    => 'Candidate Profile',
            'icon'     => 'eicon-user-circle-o',
            'keywords' => array('profile', 'cnic', 'details', 'account'),
            'render'   => 'cwcp_profile_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Education extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'education',
            'title'    => 'Education',
            'icon'     => 'eicon-document-file',
            'keywords' => array('education', 'degree', 'qualification'),
            'render'   => 'cwcp_education_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Experience extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'experience',
            'title'    => 'Experience',
            'icon'     => 'eicon-time-line',
            'keywords' => array('experience', 'work', 'employment'),
            'render'   => 'cwcp_experience_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Skills extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'skills',
            'title'    => 'Skills',
            'icon'     => 'eicon-star-o',
            'keywords' => array('skills', 'abilities'),
            'render'   => 'cwcp_skills_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Resume extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'resume',
            'title'    => 'Resume',
            'icon'     => 'eicon-document-file',
            'keywords' => array('resume', 'cv', 'upload'),
            'render'   => 'cwcp_resume_shortcode',
        );
    }
}


/*
|--------------------------------------------------------------------------
| Jobs And Tenders
|--------------------------------------------------------------------------
*/

class CWCP_Elementor_Widget_Jobs extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'jobs',
            'title'    => 'Job Listing',
            'icon'     => 'eicon-posts-grid',
            'keywords' => array('jobs', 'vacancies', 'careers', 'listing'),
            'render'   => 'cwcp_jobs_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Applied_Jobs extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'applied-jobs',
            'title'    => 'Applied Jobs',
            'icon'     => 'eicon-checkbox',
            'keywords' => array('applied', 'applications', 'jobs'),
            'render'   => 'cwcp_applied_jobs_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Saved_Jobs extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'saved-jobs',
            'title'    => 'Saved Jobs',
            'icon'     => 'eicon-save-o',
            'keywords' => array('saved', 'bookmark', 'jobs', 'shortlist'),
            'render'   => 'cwcp_saved_jobs_shortcode',
        );
    }
}

class CWCP_Elementor_Widget_Tenders extends CWCP_Elementor_Widget {

    protected function cwcp_config() {

        return array(
            'slug'     => 'tenders',
            'title'    => 'Tenders & Appeals',
            'icon'     => 'eicon-archive-posts',
            'keywords' => array('tender', 'donation', 'appeal', 'procurement'),
            'render'   => 'cwcp_tenders_shortcode',
        );
    }
}


/**
 * Every widget class, in the order they appear in the Elementor panel.
 *
 * @return array
 */
function cwcp_elementor_widget_classes() {

    return array(
        'CWCP_Elementor_Widget_Jobs',
        'CWCP_Elementor_Widget_Volunteer_Form',
        'CWCP_Elementor_Widget_Internship_Form',
        'CWCP_Elementor_Widget_Field_Facilitator_Form',
        'CWCP_Elementor_Widget_Tenders',
        'CWCP_Elementor_Widget_Register',
        'CWCP_Elementor_Widget_Login',
        'CWCP_Elementor_Widget_Lost_Password',
        'CWCP_Elementor_Widget_Reset_Password',
        'CWCP_Elementor_Widget_Dashboard',
        'CWCP_Elementor_Widget_Profile',
        'CWCP_Elementor_Widget_Education',
        'CWCP_Elementor_Widget_Experience',
        'CWCP_Elementor_Widget_Skills',
        'CWCP_Elementor_Widget_Resume',
        'CWCP_Elementor_Widget_Applied_Jobs',
        'CWCP_Elementor_Widget_Saved_Jobs',
    );
}
