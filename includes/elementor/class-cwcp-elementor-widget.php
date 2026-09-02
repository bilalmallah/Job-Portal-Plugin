<?php
/**
 * CareerHub - Base class for every Elementor widget.
 *
 * The portal already draws its components from a small set of CSS custom
 * properties (see cwcp_color_variables()). Rather than duplicate that styling
 * in Elementor controls, every colour control here writes back into those same
 * variables on the widget wrapper, so an Elementor override stays scoped to the
 * one widget and the rest of the site keeps the palette from Portal Settings.
 *
 * @package CareerHub
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class CWCP_Elementor_Widget extends \Elementor\Widget_Base {

    /**
     * Per widget definition. Subclasses return:
     *
     *   slug     - widget name suffix, also a CSS hook
     *   title    - label in the Elementor panel
     *   icon     - eicon class
     *   keywords - panel search terms
     *   render   - callable returning the screen markup
     *   headings - true when the screen accepts title/subtitle/intro overrides
     *
     * @return array
     */
    abstract protected function cwcp_config();

    protected function cwcp_get($key, $fallback = '') {

        $config = $this->cwcp_config();

        return isset($config[$key]) ? $config[$key] : $fallback;
    }

    public function get_name() {

        return 'careerhub-' . $this->cwcp_get('slug');
    }

    public function get_title() {

        return $this->cwcp_get('title');
    }

    public function get_icon() {

        return $this->cwcp_get('icon', 'eicon-form-horizontal');
    }

    public function get_categories() {

        return array('careerhub');
    }

    public function get_keywords() {

        return array_merge(
            array('careerhub', 'career', 'portal', 'job'),
            (array) $this->cwcp_get('keywords', array())
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Controls
    |--------------------------------------------------------------------------
    */

    protected function register_controls() {

        $this->cwcp_content_section();
        $this->cwcp_layout_section();
        $this->cwcp_palette_section();
        $this->cwcp_heading_section();
        $this->cwcp_card_section();
        $this->cwcp_field_section();
        $this->cwcp_button_section();
    }

    protected function cwcp_content_section() {

        $this->start_controls_section(
            'cwcp_section_content',
            array(
                'label' => 'Content',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'cwcp_show_header',
            array(
                'label'        => 'Screen heading',
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => 'Show',
                'label_off'    => 'Hide',
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => 'Hide this when the section already has an Elementor heading above the widget.',
            )
        );

        if ($this->cwcp_get('headings')) {

            $this->add_control(
                'cwcp_title',
                array(
                    'label'       => 'Title',
                    'type'        => \Elementor\Controls_Manager::TEXT,
                    'default'     => '',
                    'placeholder' => $this->cwcp_get('title'),
                    'label_block' => true,
                    'condition'   => array('cwcp_show_header' => 'yes'),
                )
            );

            $this->add_control(
                'cwcp_subtitle',
                array(
                    'label'       => 'Subtitle',
                    'type'        => \Elementor\Controls_Manager::TEXTAREA,
                    'rows'        => 2,
                    'default'     => '',
                    'placeholder' => 'Leave empty to keep the built in wording.',
                    'condition'   => array('cwcp_show_header' => 'yes'),
                )
            );

            $this->add_control(
                'cwcp_intro',
                array(
                    'label'       => 'Intro above the fields',
                    'type'        => \Elementor\Controls_Manager::TEXTAREA,
                    'rows'        => 2,
                    'default'     => '',
                    'placeholder' => 'Leave empty to keep the built in wording.',
                )
            );
        }

        $this->end_controls_section();
    }

    protected function cwcp_layout_section() {

        $this->start_controls_section(
            'cwcp_section_layout',
            array(
                'label' => 'Layout',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'cwcp_content_width',
            array(
                'label'      => 'Content width',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', '%', 'vw'),
                'range'      => array(
                    'px' => array('min' => 320, 'max' => 1600, 'step' => 10),
                    '%'  => array('min' => 30, 'max' => 100),
                    'vw' => array('min' => 30, 'max' => 100),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-container' => 'max-width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_content_align',
            array(
                'label'                => 'Align content block',
                'type'                 => \Elementor\Controls_Manager::CHOOSE,
                'options'              => array(
                    'left'   => array('title' => 'Left', 'icon' => 'eicon-h-align-left'),
                    'center' => array('title' => 'Center', 'icon' => 'eicon-h-align-center'),
                    'right'  => array('title' => 'Right', 'icon' => 'eicon-h-align-right'),
                ),
                'default'              => 'center',
                'selectors_dictionary' => array(
                    'left'   => 'margin-left: 0; margin-right: auto;',
                    'center' => 'margin-left: auto; margin-right: auto;',
                    'right'  => 'margin-left: auto; margin-right: 0;',
                ),
                'selectors'            => array(
                    '{{WRAPPER}} .cwcp-container' => '{{VALUE}}',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_page_padding',
            array(
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-page' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_page_background',
            array(
                'label'     => 'Screen background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-page' => 'background: {{VALUE}};',
                    '{{WRAPPER}}'            => '--cwcp-background: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function cwcp_palette_section() {

        $this->start_controls_section(
            'cwcp_section_palette',
            array(
                'label' => 'Colors',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'cwcp_primary',
            array(
                'label'     => 'Brand / primary',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}}' => '--cwcp-primary: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_primary_dark',
            array(
                'label'     => 'Brand hover',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}}' => '--cwcp-primary-dark: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_primary_light',
            array(
                'label'       => 'Brand tint',
                'description' => 'Sits behind icons, active pills and highlighted rows.',
                'type'        => \Elementor\Controls_Manager::COLOR,
                'selectors'   => array(
                    '{{WRAPPER}}' => '--cwcp-primary-light: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_text',
            array(
                'label'     => 'Body text',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'separator' => 'before',
                'selectors' => array(
                    '{{WRAPPER}}' => '--cwcp-text: {{VALUE}}; --cwcp-text-dark: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_text_muted',
            array(
                'label'     => 'Muted text',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}}' => '--cwcp-text-muted: {{VALUE}}; --cwcp-text-light: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_border',
            array(
                'label'     => 'Borders',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}}' => '--cwcp-border: {{VALUE}}; --cwcp-border-light: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'cwcp_base_typography',
                'label'    => 'Base typography',
                'separator' => 'before',
                'selector' => '{{WRAPPER}} .cwcp-page, {{WRAPPER}} .cwcp-scope',
            )
        );

        $this->add_control(
            'cwcp_status_heading',
            array(
                'label'     => 'Status colors',
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'cwcp_success',
            array(
                'label'     => 'Success',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array('{{WRAPPER}}' => '--cwcp-success: {{VALUE}};'),
            )
        );

        $this->add_control(
            'cwcp_danger',
            array(
                'label'     => 'Error / required mark',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array('{{WRAPPER}}' => '--cwcp-danger: {{VALUE}};'),
            )
        );

        $this->add_control(
            'cwcp_warning',
            array(
                'label'     => 'Warning',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array('{{WRAPPER}}' => '--cwcp-warning: {{VALUE}};'),
            )
        );

        $this->end_controls_section();
    }

    protected function cwcp_heading_section() {

        $this->start_controls_section(
            'cwcp_section_heading',
            array(
                'label'     => 'Screen heading',
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array('cwcp_show_header' => 'yes'),
            )
        );

        $this->add_responsive_control(
            'cwcp_heading_align',
            array(
                'label'     => 'Alignment',
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => array(
                    'left'   => array('title' => 'Left', 'icon' => 'eicon-text-align-left'),
                    'center' => array('title' => 'Center', 'icon' => 'eicon-text-align-center'),
                    'right'  => array('title' => 'Right', 'icon' => 'eicon-text-align-right'),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-page-header'   => 'text-align: {{VALUE}};',
                    '{{WRAPPER}} .cwcp-page-header p' => 'margin-left: auto; margin-right: auto;',
                ),
            )
        );

        $this->add_control(
            'cwcp_heading_color',
            array(
                'label'     => 'Title color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-page-header h1' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'cwcp_heading_typography',
                'selector' => '{{WRAPPER}} .cwcp-page-header h1',
            )
        );

        $this->add_control(
            'cwcp_subtitle_color',
            array(
                'label'     => 'Subtitle color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'separator' => 'before',
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-page-header p' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'cwcp_subtitle_typography',
                'selector' => '{{WRAPPER}} .cwcp-page-header p',
            )
        );

        $this->add_responsive_control(
            'cwcp_heading_spacing',
            array(
                'label'      => 'Space below heading',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range'      => array('px' => array('min' => 0, 'max' => 120)),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-page-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function cwcp_card_section() {

        $this->start_controls_section(
            'cwcp_section_card',
            array(
                'label' => 'Cards',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'cwcp_card_background',
            array(
                'label'     => 'Card background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}}' => '--cwcp-white: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_card_border_color',
            array(
                'label'     => 'Card border',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-card' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_card_radius',
            array(
                'label'      => 'Card radius',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range'      => array('px' => array('min' => 0, 'max' => 40)),
                'selectors'  => array(
                    '{{WRAPPER}}' => '--cwcp-radius-large: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_card_padding',
            array(
                'label'      => 'Card padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-pad' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'cwcp_card_shadow',
                'selector' => '{{WRAPPER}} .cwcp-card',
            )
        );

        $this->end_controls_section();
    }

    protected function cwcp_field_section() {

        $this->start_controls_section(
            'cwcp_section_fields',
            array(
                'label' => 'Form fields',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'cwcp_label_color',
            array(
                'label'     => 'Label color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-form-label' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'cwcp_label_typography',
                'label'    => 'Label typography',
                'selector' => '{{WRAPPER}} .cwcp-form-label',
            )
        );

        $this->add_control(
            'cwcp_input_heading',
            array(
                'label'     => 'Inputs',
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'cwcp_input_typography',
                'label'    => 'Input typography',
                'selector' => '{{WRAPPER}} .cwcp-form-input',
            )
        );

        $this->add_control(
            'cwcp_input_color',
            array(
                'label'     => 'Text color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-form-input' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_input_background',
            array(
                'label'     => 'Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-form-input' => 'background: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_input_border_color',
            array(
                'label'     => 'Border color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-form-input' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_input_focus_color',
            array(
                'label'     => 'Focus border color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-form-input:focus' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_input_radius',
            array(
                'label'      => 'Radius',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range'      => array('px' => array('min' => 0, 'max' => 40)),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-form-input' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_input_height',
            array(
                'label'      => 'Minimum height',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 30, 'max' => 90)),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-form-input' => 'min-height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_input_padding',
            array(
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-form-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_field_gap',
            array(
                'label'      => 'Gap between fields',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 0, 'max' => 60)),
                'separator'  => 'before',
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_field_columns',
            array(
                'label'     => 'Field columns',
                'type'      => \Elementor\Controls_Manager::SELECT,
                'options'   => array(
                    ''  => 'Default (two)',
                    '1' => 'One',
                    '2' => 'Two',
                    '3' => 'Three',
                ),
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-public-form .cwcp-grid-2' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function cwcp_button_section() {

        $this->start_controls_section(
            'cwcp_section_buttons',
            array(
                'label' => 'Buttons',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'cwcp_button_typography',
                'selector' => '{{WRAPPER}} .cwcp-btn-primary',
            )
        );

        $this->start_controls_tabs('cwcp_button_tabs');

        $this->start_controls_tab('cwcp_button_tab_normal', array('label' => 'Normal'));

        $this->add_control(
            'cwcp_button_color',
            array(
                'label'     => 'Text color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    /* Portal Settings prints this one !important, so match it or lose. */
                    '{{WRAPPER}} .cwcp-btn-primary' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'cwcp_button_background',
            array(
                'label'     => 'Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-btn-primary' => 'background: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab('cwcp_button_tab_hover', array('label' => 'Hover'));

        $this->add_control(
            'cwcp_button_color_hover',
            array(
                'label'     => 'Text color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-btn-primary:hover' => 'color: {{VALUE}} !important;',
                ),
            )
        );

        $this->add_control(
            'cwcp_button_background_hover',
            array(
                'label'     => 'Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .cwcp-btn-primary:hover' => 'background: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'cwcp_button_padding',
            array(
                'label'      => 'Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'separator'  => 'before',
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-btn-primary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'cwcp_button_radius',
            array(
                'label'      => 'Radius',
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range'      => array('px' => array('min' => 0, 'max' => 50)),
                'selectors'  => array(
                    '{{WRAPPER}} .cwcp-btn-primary' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'cwcp_button_full_width',
            array(
                'label'        => 'Full width submit',
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => 'Yes',
                'label_off'    => 'No',
                'return_value' => 'yes',
                'default'      => '',
                'selectors'    => array(
                    '{{WRAPPER}} .cwcp-public-form > .cwcp-btn-primary' => 'width: 100%;',
                ),
            )
        );

        $this->end_controls_section();
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    protected function render() {

        $settings = $this->get_settings_for_display();

        $callback = $this->cwcp_get('render');

        if (!is_callable($callback)) {
            return;
        }

        $classes = array(
            'cwcp-elementor',
            'cwcp-el-' . $this->cwcp_get('slug'),
        );

        if (empty($settings['cwcp_show_header']) || 'yes' !== $settings['cwcp_show_header']) {
            $classes[] = 'cwcp-el-no-header';
        }

        $atts = array();

        if ($this->cwcp_get('headings')) {

            foreach (array('title', 'subtitle', 'intro') as $key) {

                if (isset($settings['cwcp_' . $key]) && '' !== trim($settings['cwcp_' . $key])) {
                    $atts[$key] = $settings['cwcp_' . $key];
                }
            }
        }

        echo '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        echo call_user_func($callback, $atts); // phpcs:ignore WordPress.Security.EscapeOutput
        echo '</div>';
    }
}
