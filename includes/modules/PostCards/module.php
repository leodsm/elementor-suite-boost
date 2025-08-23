<?php

namespace CM\Suite\PostCards;

/**
 * Post Cards Module
 * Provides grid and carousel post layouts with category colors
 */
class PostCards {
    
    /**
     * Initialize module
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_filter('cm_suite_category_color', [$this, 'get_category_color'], 10, 2);
    }
    
    /**
     * Register module assets
     */
    public function register_assets() {
        // Register CSS
        wp_register_style(
            'cm-post-cards',
            CM_SUITE_URL . 'includes/modules/PostCards/assets/css/post-cards.css',
            ['swiper'],
            CM_SUITE_VERSION
        );
        
        // Register JS
        wp_register_script(
            'cm-post-cards',
            CM_SUITE_URL . 'includes/modules/PostCards/assets/js/post-cards.js',
            ['swiper'],
            CM_SUITE_VERSION,
            true
        );
    }
    
    /**
     * Get category color
     * Priority: ACF field > Internal mapping > Default
     */
    public function get_category_color($color, $category) {
        // Check for ACF field override
        if (function_exists('get_field')) {
            $acf_color = get_field('cm_category_color', $category);
            if ($acf_color) {
                return $acf_color;
            }
        }
        
        // Check term meta
        $term_color = get_term_meta($category->term_id, 'cm_category_color', true);
        if ($term_color) {
            return $term_color;
        }
        
        // Internal category color mapping
        $color_map = [
            'politica' => '#E84545',
            'economia' => '#2E8B57',
            'esportes' => '#FF6B35',
            'tecnologia' => '#4A90E2',
            'saude' => '#27AE60',
            'cultura' => '#9B59B6',
            'educacao' => '#F39C12',
            'internacional' => '#34495E',
            'geral' => '#95A5A6'
        ];
        
        $slug = $category->slug;
        return isset($color_map[$slug]) ? $color_map[$slug] : '#3498DB';
    }
    
    /**
     * Get posts for widget
     */
    public static function get_posts($settings) {
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $settings['posts_per_page'] ?? 6,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        // Add category filter
        if (!empty($settings['categories'])) {
            $args['category__in'] = $settings['categories'];
        }
        
        return new \WP_Query($args);
    }
    
    /**
     * Truncate text by words
     */
    public static function truncate_words($text, $limit = 20) {
        $words = explode(' ', strip_tags($text));
        if (count($words) > $limit) {
            return implode(' ', array_slice($words, 0, $limit)) . '...';
        }
        return $text;
    }
    
    /**
     * Truncate text by characters
     */
    public static function truncate_chars($text, $limit = 120) {
        $text = strip_tags($text);
        if (strlen($text) > $limit) {
            return substr($text, 0, $limit) . '...';
        }
        return $text;
    }
    
    /**
     * Get ghost text (2-3 words from title)
     */
    public static function get_ghost_text($title) {
        $words = explode(' ', strip_tags($title));
        $ghost_words = array_slice($words, 0, 3);
        return implode(' ', $ghost_words);
    }
}

// Initialize module
new PostCards();