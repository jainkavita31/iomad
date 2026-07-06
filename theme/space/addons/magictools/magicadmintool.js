/**
 * Magic Admin Tool for Space Theme
 * 
 * Enhanced version with modern JavaScript standards, improved performance,
 * better UX, accessibility features, and comprehensive error handling.
 * 
 * @copyright   2025 onwards, Marcin Czaja (https://rosea.io)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version     3.0.0
 */

// Constants
const CONSTANTS = {
    CONTAINER_ID: 'magic-admin-floating-btn-container',
    LOG_PREFIX: 'Magic Admin Tool:',
    NOTIFICATION_TIMEOUT: 5000,
    TOOLTIP_DELAY: 500,
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    ALLOWED_FILE_TYPES: ['application/json'],
    DEBOUNCE_DELAY: 300,
    ANIMATION_DURATION: 300,
    SELECTORS: {
        FORM_SETTING: '.form-setting',
        TEXT_INPUTS: '.form-setting input[type="text"], .form-setting input[type="number"], .form-setting input[type="url"], .form-setting input[type="email"]',
        TEXTAREAS: '.form-setting textarea:not([data-fieldtype="editor"])',
        CUSTOM_SELECTS: '.form-setting .custom-select',
        TINYMCE_EDITORS: '.form-setting textarea[data-fieldtype="editor"]',
        CHECKBOXES: '.form-setting input[type="checkbox"]',
        SELECTS: '.form-setting select',
        ATTO_EDITORS: '.form-setting .editor_atto_content',
        THEME_SETTINGS_FORM: '.settingsform .tab-content .tab-pane.active',
        SETTINGS_FORM: '.settingsform',
        MAIN_FORMS: 'form.mform, form#adminsettings',
        ALL_TAB_PANES: '.settingsform .tab-content .tab-pane'
    },
    BUTTON_CONFIGS: [
        {
            class: 'export-btn',
            icon: 'fa-download',
            title: 'Export Settings (Current Tab) - Ctrl+Shift+E',
            action: 'exportData',
            showHighlight: true,
            ariaLabel: 'Export current settings to JSON file'
        },
        {
            class: 'exportall-btn',
            icon: 'fa-solid fa-file-arrow-down',
            color: 'btn-exportall',
            title: 'Export All Settings',
            action: 'exportAllData',
            ariaLabel: 'Export all settings from all tabs to JSON file'
        },
        { separator: true },
        {
            class: 'import-btn',
            icon: 'fa-upload',
            title: 'Import Settings (Current Tab) - Ctrl+Shift+I',
            action: 'openImportDialog',
            ariaLabel: 'Import settings from JSON file'
        },
        {
            class: 'importall-btn',
            icon: 'fa-solid fa-file-arrow-up',
            color: 'btn-importall',
            title: 'Import All Settings',
            action: 'openImportAllDialog',
            ariaLabel: 'Import all settings from JSON file'
        }
    ]
};

/**
 * Main MagicAdminTool class
 */
class MagicAdminTool {
    constructor() {
        this.container = null;
        this.buttons = new Map();
        this.notifications = new Set();
        this.isInitialized = false;
        this.isProcessing = false;
        this.activeHighlight = false;
        this.observers = new Set();
        this.currentTooltip = null;
        
        // Cached DOM elements
        this.domCache = new Map();
        
        // Bind methods to preserve context
        this.handleHighlight = this.handleHighlight.bind(this);
        this.handleUnhighlight = this.handleUnhighlight.bind(this);
        this.handleKeyboard = this.handleKeyboard.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    /**
     * Initialize the tool
     */
    init() {
        try {
            if (this.isInitialized) {
                console.warn(`${CONSTANTS.LOG_PREFIX} Already initialized`);
                return;
            }

            this.setup();
            this.isInitialized = true;
            console.log(`${CONSTANTS.LOG_PREFIX} Initialized successfully`);
        } catch (error) {
            console.error(`${CONSTANTS.LOG_PREFIX} Initialization failed:`, error);
            NotificationManager.show('Failed to initialize Magic Admin Tool', 'error');
        }
    }

    /**
     * Setup the tool after DOM is ready
     */
    setup() {
        this.createFloatingButtons();
        this.attachGlobalEventListeners();
        this.observePageChanges();
    }

    /**
     * Attach global event listeners for enhanced UX
     */
    attachGlobalEventListeners() {
        document.addEventListener('keydown', this.handleKeyboard);
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => this.cleanup());
    }

    /**
     * Handle keyboard shortcuts
     */
    handleKeyboard(event) {
        // Ctrl+Shift+E for export
        if (event.ctrlKey && event.shiftKey && event.key === 'E') {
            event.preventDefault();
            this.exportData();
        }
        
        // Ctrl+Shift+I for import
        if (event.ctrlKey && event.shiftKey && event.key === 'I') {
            event.preventDefault();
            this.openImportDialog();
        }
        
        // Escape to close all notifications
        if (event.key === 'Escape') {
            this.closeAllNotifications();
        }
    }

    /**
     * Handle page visibility changes
     */
    handleVisibilityChange() {
        if (document.hidden && this.isProcessing) {
            NotificationManager.show('Operation continuing in background...', 'info');
        }
    }

    /**
     * Observe page changes for dynamic content
     */
    observePageChanges() {
        const observer = new MutationObserver(this.debounce(() => {
            this.updateButtonVisibility();
        }, CONSTANTS.DEBOUNCE_DELAY));

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'id']
        });

        this.observers.add(observer);
    }

    /**
     * Creates and adds floating action buttons to the page
     */
    createFloatingButtons() {
        if (this.container) {
            this.container.remove();
        }

        this.container = this.getOrCreateContainer();
        this.container.innerHTML = ''; // Clear existing buttons

        CONSTANTS.BUTTON_CONFIGS.forEach(config => {
            if (config.separator) {
                this.createSeparator();
            } else {
                const button = this.createButton(config);
                this.buttons.set(config.class, button);
                this.container.appendChild(button);
            }
        });

        this.initializeTooltips();
        this.updateButtonVisibility();
    }

    /**
     * Get or create the floating button container
     * @returns {HTMLElement} The container element
     */
    getOrCreateContainer() {
        let container = document.getElementById(CONSTANTS.CONTAINER_ID);
        if (!container) {
            container = document.createElement('div');
            container.id = CONSTANTS.CONTAINER_ID;
            container.className = 'magic-admin-floating-btn-container';
            container.setAttribute('role', 'toolbar');
            container.setAttribute('aria-label', 'Magic Admin Tools');
            
            Object.assign(container.style, {
                position: 'fixed',
                right: '0',
                bottom: '24px',
                zIndex: '9999',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'flex-end'
            });
            
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Create a visual separator
     */
    createSeparator() {
        const separator = document.createElement('div');
        separator.className = 'floating-btn-separator';
        
        Object.assign(separator.style, {
            width: '16px',
            height: '1px',
            background: 'rgba(0,0,0,0.12)',
            margin: '2px auto',
            borderRadius: '1px'
        });
        
        this.container.appendChild(separator);
    }

    /**
     * Create a button element with enhanced features
     * @param {Object} config - Button configuration
     */
    createButton(config) {
        const button = document.createElement('button');
        button.type = 'button';
        button.classList.add('floating-btn', config.class, 'btn', 'btn-primary');
        
        if (config.color) {
            button.classList.add(config.color);
        }
        
        button.innerHTML = `<i class="fa ${config.icon}" aria-hidden="true"></i>`;
        button.setAttribute('data-toggle', 'tooltip');
        button.setAttribute('data-placement', 'left');
        button.setAttribute('title', config.title);
        button.setAttribute('aria-label', config.ariaLabel);
        button.setAttribute('tabindex', '0');

        Object.assign(button.style, {
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '0',
            fontSize: '14px'
        });

        // Store cleanup function for event listeners
        const listeners = [];
        
        const addListener = (event, handler) => {
            button.addEventListener(event, handler);
            listeners.push({ event, handler });
        };

        // Add event listeners
        addListener('click', () => this[config.action]());
        
        if (config.showHighlight) {
            addListener('mouseenter', this.handleHighlight);
            addListener('mouseleave', this.handleUnhighlight);
        }

        // Keyboard support
        addListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this[config.action]();
            }
        });

        // Store cleanup function
        button._cleanup = () => {
            listeners.forEach(({ event, handler }) => {
                button.removeEventListener(event, handler);
            });
        };

        return button;
    }

    /**
     * Update button visibility based on page context
     */
    updateButtonVisibility() {
        const isAdminPage = this.isAdminSettingsPage();
        const container = ContainerHelper.findSettingsContainer();
        const hasExportableSettings = container ? this.hasExportableSettings(container) : false;
        const shouldShow = isAdminPage && container && hasExportableSettings;
        
        if (this.container) {
            this.container.style.display = shouldShow ? 'flex' : 'none';
        }
    }

    /**
     * Check if current page is an admin settings page
     */
    isAdminSettingsPage() {
        return document.body.classList.contains('path-admin-setting') ||
               document.body.id.startsWith('page-admin-setting') ||
               document.body.id.includes('edit') ||
               document.querySelector('.settingsform, .mform, #adminsettings');
    }

    /**
     * Check if container has exportable settings
     */
    hasExportableSettings(container) {
        if (!container) return false;
        
        const exportableSelectors = [
            '.form-setting input[type="text"]',
            '.form-setting input[type="number"]', 
            '.form-setting input[type="url"]',
            '.form-setting input[type="email"]',
            '.form-setting textarea',
            '.form-setting select',
            '.form-setting input[type="checkbox"]',
            '.form-setting .editor_atto_content'
        ];
        
        for (const selector of exportableSelectors) {
            const elements = container.querySelectorAll(selector);
            // Check if any element has a name attribute (required for export)
            for (const element of elements) {
                if (element.name || element.id) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Initialize enhanced tooltips with better positioning
     */
    initializeTooltips() {
        this.buttons.forEach(button => {
            button.addEventListener('mouseenter', (e) => this.showTooltip(e.target));
            button.addEventListener('mouseleave', () => this.hideTooltip());
            button.addEventListener('focus', (e) => this.showTooltip(e.target));
            button.addEventListener('blur', () => this.hideTooltip());
        });
    }

    /**
     * Show tooltip with smart positioning
     */
    showTooltip(button) {
        this.hideTooltip(); // Clear any existing tooltip

        const tooltip = document.createElement('div');
        tooltip.classList.add('floating-btn-tooltip', 'badge', 'badge-dark');
        tooltip.textContent = button.title;
        tooltip.setAttribute('role', 'tooltip');
        
        document.body.appendChild(tooltip);

        // Smart positioning
        const buttonRect = button.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let left = buttonRect.left - tooltipRect.width - 10;
        let top = buttonRect.top + (buttonRect.height / 2) - (tooltipRect.height / 2);

        // Adjust if tooltip would go off-screen
        if (left < 10) {
            left = buttonRect.right + 10;
        }
        if (top < 10) {
            top = 10;
        }
        if (top + tooltipRect.height > window.innerHeight - 10) {
            top = window.innerHeight - tooltipRect.height - 10;
        }

        tooltip.style.position = 'fixed';
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
        tooltip.style.zIndex = '10000';
        tooltip.style.opacity = '0';
        tooltip.style.transition = 'opacity 0.2s ease';

        // Animate in
        requestAnimationFrame(() => {
            tooltip.style.opacity = '1';
        });

        this.currentTooltip = tooltip;
    }

    /**
     * Hide current tooltip
     */
    hideTooltip() {
        if (this.currentTooltip) {
            this.currentTooltip.style.opacity = '0';
            setTimeout(() => {
                this.currentTooltip?.remove();
                this.currentTooltip = null;
            }, 200);
        }
    }

    /**
     * Handle highlighting form items with smooth animation
     */
    handleHighlight() {
        if (this.activeHighlight) return;
        
        const container = ContainerHelper.findSettingsContainer();
        if (container) {
            const formItems = container.querySelectorAll(CONSTANTS.SELECTORS.FORM_SETTING);
            formItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('active-row-to-export');
                }, index * 20); // Staggered animation
            });
            this.activeHighlight = true;
        }
    }

    /**
     * Handle removing highlight from form items
     */
    handleUnhighlight() {
        document.querySelectorAll('.form-setting.active-row-to-export')
            .forEach(item => item.classList.remove('active-row-to-export'));
        this.activeHighlight = false;
    }

    /**
     * Enhanced export with progress and better error handling
     */
    async exportData() {
        if (this.isProcessing) {
            NotificationManager.show('Another operation is in progress. Please wait.', 'warning');
            return;
        }

        try {
            this.setProcessingState(true);
            
            const container = ContainerHelper.findSettingsContainer();
            if (!container) {
                throw new Error('Could not identify the settings area on this page.');
            }

            const result = await DataProcessor.exportFromContainer(container);
            if (result.success) {
                await FileManager.downloadJSON(result.data, result.filename);
                NotificationManager.show(
                    `Successfully exported ${result.count} settings to ${result.filename}`,
                    'success'
                );
            }
        } catch (error) {
            console.error(`${CONSTANTS.LOG_PREFIX} Export failed:`, error);
            NotificationManager.show(`Export failed: ${error.message}`, 'error');
        } finally {
            this.setProcessingState(false);
        }
    }

    /**
     * Export data from all containers
     */
    async exportAllData() {
        if (this.isProcessing) {
            NotificationManager.show('Another operation is in progress. Please wait.', 'warning');
            return;
        }

        try {
            this.setProcessingState(true);
            
            const containers = ContainerHelper.findAllSettingsContainers();
            if (!containers.length) {
                NotificationManager.show('Could not identify settings areas on this page.', 'error');
                return;
            }

            const result = await DataProcessor.exportFromAllContainers(containers);
            if (result.success) {
                await FileManager.downloadJSON(result.data, result.filename);
                NotificationManager.show(
                    `Successfully exported ${result.count} settings from all areas to ${result.filename}`,
                    'success'
                );
            }
        } catch (error) {
            console.error(`${CONSTANTS.LOG_PREFIX} Export All failed:`, error);
            NotificationManager.show(`Export All failed: ${error.message}`, 'error');
        } finally {
            this.setProcessingState(false);
        }
    }

    /**
     * Open import dialog with enhanced file validation
     */
    async openImportDialog() {
        if (this.isProcessing) {
            NotificationManager.show('Another operation is in progress. Please wait.', 'warning');
            return;
        }

        // Show enhanced confirmation dialog with more detailed warning
        const confirmImport = await this.showConfirmDialog(
            'Import Settings - Current Tab',
            `<div class="warning-icon">⚠️</div>
            <p><strong>Warning:</strong> This action will replace ALL current settings in the active tab with the imported values.</p>
            <p><strong>What will happen:</strong></p>
            <ul>
                <li>Current form values will be overwritten</li>
                <li>Changes cannot be undone automatically</li>
                <li>Make sure you have exported your current settings as backup</li>
            </ul>
            <p>Do you want to proceed with the import?</p>`,
            'Import Settings',
            'Cancel',
            'warning'
        );

        if (!confirmImport) return;

        try {
            const data = await FileManager.selectJSONFile();
            await DataProcessor.importToContainer(data);
        } catch (error) {
            if (error.message !== 'No file selected') {
                console.error(`${CONSTANTS.LOG_PREFIX} Import failed:`, error);
                NotificationManager.show(`Import failed: ${error.message}`, 'error');
            }
        }
    }

    /**
     * Open import dialog for all containers
     */
    async openImportAllDialog() {
        if (this.isProcessing) {
            NotificationManager.show('Another operation is in progress. Please wait.', 'warning');
            return;
        }

        // Show enhanced confirmation dialog with more detailed warning for all settings
        const confirmImport = await this.showConfirmDialog(
            'Import All Settings - ALL TABS',
            `<div class="warning-icon">🚨</div>
            <p><strong>CRITICAL WARNING:</strong> This action will replace ALL settings across ALL tabs with the imported values.</p>
            <p><strong>What will happen:</strong></p>
            <ul>
                <li>ALL current form values in ALL tabs will be overwritten</li>
                <li>This affects the entire theme configuration</li>
                <li>Changes cannot be undone automatically</li>
                <li>Strongly recommended to export all current settings first</li>
            </ul>
            <p><strong>Are you absolutely sure you want to proceed?</strong></p>`,
            'Import All Settings',
            'Cancel',
            'danger'
        );

        if (!confirmImport) return;

        try {
            const data = await FileManager.selectJSONFile();
            await DataProcessor.importToAllContainers(data);
        } catch (error) {
            if (error.message !== 'No file selected') {
                console.error(`${CONSTANTS.LOG_PREFIX} Import All failed:`, error);
                NotificationManager.show(`Import All failed: ${error.message}`, 'error');
            }
        }
    }

    /**
     * Show enhanced confirmation dialog with better styling and safety features
     */
    showConfirmDialog(title, message, confirmText = 'OK', cancelText = 'Cancel', type = 'info') {
        return new Promise((resolve) => {
            const dialog = document.createElement('div');
            dialog.className = `magic-confirm-dialog dialog-${type}`;
            dialog.setAttribute('role', 'dialog');
            dialog.setAttribute('aria-modal', 'true');
            dialog.setAttribute('aria-labelledby', 'dialog-title');

            // Enhanced styling based on dialog type
            const dialogStyles = {
                warning: {
                    borderColor: '#ffc107',
                    headerColor: '#856404',
                    confirmBtnClass: 'btn-warning'
                },
                danger: {
                    borderColor: '#dc3545',
                    headerColor: '#721c24',
                    confirmBtnClass: 'btn-danger'
                },
                info: {
                    borderColor: '#17a2b8',
                    headerColor: '#0c5460',
                    confirmBtnClass: 'btn-primary'
                }
            };

            const currentStyle = dialogStyles[type] || dialogStyles.info;

            dialog.innerHTML = `
                <style>
                    .magic-confirm-dialog {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 10002;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    }
                    .magic-confirm-dialog.show {
                        opacity: 1;
                    }
                    .dialog-overlay {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.6);
                        backdrop-filter: blur(2px);
                    }
                    .dialog-content {
                        position: relative;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                        max-width: 500px;
                        width: 90%;
                        max-height: 70vh;
                        overflow-y: auto;
                        border: 3px solid ${currentStyle.borderColor};
                        animation: slideIn 0.3s ease;
                    }
                    @keyframes slideIn {
                        from {
                            transform: scale(0.9) translateY(-20px);
                            opacity: 0;
                        }
                        to {
                            transform: scale(1) translateY(0);
                            opacity: 1;
                        }
                    }
                    .dialog-content h3 {
                        margin: 0;
                        padding: 20px 20px 10px;
                        color: ${currentStyle.headerColor};
                        border-bottom: 2px solid ${currentStyle.borderColor};
                        font-weight: bold;
                        font-size: 1.3em;
                    }
                    .dialog-content .dialog-body {
                        padding: 20px;
                        line-height: 1.5;
                    }
                    .dialog-content .warning-icon {
                        font-size: 2em;
                        text-align: center;
                        margin-bottom: 15px;
                    }
                    .dialog-content ul {
                        margin: 10px 0;
                        padding-left: 20px;
                    }
                    .dialog-content li {
                        margin: 5px 0;
                    }
                    .dialog-buttons {
                        padding: 0 20px 20px;
                        display: flex;
                        gap: 10px;
                        justify-content: flex-end;
                    }
                    .dialog-buttons button {
                        padding: 10px 20px;
                        border: none;
                        border-radius: 5px;
                        cursor: pointer;
                        font-weight: 500;
                        transition: all 0.2s ease;
                        min-width: 100px;
                    }
                    .dialog-buttons .btn-secondary {
                        background: #6c757d;
                        color: white;
                    }
                    .dialog-buttons .btn-secondary:hover {
                        background: #545b62;
                        transform: translateY(-1px);
                    }
                    .dialog-buttons .${currentStyle.confirmBtnClass} {
                        background: ${currentStyle.borderColor};
                        color: white;
                    }
                    .dialog-buttons .${currentStyle.confirmBtnClass}:hover {
                        filter: brightness(1.1);
                        transform: translateY(-1px);
                    }
                    .dialog-buttons .${currentStyle.confirmBtnClass}:focus {
                        box-shadow: 0 0 0 3px rgba(${type === 'danger' ? '220, 53, 69' : type === 'warning' ? '255, 193, 7' : '23, 162, 184'}, 0.3);
                    }
                </style>
                <div class="dialog-overlay"></div>
                <div class="dialog-content">
                    <h3 id="dialog-title">${title}</h3>
                    <div class="dialog-body">${message}</div>
                    <div class="dialog-buttons">
                        <button class="btn btn-secondary cancel-btn">${cancelText}</button>
                        <button class="btn ${currentStyle.confirmBtnClass} confirm-btn">${confirmText}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(dialog);

            const confirmBtn = dialog.querySelector('.confirm-btn');
            const cancelBtn = dialog.querySelector('.cancel-btn');
            const overlay = dialog.querySelector('.dialog-overlay');

            const cleanup = () => {
                dialog.style.opacity = '0';
                setTimeout(() => {
                    dialog.remove();
                    document.body.classList.remove('modal-open');
                }, 300);
            };

            confirmBtn.addEventListener('click', () => {
                cleanup();
                resolve(true);
            });

            [cancelBtn, overlay].forEach(element => {
                element.addEventListener('click', () => {
                    cleanup();
                    resolve(false);
                });
            });

            // Enhanced keyboard support with focus trapping
            const focusableElements = dialog.querySelectorAll('button');
            let focusIndex = 1; // Start with confirm button focused (safer default)

            dialog.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    cleanup();
                    resolve(false);
                } else if (e.key === 'Tab') {
                    e.preventDefault();
                    focusIndex = (focusIndex + (e.shiftKey ? -1 : 1) + focusableElements.length) % focusableElements.length;
                    focusableElements[focusIndex].focus();
                } else if (e.key === 'Enter' && e.target === confirmBtn) {
                    cleanup();
                    resolve(true);
                }
            });

            // Focus management - start with cancel button for safety
            document.body.classList.add('modal-open');
            requestAnimationFrame(() => {
                dialog.classList.add('show');
                cancelBtn.focus(); // Focus cancel by default for safety
            });
        });
    }

    /**
     * Set processing state with visual feedback
     */
    setProcessingState(isProcessing) {
        this.isProcessing = isProcessing;
        
        this.buttons.forEach(button => {
            button.disabled = isProcessing;
            if (isProcessing) {
                button.classList.add('processing');
            } else {
                button.classList.remove('processing');
            }
        });

        if (this.container) {
            this.container.classList.toggle('processing', isProcessing);
        }
    }

    /**
     * Close all notifications
     */
    closeAllNotifications() {
        this.notifications.forEach(notification => {
            NotificationManager.hide(notification);
        });
        this.notifications.clear();
    }

    /**
     * Debounce utility function
     */
    debounce(func, delay) {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    /**
     * Cleanup resources to prevent memory leaks
     */
    cleanup() {
        // Remove event listeners
        document.removeEventListener('keydown', this.handleKeyboard);
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);

        // Cleanup buttons
        this.buttons.forEach(button => {
            if (button._cleanup) {
                button._cleanup();
            }
        });

        // Cleanup observers
        this.observers.forEach(observer => {
            observer.disconnect();
        });

        // Clear notifications
        this.closeAllNotifications();

        // Clear DOM cache
        this.domCache.clear();

        // Remove container
        if (this.container) {
            this.container.remove();
        }

        // Clear tooltip
        this.hideTooltip();
    }
}

/**
 * Container Helper utility class
 */
class ContainerHelper {
    // Static cache for DOM elements
    static domCache = new Map();
    
    /**
     * Get the active tab or form container
     * @returns {HTMLElement|null}
     */
    static getActiveTab() {
        const cacheKey = 'activeTab';
        const cached = this.domCache.get(cacheKey);
        
        if (cached && document.contains(cached)) {
            return cached;
        }

        let container = null;
        
        if (document.body.classList.contains('path-admin-setting')) {
            container = document.querySelector('#adminsettings');
        } else if (document.body.id.match(/-edit$/) || document.body.id.includes('edit')) {
            container = document.querySelector('.mform');
        } else if (document.body.id.startsWith('page-admin-setting-themesetting')) {
            container = document.querySelector('.tab-pane.active');
        }
        
        if (container) {
            this.domCache.set(cacheKey, container);
            // Clear cache after short time as tab might change
            setTimeout(() => this.domCache.delete(cacheKey), 2000);
        }
        
        return container;
    }

    /**
     * Enhanced settings container finder with caching
     * @returns {HTMLElement|null}
     */
    static findSettingsContainer() {
        const cacheKey = 'settingsContainer';
        const cached = this.domCache.get(cacheKey);
        
        if (cached && document.contains(cached)) {
            return cached;
        }

        let container = null;
        const selectors = [
            // Theme settings pages - active tab first
            '.settingsform .tab-content .tab-pane.active',
            // Theme settings pages - fallback
            '.settingsform',
            // General admin forms
            'form.mform',
            'form#adminsettings',
            // Last resort
            '#region-main .mform'
        ];

        for (const selector of selectors) {
            container = document.querySelector(selector);
            if (container) {
                console.log(`${CONSTANTS.LOG_PREFIX} Found container with selector: ${selector}`);
                break;
            }
        }

        if (!container) {
            console.error(`${CONSTANTS.LOG_PREFIX} Failed to identify settings container`);
            return null;
        }

        // Cache the container
        this.domCache.set(cacheKey, container);
        // Clear cache when container might change
        setTimeout(() => this.domCache.delete(cacheKey), 5000);

        return container;
    }

    /**
     * Find all settings containers with improved detection
     * @returns {HTMLElement[]}
     */
    static findAllSettingsContainers() {
        const cacheKey = 'allSettingsContainers';
        const cached = this.domCache.get(cacheKey);
        
        if (cached && cached.every(container => document.contains(container))) {
            return cached;
        }

        let containers = [];

        if (document.body.id.startsWith('page-admin-setting-themesettings')) {
            const panes = Array.from(document.querySelectorAll(CONSTANTS.SELECTORS.ALL_TAB_PANES));
            if (panes.length > 0) {
                containers = panes;
            } else {
                const form = document.querySelector(CONSTANTS.SELECTORS.SETTINGS_FORM);
                if (form) containers = [form];
            }
        }

        if (containers.length === 0) {
            const form = document.querySelector(CONSTANTS.SELECTORS.MAIN_FORMS);
            if (form) containers = [form];
        }

        // Cache the containers
        if (containers.length > 0) {
            this.domCache.set(cacheKey, containers);
            setTimeout(() => this.domCache.delete(cacheKey), 5000);
        }

        return containers;
    }

    /**
     * Clear the DOM cache
     */
    static clearCache() {
        this.domCache.clear();
    }
}

/**
 * Data Processing utility class
 */
class DataProcessor {
    /**
     * Export data from a single container
     * @param {HTMLElement} container
     * @returns {Promise<Object>}
     */
    static async exportFromContainer(container) {
        const formData = {};
        const warnings = [];
        let exportedCount = 0;

        const exportHandlers = [
            { selector: CONSTANTS.SELECTORS.TEXT_INPUTS, handler: el => el.value },
            { selector: CONSTANTS.SELECTORS.TEXTAREAS, handler: el => el.value },
            { selector: CONSTANTS.SELECTORS.CUSTOM_SELECTS, handler: el => el.value },
            { selector: CONSTANTS.SELECTORS.SELECTS, handler: el => el.value },
            { selector: CONSTANTS.SELECTORS.CHECKBOXES, handler: el => el.checked ? '1' : '0' },
            { selector: CONSTANTS.SELECTORS.ATTO_EDITORS, handler: el => el.innerHTML, key: 'id' }
        ];

        exportHandlers.forEach(({ selector, handler, key = 'name' }) => {
            container.querySelectorAll(selector).forEach(element => {
                if (this.tryExport(element, key, handler, formData, warnings)) {
                    exportedCount++;
                }
            });
        });

        // Handle TinyMCE editors separately
        exportedCount += this.exportTinyMCEEditors(container, formData, warnings);

        if (exportedCount === 0) {
            NotificationManager.show('No settings found to export in the selected area.', 'info');
            return { success: false };
        }

        if (warnings.length > 0) {
            console.warn(`${CONSTANTS.LOG_PREFIX} Export warnings:`, warnings);
            NotificationManager.show(
                `Exported ${exportedCount} settings with ${warnings.length} warnings. Check console.`,
                'warning'
            );
        }

        const filename = this.generateFilename(container);
        return { success: true, data: formData, count: exportedCount, filename, warnings };
    }

    /**
     * Export data from all containers
     * @param {HTMLElement[]} containers
     * @returns {Promise<Object>}
     */
    static async exportFromAllContainers(containers) {
        const formData = {};
        const warnings = [];
        let exportedCount = 0;

        for (const container of containers) {
            const result = await this.exportFromContainer(container);
            if (result.success) {
                Object.assign(formData, result.data);
                exportedCount += result.count;
                warnings.push(...result.warnings);
            }
        }

        if (exportedCount === 0) {
            NotificationManager.show('No settings found to export in any area.', 'info');
            return { success: false };
        }

        const filename = this.generateAllFilename();
        return { success: true, data: formData, count: exportedCount, filename, warnings };
    }

    /**
     * Import data to a single container
     * @param {Object} formObject
     * @returns {Promise<void>}
     */
    static async importToContainer(formObject) {
        const container = ContainerHelper.findSettingsContainer();
        if (!container) return;

        const result = this.processImport(container, formObject);
        this.showImportResult(result, 'current area');
    }

    /**
     * Import data to all containers
     * @param {Object} formObject
     * @returns {Promise<void>}
     */
    static async importToAllContainers(formObject) {
        const containers = ContainerHelper.findAllSettingsContainers();
        if (!containers.length) {
            NotificationManager.show('Could not identify settings areas on this page.', 'error');
            return;
        }

        let totalImported = 0;
        const allErrors = [];
        const allWarnings = [];

        containers.forEach(container => {
            const result = this.processImport(container, formObject);
            totalImported += result.importedCount;
            allErrors.push(...result.errors);
            allWarnings.push(...result.warnings);
        });

        this.showImportResult({
            importedCount: totalImported,
            errors: allErrors,
            warnings: allWarnings
        }, 'ALL areas');
    }

    /**
     * Process import for a container
     * @param {HTMLElement} container
     * @param {Object} formObject
     * @returns {Object}
     */
    static processImport(container, formObject) {
        let importedCount = 0;
        const errors = [];
        const warnings = [];

        const importHandlers = [
            {
                selector: `${CONSTANTS.SELECTORS.TEXT_INPUTS}, ${CONSTANTS.SELECTORS.TEXTAREAS}, ${CONSTANTS.SELECTORS.CUSTOM_SELECTS}`,
                handler: (el, value) => { el.value = value; }
            },
            {
                selector: CONSTANTS.SELECTORS.CHECKBOXES,
                handler: (el, value) => { el.checked = String(value) === '1' || value === true; }
            },
            {
                selector: CONSTANTS.SELECTORS.SELECTS,
                handler: (el, value) => {
                    if (el.querySelector(`option[value="${CSS.escape(String(value))}"]`)) {
                        el.value = value;
                    } else {
                        throw new Error(`Value "${value}" not found for select field`);
                    }
                }
            },
            {
                selector: CONSTANTS.SELECTORS.ATTO_EDITORS,
                handler: (el, value) => { el.innerHTML = value; },
                key: 'id'
            }
        ];

        importHandlers.forEach(({ selector, handler, key = 'name' }) => {
            container.querySelectorAll(selector).forEach(element => {
                const keyValue = element[key];
                if (keyValue && formObject.hasOwnProperty(keyValue)) {
                    if (this.tryImport(element, handler, formObject[keyValue], keyValue, errors)) {
                        importedCount++;
                    }
                }
            });
        });

        // Handle TinyMCE editors separately
        importedCount += this.importTinyMCEEditors(container, formObject, warnings, errors);

        return { importedCount, errors, warnings };
    }

    /**
     * Export TinyMCE editors
     * @param {HTMLElement} container
     * @param {Object} formData
     * @param {Array} warnings
     * @returns {number}
     */
    static exportTinyMCEEditors(container, formData, warnings) {
        let count = 0;
        container.querySelectorAll(CONSTANTS.SELECTORS.TINYMCE_EDITORS).forEach(textarea => {
            if (this.tryExport(textarea, 'name', el => el.value, formData, warnings)) {
                count++;
            }
        });
        return count;
    }

    /**
     * Import TinyMCE editors
     * @param {HTMLElement} container
     * @param {Object} formObject
     * @param {Array} warnings
     * @param {Array} errors
     * @returns {number}
     */
    static importTinyMCEEditors(container, formObject, warnings, errors) {
        let count = 0;
        container.querySelectorAll(CONSTANTS.SELECTORS.TINYMCE_EDITORS).forEach(textarea => {
            if (textarea.name && formObject.hasOwnProperty(textarea.name)) {
                const editorId = textarea.id;
                const tinyEditor = typeof tinymce !== 'undefined' ? tinymce.get(editorId) : null;
                
                if (tinyEditor) {
                    if (this.tryImport(textarea, () => tinyEditor.setContent(formObject[textarea.name]), formObject[textarea.name], textarea.name, errors)) {
                        count++;
                    }
                } else {
                    warnings.push(`TinyMCE instance not found for ID "${editorId}". Attempting direct textarea update.`);
                    if (this.tryImport(textarea, el => { el.value = formObject[textarea.name]; }, formObject[textarea.name], textarea.name, errors)) {
                        count++;
                    }
                }
            }
        });
        return count;
    }

    /**
     * Try to export a field value
     * @param {HTMLElement} element
     * @param {string} keyAttribute
     * @param {Function} valueGetter
     * @param {Object} formData
     * @param {Array} warnings
     * @returns {boolean}
     */
    static tryExport(element, keyAttribute, valueGetter, formData, warnings) {
        const key = element[keyAttribute];
        if (!key) {
            console.warn(`${CONSTANTS.LOG_PREFIX} Element missing key attribute "${keyAttribute}":`, element);
            return false;
        }
        
        try {
            formData[key] = valueGetter(element);
            return true;
        } catch (error) {
            warnings.push(`Error exporting field "${key}": ${error.message}`);
            console.error(`${CONSTANTS.LOG_PREFIX} Export error for ${key}:`, error);
            return false;
        }
    }

    /**
     * Try to import a field value
     * @param {HTMLElement} element
     * @param {Function} valueSetter
     * @param {*} value
     * @param {string} fieldIdentifier
     * @param {Array} errors
     * @returns {boolean}
     */
    static tryImport(element, valueSetter, value, fieldIdentifier, errors) {
        try {
            valueSetter(element, value);
            return true;
        } catch (error) {
            const fieldName = fieldIdentifier || element.name || element.id || 'Unnamed Field';
            errors.push(`Error importing field "${fieldName}": ${error.message}`);
            console.error(`${CONSTANTS.LOG_PREFIX} Import error for ${fieldName}:`, error);
            return false;
        }
    }

    /**
     * Generate filename for export
     * @param {HTMLElement} container
     * @returns {string}
     */
    static generateFilename(container) {
        let identifier = 'settings';
        
        if (container.id && container.id !== 'region-main' && !container.classList.contains('mform')) {
            identifier = container.id.replace(/^(tab-|region-)/, '');
        } else if (container.closest('.tab-pane[id]')) {
            identifier = container.closest('.tab-pane[id]').id.replace(/^(tab-)/, '');
        } else if (document.body.id.startsWith('page-admin-setting-themesettings')) {
            identifier = document.body.id.replace('page-admin-setting-', '');
        } else if (document.body.classList.contains('path-admin-settings')) {
            const pathParts = window.location.pathname.split('/');
            for (let i = pathParts.length - 1; i >= 0; i--) {
                if (pathParts[i] && !['settings.php', 'index.php', 'admin'].includes(pathParts[i])) {
                    identifier = pathParts[i];
                    break;
                }
            }
        }
        
        identifier = identifier.replace(/[^a-z0-9_]/gi, '_').replace(/_+/g, '_').toLowerCase() || 'export';
        const dateStr = new Date().toISOString().split('T')[0];
        return `space_theme_${identifier}_${dateStr}.json`;
    }

    /**
     * Generate filename for export all
     * @returns {string}
     */
    static generateAllFilename() {
        let identifier = 'all_settings';
        
        if (document.body.id.startsWith('page-admin-setting-themesettings')) {
            identifier = document.body.id.replace('page-admin-setting-', '') + '_all';
        } else if (document.body.classList.contains('path-admin-settings')) {
            const pathParts = window.location.pathname.split('/');
            for (let i = pathParts.length - 1; i >= 0; i--) {
                if (pathParts[i] && !['settings.php', 'index.php', 'admin'].includes(pathParts[i])) {
                    identifier = pathParts[i] + '_all';
                    break;
                }
            }
        }
        
        identifier = identifier.replace(/[^a-z0-9_]/gi, '_').replace(/_+/g, '_').toLowerCase() || 'exportall';
        const dateStr = new Date().toISOString().split('T')[0];
        return `space_theme_${identifier}_${dateStr}.json`;
    }

    /**
     * Show import result notification
     * @param {Object} result
     * @param {string} area
     */
    static showImportResult(result, area) {
        const { importedCount, errors, warnings } = result;
        
        if (errors.length > 0) {
            NotificationManager.show(
                `Import finished with errors. ${importedCount} settings applied, ${errors.length} errors occurred. Check console.`,
                'error'
            );
            console.error(`${CONSTANTS.LOG_PREFIX} Import errors:`, errors);
        } else if (warnings.length > 0) {
            NotificationManager.show(
                `Import complete with warnings: ${importedCount} settings applied, ${warnings.length} warnings. Check console.`,
                'warning'
            );
            console.warn(`${CONSTANTS.LOG_PREFIX} Import warnings:`, warnings);
        } else if (importedCount > 0) {
            NotificationManager.show(
                `Successfully imported ${importedCount} settings into ${area}!`,
                'success'
            );
        } else {
            NotificationManager.show(
                `No matching settings found to import in ${area} or the file.`,
                'info'
            );
        }
    }
}

/**
 * File Management utility class
 */
class FileManager {
    /**
     * Select and read a JSON file with enhanced validation
     * @returns {Promise<Object>}
     */
    static selectJSONFile() {
        return new Promise((resolve, reject) => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            input.setAttribute('aria-label', 'Select JSON file to import');
            
            input.addEventListener('change', async (event) => {
                const file = event.target.files[0];
                if (!file) {
                    reject(new Error('No file selected'));
                    return;
                }
                
                try {
                    // Validate file
                    const validation = this.validateFile(file);
                    if (!validation.valid) {
                        reject(new Error(validation.error));
                        return;
                    }

                    NotificationManager.show('Reading file...', 'info');
                    
                    const content = await this.readFileAsync(file);
                    const data = JSON.parse(content);
                    
                    // Validate JSON structure
                    if (!this.validateImportData(data)) {
                        reject(new Error('Invalid file format. Expected settings object.'));
                        return;
                    }
                    
                    resolve(data);
                } catch (error) {
                    if (error.name === 'SyntaxError') {
                        reject(new Error('Invalid JSON format. Please check your file.'));
                    } else {
                        reject(error);
                    }
                }
            });
            
            input.click();
        });
    }

    /**
     * Validate uploaded file
     */
    static validateFile(file) {
        if (!file) {
            return { valid: false, error: 'No file selected' };
        }

        if (file.size > CONSTANTS.MAX_FILE_SIZE) {
            return { 
                valid: false, 
                error: `File too large. Maximum size: ${CONSTANTS.MAX_FILE_SIZE / 1024 / 1024}MB` 
            };
        }

        if (!CONSTANTS.ALLOWED_FILE_TYPES.includes(file.type) && !file.name.endsWith('.json')) {
            return { 
                valid: false, 
                error: 'Invalid file type. Please select a JSON file.' 
            };
        }

        return { valid: true };
    }

    /**
     * Read file asynchronously with progress
     */
    static readFileAsync(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.onprogress = (e) => {
                if (e.lengthComputable) {
                    const progress = (e.loaded / e.total) * 100;
                    console.log(`Reading file: ${progress.toFixed(1)}%`);
                }
            };

            reader.readAsText(file);
        });
    }

    /**
     * Validate import data structure
     */
    static validateImportData(data) {
        return typeof data === 'object' && 
               data !== null && 
               !Array.isArray(data) &&
               Object.keys(data).length > 0;
    }

    /**
     * Download data as JSON file with progress
     * @param {Object} data
     * @param {string} filename
     * @returns {Promise<void>}
     */
    static async downloadJSON(data, filename) {
        try {
            const blob = new Blob([JSON.stringify(data, null, 2)], { 
                type: 'application/json;charset=utf-8' 
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            
            link.href = url;
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            
            // Cleanup
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch (error) {
            throw new Error(`File download failed: ${error.message}`);
        }
    }
}

/**
 * Notification Management utility class
 */
class NotificationManager {
    static notifications = new Set();

    /**
     * Show a notification message with enhanced UX
     * @param {string} message
     * @param {string} type
     * @param {boolean} persistent
     */
    static show(message, type = 'info', persistent = false) {
        const notification = this.createNotificationElement(message, type);
        document.body.appendChild(notification);
        this.notifications.add(notification);

        // Animate in
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        // Auto-hide setup (unless persistent)
        let hideTimeout;
        if (!persistent) {
            hideTimeout = setTimeout(() => {
                this.hide(notification);
            }, CONSTANTS.NOTIFICATION_TIMEOUT);
        }

        // Manual close setup
        const closeButton = notification.querySelector('.notification-close');
        closeButton?.addEventListener('click', () => {
            clearTimeout(hideTimeout);
            this.hide(notification);
        });

        // Keyboard handling
        notification.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                clearTimeout(hideTimeout);
                this.hide(notification);
            }
        });

        return notification;
    }

    /**
     * Create notification element with better accessibility
     * @param {string} message
     * @param {string} type
     * @returns {HTMLElement}
     */
    static createNotificationElement(message, type) {
        const notification = document.createElement('div');
        notification.classList.add('floating-notification', `notification-${type}`);
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'polite');

        const content = document.createElement('div');
        content.className = 'notification-content';

        const messageSpan = document.createElement('span');
        messageSpan.className = 'notification-message me-4';
        messageSpan.textContent = message;

        const closeButton = document.createElement('button');
        closeButton.className = 'notification-close btn-close';
        closeButton.setAttribute('aria-label', 'Close notification');
        closeButton.innerHTML = '&times;';

        content.appendChild(messageSpan);
        content.appendChild(closeButton);
        notification.appendChild(content);

        // Position notifications in a stack
        this.positionNotification(notification);

        return notification;
    }

    /**
     * Position notification in a stack
     */
    static positionNotification(notification) {
        const existingNotifications = Array.from(this.notifications);
        const offset = existingNotifications.length * 70; // Stack notifications

        Object.assign(notification.style, {
            position: 'fixed',
            top: `${20 + offset}px`,
            right: '20px',
            zIndex: '10001',
            maxWidth: '400px',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease'
        });
    }

    /**
     * Hide notification with animation
     * @param {HTMLElement} notification
     */
    static hide(notification) {
        if (!notification || !notification.parentNode) return;

        notification.classList.remove('show');
        notification.style.transform = 'translateX(100%)';
        this.notifications.delete(notification);

        setTimeout(() => {
            notification.remove();
            this.repositionNotifications();
        }, CONSTANTS.ANIMATION_DURATION);
    }

    /**
     * Reposition remaining notifications
     */
    static repositionNotifications() {
        const notifications = Array.from(this.notifications);
        notifications.forEach((notification, index) => {
            notification.style.top = `${20 + (index * 70)}px`;
        });
    }

    /**
     * Close all notifications
     */
    static hideAll() {
        this.notifications.forEach(notification => {
            this.hide(notification);
        });
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text
     * @returns {string}
     */
    static escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the tool
const magicAdminTool = new MagicAdminTool();

// Export for global access if needed
window.MagicAdminTool = MagicAdminTool;
window.magicAdminTool = magicAdminTool;

// Additional cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (magicAdminTool && typeof magicAdminTool.cleanup === 'function') {
        magicAdminTool.cleanup();
    }
    // Clear container helper cache
    if (ContainerHelper && typeof ContainerHelper.clearCache === 'function') {
        ContainerHelper.clearCache();
    }
    // Clear all notifications
    if (NotificationManager && typeof NotificationManager.hideAll === 'function') {
        NotificationManager.hideAll();
    }
});

console.log(`${CONSTANTS.LOG_PREFIX} Enhanced Magic Admin Tool v3.0.0 loaded successfully`);


