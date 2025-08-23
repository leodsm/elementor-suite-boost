<?php

namespace CM\Suite\PlayerLauncher\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * CM Player Launcher Widget
 */
class CM_Player_Launcher extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'cm-suite-player-launcher';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('CM Player Launcher', 'cm-suite-elementor');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-external-link-square';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['cm-suite', 'general'];
    }
    
    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['launcher', 'player', 'stories', 'trigger', 'open'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Launcher Settings', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'trigger_selector',
            [
                'label' => esc_html__('Trigger Selector', 'cm-suite-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '.cmpc-card__link',
                'description' => esc_html__('CSS selector for elements that will trigger the stories player', 'cm-suite-elementor'),
                'label_block' => true,
            ]
        );
        
        $this->add_control(
            'player_selector',
            [
                'label' => esc_html__('Player Selector', 'cm-suite-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '.cmsp-overlay',
                'description' => esc_html__('CSS selector for the stories player overlay', 'cm-suite-elementor'),
                'label_block' => true,
            ]
        );
        
        $this->add_control(
            'extract_story_id',
            [
                'label' => esc_html__('Extract Story ID', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => esc_html__('Try to extract story ID from trigger element data attributes or URL', 'cm-suite-elementor'),
            ]
        );
        
        $this->add_control(
            'prevent_default',
            [
                'label' => esc_html__('Prevent Default Action', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => esc_html__('Prevent the default click action (e.g., following links)', 'cm-suite-elementor'),
            ]
        );
        
        $this->add_control(
            'debug_mode',
            [
                'label' => esc_html__('Debug Mode', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
                'description' => esc_html__('Log debug information to browser console', 'cm-suite-elementor'),
            ]
        );
        
        $this->end_controls_section();
        
        // Advanced Section
        $this->start_controls_section(
            'advanced_section',
            [
                'label' => esc_html__('Advanced', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'fallback_action',
            [
                'label' => esc_html__('Fallback Action', 'cm-suite-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'console',
                'options' => [
                    'console' => esc_html__('Log to Console', 'cm-suite-elementor'),
                    'alert' => esc_html__('Show Alert', 'cm-suite-elementor'),
                    'ignore' => esc_html__('Ignore Silently', 'cm-suite-elementor'),
                ],
                'description' => esc_html__('What to do when stories player is not found on the page', 'cm-suite-elementor'),
            ]
        );
        
        $this->add_control(
            'custom_data_attribute',
            [
                'label' => esc_html__('Custom Data Attribute', 'cm-suite-elementor'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => esc_html__('Custom data attribute to extract story ID from (e.g., "data-story-id")', 'cm-suite-elementor'),
                'label_block' => true,
                'condition' => [
                    'extract_story_id' => 'yes',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Enqueue launcher script
        wp_enqueue_script('cm-player-launcher');
        
        // Generate unique ID for this widget
        $widget_id = 'cm-launcher-' . $this->get_id();
        
        // Prepare launcher configuration
        $config = [
            'triggerSelector' => $settings['trigger_selector'],
            'playerSelector' => $settings['player_selector'],
            'extractStoryId' => $settings['extract_story_id'] === 'yes',
            'preventDefault' => $settings['prevent_default'] === 'yes',
            'debugMode' => $settings['debug_mode'] === 'yes',
            'fallbackAction' => $settings['fallback_action'],
            'customDataAttribute' => $settings['custom_data_attribute'],
        ];
        ?>
        
        <div id="<?php echo esc_attr($widget_id); ?>" class="cm-player-launcher" style="display: none;">
            <!-- This widget works behind the scenes - no visible output -->
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.CMPlayerLauncher) {
                window.CMPlayerLauncher.init(<?php echo wp_json_encode($config); ?>);
            }
        });
        </script>
        
        <?php
        
        // Output widget info for admin preview
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            ?>
            <div class="cm-launcher-preview">
                <div style="padding: 20px; border: 2px dashed #ddd; border-radius: 8px; text-align: center; background: #f9f9f9;">
                    <h4 style="margin: 0 0 10px; color: #333;">🚀 CM Player Launcher</h4>
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        <strong>Trigger:</strong> <?php echo esc_html($settings['trigger_selector']); ?><br>
                        <strong>Player:</strong> <?php echo esc_html($settings['player_selector']); ?>
                    </p>
                    <p style="margin: 10px 0 0; color: #999; font-size: 12px;">
                        This widget enables clicking on elements to open the stories player. It's invisible on the frontend.
                    </p>
                </div>
            </div>
            <?php
        }
    }
}