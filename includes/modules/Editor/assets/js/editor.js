/**
 * CM Suite Editor JavaScript
 */

(function($) {
    'use strict';
    
    // Editor instance
    let editorInstance = null;
    
    /**
     * Story Editor Class
     */
    class CMStoryEditor {
        constructor() {
            this.pages = [];
            this.currentEditingIndex = -1;
            this.isInitialized = false;
            
            this.init();
        }
        
        /**
         * Initialize editor
         */
        init() {
            if (this.isInitialized) return;
            
            this.bindEvents();
            this.loadExistingPages();
            this.initSortable();
            this.isInitialized = true;
            
            console.log('CM Story Editor initialized');
        }
        
        /**
         * Bind events
         */
        bindEvents() {
            // Tab switching
            $(document).on('click', '.cm-tab-btn', (e) => {
                this.switchTab($(e.currentTarget));
            });
            
            // Add page buttons
            $(document).on('click', '.cm-add-page', (e) => {
                const type = $(e.currentTarget).data('type');
                this.addPage(type);
            });
            
            // Page actions
            $(document).on('click', '.cm-page-edit', (e) => {
                const index = $(e.currentTarget).closest('.cm-page-item').index();
                this.editPage(index);
            });
            
            $(document).on('click', '.cm-page-delete', (e) => {
                const index = $(e.currentTarget).closest('.cm-page-item').index();
                this.deletePage(index);
            });
            
            // Modal events
            $(document).on('click', '.cm-modal-close, .cm-page-modal', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });
            
            $(document).on('click', '.cm-modal-save', () => {
                this.savePageEdit();
            });
            
            $(document).on('click', '.cm-modal-cancel', () => {
                this.closeModal();
            });
            
            // Media buttons
            $(document).on('click', '.cm-select-media', (e) => {
                this.openMediaLibrary($(e.currentTarget));
            });
            
            // Sync JSON when visual editor changes
            $(document).on('input change', '#cm-story-pages-json', () => {
                this.syncFromJSON();
            });
            
            // ESC key to close modal
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                }
            });
        }
        
        /**
         * Switch between tabs
         */
        switchTab($tabBtn) {
            const tab = $tabBtn.data('tab');
            
            // Update tab buttons
            $('.cm-tab-btn').removeClass('active');
            $tabBtn.addClass('active');
            
            // Update tab content
            $('.cm-tab-content').hide();
            $(`#cm-${tab}-tab`).show();
            
            // Sync data when switching to JSON tab
            if (tab === 'json') {
                this.syncToJSON();
            }
        }
        
        /**
         * Load existing pages from JSON
         */
        loadExistingPages() {
            const jsonData = $('#cm-story-pages-json').val();
            
            try {
                this.pages = jsonData ? JSON.parse(jsonData) : [];
            } catch (e) {
                console.warn('Invalid JSON data, starting with empty pages');
                this.pages = [];
            }
            
            this.renderPages();
        }
        
        /**
         * Render pages in visual editor
         */
        renderPages() {
            const $container = $('#cm-visual-pages');
            const $emptyState = $('.cm-empty-state');
            
            $container.empty();
            
            if (this.pages.length === 0) {
                $emptyState.show();
                return;
            }
            
            $emptyState.hide();
            
            this.pages.forEach((page, index) => {
                const $pageItem = this.createPageElement(page, index);
                $container.append($pageItem);
            });
        }
        
        /**
         * Create page element
         */
        createPageElement(page, index) {
            const typeLabel = this.getTypeLabel(page.type);
            const content = this.getPageContentPreview(page);
            
            const $pageItem = $(`
                <div class="cm-page-item" data-type="${page.type}">
                    <div class="cm-page-header">
                        <span class="cm-page-type">${typeLabel}</span>
                        <div class="cm-page-actions">
                            <button type="button" class="cm-page-edit" title="${cmEditorAjax.strings.editPage}">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="cm-page-delete" title="${cmEditorAjax.strings.confirmDelete}">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <span class="cm-page-handle" title="Drag to reorder">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                        </div>
                    </div>
                    <div class="cm-page-content">${content}</div>
                </div>
            `);
            
            // Add animation for new items
            if (index === this.pages.length - 1) {
                $pageItem.addClass('newly-added');
                setTimeout(() => $pageItem.removeClass('newly-added'), 300);
            }
            
            return $pageItem;
        }
        
        /**
         * Get type label
         */
        getTypeLabel(type) {
            const labels = {
                image: 'Image',
                text: 'Text',
                video: 'Video'
            };
            return labels[type] || type;
        }
        
        /**
         * Get page content preview
         */
        getPageContentPreview(page) {
            switch (page.type) {
                case 'image':
                    return `
                        <img src="${page.url}" alt="" loading="lazy">
                        <div>
                            <a href="${page.url}" target="_blank" class="page-url">${this.truncateUrl(page.url)}</a>
                        </div>
                    `;
                    
                case 'text':
                    return `
                        <div class="page-title">${page.title || 'Untitled'}</div>
                        <div class="page-text">${this.truncateText(page.text || '', 100)}</div>
                    `;
                    
                case 'video':
                    return `
                        <div class="page-title">Video</div>
                        <a href="${page.url}" target="_blank" class="page-url">${this.truncateUrl(page.url)}</a>
                    `;
                    
                default:
                    return '<div>Unknown content type</div>';
            }
        }
        
        /**
         * Add new page
         */
        addPage(type) {
            const newPage = this.createNewPage(type);
            this.pages.push(newPage);
            this.renderPages();
            this.syncToJSON();
            
            // Auto-open edit modal for new page
            setTimeout(() => {
                this.editPage(this.pages.length - 1);
            }, 100);
        }
        
        /**
         * Create new page object
         */
        createNewPage(type) {
            switch (type) {
                case 'image':
                    return { type: 'image', url: '', full_post_id: '', full_post_url: '' };

                case 'text':
                    return { type: 'text', title: '', text: '', full_post_id: '', full_post_url: '' };

                case 'video':
                    return { type: 'video', url: '', full_post_id: '', full_post_url: '' };

                default:
                    return { type: 'text', title: '', text: '', full_post_id: '', full_post_url: '' };
            }
        }
        
        /**
         * Edit page
         */
        editPage(index) {
            if (index < 0 || index >= this.pages.length) return;
            
            this.currentEditingIndex = index;
            const page = this.pages[index];
            
            this.openEditModal(page);
        }
        
        /**
         * Delete page
         */
        deletePage(index) {
            if (index < 0 || index >= this.pages.length) return;
            
            if (confirm(cmEditorAjax.strings.confirmDelete)) {
                this.pages.splice(index, 1);
                this.renderPages();
                this.syncToJSON();
            }
        }
        
        /**
         * Open edit modal
         */
        openEditModal(page) {
            const modalHTML = this.getEditModalHTML(page);
            
            // Remove existing modal
            $('.cm-page-modal').remove();
            
            // Add new modal
            $('body').append(modalHTML);
            
            // Show modal with animation
            setTimeout(() => {
                $('.cm-page-modal').addClass('open');
            }, 10);
            
            // Focus first input
            $('.cm-page-modal input:first').focus();
        }
        
        /**
         * Get edit modal HTML
         */
        getEditModalHTML(page) {
            const typeLabel = this.getTypeLabel(page.type);
            let fieldsHTML = '';
            
            switch (page.type) {
                case 'image':
                    fieldsHTML = `
                        <div class="cm-field">
                            <label>${cmEditorAjax.strings.imageUrl}</label>
                            <div class="cm-media-field">
                                <input type="url" name="url" value="${page.url || ''}" placeholder="https://example.com/image.jpg">
                                <button type="button" class="cm-media-btn cm-select-media" data-type="image">
                                    ${cmEditorAjax.strings.selectImage}
                                </button>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'text':
                    fieldsHTML = `
                        <div class="cm-field">
                            <label>${cmEditorAjax.strings.pageTitle}</label>
                            <input type="text" name="title" value="${page.title || ''}" placeholder="Enter page title">
                        </div>
                        <div class="cm-field">
                            <label>${cmEditorAjax.strings.pageText}</label>
                            <textarea name="text" placeholder="Enter page content">${page.text || ''}</textarea>
                        </div>
                    `;
                    break;
                    
                case 'video':
                    fieldsHTML = `
                        <div class="cm-field">
                            <label>${cmEditorAjax.strings.videoUrl}</label>
                            <div class="cm-media-field">
                                <input type="url" name="url" value="${page.url || ''}" placeholder="https://example.com/video.mp4">
                                <button type="button" class="cm-media-btn cm-select-media" data-type="video">
                                    ${cmEditorAjax.strings.selectVideo}
                                </button>
                            </div>
                        </div>
                    `;
                    break;
            }

            // Full post fields common to all types
            fieldsHTML += `
                <div class="cm-field">
                    <label>Full Post ID</label>
                    <input type="number" name="full_post_id" value="${page.full_post_id || ''}" placeholder="123">
                </div>
                <div class="cm-field">
                    <label>Full Post URL</label>
                    <input type="url" name="full_post_url" value="${page.full_post_url || ''}" placeholder="https://example.com/full-post">
                </div>
            `;

            return `
                <div class="cm-page-modal">
                    <div class="cm-modal-content">
                        <div class="cm-modal-header">
                            <h3 class="cm-modal-title">${cmEditorAjax.strings.editPage} - ${typeLabel}</h3>
                            <button type="button" class="cm-modal-close">&times;</button>
                        </div>
                        <div class="cm-modal-body">
                            <form class="cm-page-form">
                                ${fieldsHTML}
                            </form>
                        </div>
                        <div class="cm-modal-footer">
                            <button type="button" class="cm-btn cm-modal-cancel">${cmEditorAjax.strings.cancel}</button>
                            <button type="button" class="cm-btn primary cm-modal-save">${cmEditorAjax.strings.save}</button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        /**
         * Save page edit
         */
        savePageEdit() {
            if (this.currentEditingIndex < 0) return;
            
            const $form = $('.cm-page-form');
            const page = this.pages[this.currentEditingIndex];
            
            // Get form data
            $form.find('input, textarea').each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                
                if (name) {
                    page[name] = value;
                }
            });
            
            // Update display
            this.renderPages();
            this.syncToJSON();
            this.closeModal();
        }
        
        /**
         * Close modal
         */
        closeModal() {
            $('.cm-page-modal').removeClass('open');
            setTimeout(() => {
                $('.cm-page-modal').remove();
            }, 300);
            this.currentEditingIndex = -1;
        }
        
        /**
         * Open media library
         */
        openMediaLibrary($button) {
            const mediaType = $button.data('type');
            const $input = $button.siblings('input');
            
            // Create media frame
            const frame = wp.media({
                title: mediaType === 'image' ? cmEditorAjax.strings.uploadImage : cmEditorAjax.strings.uploadVideo,
                button: { text: cmEditorAjax.strings.selectImage },
                multiple: false,
                library: {
                    type: mediaType === 'image' ? 'image' : 'video'
                }
            });
            
            // Handle selection
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.url);
            });
            
            // Open frame
            frame.open();
        }
        
        /**
         * Initialize sortable
         */
        initSortable() {
            $('#cm-visual-pages').sortable({
                handle: '.cm-page-handle',
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                start: (e, ui) => {
                    ui.placeholder.height(ui.item.height());
                },
                update: (e, ui) => {
                    this.updatePageOrder();
                }
            });
        }
        
        /**
         * Update page order after sort
         */
        updatePageOrder() {
            const newOrder = [];
            
            $('#cm-visual-pages .cm-page-item').each((index, element) => {
                const oldIndex = $(element).data('old-index') || 
                    $('#cm-visual-pages .cm-page-item').index(element);
                newOrder.push(this.pages[oldIndex]);
            });
            
            this.pages = newOrder;
            this.renderPages();
            this.syncToJSON();
        }
        
        /**
         * Sync visual editor to JSON
         */
        syncToJSON() {
            const jsonString = JSON.stringify(this.pages, null, 2);
            $('#cm-story-pages-json').val(jsonString);
        }
        
        /**
         * Sync JSON to visual editor
         */
        syncFromJSON() {
            try {
                const jsonData = $('#cm-story-pages-json').val();
                this.pages = jsonData ? JSON.parse(jsonData) : [];
                this.renderPages();
            } catch (e) {
                console.warn('Invalid JSON, keeping current pages');
            }
        }
        
        /**
         * Utility functions
         */
        truncateText(text, length) {
            if (text.length <= length) return text;
            return text.substring(0, length) + '...';
        }
        
        truncateUrl(url, length = 50) {
            if (url.length <= length) return url;
            return url.substring(0, length) + '...';
        }
        
        /**
         * Get pages data
         */
        getPages() {
            return this.pages;
        }
        
        /**
         * Set pages data
         */
        setPages(pages) {
            this.pages = pages || [];
            this.renderPages();
            this.syncToJSON();
        }
    }
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(() => {
        // Only initialize on story edit pages
        if ($('.cm-story-editor').length > 0) {
            editorInstance = new CMStoryEditor();
        }
    });
    
    /**
     * Global access
     */
    window.CMStoryEditor = {
        getInstance: () => editorInstance,
        init: () => {
            if (!editorInstance) {
                editorInstance = new CMStoryEditor();
            }
            return editorInstance;
        }
    };
    
})(jQuery);