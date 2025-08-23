<?php

namespace CM\Suite\StoriesPlayer\Rest;

/**
 * Stories REST API Controller
 */
class Stories_Rest_Controller extends \WP_REST_Controller {
    
    /**
     * The namespace and version for the REST SERVER
     */
    protected $namespace = 'cm/v1';
    protected $rest_base = 'stories';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        // GET /wp-json/cm/v1/stories
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_stories'],
                'permission_callback' => [$this, 'get_stories_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);
        
        // GET /wp-json/cm/v1/stories/{id}
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_story'],
                'permission_callback' => [$this, 'get_story_permissions_check'],
                'args' => [
                    'id' => [
                        'description' => esc_html__('Unique identifier for the story.', 'cm-suite-elementor'),
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        },
                    ],
                ],
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);
    }
    
    /**
     * Get stories collection
     */
    public function get_stories($request) {
        $args = [
            'post_type' => 'cm_story',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?? 20,
            'paged' => $request->get_param('page') ?? 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        // Handle featured parameter
        if ($request->get_param('featured')) {
            $args['meta_query'][] = [
                'key' => '_cm_story_featured',
                'value' => '1',
                'compare' => '='
            ];
        }
        
        // Handle search parameter
        if ($request->get_param('search')) {
            $args['s'] = sanitize_text_field($request->get_param('search'));
        }
        
        $query = new \WP_Query($args);
        $stories = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $stories[] = $this->prepare_story_for_response(get_post(), $request);
            }
            wp_reset_postdata();
        }
        
        $response = rest_ensure_response($stories);
        
        // Add pagination headers
        $max_pages = $query->max_num_pages;
        $total = $query->found_posts;
        
        $response->header('X-WP-Total', $total);
        $response->header('X-WP-TotalPages', $max_pages);
        
        return $response;
    }
    
    /**
     * Get single story with pages
     */
    public function get_story($request) {
        $story_id = (int) $request['id'];
        $story = get_post($story_id);
        
        if (empty($story) || $story->post_type !== 'cm_story') {
            return new \WP_Error(
                'rest_story_invalid_id',
                esc_html__('Invalid story ID.', 'cm-suite-elementor'),
                ['status' => 404]
            );
        }
        
        if ($story->post_status !== 'publish') {
            return new \WP_Error(
                'rest_story_not_published',
                esc_html__('Story is not published.', 'cm-suite-elementor'),
                ['status' => 404]
            );
        }
        
        $data = $this->prepare_story_for_response($story, $request, true);
        
        return rest_ensure_response($data);
    }
    
    /**
     * Prepare story for response
     */
    protected function prepare_story_for_response($story, $request, $include_pages = false) {
        $thumbnail_id = get_post_thumbnail_id($story->ID);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'cm-story-cover') : '';
        
        $data = [
            'id' => $story->ID,
            'title' => get_the_title($story->ID),
            'slug' => $story->post_name,
            'thumbnail' => $thumbnail_url,
            'excerpt' => get_the_excerpt($story->ID),
            'permalink' => get_permalink($story->ID),
            'date' => get_the_date('c', $story->ID),
            'modified' => get_the_modified_date('c', $story->ID),
            'featured' => get_post_meta($story->ID, '_cm_story_featured', true) === '1',
            'duration' => (int) get_post_meta($story->ID, '_cm_story_duration', true) ?: 5,
        ];
        
        if ($include_pages) {
            $pages_json = get_post_meta($story->ID, '_cm_story_pages', true);
            $pages = $pages_json ? json_decode($pages_json, true) : [];
            $data['pages'] = is_array($pages) ? $pages : [];
        }
        
        return $data;
    }
    
    /**
     * Check permissions for getting stories
     */
    public function get_stories_permissions_check($request) {
        // Stories are public, so always allow
        return true;
    }
    
    /**
     * Check permissions for getting a single story
     */
    public function get_story_permissions_check($request) {
        // Stories are public, so always allow
        return true;
    }
    
    /**
     * Get collection parameters
     */
    public function get_collection_params() {
        $params = parent::get_collection_params();
        
        $params['featured'] = [
            'description' => esc_html__('Limit result set to featured stories.', 'cm-suite-elementor'),
            'type' => 'boolean',
            'default' => false,
        ];
        
        $params['search'] = [
            'description' => esc_html__('Limit results to those matching a string.', 'cm-suite-elementor'),
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];
        
        $params['per_page']['default'] = 20;
        $params['per_page']['maximum'] = 100;
        
        return $params;
    }
    
    /**
     * Get public schema
     */
    public function get_public_item_schema() {
        if ($this->schema) {
            return $this->add_additional_fields_schema($this->schema);
        }
        
        $schema = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'story',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'description' => esc_html__('Unique identifier for the story.', 'cm-suite-elementor'),
                    'type' => 'integer',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'title' => [
                    'description' => esc_html__('The title for the story.', 'cm-suite-elementor'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                ],
                'slug' => [
                    'description' => esc_html__('An alphanumeric identifier for the story unique to its type.', 'cm-suite-elementor'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                ],
                'thumbnail' => [
                    'description' => esc_html__('The story thumbnail URL.', 'cm-suite-elementor'),
                    'type' => 'string',
                    'format' => 'uri',
                    'context' => ['view', 'edit'],
                ],
                'excerpt' => [
                    'description' => esc_html__('The story excerpt.', 'cm-suite-elementor'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                ],
                'permalink' => [
                    'description' => esc_html__('The story permalink.', 'cm-suite-elementor'),
                    'type' => 'string',
                    'format' => 'uri',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'date' => [
                    'description' => esc_html__('The date the story was published, in the site\'s timezone.', 'cm-suite-elementor'),
                    'type' => 'string',
                    'format' => 'date-time',
                    'context' => ['view', 'edit'],
                ],
                'featured' => [
                    'description' => esc_html__('Whether the story is featured.', 'cm-suite-elementor'),
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
                ],
                'duration' => [
                    'description' => esc_html__('Duration per page in seconds.', 'cm-suite-elementor'),
                    'type' => 'integer',
                    'context' => ['view', 'edit'],
                ],
                'pages' => [
                    'description' => esc_html__('Story pages array.', 'cm-suite-elementor'),
                    'type' => 'array',
                    'context' => ['view', 'edit'],
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => ['image', 'text', 'video'],
                            ],
                            'url' => [
                                'type' => 'string',
                                'format' => 'uri',
                            ],
                            'title' => [
                                'type' => 'string',
                            ],
                            'text' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        $this->schema = $schema;
        
        return $this->add_additional_fields_schema($this->schema);
    }
}