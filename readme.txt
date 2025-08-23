=== CM Suite for Elementor ===
Contributors: cmsuite
Tags: elementor, widgets, stories, post-cards, theme-kit
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete suite of advanced widgets and tools for Elementor with Stories Player, Post Cards, Theme Kit and more.

== Description ==

CM Suite for Elementor is a comprehensive collection of advanced widgets and tools designed to enhance your Elementor experience. Build stunning websites with our professional-grade components.

**Included Modules:**

* **Theme Kit** - Design tokens, image sizes, and basic helpers for consistent styling
* **Post Cards** - Beautiful grid and carousel layouts for your posts with category colors and hover effects
* **Stories Player** - Vertical stories player with nested horizontal pages and overlay interface
* **Player Launcher** - Launch stories player from any element on your page
* **Editor** - Visual drag-and-drop editor for story pages with rich content types

**Key Features:**

* 🎨 Beautiful presets: Clean, Glass, Magazine, Overlay, Parallax
* 🎯 Category-based color mapping with ACF support
* 📱 Fully responsive and mobile-optimized
* ♿ Accessibility compliant (WCAG guidelines)
* ⚡ Performance optimized with lazy loading
* 🔧 Developer-friendly with hooks and filters
* 🌐 REST API for stories integration
* 📦 Custom Post Type for stories management

**Requirements:**

* WordPress 6.8 or higher
* PHP 8.1 to 8.3
* Elementor 3.30 or higher (Free version supported, Pro features optional)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cm-suite-elementor` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Make sure Elementor is installed and activated
4. Go to CM Suite settings to configure modules
5. Start using the new widgets in Elementor!

== Frequently Asked Questions ==

= Does this work with Elementor Free? =

Yes! CM Suite is fully compatible with Elementor Free. Some advanced features work even better with Elementor Pro, but it's not required.

= Can I customize the colors? =

Absolutely! The plugin includes a comprehensive color system that integrates with your theme. You can also use ACF to set custom colors per category.

= Is it mobile responsive? =

Yes, all widgets are fully responsive and optimized for mobile devices with touch-friendly interactions.

= Does it affect site performance? =

No, we've optimized everything for performance. Assets are loaded only when needed, and queries are optimized to avoid unnecessary database calls.

== Screenshots ==

1. Post Cards widget with different layout presets
2. Stories Player with vertical scrolling interface
3. Visual story editor with drag-and-drop functionality
4. Admin panel with module management
5. Category color mapping interface

== Changelog ==

= 1.1.0 =
* Initial release
* Theme Kit module with design tokens
* Post Cards widget with grid/carousel layouts
* Stories Player with custom post type
* Player Launcher for seamless integration
* Visual Editor for story management
* REST API endpoints for stories
* Accessibility improvements
* Performance optimizations
* Multi-language support ready

== Upgrade Notice ==

= 1.1.0 =
Initial release of CM Suite for Elementor. Install to get access to all premium widgets and tools.

== Developer Notes ==

**Extending the Plugin:**

* Use filter `cm_suite_category_color` to customize category colors
* Hook into `CM\Suite\Stories\before_render` for custom story processing
* Override CSS variables for complete visual customization
* Use REST API endpoints for headless integrations

**Category Color Mapping:**

1. Internal mapping by category slug (e.g., 'politics' → '#E84545')
2. ACF field override: `cm_category_color` in term meta
3. Theme filter: `cm_suite_category_color`

**JavaScript Hooks:**

* `window.CMSuiteStoriesPlayer.open()` - Open stories player
* `window.CMSuiteStoriesPlayer.close()` - Close stories player
* Event: `cm-suite:story-opened` - Fired when story opens
* Event: `cm-suite:story-closed` - Fired when story closes

For detailed documentation, visit our GitHub repository or contact support.