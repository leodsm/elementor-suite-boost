<?php

namespace CM\Suite;

/**
 * Main Suite class
 */
class Suite {
    
    private static $instance = null;
    private $modules = [];
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_modules();
        $this->register_elementor_category();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('init', [$this, 'load_textdomain']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('cm-suite-elementor', false, dirname(CM_SUITE_BASENAME) . '/languages');
    }
    
    /**
     * Load enabled modules
     */
    private function load_modules() {
        $enabled_modules = get_option('cm_suite_modules', [
            'ThemeKit' => true,
            'PostCards' => true,
            'StoriesPlayer' => true,
            'PlayerLauncher' => true,
            'Editor' => true
        ]);
        
        foreach ($enabled_modules as $module => $enabled) {
            if ($enabled) {
                $this->load_module($module);
            }
        }
    }
    
    /**
     * Load individual module
     */
    private function load_module($module) {
        $module_file = CM_SUITE_PATH . "includes/modules/{$module}/module.php";
        
        if (file_exists($module_file)) {
            require_once $module_file;
            $this->modules[$module] = true;
        }
    }
    
    /**
     * Register Elementor category
     */
    private function register_elementor_category() {
        add_action('elementor/elements/categories_registered', function($elements_manager) {
            $elements_manager->add_category('cm-suite', [
                'title' => esc_html__('CM Suite', 'cm-suite-elementor'),
                'icon' => 'fa fa-plug'
            ]);
        });
    }
    
    /**
     * Register widgets with Elementor
     */
    public function register_widgets($widgets_manager) {
        // Register Post Cards widget
        if (isset($this->modules['PostCards'])) {
            require_once CM_SUITE_PATH . 'includes/modules/PostCards/widgets/class-cm-post-cards.php';
            $widgets_manager->register(new \CM\Suite\PostCards\Widgets\CM_Post_Cards());
        }
        
        // Register Stories Player widget
        if (isset($this->modules['StoriesPlayer'])) {
            require_once CM_SUITE_PATH . 'includes/modules/StoriesPlayer/widgets/class-cm-stories-player.php';
            $widgets_manager->register(new \CM\Suite\StoriesPlayer\Widgets\CM_Stories_Player());
        }
        
        // Register Player Launcher widget
        if (isset($this->modules['PlayerLauncher'])) {
            require_once CM_SUITE_PATH . 'includes/modules/PlayerLauncher/widgets/class-cm-player-launcher.php';
            $widgets_manager->register(new \CM\Suite\PlayerLauncher\Widgets\CM_Player_Launcher());
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Register common dependencies
        wp_register_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.0.0', true);
        wp_register_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.0.0');
        
        // Global theme styles (always loaded if ThemeKit is active)
        if (isset($this->modules['ThemeKit'])) {
            wp_enqueue_style('cm-theme-kit', CM_SUITE_URL . 'includes/modules/ThemeKit/assets/css/theme.css', [], CM_SUITE_VERSION);
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Load admin styles on CM Suite pages
        if (strpos($hook, 'cm-suite') !== false) {
            wp_enqueue_style('cm-admin', CM_SUITE_URL . 'includes/modules/Editor/assets/css/editor.css', [], CM_SUITE_VERSION);
            wp_enqueue_script('cm-admin', CM_SUITE_URL . 'includes/modules/Editor/assets/js/editor.js', ['jquery', 'jquery-ui-sortable'], CM_SUITE_VERSION, true);
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        if (isset($this->modules['StoriesPlayer'])) {
            require_once CM_SUITE_PATH . 'includes/modules/StoriesPlayer/rest/class-rest.php';
            new \CM\Suite\StoriesPlayer\Rest\Stories_Rest_Controller();
        }
    }
    
    /**
     * Get module status
     */
    public function is_module_active($module) {
        return isset($this->modules[$module]);
    }
    
    /**
     * Get all registered modules
     */
    public function get_modules() {
        return $this->modules;
    }
}

// Initialize Admin
require_once CM_SUITE_PATH . 'includes/Admin/Admin.php';
new \CM\Suite\Admin\Admin();