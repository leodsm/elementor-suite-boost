<?php

namespace CM\Suite\ThemeKit;

/**
 * Theme Kit Module
 * Provides design tokens, image sizes, and basic helpers
 */
class ThemeKit {
    
    /**
     * Initialize module
     */
    public function __construct() {
        add_action('after_setup_theme', [$this, 'add_image_sizes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_head', [$this, 'add_css_variables']);
    }
    
    /**
     * Add custom image sizes
     */
    public function add_image_sizes() {
        // Card size for post cards (4:3 ratio)
        add_image_size('cm-card', 800, 1066, true);
        
        // Story cover size (9:16 ratio for vertical stories)
        add_image_size('cm-story-cover', 1080, 1920, true);
    }
    
    /**
     * Enqueue theme assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'cm-theme-kit',
            CM_SUITE_URL . 'includes/modules/ThemeKit/assets/css/theme.css',
            [],
            CM_SUITE_VERSION
        );
    }
    
    /**
     * Add CSS custom properties
     */
    public function add_css_variables() {
        ?>
        <style id="cm-suite-theme-vars">
        :root {
            --cm-gap: 24px;
            --cm-radius: 16px;
            --cm-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --cm-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --cm-shadow-hover: 0 8px 40px rgba(0, 0, 0, 0.15);
            --cm-gradient-overlay: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.8) 100%);
            --cm-color-primary: #1e40af;
            --cm-color-secondary: #f97316;
            --cm-color-background: #ffffff;
            --cm-color-text: #111827;
            --cm-font-heading: 'Poppins', sans-serif;
            --cm-font-body: 'Roboto', sans-serif;
        }
        </style>
        <?php
    }
    
    /**
     * Get design token value
     */
    public static function get_token($token, $default = '') {
        $tokens = [
            'gap' => '24px',
            'radius' => '16px',
            'transition' => 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
            'shadow' => '0 4px 20px rgba(0, 0, 0, 0.1)',
            'shadow-hover' => '0 8px 40px rgba(0, 0, 0, 0.15)',
            'color-primary' => '#1e40af',
            'color-secondary' => '#f97316',
            'color-background' => '#ffffff',
            'color-text' => '#111827',
            'font-heading' => "'Poppins', sans-serif",
            'font-body' => "'Roboto', sans-serif"
        ];
        
        return isset($tokens[$token]) ? $tokens[$token] : $default;
    }
    
    /**
     * Get responsive image HTML
     */
    public static function get_responsive_image($attachment_id, $size = 'cm-card', $alt = '', $class = '') {
        if (empty($attachment_id)) {
            return '';
        }
        
        $image = wp_get_attachment_image($attachment_id, $size, false, [
            'alt' => esc_attr($alt),
            'class' => esc_attr($class),
            'loading' => 'lazy'
        ]);
        
        return $image;
    }
}

// Initialize module
new ThemeKit();