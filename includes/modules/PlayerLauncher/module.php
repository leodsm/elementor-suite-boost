<?php

namespace CM\Suite\PlayerLauncher;

/**
 * Player Launcher Module
 * Provides functionality to launch Stories Player from any element
 */
class PlayerLauncher {
    
    /**
     * Initialize module
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }
    
    /**
     * Register module assets
     */
    public function register_assets() {
        // Register JS
        wp_register_script(
            'cm-player-launcher',
            CM_SUITE_URL . 'includes/modules/PlayerLauncher/assets/js/launcher.js',
            ['jquery'],
            CM_SUITE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cm-player-launcher', 'cmLauncherAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cm_launcher_nonce'),
        ]);
    }
    
    /**
     * Get launcher settings
     */
    public static function get_default_settings() {
        return [
            'trigger_selector' => '.cmpc-card__link',
            'player_selector' => '.cmsp-overlay',
            'extract_story_id' => true,
            'prevent_default' => true,
            'debug_mode' => false
        ];
    }
}

// Initialize module
new PlayerLauncher();