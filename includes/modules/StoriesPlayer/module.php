<?php

namespace CM\Suite\StoriesPlayer;

/**
 * Stories Player Module
 * Provides vertical stories player with custom post type and REST API
 */
class StoriesPlayer {
    
    /**
     * Initialize module
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('wp', [$this, 'handle_story_deep_link']);
    }
    
    /**
     * Register Stories custom post type
     */
    public function register_post_type() {
        $labels = [
            'name' => esc_html__('Stories', 'cm-suite-elementor'),
            'singular_name' => esc_html__('Story', 'cm-suite-elementor'),
            'menu_name' => esc_html__('Stories', 'cm-suite-elementor'),
            'add_new' => esc_html__('Add New', 'cm-suite-elementor'),
            'add_new_item' => esc_html__('Add New Story', 'cm-suite-elementor'),
            'edit_item' => esc_html__('Edit Story', 'cm-suite-elementor'),
            'new_item' => esc_html__('New Story', 'cm-suite-elementor'),
            'view_item' => esc_html__('View Story', 'cm-suite-elementor'),
            'search_items' => esc_html__('Search Stories', 'cm-suite-elementor'),
            'not_found' => esc_html__('No stories found', 'cm-suite-elementor'),
            'not_found_in_trash' => esc_html__('No stories found in trash', 'cm-suite-elementor'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'rest_base' => 'stories',
            'query_var' => true,
            'rewrite' => ['slug' => 'stories'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-format-gallery',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'show_in_nav_menus' => true,
        ];
        
        register_post_type('cm_story', $args);
    }
    
    /**
     * Register module assets
     */
    public function register_assets() {
        // Register CSS
        wp_register_style(
            'cm-stories-player',
            CM_SUITE_URL . 'includes/modules/StoriesPlayer/assets/css/stories-player.css',
            ['swiper'],
            CM_SUITE_VERSION
        );
        
        // Register JS
        wp_register_script(
            'cm-stories-player',
            CM_SUITE_URL . 'includes/modules/StoriesPlayer/assets/js/stories-player.js',
            ['swiper'],
            CM_SUITE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cm-stories-player', 'cmStoriesAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cm_stories_nonce'),
            'restUrl' => rest_url('cm/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }
    
    /**
     * Add rewrite rules for deep linking
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^story/([^/]+)/?', 'index.php?story=$matches[1]', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'story';
        return $vars;
    }
    
    /**
     * Handle story deep link
     */
    public function handle_story_deep_link() {
        $story_slug = get_query_var('story');
        
        if ($story_slug && !is_admin()) {
            // Find story by slug
            $story = get_page_by_path($story_slug, OBJECT, 'cm_story');
            
            if ($story) {
                // Enqueue stories player assets
                wp_enqueue_style('cm-stories-player');
                wp_enqueue_script('cm-stories-player');
                
                // Add body class for styling
                add_filter('body_class', function($classes) {
                    $classes[] = 'cm-story-deeplink';
                    return $classes;
                });
                
                // Add story data to page
                add_action('wp_footer', function() use ($story) {
                    ?>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (window.CMSuiteStoriesPlayer) {
                            window.CMSuiteStoriesPlayer.openStory(<?php echo $story->ID; ?>);
                        }
                    });
                    </script>
                    <?php
                });
            }
        }
    }
    
    /**
     * Get story pages
     */
    public static function get_story_pages($story_id) {
        $pages_json = get_post_meta($story_id, '_cm_story_pages', true);
        
        if (empty($pages_json)) {
            return [];
        }
        
        $pages = json_decode($pages_json, true);
        return is_array($pages) ? $pages : [];
    }
    
    /**
     * Get stories for REST API
     */
    public static function get_stories($args = []) {
        $default_args = [
            'post_type' => 'cm_story',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        return new \WP_Query($args);
    }
    
    /**
     * Format story for API response
     */
    public static function format_story_for_api($story) {
        $thumbnail_id = get_post_thumbnail_id($story->ID);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'cm-story-cover') : '';
        
        return [
            'id' => $story->ID,
            'title' => get_the_title($story->ID),
            'thumbnail' => $thumbnail_url,
            'excerpt' => get_the_excerpt($story->ID),
            'permalink' => get_permalink($story->ID),
            'date' => get_the_date('c', $story->ID),
        ];
    }
    
    /**
     * Format story with pages for API response
     */
    public static function format_story_with_pages($story) {
        $data = self::format_story_for_api($story);
        $data['pages'] = self::get_story_pages($story->ID);
        
        return $data;
    }
}

// Initialize module
new StoriesPlayer();

// Initialize CPT
require_once __DIR__ . '/cpt/class-cpt-story.php';
new \CM\Suite\StoriesPlayer\CPT\Story_CPT();