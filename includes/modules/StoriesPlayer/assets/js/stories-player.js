/**
 * CM Stories Player JavaScript
 */

(function() {
    'use strict';
    
    // Player instance
    let playerInstance = null;
    
    /**
     * Stories Player Class
     */
    class CMStoriesPlayer {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            this.isOpen = false;
            this.currentStoryIndex = 0;
            this.currentPageIndex = 0;
            this.stories = [];
            this.isPlaying = false;
            this.pageTimer = null;
            
            // Settings
            this.settings = {
                deepLink: this.container.dataset.deepLink === 'yes',
                autoClose: parseInt(this.container.dataset.autoClose) || 0,
                showProgress: this.container.dataset.showProgress === 'yes',
                showControls: this.container.dataset.showControls === 'yes'
            };
            
            // Elements
            this.dialog = this.container.querySelector('.cmsp-dialog');
            this.storiesContainer = this.container.querySelector('.cmsp-stories');
            this.progressContainer = this.container.querySelector('.cmsp-progress-container');
            this.storyTitle = this.container.querySelector('.cmsp-story-title');
            this.storyDate = this.container.querySelector('.cmsp-story-date');
            this.storyAvatar = this.container.querySelector('.cmsp-story-avatar');
            this.loading = this.container.querySelector('.cmsp-loading');
            this.error = this.container.querySelector('.cmsp-error');
            this.articleModal = document.getElementById(containerId + '-article-modal');
            this.articleTitle = this.articleModal?.querySelector('.cmsp-modal-title');
            this.articleContent = this.articleModal?.querySelector('.cmsp-modal-content');
            
            this.init();
        }
        
        /**
         * Initialize player
         */
        init() {
            this.bindEvents();
            this.loadStories();
            
            // Auto-open story from URL if deep linking is enabled
            if (this.settings.deepLink) {
                const urlParams = new URLSearchParams(window.location.search);
                const storyParam = urlParams.get('story');
                if (storyParam) {
                    setTimeout(() => this.openStoryBySlug(storyParam), 100);
                }
            }
        }
        
        /**
         * Bind events
         */
        bindEvents() {
            // Close button
            const closeBtn = this.container.querySelector('.cmsp-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }
            
            // Play/pause button
            const playPauseBtn = this.container.querySelector('.cmsp-play-pause');
            if (playPauseBtn) {
                playPauseBtn.addEventListener('click', () => this.togglePlayPause());
            }

            // Open article button
            const openArticleBtn = this.container.querySelector('.cmsp-open-article');
            if (openArticleBtn) {
                openArticleBtn.addEventListener('click', () => this.openArticle());
            }

            if (this.articleModal) {
                const modalClose = this.articleModal.querySelector('.cmsp-modal-close');
                if (modalClose) {
                    modalClose.addEventListener('click', () => this.closeArticle());
                }
                this.articleModal.addEventListener('click', (e) => {
                    if (e.target === this.articleModal) {
                        this.closeArticle();
                    }
                });
            }
            
            // Navigation buttons
            const prevBtn = this.container.querySelector('.cmsp-nav-prev');
            const nextBtn = this.container.querySelector('.cmsp-nav-next');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => this.previousStory());
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => this.nextStory());
            }
            
            // Retry button
            const retryBtn = this.container.querySelector('.cmsp-retry-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => this.loadStories());
            }
            
            // Keyboard events
            document.addEventListener('keydown', (e) => {
                if (!this.isOpen) return;
                
                switch (e.key) {
                    case 'Escape':
                        e.preventDefault();
                        if (this.articleModal && this.articleModal.getAttribute('aria-hidden') === 'false') {
                            this.closeArticle();
                        } else {
                            this.close();
                        }
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.previousPage();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.nextPage();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        this.previousStory();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        this.nextStory();
                        break;
                    case ' ':
                        e.preventDefault();
                        this.togglePlayPause();
                        break;
                }
            });
            
            // Touch events for pages navigation
            this.bindTouchEvents();
            
            // Click outside to close
            this.container.addEventListener('click', (e) => {
                if (e.target === this.container) {
                    this.close();
                }
            });
        }
        
        /**
         * Bind touch events for page navigation
         */
        bindTouchEvents() {
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;
            let touchEndY = 0;
            
            this.dialog.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });
            
            this.dialog.addEventListener('touchend', (e) => {
                if (!this.isOpen) return;
                
                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                
                const deltaX = touchEndX - touchStartX;
                const deltaY = touchEndY - touchStartY;
                const threshold = 50;
                
                // Horizontal swipes for pages
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > threshold) {
                    if (deltaX > 0) {
                        this.previousPage();
                    } else {
                        this.nextPage();
                    }
                }
                // Vertical swipes for stories
                else if (Math.abs(deltaY) > threshold) {
                    if (deltaY > 0) {
                        this.previousStory();
                    } else {
                        this.nextStory();
                    }
                }
                // Tap to toggle play/pause
                else if (Math.abs(deltaX) < 10 && Math.abs(deltaY) < 10) {
                    this.togglePlayPause();
                }
            }, { passive: true });
        }
        
        /**
         * Load stories from REST API
         */
        async loadStories() {
            this.showLoading();
            
            try {
                const response = await fetch(`${cmStoriesAjax.restUrl}stories`, {
                    headers: {
                        'X-WP-Nonce': cmStoriesAjax.restNonce
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load stories');
                }
                
                this.stories = await response.json();
                this.hideLoading();
                
                if (this.stories.length === 0) {
                    this.showError('No stories available.');
                    return;
                }
                
                this.initSwiper();
                
            } catch (error) {
                console.error('Stories Player: Error loading stories', error);
                this.hideLoading();
                this.showError('Error loading stories. Please try again.');
            }
        }
        
        /**
         * Initialize Swiper for stories
         */
        initSwiper() {
            if (typeof Swiper === 'undefined') {
                console.error('Stories Player: Swiper not loaded');
                return;
            }
            
            // Create slides for each story
            const wrapper = this.storiesContainer.querySelector('.swiper-wrapper');
            wrapper.innerHTML = '';
            
            this.stories.forEach((story, index) => {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide cmsp-story-slide';
                slide.innerHTML = `
                    <div class="cmsp-story-pages swiper swiper-horizontal" data-story-id="${story.id}">
                        <div class="swiper-wrapper"></div>
                    </div>
                `;
                wrapper.appendChild(slide);
            });
            
            // Initialize main (vertical) swiper
            this.mainSwiper = new Swiper(this.storiesContainer, {
                direction: 'vertical',
                slidesPerView: 1,
                spaceBetween: 0,
                speed: 300,
                allowTouchMove: true,
                keyboard: {
                    enabled: true,
                    onlyInViewport: true,
                },
                on: {
                    slideChange: () => {
                        this.onStoryChange();
                    }
                }
            });
            
            // Initialize page swipers for each story
            this.pagesSwipers = [];
            this.stories.forEach((story, storyIndex) => {
                const storyElement = wrapper.children[storyIndex].querySelector('.cmsp-story-pages');
                
                const pageSwiper = new Swiper(storyElement, {
                    direction: 'horizontal',
                    slidesPerView: 1,
                    spaceBetween: 0,
                    speed: 300,
                    allowTouchMove: true,
                    on: {
                        slideChange: () => {
                            if (storyIndex === this.currentStoryIndex) {
                                this.onPageChange();
                            }
                        }
                    }
                });
                
                this.pagesSwipers.push(pageSwiper);
            });
        }
        
        /**
         * Load story pages
         */
        async loadStoryPages(storyIndex) {
            const story = this.stories[storyIndex];
            if (!story || story.pagesLoaded) return;
            
            try {
                const response = await fetch(`${cmStoriesAjax.restUrl}stories/${story.id}`, {
                    headers: {
                        'X-WP-Nonce': cmStoriesAjax.restNonce
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to load story pages');
                }
                
                const storyData = await response.json();
                story.pages = storyData.pages || [];
                story.pagesLoaded = true;
                story.duration = storyData.duration || 5;
                
                this.renderStoryPages(storyIndex);
                
            } catch (error) {
                console.error('Stories Player: Error loading story pages', error);
            }
        }
        
        /**
         * Render story pages in swiper
         */
        renderStoryPages(storyIndex) {
            const story = this.stories[storyIndex];
            const pageSwiper = this.pagesSwipers[storyIndex];
            const wrapper = pageSwiper.wrapperEl;
            
            wrapper.innerHTML = '';
            
            if (!story.pages || story.pages.length === 0) {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide cmsp-page';
                slide.innerHTML = `
                    <div class="cmsp-page-text">
                        <h2>${story.title}</h2>
                        <p>No content available</p>
                    </div>
                `;
                wrapper.appendChild(slide);
                pageSwiper.update();
                return;
            }
            
            story.pages.forEach(page => {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide cmsp-page';
                slide.innerHTML = this.renderPage(page);
                wrapper.appendChild(slide);
            });
            
            pageSwiper.update();
            this.updateProgressBar();
        }
        
        /**
         * Render individual page content
         */
        renderPage(page) {
            switch (page.type) {
                case 'image':
                    return `<img src="${page.url}" alt="" class="cmsp-page-image" loading="lazy">`;
                    
                case 'text':
                    return `
                        <div class="cmsp-page-text">
                            <h2>${page.title || ''}</h2>
                            <p>${page.text || ''}</p>
                        </div>
                    `;
                    
                case 'video':
                    return `
                        <video class="cmsp-page-video" controls playsinline>
                            <source src="${page.url}" type="video/mp4">
                            Your browser does not support video playback.
                        </video>
                    `;
                    
                default:
                    return `
                        <div class="cmsp-page-text">
                            <h2>Unsupported Content</h2>
                            <p>This content type is not supported.</p>
                        </div>
                    `;
            }
        }
        
        /**
         * Open player
         */
        async open(storyIndex = 0) {
            if (this.stories.length === 0) {
                await this.loadStories();
                if (this.stories.length === 0) return;
            }
            
            this.isOpen = true;
            this.container.style.display = 'flex';
            
            // Add class after display to trigger transition
            requestAnimationFrame(() => {
                this.container.classList.add('cmsp-open');
                document.body.style.overflow = 'hidden';
            });
            
            // Load and show story
            this.currentStoryIndex = Math.max(0, Math.min(storyIndex, this.stories.length - 1));
            this.mainSwiper?.slideTo(this.currentStoryIndex, 0);
            
            await this.loadStoryPages(this.currentStoryIndex);
            this.updateStoryInfo();
            this.updateProgressBar();
            this.startAutoPlay();
            
            // Emit event
            this.emit('opened', { storyIndex: this.currentStoryIndex });
            
            // Update URL if deep linking is enabled
            if (this.settings.deepLink) {
                this.updateURL();
            }
        }
        
        /**
         * Close player
         */
        close() {
            if (!this.isOpen) return;

            this.isOpen = false;
            this.stopAutoPlay();
            
            this.container.classList.remove('cmsp-open');
            document.body.style.overflow = '';
            
            setTimeout(() => {
                this.container.style.display = 'none';
            }, 300);
            
            // Emit event
            this.emit('closed');
            
            // Clear URL parameter if deep linking is enabled
            if (this.settings.deepLink) {
                this.clearURL();
            }
        }

        /**
         * Open article modal
         */
        async openArticle() {
            if (!this.articleModal) return;
            const story = this.stories[this.currentStoryIndex];
            if (!story) return;

            try {
                const wpRest = cmStoriesAjax.restUrl.replace('cm/v1/', 'wp/v2/');
                const response = await fetch(`${wpRest}cm_story/${story.id}`);
                if (!response.ok) throw new Error('Failed to load article');
                const data = await response.json();
                if (this.articleTitle) {
                    this.articleTitle.textContent = data.title?.rendered || story.title || '';
                }
                if (this.articleContent) {
                    this.articleContent.innerHTML = data.content?.rendered || '';
                }
                this.articleModal.style.display = 'flex';
                this.articleModal.setAttribute('aria-hidden', 'false');
            } catch (err) {
                console.error('Stories Player: Error loading article', err);
            }
        }

        /**
         * Close article modal
         */
        closeArticle() {
            if (!this.articleModal) return;
            this.articleModal.style.display = 'none';
            this.articleModal.setAttribute('aria-hidden', 'true');
        }
        
        /**
         * Open story by slug
         */
        async openStoryBySlug(slug) {
            const storyIndex = this.stories.findIndex(story => story.slug === slug);
            if (storyIndex >= 0) {
                await this.open(storyIndex);
            }
        }
        
        /**
         * Open specific story by ID
         */
        async openStory(storyId) {
            const storyIndex = this.stories.findIndex(story => story.id === storyId);
            if (storyIndex >= 0) {
                await this.open(storyIndex);
            }
        }
        
        /**
         * Story change handler
         */
        async onStoryChange() {
            this.currentStoryIndex = this.mainSwiper.activeIndex;
            this.currentPageIndex = 0;
            
            // Load pages if not loaded
            await this.loadStoryPages(this.currentStoryIndex);
            
            // Reset page swiper to first slide
            const pageSwiper = this.pagesSwipers[this.currentStoryIndex];
            if (pageSwiper) {
                pageSwiper.slideTo(0, 0);
            }
            
            this.updateStoryInfo();
            this.updateProgressBar();
            this.restartAutoPlay();
            
            // Update URL
            if (this.settings.deepLink) {
                this.updateURL();
            }
        }
        
        /**
         * Page change handler
         */
        onPageChange() {
            const pageSwiper = this.pagesSwipers[this.currentStoryIndex];
            if (pageSwiper) {
                this.currentPageIndex = pageSwiper.activeIndex;
                this.updateProgressBar();
                this.restartAutoPlay();
            }
        }
        
        /**
         * Navigate to previous story
         */
        previousStory() {
            if (this.currentStoryIndex > 0) {
                this.mainSwiper?.slideTo(this.currentStoryIndex - 1);
            }
        }
        
        /**
         * Navigate to next story
         */
        nextStory() {
            if (this.currentStoryIndex < this.stories.length - 1) {
                this.mainSwiper?.slideTo(this.currentStoryIndex + 1);
            } else if (this.settings.autoClose > 0) {
                // Auto close when reaching the end
                setTimeout(() => this.close(), this.settings.autoClose * 1000);
            }
        }
        
        /**
         * Navigate to previous page
         */
        previousPage() {
            const pageSwiper = this.pagesSwipers[this.currentStoryIndex];
            if (pageSwiper && this.currentPageIndex > 0) {
                pageSwiper.slideTo(this.currentPageIndex - 1);
            } else {
                this.previousStory();
            }
        }
        
        /**
         * Navigate to next page
         */
        nextPage() {
            const story = this.stories[this.currentStoryIndex];
            const pageSwiper = this.pagesSwipers[this.currentStoryIndex];
            
            if (pageSwiper && story.pages && this.currentPageIndex < story.pages.length - 1) {
                pageSwiper.slideTo(this.currentPageIndex + 1);
            } else {
                this.nextStory();
            }
        }
        
        /**
         * Toggle play/pause
         */
        togglePlayPause() {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        }
        
        /**
         * Start playing
         */
        play() {
            this.isPlaying = true;
            this.updatePlayPauseButton();
            this.startPageTimer();
        }
        
        /**
         * Pause playing
         */
        pause() {
            this.isPlaying = false;
            this.updatePlayPauseButton();
            this.stopPageTimer();
        }
        
        /**
         * Start auto play
         */
        startAutoPlay() {
            if (!this.isPlaying) {
                this.play();
            }
        }
        
        /**
         * Stop auto play
         */
        stopAutoPlay() {
            this.pause();
        }
        
        /**
         * Restart auto play
         */
        restartAutoPlay() {
            this.stopPageTimer();
            if (this.isPlaying) {
                this.startPageTimer();
            }
        }
        
        /**
         * Start page timer
         */
        startPageTimer() {
            this.stopPageTimer();
            
            const story = this.stories[this.currentStoryIndex];
            const duration = (story?.duration || 5) * 1000;
            
            this.pageTimer = setTimeout(() => {
                this.nextPage();
            }, duration);
            
            // Animate progress bar
            this.animateProgressBar(duration);
        }
        
        /**
         * Stop page timer
         */
        stopPageTimer() {
            if (this.pageTimer) {
                clearTimeout(this.pageTimer);
                this.pageTimer = null;
            }
        }
        
        /**
         * Update story info
         */
        updateStoryInfo() {
            const story = this.stories[this.currentStoryIndex];
            if (!story) return;
            
            if (this.storyTitle) {
                this.storyTitle.textContent = story.title;
            }
            
            if (this.storyDate) {
                const date = new Date(story.date);
                this.storyDate.textContent = date.toLocaleDateString();
            }
            
            if (this.storyAvatar && story.thumbnail) {
                this.storyAvatar.style.backgroundImage = `url(${story.thumbnail})`;
            }
        }
        
        /**
         * Update progress bar
         */
        updateProgressBar() {
            if (!this.settings.showProgress || !this.progressContainer) return;
            
            const story = this.stories[this.currentStoryIndex];
            if (!story?.pages) return;
            
            const segmentsContainer = this.progressContainer.querySelector('.cmsp-progress-segments');
            segmentsContainer.innerHTML = '';
            
            story.pages.forEach((page, index) => {
                const segment = document.createElement('div');
                segment.className = 'cmsp-progress-segment';
                
                if (index < this.currentPageIndex) {
                    segment.classList.add('completed');
                } else if (index === this.currentPageIndex) {
                    segment.classList.add('active');
                    segment.innerHTML = '<div class="cmsp-progress-bar"></div>';
                }
                
                segmentsContainer.appendChild(segment);
            });
        }
        
        /**
         * Animate progress bar
         */
        animateProgressBar(duration) {
            const activeSegment = this.progressContainer?.querySelector('.cmsp-progress-segment.active');
            const progressBar = activeSegment?.querySelector('.cmsp-progress-bar');
            
            if (progressBar) {
                progressBar.style.transition = 'none';
                progressBar.style.width = '0%';
                
                requestAnimationFrame(() => {
                    progressBar.style.transition = `width ${duration}ms linear`;
                    progressBar.style.width = '100%';
                });
            }
        }
        
        /**
         * Update play/pause button
         */
        updatePlayPauseButton() {
            const playPauseBtn = this.container.querySelector('.cmsp-play-pause');
            if (playPauseBtn) {
                if (this.isPlaying) {
                    playPauseBtn.classList.remove('paused');
                } else {
                    playPauseBtn.classList.add('paused');
                }
            }
        }
        
        /**
         * Update URL for deep linking
         */
        updateURL() {
            const story = this.stories[this.currentStoryIndex];
            if (story?.slug) {
                const url = new URL(window.location);
                url.searchParams.set('story', story.slug);
                window.history.replaceState({}, '', url);
            }
        }
        
        /**
         * Clear URL parameter
         */
        clearURL() {
            const url = new URL(window.location);
            url.searchParams.delete('story');
            window.history.replaceState({}, '', url);
        }
        
        /**
         * Show loading state
         */
        showLoading() {
            if (this.loading) {
                this.loading.style.display = 'flex';
            }
            if (this.error) {
                this.error.style.display = 'none';
            }
        }
        
        /**
         * Hide loading state
         */
        hideLoading() {
            if (this.loading) {
                this.loading.style.display = 'none';
            }
        }
        
        /**
         * Show error state
         */
        showError(message) {
            if (this.error) {
                const errorMessage = this.error.querySelector('p');
                if (errorMessage) {
                    errorMessage.textContent = message;
                }
                this.error.style.display = 'flex';
            }
        }
        
        /**
         * Emit custom events
         */
        emit(eventName, detail = {}) {
            const event = new CustomEvent(`cm-suite:story-${eventName}`, {
                detail: { ...detail, player: this }
            });
            document.dispatchEvent(event);
        }
    }
    
    /**
     * Global API
     */
    window.CMSuiteStoriesPlayer = {
        init: function(containerId) {
            if (playerInstance) {
                playerInstance = null;
            }
            playerInstance = new CMStoriesPlayer(containerId);
            return playerInstance;
        },
        
        open: function(storyIndex = 0) {
            if (playerInstance) {
                playerInstance.open(storyIndex);
            }
        },
        
        close: function() {
            if (playerInstance) {
                playerInstance.close();
            }
        },
        
        openStory: function(storyId) {
            if (playerInstance) {
                playerInstance.openStory(storyId);
            }
        },
        
        getInstance: function() {
            return playerInstance;
        }
    };
    
})();