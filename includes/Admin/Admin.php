<?php

namespace CM\Suite\Admin;

/**
 * Admin functionality
 */
class Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_settings_save']);
        add_action('add_meta_boxes', [$this, 'add_story_metaboxes']);
        add_action('save_post', [$this, 'save_story_meta']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            esc_html__('CM Suite', 'cm-suite-elementor'),
            esc_html__('CM Suite', 'cm-suite-elementor'),
            'manage_options',
            'cm-suite',
            [$this, 'admin_page'],
            'dashicons-admin-plugins',
            30
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        $modules = get_option('cm_suite_modules', [
            'ThemeKit' => true,
            'PostCards' => true,
            'StoriesPlayer' => true,
            'PlayerLauncher' => true,
            'Editor' => true
        ]);
        
        $updated = isset($_GET['updated']) && $_GET['updated'] === 'true';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('CM Suite Settings', 'cm-suite-elementor'); ?></h1>
            
            <?php if ($updated): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Modules updated successfully!', 'cm-suite-elementor'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('cm_suite_settings', 'cm_suite_nonce'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Active Modules', 'cm-suite-elementor'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="cm_suite_modules[ThemeKit]" value="1" <?php checked(isset($modules['ThemeKit']) && $modules['ThemeKit']); ?>>
                                        <?php esc_html_e('Theme Kit', 'cm-suite-elementor'); ?>
                                        <p class="description"><?php esc_html_e('Design tokens, image sizes, and basic helpers', 'cm-suite-elementor'); ?></p>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="cm_suite_modules[PostCards]" value="1" <?php checked(isset($modules['PostCards']) && $modules['PostCards']); ?>>
                                        <?php esc_html_e('Post Cards', 'cm-suite-elementor'); ?>
                                        <p class="description"><?php esc_html_e('Grid and carousel layouts for posts', 'cm-suite-elementor'); ?></p>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="cm_suite_modules[StoriesPlayer]" value="1" <?php checked(isset($modules['StoriesPlayer']) && $modules['StoriesPlayer']); ?>>
                                        <?php esc_html_e('Stories Player', 'cm-suite-elementor'); ?>
                                        <p class="description"><?php esc_html_e('Vertical stories player with overlay interface', 'cm-suite-elementor'); ?></p>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="cm_suite_modules[PlayerLauncher]" value="1" <?php checked(isset($modules['PlayerLauncher']) && $modules['PlayerLauncher']); ?>>
                                        <?php esc_html_e('Player Launcher', 'cm-suite-elementor'); ?>
                                        <p class="description"><?php esc_html_e('Launch stories from any page element', 'cm-suite-elementor'); ?></p>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="cm_suite_modules[Editor]" value="1" <?php checked(isset($modules['Editor']) && $modules['Editor']); ?>>
                                        <?php esc_html_e('Visual Editor', 'cm-suite-elementor'); ?>
                                        <p class="description"><?php esc_html_e('Drag-and-drop story page editor', 'cm-suite-elementor'); ?></p>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button(__('Save Settings', 'cm-suite-elementor')); ?>
            </form>
            
            <div class="cm-suite-info">
                <h2><?php esc_html_e('Plugin Information', 'cm-suite-elementor'); ?></h2>
                <p><strong><?php esc_html_e('Version:', 'cm-suite-elementor'); ?></strong> <?php echo esc_html(CM_SUITE_VERSION); ?></p>
                <p><strong><?php esc_html_e('Elementor Status:', 'cm-suite-elementor'); ?></strong> 
                    <?php if (defined('ELEMENTOR_VERSION')): ?>
                        <span style="color: green;"><?php printf(esc_html__('Active (v%s)', 'cm-suite-elementor'), ELEMENTOR_VERSION); ?></span>
                    <?php else: ?>
                        <span style="color: red;"><?php esc_html_e('Not installed', 'cm-suite-elementor'); ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle settings save
     */
    public function handle_settings_save() {
        if (!isset($_POST['cm_suite_nonce']) || !wp_verify_nonce($_POST['cm_suite_nonce'], 'cm_suite_settings')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $modules = isset($_POST['cm_suite_modules']) ? $_POST['cm_suite_modules'] : [];
        
        // Ensure boolean values
        $clean_modules = [
            'ThemeKit' => isset($modules['ThemeKit']),
            'PostCards' => isset($modules['PostCards']),
            'StoriesPlayer' => isset($modules['StoriesPlayer']),
            'PlayerLauncher' => isset($modules['PlayerLauncher']),
            'Editor' => isset($modules['Editor'])
        ];
        
        update_option('cm_suite_modules', $clean_modules);
        
        wp_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=cm-suite')));
        exit;
    }
    
    /**
     * Add story metaboxes
     */
    public function add_story_metaboxes() {
        add_meta_box(
            'cm-story-pages',
            esc_html__('Story Pages', 'cm-suite-elementor'),
            [$this, 'story_pages_metabox'],
            'cm_story',
            'normal',
            'high'
        );
    }
    
    /**
     * Story pages metabox
     */
    public function story_pages_metabox($post) {
        wp_nonce_field('cm_story_pages_meta', 'cm_story_pages_nonce');
        
        $pages_data = get_post_meta($post->ID, '_cm_story_pages', true);
        $pages_json = $pages_data ? $pages_data : '[]';
        ?>
        <div class="cm-story-editor">
            <div class="cm-editor-controls">
                <button type="button" class="button" id="cm-visual-editor-toggle">
                    <?php esc_html_e('Visual Editor', 'cm-suite-elementor'); ?>
                </button>
            </div>
            
            <div class="cm-json-editor">
                <label for="cm-story-pages-json"><?php esc_html_e('Pages JSON:', 'cm-suite-elementor'); ?></label>
                <textarea 
                    id="cm-story-pages-json" 
                    name="cm_story_pages" 
                    rows="10" 
                    class="large-text code"
                    placeholder='[{"type":"image","url":"https://example.com/image.jpg"},{"type":"text","title":"Title","text":"Content"}]'
                ><?php echo esc_textarea($pages_json); ?></textarea>
                <p class="description">
                    <?php esc_html_e('JSON format: [{"type":"image","url":"..."}, {"type":"text","title":"...","text":"..."}, {"type":"video","url":"..."}]', 'cm-suite-elementor'); ?>
                </p>
            </div>
            
            <div id="cm-visual-editor" class="cm-visual-editor" style="display: none;">
                <div class="cm-visual-controls">
                    <button type="button" class="button" data-type="image"><?php esc_html_e('Add Image', 'cm-suite-elementor'); ?></button>
                    <button type="button" class="button" data-type="text"><?php esc_html_e('Add Text', 'cm-suite-elementor'); ?></button>
                    <button type="button" class="button" data-type="video"><?php esc_html_e('Add Video', 'cm-suite-elementor'); ?></button>
                </div>
                <div id="cm-visual-pages" class="cm-visual-pages sortable"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save story meta
     */
    public function save_story_meta($post_id) {
        if (!isset($_POST['cm_story_pages_nonce']) || !wp_verify_nonce($_POST['cm_story_pages_nonce'], 'cm_story_pages_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['cm_story_pages'])) {
            $pages_json = sanitize_textarea_field($_POST['cm_story_pages']);
            
            // Validate JSON
            $pages_array = json_decode($pages_json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                update_post_meta($post_id, '_cm_story_pages', $pages_json);
            }
        }
    }
}