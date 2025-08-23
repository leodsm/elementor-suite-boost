/**
 * CM Player Launcher JavaScript
 */

(function() {
    'use strict';
    
    let launcherConfig = null;
    let isInitialized = false;
    
    /**
     * Player Launcher Class
     */
    class CMPlayerLauncher {
        constructor(config) {
            this.config = {
                triggerSelector: '.cmpc-card__link',
                playerSelector: '.cmsp-overlay',
                extractStoryId: true,
                preventDefault: true,
                debugMode: false,
                fallbackAction: 'console',
                customDataAttribute: '',
                ...config
            };
            
            this.init();
        }
        
        /**
         * Initialize launcher
         */
        init() {
            if (isInitialized) {
                this.log('Launcher already initialized');
                return;
            }
            
            this.bindEvents();
            isInitialized = true;
            
            this.log('Player Launcher initialized', this.config);
        }
        
        /**
         * Bind click events
         */
        bindEvents() {
            // Use event delegation for better performance and dynamic content support
            document.addEventListener('click', (e) => {
                const target = e.target.closest(this.config.triggerSelector);
                
                if (target) {
                    this.handleTriggerClick(e, target);
                }
            });
            
            this.log('Event listeners bound for selector:', this.config.triggerSelector);
        }
        
        /**
         * Handle trigger element click
         */
        handleTriggerClick(event, triggerElement) {
            this.log('Trigger clicked:', triggerElement);
            
            // Prevent default action if configured
            if (this.config.preventDefault) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Check if stories player exists
            const playerElement = document.querySelector(this.config.playerSelector);
            
            if (!playerElement) {
                this.handlePlayerNotFound();
                return;
            }
            
            // Extract story information if needed
            let storyId = null;
            let storyIndex = 0;
            
            if (this.config.extractStoryId) {
                storyId = this.extractStoryId(triggerElement);
                this.log('Extracted story ID:', storyId);
            }
            
            // Open stories player
            this.openStoriesPlayer(storyId, storyIndex);
        }
        
        /**
         * Extract story ID from trigger element
         */
        extractStoryId(element) {
            // Try custom data attribute first
            if (this.config.customDataAttribute) {
                const customValue = element.getAttribute(this.config.customDataAttribute);
                if (customValue) {
                    return this.parseStoryId(customValue);
                }
            }
            
            // Try common data attributes
            const dataAttributes = [
                'data-story-id',
                'data-story',
                'data-post-id',
                'data-id'
            ];
            
            for (const attr of dataAttributes) {
                const value = element.getAttribute(attr);
                if (value) {
                    return this.parseStoryId(value);
                }
            }
            
            // Try to extract from href attribute (for links)
            if (element.tagName === 'A' && element.href) {
                const urlStoryId = this.extractStoryIdFromUrl(element.href);
                if (urlStoryId) {
                    return urlStoryId;
                }
            }
            
            // Try parent elements
            let parent = element.parentElement;
            while (parent && parent !== document.body) {
                for (const attr of dataAttributes) {
                    const value = parent.getAttribute(attr);
                    if (value) {
                        return this.parseStoryId(value);
                    }
                }
                parent = parent.parentElement;
            }
            
            return null;
        }
        
        /**
         * Extract story ID from URL
         */
        extractStoryIdFromUrl(url) {
            try {
                const urlObj = new URL(url);
                
                // Check query parameters
                const storyParam = urlObj.searchParams.get('story');
                if (storyParam) {
                    return this.parseStoryId(storyParam);
                }
                
                // Check URL path segments
                const pathSegments = urlObj.pathname.split('/').filter(segment => segment);
                
                // Look for story in path (e.g., /stories/123 or /story/my-story)
                const storyIndex = pathSegments.findIndex(segment => 
                    segment === 'story' || segment === 'stories'
                );
                
                if (storyIndex >= 0 && pathSegments[storyIndex + 1]) {
                    return this.parseStoryId(pathSegments[storyIndex + 1]);
                }
                
                // Try to extract post ID from WordPress permalink
                const postIdMatch = url.match(/\/\?p=(\d+)/);
                if (postIdMatch) {
                    return parseInt(postIdMatch[1]);
                }
                
            } catch (error) {
                this.log('Error parsing URL:', error);
            }
            
            return null;
        }
        
        /**
         * Parse story ID to ensure it's the correct type
         */
        parseStoryId(value) {
            // Try to parse as integer first
            const intValue = parseInt(value);
            if (!isNaN(intValue) && intValue > 0) {
                return intValue;
            }
            
            // Return as string (for slug-based IDs)
            return value.toString();
        }
        
        /**
         * Open stories player
         */
        openStoriesPlayer(storyId, storyIndex = 0) {
            // Check if global Stories Player API exists
            if (window.CMSuiteStoriesPlayer) {
                if (storyId) {
                    if (typeof storyId === 'number') {
                        window.CMSuiteStoriesPlayer.openStory(storyId);
                    } else {
                        // Assume it's a slug, but we need to find the story index
                        // For now, just open the first story
                        window.CMSuiteStoriesPlayer.open(storyIndex);
                    }
                } else {
                    window.CMSuiteStoriesPlayer.open(storyIndex);
                }
                
                this.log('Stories player opened', { storyId, storyIndex });
            } else {
                this.log('CMSuiteStoriesPlayer API not found');
                this.handlePlayerNotFound();
            }
        }
        
        /**
         * Handle case when player is not found
         */
        handlePlayerNotFound() {
            const message = 'Stories player not found on this page';
            
            switch (this.config.fallbackAction) {
                case 'alert':
                    alert(message);
                    break;
                    
                case 'console':
                    console.warn('CM Player Launcher:', message);
                    break;
                    
                case 'ignore':
                    // Do nothing
                    break;
                    
                default:
                    console.warn('CM Player Launcher:', message);
            }
        }
        
        /**
         * Log debug messages
         */
        log(...args) {
            if (this.config.debugMode) {
                console.log('CM Player Launcher:', ...args);
            }
        }
        
        /**
         * Update configuration
         */
        updateConfig(newConfig) {
            this.config = { ...this.config, ...newConfig };
            this.log('Configuration updated:', this.config);
        }
        
        /**
         * Get current configuration
         */
        getConfig() {
            return { ...this.config };
        }
    }
    
    /**
     * Global API
     */
    window.CMPlayerLauncher = {
        init: function(config) {
            launcherConfig = new CMPlayerLauncher(config);
            return launcherConfig;
        },
        
        getInstance: function() {
            return launcherConfig;
        },
        
        updateConfig: function(config) {
            if (launcherConfig) {
                launcherConfig.updateConfig(config);
            }
        },
        
        trigger: function(storyId, storyIndex = 0) {
            if (launcherConfig) {
                launcherConfig.openStoriesPlayer(storyId, storyIndex);
            }
        }
    };
    
    // Auto-initialize with default config if no explicit initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Only auto-init if not already initialized
        if (!isInitialized && !launcherConfig) {
            // Check if there are trigger elements on the page
            if (document.querySelector('.cmpc-card__link')) {
                window.CMPlayerLauncher.init({});
            }
        }
    });
    
})();