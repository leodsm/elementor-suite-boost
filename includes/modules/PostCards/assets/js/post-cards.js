/**
 * CM Post Cards JavaScript
 */

(function() {
    'use strict';
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPostCards);
    } else {
        initPostCards();
    }
    
    /**
     * Initialize Post Cards
     */
    function initPostCards() {
        const carousels = document.querySelectorAll('.cmpc-swiper');
        
        carousels.forEach(initCarousel);
    }
    
    /**
     * Initialize individual carousel
     */
    function initCarousel(element) {
        // Check if Swiper is available
        if (typeof Swiper === 'undefined') {
            console.warn('CM Post Cards: Swiper not loaded');
            return;
        }
        
        const wrapper = element.closest('.cmpc-wrapper');
        const prevButton = wrapper.querySelector('.cmpc-nav-prev');
        const nextButton = wrapper.querySelector('.cmpc-nav-next');
        const pagination = wrapper.querySelector('.cmpc-pagination');
        
        // Get breakpoints from data attribute
        let breakpoints = {
            768: { slidesPerView: 1.2, spaceBetween: 20 },
            1024: { slidesPerView: 2.2, spaceBetween: 24 },
            1200: { slidesPerView: 3.2, spaceBetween: 24 }
        };
        
        try {
            const dataBreakpoints = element.getAttribute('data-breakpoints');
            if (dataBreakpoints) {
                breakpoints = JSON.parse(dataBreakpoints);
            }
        } catch (e) {
            console.warn('CM Post Cards: Invalid breakpoints data');
        }
        
        // Initialize Swiper
        const swiper = new Swiper(element, {
            slidesPerView: 1.1,
            spaceBetween: 16,
            centeredSlides: false,
            grabCursor: true,
            keyboard: {
                enabled: true,
                onlyInViewport: true,
            },
            a11y: {
                enabled: true,
                prevSlideMessage: 'Previous slide',
                nextSlideMessage: 'Next slide',
            },
            breakpoints: breakpoints,
            navigation: {
                nextEl: nextButton,
                prevEl: prevButton,
            },
            pagination: {
                el: pagination,
                clickable: true,
                bulletActiveClass: 'swiper-pagination-bullet-active',
            },
            on: {
                init: function() {
                    updateNavigationState(this, prevButton, nextButton);
                },
                slideChange: function() {
                    updateNavigationState(this, prevButton, nextButton);
                },
                reachBeginning: function() {
                    updateNavigationState(this, prevButton, nextButton);
                },
                reachEnd: function() {
                    updateNavigationState(this, prevButton, nextButton);
                }
            }
        });
        
        // Handle navigation clicks
        if (prevButton) {
            prevButton.addEventListener('click', (e) => {
                e.preventDefault();
                swiper.slidePrev();
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', (e) => {
                e.preventDefault();
                swiper.slideNext();
            });
        }
        
        // Handle keyboard navigation
        element.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                swiper.slidePrev();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                swiper.slideNext();
            }
        });
        
        // Store reference for potential external access
        element.swiper = swiper;
    }
    
    /**
     * Update navigation button states
     */
    function updateNavigationState(swiper, prevButton, nextButton) {
        if (prevButton) {
            prevButton.disabled = swiper.isBeginning;
            prevButton.setAttribute('aria-disabled', swiper.isBeginning ? 'true' : 'false');
        }
        
        if (nextButton) {
            nextButton.disabled = swiper.isEnd;
            nextButton.setAttribute('aria-disabled', swiper.isEnd ? 'true' : 'false');
        }
    }
    
    /**
     * Handle responsive grid columns
     */
    function handleGridColumns() {
        const grids = document.querySelectorAll('.cmpc-grid');
        
        grids.forEach(grid => {
            const updateColumns = () => {
                const width = window.innerWidth;
                let columns;
                
                if (width <= 768) {
                    columns = grid.getAttribute('data-columns-mobile') || '1';
                } else if (width <= 1024) {
                    columns = grid.getAttribute('data-columns-tablet') || '2';
                } else {
                    columns = grid.getAttribute('data-columns') || '3';
                }
                
                grid.style.setProperty('--columns', columns);
            };
            
            updateColumns();
            window.addEventListener('resize', updateColumns);
        });
    }
    
    // Initialize grid handling
    handleGridColumns();
    
    /**
     * Lazy load images when they come into viewport
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            img.classList.add('cmpc-loaded');
                            observer.unobserve(img);
                        }
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            document.querySelectorAll('.cmpc-card__img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    // Initialize lazy loading
    initLazyLoading();
    
    /**
     * Handle hover effects with touch support
     */
    function initHoverEffects() {
        const cards = document.querySelectorAll('.cmpc-card');
        
        cards.forEach(card => {
            let isTouch = false;
            
            // Detect touch devices
            card.addEventListener('touchstart', () => {
                isTouch = true;
            }, { passive: true });
            
            // Add hover class on mouse enter for non-touch devices
            card.addEventListener('mouseenter', () => {
                if (!isTouch) {
                    card.classList.add('cmpc-hovered');
                }
            });
            
            card.addEventListener('mouseleave', () => {
                card.classList.remove('cmpc-hovered');
            });
            
            // Handle touch devices
            card.addEventListener('touchend', (e) => {
                if (isTouch) {
                    // Toggle hover state on touch
                    card.classList.toggle('cmpc-hovered');
                    
                    // Remove hover state from other cards
                    cards.forEach(otherCard => {
                        if (otherCard !== card) {
                            otherCard.classList.remove('cmpc-hovered');
                        }
                    });
                }
            }, { passive: true });
        });
        
        // Remove all hover states when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.cmpc-card')) {
                cards.forEach(card => {
                    card.classList.remove('cmpc-hovered');
                });
            }
        });
    }
    
    // Initialize hover effects
    initHoverEffects();
    
    // Expose public API
    window.CMPostCards = {
        reinit: initPostCards,
        initCarousel: initCarousel
    };
    
})();