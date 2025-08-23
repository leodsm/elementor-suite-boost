<?php

namespace CM\Suite\PostCards\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * CM Post Cards Widget
 */
class CM_Post_Cards extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'cm-suite-post-cards';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('CM Post Cards', 'cm-suite-elementor');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
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
        return ['posts', 'cards', 'grid', 'carousel', 'blog'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section - Query
        $this->start_controls_section(
            'query_section',
            [
                'label' => esc_html__('Query', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'posts_per_page',
            [
                'label' => esc_html__('Posts Per Page', 'cm-suite-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 20,
            ]
        );
        
        $this->add_control(
            'categories',
            [
                'label' => esc_html__('Categories', 'cm-suite-elementor'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_categories_options(),
                'label_block' => true,
            ]
        );
        
        $this->end_controls_section();
        
        // Content Section - Layout
        $this->start_controls_section(
            'layout_section',
            [
                'label' => esc_html__('Layout', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'layout_type',
            [
                'label' => esc_html__('Layout Type', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Carousel', 'cm-suite-elementor'),
                'label_off' => esc_html__('Grid', 'cm-suite-elementor'),
                'return_value' => 'carousel',
                'default' => '',
            ]
        );
        
        $this->add_control(
            'aspect_ratio',
            [
                'label' => esc_html__('Aspect Ratio', 'cm-suite-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '4-3',
                'options' => [
                    '16-9' => '16:9',
                    '4-3' => '4:3',
                    '1-1' => '1:1',
                    '3-4' => '3:4',
                    '9-16' => '9:16',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'cm-suite-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'layout_type!' => 'carousel',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Content Section - Content
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'show_category',
            [
                'label' => esc_html__('Show Category', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_date',
            [
                'label' => esc_html__('Show Date', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );
        
        $this->add_control(
            'show_author',
            [
                'label' => esc_html__('Show Author', 'cm-suite-elementor'),
                'type' => Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );
        
        $this->add_control(
            'title_words_limit',
            [
                'label' => esc_html__('Title Words Limit', 'cm-suite-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
                'min' => 1,
                'max' => 20,
            ]
        );
        
        $this->add_control(
            'excerpt_chars_limit',
            [
                'label' => esc_html__('Excerpt Characters Limit', 'cm-suite-elementor'),
                'type' => Controls_Manager::NUMBER,
                'default' => 120,
                'min' => 50,
                'max' => 300,
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Cards
        $this->start_controls_section(
            'style_cards_section',
            [
                'label' => esc_html__('Cards', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'preset',
            [
                'label' => esc_html__('Preset', 'cm-suite-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'clean',
                'options' => [
                    'clean' => esc_html__('Clean', 'cm-suite-elementor'),
                    'glass' => esc_html__('Glass', 'cm-suite-elementor'),
                    'magazine' => esc_html__('Magazine', 'cm-suite-elementor'),
                    'overlay' => esc_html__('Overlay', 'cm-suite-elementor'),
                    'parallax' => esc_html__('Parallax', 'cm-suite-elementor'),
                ],
            ]
        );
        
        $this->add_control(
            'hover_effect',
            [
                'label' => esc_html__('Hover Effect', 'cm-suite-elementor'),
                'type' => Controls_Manager::SELECT,
                'default' => 'zoom',
                'options' => [
                    'none' => esc_html__('None', 'cm-suite-elementor'),
                    'zoom' => esc_html__('Zoom', 'cm-suite-elementor'),
                    'parallax' => esc_html__('Parallax', 'cm-suite-elementor'),
                    'glow' => esc_html__('Gradient Glow', 'cm-suite-elementor'),
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Typography
        $this->start_controls_section(
            'style_typography_section',
            [
                'label' => esc_html__('Typography', 'cm-suite-elementor'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Title Typography', 'cm-suite-elementor'),
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .cmpc-card__title',
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Get categories options for select control
     */
    private function get_categories_options() {
        $categories = get_categories([
            'hide_empty' => false,
        ]);
        
        $options = [];
        foreach ($categories as $category) {
            $options[$category->term_id] = $category->name;
        }
        
        return $options;
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get posts
        $query = \CM\Suite\PostCards\PostCards::get_posts($settings);
        
        if (!$query->have_posts()) {
            echo '<div class="cmpc-no-posts">' . esc_html__('No posts found.', 'cm-suite-elementor') . '</div>';
            return;
        }
        
        // Enqueue assets
        wp_enqueue_style('cm-post-cards');
        wp_enqueue_script('cm-post-cards');
        
        $is_carousel = $settings['layout_type'] === 'carousel';
        $wrapper_class = $is_carousel ? 'cmpc-carousel' : 'cmpc-grid';
        $columns = $settings['columns'] ?? '3';
        ?>
        
        <div class="cmpc-wrapper cmpc-preset-<?php echo esc_attr($settings['preset']); ?> cmpc-hover-<?php echo esc_attr($settings['hover_effect']); ?>" 
             data-aspect-ratio="<?php echo esc_attr($settings['aspect_ratio']); ?>">
            
            <?php if ($is_carousel): ?>
                <div class="cmpc-swiper swiper" data-breakpoints='{"768": {"slidesPerView": 1.2}, "1024": {"slidesPerView": 2.2}, "1200": {"slidesPerView": 3.2}}'>
                    <div class="swiper-wrapper">
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <div class="swiper-slide">
                                <?php $this->render_card($settings); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="cmpc-nav">
                        <button class="cmpc-nav-prev" aria-label="<?php esc_attr_e('Previous', 'cm-suite-elementor'); ?>">‹</button>
                        <button class="cmpc-nav-next" aria-label="<?php esc_attr_e('Next', 'cm-suite-elementor'); ?>">›</button>
                    </div>
                    
                    <div class="cmpc-pagination"></div>
                </div>
                
            <?php else: ?>
                <div class="cmpc-grid" data-columns="<?php echo esc_attr($columns); ?>" data-columns-tablet="<?php echo esc_attr($settings['columns_tablet'] ?? '2'); ?>" data-columns-mobile="<?php echo esc_attr($settings['columns_mobile'] ?? '1'); ?>">
                    <?php while ($query->have_posts()): $query->the_post(); ?>
                        <?php $this->render_card($settings); ?>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            
        </div>
        
        <?php
        wp_reset_postdata();
    }
    
    /**
     * Render individual card
     */
    private function render_card($settings) {
        $categories = get_the_category();
        $primary_category = $categories[0] ?? null;
        $category_color = $primary_category ? apply_filters('cm_suite_category_color', '', $primary_category) : '#3498DB';
        $ghost_text = \CM\Suite\PostCards\PostCards::get_ghost_text(get_the_title());
        ?>
        
        <article class="cmpc-card" style="--cmpc-accent: <?php echo esc_attr($category_color); ?>;">
            <a href="<?php the_permalink(); ?>" class="cmpc-card__link" aria-label="<?php printf(esc_attr__('Read more about %s', 'cm-suite-elementor'), get_the_title()); ?>">
                
                <div class="cmpc-card__image">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('cm-card', ['class' => 'cmpc-card__img']); ?>
                    <?php endif; ?>
                    
                    <div class="cmpc-card__ghost" aria-hidden="true">
                        <?php echo esc_html($ghost_text); ?>
                    </div>
                </div>
                
                <div class="cmpc-card__content">
                    
                    <div class="cmpc-card__meta">
                        <?php if ($settings['show_category'] === 'yes' && $primary_category): ?>
                            <span class="cmpc-card__category"><?php echo esc_html($primary_category->name); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($settings['show_date'] === 'yes'): ?>
                            <time class="cmpc-card__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                <?php echo esc_html(get_the_date()); ?>
                            </time>
                        <?php endif; ?>
                        
                        <?php if ($settings['show_author'] === 'yes'): ?>
                            <span class="cmpc-card__author"><?php the_author(); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="cmpc-card__title">
                        <?php echo esc_html(\CM\Suite\PostCards\PostCards::truncate_words(get_the_title(), $settings['title_words_limit'])); ?>
                    </h3>
                    
                    <?php if ($settings['excerpt_chars_limit']): ?>
                        <div class="cmpc-card__excerpt">
                            <?php echo esc_html(\CM\Suite\PostCards\PostCards::truncate_chars(get_the_excerpt(), $settings['excerpt_chars_limit'])); ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            </a>
        </article>
        
        <?php
    }
}