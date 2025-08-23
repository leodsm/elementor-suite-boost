<?php

namespace CM\Suite\StoriesPlayer\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * CM Stories Player Widget
 */
class CM_Stories_Player extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'cm-suite-stories-player';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('CM Stories Player', 'cm-suite-elementor');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-play';
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
        return ['stories', 'player', 'vertical', 'overlay', 'instagram'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Settings', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'enable_deep_link',
            [
                'label' => esc_html__('Enable Deep Link', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => esc_html__('Allow opening stories via URL parameter ?story=slug', 'cm-suite-elementor'),
            ]
        );
        
        $this->add_control(
            'auto_close',
            [
                'label' => esc_html__('Auto Close', 'cm-suite-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 0,
                'min' => 0,
                'max' => 60,
                'description' => esc_html__('Auto close after X seconds (0 = disabled)', 'cm-suite-elementor'),
            ]
        );
        
        $this->add_control(
            'show_progress',
            [
                'label' => esc_html__('Show Progress Bar', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_controls',
            [
                'label' => esc_html__('Show Controls', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => esc_html__('Show play/pause and navigation controls', 'cm-suite-elementor'),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'overlay_color',
            [
                'label' => esc_html__('Overlay Background', 'cm-suite-elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.9)',
                'selectors' => [
                    '{{WRAPPER}} .cmsp-overlay' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'accent_color',
            [
                'label' => esc_html__('Accent Color', 'cm-suite-elementor'),
                'type' => Controls_Manager::COLOR,
                'default' => '#3498DB',
                'selectors' => [
                    '{{WRAPPER}} .cmsp-progress-bar' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .cmsp-control-btn:hover' => 'background-color: {{VALUE}};',
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
        
        // Enqueue assets
        wp_enqueue_style('cm-stories-player');
        wp_enqueue_script('cm-stories-player');
        
        // Generate unique ID
        $widget_id = 'cmsp-' . $this->get_id();
        ?>
        
        <div id="<?php echo esc_attr($widget_id); ?>" class="cmsp-overlay" style="display: none;" 
             data-deep-link="<?php echo esc_attr($settings['enable_deep_link']); ?>"
             data-auto-close="<?php echo esc_attr($settings['auto_close']); ?>"
             data-show-progress="<?php echo esc_attr($settings['show_progress']); ?>"
             data-show-controls="<?php echo esc_attr($settings['show_controls']); ?>">
            
            <div class="cmsp-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Stories Player', 'cm-suite-elementor'); ?>">
                
                <!-- Progress Bar -->
                <?php if ($settings['show_progress'] === 'yes'): ?>
                    <div class="cmsp-progress-container">
                        <div class="cmsp-progress-segments"></div>
                    </div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="cmsp-header">
                    <div class="cmsp-story-info">
                        <div class="cmsp-story-avatar"></div>
                        <div class="cmsp-story-meta">
                            <h3 class="cmsp-story-title"></h3>
                            <time class="cmsp-story-date"></time>
                        </div>
                    </div>
                    
                    <!-- Controls -->
                    <?php if ($settings['show_controls'] === 'yes'): ?>
                        <div class="cmsp-controls">
                            <button class="cmsp-control-btn cmsp-play-pause" aria-label="<?php esc_attr_e('Play/Pause', 'cm-suite-elementor'); ?>">
                                <span class="cmsp-play-icon">▶</span>
                                <span class="cmsp-pause-icon">⏸</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Close Button -->
                    <button class="cmsp-close" aria-label="<?php esc_attr_e('Close Stories', 'cm-suite-elementor'); ?>">×</button>
                </div>
                
                <!-- Stories Container -->
                <div class="cmsp-stories swiper swiper-vertical">
                    <div class="swiper-wrapper"></div>
                </div>
                
                <!-- Navigation -->
                <div class="cmsp-navigation">
                    <button class="cmsp-nav-prev" aria-label="<?php esc_attr_e('Previous Story', 'cm-suite-elementor'); ?>">‹</button>
                    <button class="cmsp-nav-next" aria-label="<?php esc_attr_e('Next Story', 'cm-suite-elementor'); ?>">›</button>
                </div>
                
                <!-- Loading -->
                <div class="cmsp-loading" style="display: none;">
                    <div class="cmsp-spinner"></div>
                    <p><?php esc_html_e('Loading stories...', 'cm-suite-elementor'); ?></p>
                </div>
                
                <!-- Error -->
                <div class="cmsp-error" style="display: none;">
                    <p><?php esc_html_e('Error loading stories.', 'cm-suite-elementor'); ?></p>
                    <button class="cmsp-retry-btn"><?php esc_html_e('Retry', 'cm-suite-elementor'); ?></button>
                </div>
                
            </div>
            
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.CMSuiteStoriesPlayer) {
                window.CMSuiteStoriesPlayer.init('<?php echo esc_js($widget_id); ?>');
            }
        });
        </script>
        
        <?php
    }
}