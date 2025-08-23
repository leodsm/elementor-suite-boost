<?php

namespace CM\Suite\StoriesPlayer\CPT;

/**
 * Story Custom Post Type Handler
 */
class Story_CPT {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta']);
        add_filter('manage_cm_story_posts_columns', [$this, 'add_columns']);
        add_action('manage_cm_story_posts_custom_column', [$this, 'fill_columns'], 10, 2);
        add_filter('manage_edit-cm_story_sortable_columns', [$this, 'sortable_columns']);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cm-story-pages',
            esc_html__('Story Pages', 'cm-suite-elementor'),
            [$this, 'pages_meta_box'],
            'cm_story',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cm-story-settings',
            esc_html__('Story Settings', 'cm-suite-elementor'),
            [$this, 'settings_meta_box'],
            'cm_story',
            'side',
            'default'
        );
    }
    
    /**
     * Pages meta box
     */
    public function pages_meta_box($post) {
        wp_nonce_field('cm_story_pages_meta', 'cm_story_pages_nonce');
        
        $pages_data = get_post_meta($post->ID, '_cm_story_pages', true);
        $pages_json = $pages_data ? $pages_data : '[]';
        ?>
        <div class="cm-story-editor">
            <div class="cm-editor-tabs">
                <button type="button" class="cm-tab-btn active" data-tab="visual"><?php esc_html_e('Visual Editor', 'cm-suite-elementor'); ?></button>
                <button type="button" class="cm-tab-btn" data-tab="json"><?php esc_html_e('JSON Editor', 'cm-suite-elementor'); ?></button>
            </div>
            
            <div class="cm-tab-content" id="cm-visual-tab">
                <div class="cm-visual-controls">
                    <button type="button" class="button cm-add-page" data-type="image">
                        <span class="dashicons dashicons-format-image"></span>
                        <?php esc_html_e('Add Image', 'cm-suite-elementor'); ?>
                    </button>
                    <button type="button" class="button cm-add-page" data-type="text">
                        <span class="dashicons dashicons-text"></span>
                        <?php esc_html_e('Add Text', 'cm-suite-elementor'); ?>
                    </button>
                    <button type="button" class="button cm-add-page" data-type="video">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <?php esc_html_e('Add Video', 'cm-suite-elementor'); ?>
                    </button>
                </div>
                
                <div class="cm-pages-container sortable" id="cm-visual-pages">
                    <!-- Pages will be populated by JavaScript -->
                </div>
                
                <div class="cm-empty-state" style="display: none;">
                    <p><?php esc_html_e('No pages yet. Add your first page using the buttons above.', 'cm-suite-elementor'); ?></p>
                </div>
            </div>
            
            <div class="cm-tab-content" id="cm-json-tab" style="display: none;">
                <label for="cm-story-pages-json"><?php esc_html_e('Pages JSON:', 'cm-suite-elementor'); ?></label>
                <textarea 
                    id="cm-story-pages-json" 
                    name="cm_story_pages" 
                    rows="15" 
                    class="large-text code"
                    placeholder='[{"type":"image","url":"https://example.com/image.jpg"},{"type":"text","title":"Title","text":"Content"}]'
                ><?php echo esc_textarea($pages_json); ?></textarea>
                
                <p class="description">
                    <?php esc_html_e('JSON format examples:', 'cm-suite-elementor'); ?><br>
                    <code>{"type":"image","url":"https://example.com/image.jpg"}</code><br>
                    <code>{"type":"text","title":"Page Title","text":"Page content"}</code><br>
                    <code>{"type":"video","url":"https://example.com/video.mp4"}</code>
                </p>
            </div>
        </div>
        
        <script type="text/template" id="cm-page-template">
            <div class="cm-page-item" data-type="{{type}}">
                <div class="cm-page-header">
                    <span class="cm-page-type">{{typeLabel}}</span>
                    <div class="cm-page-actions">
                        <button type="button" class="cm-page-edit" title="<?php esc_attr_e('Edit', 'cm-suite-elementor'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="cm-page-delete" title="<?php esc_attr_e('Delete', 'cm-suite-elementor'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                        <span class="cm-page-handle" title="<?php esc_attr_e('Drag to reorder', 'cm-suite-elementor'); ?>">
                            <span class="dashicons dashicons-menu"></span>
                        </span>
                    </div>
                </div>
                <div class="cm-page-content">{{content}}</div>
            </div>
        </script>
        <?php
    }
    
    /**
     * Settings meta box
     */
    public function settings_meta_box($post) {
        wp_nonce_field('cm_story_settings_meta', 'cm_story_settings_nonce');
        
        $duration = get_post_meta($post->ID, '_cm_story_duration', true) ?: 5;
        $featured = get_post_meta($post->ID, '_cm_story_featured', true);
        ?>
        
        <p>
            <label for="cm-story-duration"><?php esc_html_e('Page Duration (seconds)', 'cm-suite-elementor'); ?></label>
            <input type="number" id="cm-story-duration" name="cm_story_duration" 
                   value="<?php echo esc_attr($duration); ?>" min="1" max="30" class="widefat">
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="cm_story_featured" value="1" <?php checked($featured, '1'); ?>>
                <?php esc_html_e('Featured Story', 'cm-suite-elementor'); ?>
            </label>
        </p>
        
        <p class="description">
            <?php esc_html_e('Featured stories appear first in the player.', 'cm-suite-elementor'); ?>
        </p>
        
        <?php
    }
    
    /**
     * Save meta data
     */
    public function save_meta($post_id) {
        // Check nonces
        if (!isset($_POST['cm_story_pages_nonce']) || !wp_verify_nonce($_POST['cm_story_pages_nonce'], 'cm_story_pages_meta')) {
            return;
        }
        
        if (!isset($_POST['cm_story_settings_nonce']) || !wp_verify_nonce($_POST['cm_story_settings_nonce'], 'cm_story_settings_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save pages
        if (isset($_POST['cm_story_pages'])) {
            $pages_json = sanitize_textarea_field($_POST['cm_story_pages']);
            
            // Validate JSON
            $pages_array = json_decode($pages_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($pages_array)) {
                update_post_meta($post_id, '_cm_story_pages', $pages_json);
            }
        }
        
        // Save settings
        if (isset($_POST['cm_story_duration'])) {
            $duration = absint($_POST['cm_story_duration']);
            $duration = max(1, min(30, $duration)); // Clamp between 1-30 seconds
            update_post_meta($post_id, '_cm_story_duration', $duration);
        }
        
        $featured = isset($_POST['cm_story_featured']) ? '1' : '0';
        update_post_meta($post_id, '_cm_story_featured', $featured);
    }
    
    /**
     * Add custom columns
     */
    public function add_columns($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['story_thumbnail'] = esc_html__('Thumbnail', 'cm-suite-elementor');
                $new_columns['story_pages'] = esc_html__('Pages', 'cm-suite-elementor');
                $new_columns['story_featured'] = esc_html__('Featured', 'cm-suite-elementor');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Fill custom columns
     */
    public function fill_columns($column, $post_id) {
        switch ($column) {
            case 'story_thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [60, 60]);
                } else {
                    echo '<div style="width:60px;height:60px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:4px;color:#999;">📷</div>';
                }
                break;
                
            case 'story_pages':
                $pages_data = get_post_meta($post_id, '_cm_story_pages', true);
                $pages = $pages_data ? json_decode($pages_data, true) : [];
                $count = is_array($pages) ? count($pages) : 0;
                
                printf(
                    _n('%d page', '%d pages', $count, 'cm-suite-elementor'),
                    $count
                );
                break;
                
            case 'story_featured':
                $featured = get_post_meta($post_id, '_cm_story_featured', true);
                if ($featured === '1') {
                    echo '<span class="dashicons dashicons-star-filled" style="color:#f1c40f;" title="' . esc_attr__('Featured', 'cm-suite-elementor') . '"></span>';
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function sortable_columns($columns) {
        $columns['story_pages'] = 'story_pages';
        $columns['story_featured'] = 'story_featured';
        
        return $columns;
    }
}