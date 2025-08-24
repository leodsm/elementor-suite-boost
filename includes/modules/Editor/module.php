<?php

namespace CM\Suite\Editor;

/**
 * Editor Module
 * Provides visual editing capabilities for story pages
 */
class Editor {
    
    /**
     * Initialize module
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_cm_save_story_pages', [$this, 'ajax_save_story_pages']);
        add_action('wp_ajax_cm_get_media_info', [$this, 'ajax_get_media_info']);
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_story_studio_assets']);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on story edit pages
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        global $post_type;
        if ($post_type !== 'cm_story') {
            return;
        }
        
        // Editor CSS
        wp_enqueue_style(
            'cm-editor',
            CM_SUITE_URL . 'includes/modules/Editor/assets/css/editor.css',
            [],
            CM_SUITE_VERSION
        );
        
        // Editor JS
        wp_enqueue_script(
            'cm-editor',
            CM_SUITE_URL . 'includes/modules/Editor/assets/js/editor.js',
            ['jquery', 'jquery-ui-sortable', 'wp-media'],
            CM_SUITE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cm-editor', 'cmEditorAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cm_editor_nonce'),
            'strings' => [
                'confirmDelete' => esc_html__('Are you sure you want to delete this page?', 'cm-suite-elementor'),
                'editPage' => esc_html__('Edit Page', 'cm-suite-elementor'),
                'imageUrl' => esc_html__('Image URL', 'cm-suite-elementor'),
                'pageTitle' => esc_html__('Page Title', 'cm-suite-elementor'),
                'pageText' => esc_html__('Page Text', 'cm-suite-elementor'),
                'videoUrl' => esc_html__('Video URL', 'cm-suite-elementor'),
                'save' => esc_html__('Save', 'cm-suite-elementor'),
                'cancel' => esc_html__('Cancel', 'cm-suite-elementor'),
                'selectImage' => esc_html__('Select Image', 'cm-suite-elementor'),
                'selectVideo' => esc_html__('Select Video', 'cm-suite-elementor'),
                'uploadImage' => esc_html__('Upload Image', 'cm-suite-elementor'),
                'uploadVideo' => esc_html__('Upload Video', 'cm-suite-elementor'),
            ]
        ]);
    }
    
    /**
     * AJAX handler for saving story pages
     */
    public function ajax_save_story_pages() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cm_editor_nonce')) {
            wp_die('Nonce verification failed');
        }
        
        // Check permissions
        $post_id = intval($_POST['post_id']);
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        // Validate and save pages
        $pages_json = sanitize_textarea_field($_POST['pages_json']);
        $pages_array = json_decode($pages_json, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($pages_array)) {
            update_post_meta($post_id, '_cm_story_pages', $pages_json);
            wp_send_json_success(['message' => 'Pages saved successfully']);
        } else {
            wp_send_json_error(['message' => 'Invalid JSON data']);
        }
    }
    
    /**
     * AJAX handler for getting media information
     */
    public function ajax_get_media_info() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'cm_editor_nonce')) {
            wp_die('Nonce verification failed');
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        $attachment = get_post($attachment_id);
        
        if ($attachment && $attachment->post_type === 'attachment') {
            $url = wp_get_attachment_url($attachment_id);
            $metadata = wp_get_attachment_metadata($attachment_id);
            
            wp_send_json_success([
                'id' => $attachment_id,
                'url' => $url,
                'title' => get_the_title($attachment_id),
                'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                'metadata' => $metadata
            ]);
        } else {
            wp_send_json_error(['message' => 'Attachment not found']);
        }
    }

    /**
     * Register Story Studio admin page
     */
    public function register_admin_page() {
        add_menu_page(
            esc_html__('Story Studio', 'cm-suite-elementor'),
            esc_html__('Story Studio', 'cm-suite-elementor'),
            'edit_posts',
            'cm-story-studio',
            [$this, 'render_story_studio'],
            'dashicons-format-gallery',
            20
        );
    }

    /**
     * Render Story Studio root div
     */
    public function render_story_studio() {
        echo '<div id="cm-story-studio-root"></div>';
    }

    /**
     * Enqueue assets for Story Studio
     */
    public function enqueue_story_studio_assets($hook) {
        if ($hook !== 'toplevel_page_cm-story-studio') {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'cm-story-studio',
            CM_SUITE_URL . 'includes/modules/Editor/assets/js/story-studio.js',
            [],
            CM_SUITE_VERSION,
            true
        );
        wp_localize_script('cm-story-studio', 'cmStoryStudio', [
            'restUrl' => esc_url_raw(rest_url('cm/v1/')),
            'nonce'   => wp_create_nonce('wp_rest'),
            'storyId' => isset($_GET['story_id']) ? absint($_GET['story_id']) : 0,
        ]);
    }
    
    /**
     * Validate page data
     */
    public static function validate_page($page) {
        if (!is_array($page) || !isset($page['type'])) {
            return false;
        }
        
        switch ($page['type']) {
            case 'image':
                return !empty($page['url']) && filter_var($page['url'], FILTER_VALIDATE_URL);
                
            case 'text':
                return !empty($page['title']) || !empty($page['text']);
                
            case 'video':
                return !empty($page['url']) && filter_var($page['url'], FILTER_VALIDATE_URL);
                
            default:
                return false;
        }
    }
    
    /**
     * Sanitize page data
     */
    public static function sanitize_page($page) {
        $sanitized = [
            'type' => sanitize_key($page['type'])
        ];
        
        switch ($page['type']) {
            case 'image':
                $sanitized['url'] = esc_url_raw($page['url']);
                if (isset($page['alt'])) {
                    $sanitized['alt'] = sanitize_text_field($page['alt']);
                }
                break;
                
            case 'text':
                $sanitized['title'] = sanitize_text_field($page['title'] ?? '');
                $sanitized['text'] = sanitize_textarea_field($page['text'] ?? '');
                break;
                
            case 'video':
                $sanitized['url'] = esc_url_raw($page['url']);
                if (isset($page['poster'])) {
                    $sanitized['poster'] = esc_url_raw($page['poster']);
                }
                break;
        }
        
        return $sanitized;
    }
}

// Initialize module
new Editor();