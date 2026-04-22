//
// This file is part of space theme for moodle
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
//
// space main JS file
//
// @package    theme_space
// @copyright  Copyright © 2021 onwards Marcin Czaja Rosea Themes
//
// @license    Commercial

/* jshint ignore:start */
define(['jquery', 'core/aria', 'core_user/repository'], function ($, setUserPreference, UserRepository) {
    "use strict";

    // Enhanced Constants with validation
    const BREAKPOINTS = Object.freeze({
        MOBILE: 1399,
        TABLET: 768,
        DESKTOP: 1200
    });

    const SELECTORS = Object.freeze({
        DARK_MODE_BTN: '#darkModeBtn',
        MY_COURSES_BTN: '#myCoursesBtn',
        MY_COURSES_HIDDEN: '#myCoursesHidden',
        MY_COURSES_INPROGRESS: '#myCoursesInprogress',
        MAIN_NAV: '#mainNav',
        TOP_BAR: '#topBar',
        NAV_DRAWER: '#nav-drawer',
        SPACE_DRAWERS_BLOCKS: '#space-drawers-blocks',
        PAGE: '#page',
        EDITING_SWITCH_FORM: '#editingswitchForm',
        MOBILE_NAV: '#mobileNav',
        MOBILE_BTN_CLOSE: '#mobileBtnClose',
        SHOW_BLOCK_AREA: '#showBlockArea',
        DRAWER_TOGGLE: '.drawertoggle',
        DRAWER_COURSE_LEFT: '.drawer-course-left',
        DRAWER_RIGHT_TOGGLE: '.drawer-right-toggle',
        BTN_DRAWER_LEFT: '.btn-drawer--left',
        MOBILE_NAV_BTN_CLOSE: '.rui-mobile-nav-btn-close',
        ADMIN_NAV_LINK: '.rui-nav--admin .nav-link',
        NAVBAR_LANG: '.rui-navbar-lang',
        MOBILE_NAV_CLASS: '.rui-mobile-nav'
    });

    const CSS_CLASSES = Object.freeze({
        THEME_DARK: 'theme-dark',
        DARK_MODE: 'dark-mode',
        MYCOURSES_ON: 'mycourses-on',
        MYCOURSES_HIDDEN_ON: 'mycourses-hidden-on',
        MYCOURSES_INPROGRESS_ON: 'mycourses-inprogress-on',
        DRAWER_OPEN_LEFT: 'drawer-open-left',
        DRAWER_COURSEINDEX_OPEN: 'drawer-courseindex--open',
        DRAWER_OPEN_INDEX_OPEN: 'drawer-open-index--open',
        OPENED: 'opened',
        SHOW: 'show',
        ACTIVE: 'active',
        RUI_EDIT_AREAS: 'rui-edit-areas',
        NO_SCROLL: 'no-scroll',
        INITIALIZING: 'rui-initializing',
        INITIALIZED: 'rui-initialized'
    });

    const DELAYS = Object.freeze({
        FOCUS_MANAGEMENT: 50,
        MUTATION_OBSERVER: 10,
        DRAWER_TOGGLE: 100,
        ARROW_NAVIGATION: 10,
        DEBOUNCE_DEFAULT: 250,
        RESIZE_DEBOUNCE: 100
    });

    const KEYBOARD_KEYS = Object.freeze({
        TAB: 'Tab',
        ENTER: 'Enter',
        ESCAPE: 'Escape',
        SPACE: ' ',
        ARROW_UP: 'ArrowUp',
        ARROW_DOWN: 'ArrowDown',
        ARROW_LEFT: 'ArrowLeft',
        ARROW_RIGHT: 'ArrowRight'
    });

    // Enhanced error handling and logging
    const Logger = {
        /**
         * Enhanced logging with context
         * @param {string} level - Log level (info, warn, error)
         * @param {string} message - Log message
         * @param {Object} context - Additional context
         */
        log(level, message, context = {}) {
            const timestamp = new Date().toISOString();
            const logMessage = `[Space Theme ${timestamp}] ${message}`;
            
            try {
                // CSP compliance check - avoid inline scripts
                if (context.html && typeof context.html === 'string') {
                    if (context.html.includes('<script') || context.html.includes('javascript:')) {
                        Logger.warn('Potential security risk detected in HTML content', { content: context.html });
                        return;
                    }
                }
                
                switch (level) {
                    case 'error':
                        console.error(logMessage, context);
                        break;
                    case 'warn':
                        console.warn(logMessage, context);
                        break;
                    default:
                        console.info(logMessage, context);
                }
            } catch (e) {
                // Fallback if console methods fail
                console.log(`${level.toUpperCase()}: ${logMessage}`, context);
            }
        },

        info(message, context) { this.log('info', message, context); },
        warn(message, context) { this.log('warn', message, context); },
        error(message, context) { this.log('error', message, context); }
    };

    /**
     * Security utilities for input validation and sanitization
     */
    const Security = {
        /**
         * Validate and sanitize HTML content
         * @param {string} content - HTML content to validate
         * @returns {boolean} - True if content is safe
         */
        validateHtmlContent(content) {
            if (typeof content !== 'string') return true;
            
            // Check for potentially dangerous content
            const dangerousPatterns = [
                /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
                /javascript:/gi,
                /on\w+\s*=/gi, // Event handlers
                /eval\s*\(/gi,
                /setTimeout\s*\([^)]*"[^"]*"/gi, // String-based setTimeout
                /setInterval\s*\([^)]*"[^"]*"/gi // String-based setInterval
            ];
            
            return !dangerousPatterns.some(pattern => pattern.test(content));
        },

        /**
         * Sanitize user input for safe DOM insertion
         * @param {string} input - User input to sanitize
         * @returns {string} - Sanitized input
         */
        sanitizeInput(input) {
            if (typeof input !== 'string') return '';
            
            return input
                .replace(/[<>]/g, '') // Remove angle brackets
                .replace(/javascript:/gi, '') // Remove javascript protocol
                .replace(/on\w+\s*=/gi, '') // Remove event handlers
                .trim();
        }
    };

    /**
     * Enhanced utility functions with better error handling and performance
     */
    const Utils = {
        // Cache for selectors to improve performance
        _selectorCache: new Map(),
        _resizeObserver: null,
        _mediaQueryLists: new Map(),

        /**
         * Enhanced element selector with caching and error handling
         * @param {string} selector - CSS selector
         * @param {boolean} useCache - Whether to use cache
         * @returns {jQuery} jQuery object
         */
        safeSelector(selector, useCache = true) {
            if (!selector || typeof selector !== 'string') {
                Logger.warn('Invalid selector provided', { selector });
                return $();
            }

            if (useCache && this._selectorCache.has(selector)) {
                const cached = this._selectorCache.get(selector);
                // Validate cached element still exists
                if (cached.length && document.contains(cached[0])) {
                    return cached;
                }
                this._selectorCache.delete(selector);
            }

            try {
                const element = $(selector);
                if (useCache && element.length) {
                    this._selectorCache.set(selector, element);
                }
                return element;
            } catch (error) {
                Logger.error(`Selector "${selector}" failed`, { error: error.message });
                return $();
            }
        },

        /**
         * Clear selector cache
         */
        clearCache() {
            this._selectorCache.clear();
        },

        /**
         * Enhanced debounce with immediate execution option
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in milliseconds
         * @param {boolean} immediate - Execute immediately on first call
         * @returns {Function} Debounced function
         */
        debounce(func, wait = DELAYS.DEBOUNCE_DEFAULT, immediate = false) {
            let timeout;
            let result;

            const debounced = function executedFunction(...args) {
                const later = () => {
                    timeout = null;
                    if (!immediate) result = func.apply(this, args);
                };

                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                
                if (callNow) result = func.apply(this, args);
                return result;
            };

            debounced.cancel = function() {
                clearTimeout(timeout);
                timeout = null;
            };

            return debounced;
        },

        /**
         * Enhanced throttle function
         * @param {Function} func - Function to throttle
         * @param {number} limit - Time limit in milliseconds
         * @returns {Function} Throttled function
         */
        throttle(func, limit = DELAYS.DEBOUNCE_DEFAULT) {
            let inThrottle;
            return function executedFunction(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Enhanced responsive utilities with ResizeObserver
         */
        isMobile() {
            return this.getViewportWidth() <= BREAKPOINTS.MOBILE;
        },

        isTablet() {
            return this.getViewportWidth() <= BREAKPOINTS.TABLET;
        },

        isDesktop() {
            return this.getViewportWidth() >= BREAKPOINTS.DESKTOP;
        },

        getViewportWidth() {
            return Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        },

        getScreenWidth() {
            return screen.width;
        },

        /**
         * Enhanced media query handling
         * @param {string} query - Media query string
         * @param {Function} callback - Callback function
         * @returns {Function} Cleanup function
         */
        onMediaChange(query, callback) {
            if (!window.matchMedia) {
                Logger.warn('matchMedia not supported');
                return () => {};
            }

            try {
                const mediaQuery = window.matchMedia(query);
                const handler = (e) => callback(e.matches, e);
                
                mediaQuery.addListener(handler);
                callback(mediaQuery.matches, mediaQuery); // Initial call
                
                this._mediaQueryLists.set(query, { mediaQuery, handler });
                
                return () => {
                    mediaQuery.removeListener(handler);
                    this._mediaQueryLists.delete(query);
                };
            } catch (error) {
                Logger.error('Media query setup failed', { query, error: error.message });
                return () => {};
            }
        },

        /**
         * Safe event handler attachment with automatic cleanup
         * @param {jQuery|string} element - Element or selector
         * @param {string} events - Event types
         * @param {Function} handler - Event handler
         * @param {Object} options - Additional options
         * @returns {Function} Cleanup function
         */
        safeEventHandler(element, events, handler, options = {}) {
            try {
                const $element = typeof element === 'string' ? this.safeSelector(element) : element;
                if (!$element.length) {
                    Logger.warn('Element not found for event handler', { element, events });
                    return () => {};
                }

                const wrappedHandler = (e) => {
                    try {
                        return handler.call(this, e);
                    } catch (error) {
                        Logger.error('Event handler error', { 
                            events, 
                            error: error.message,
                            stack: error.stack 
                        });
                    }
                };

                $element.on(events, options.selector || null, wrappedHandler);
                
                return () => {
                    $element.off(events, options.selector || null, wrappedHandler);
                };
            } catch (error) {
                Logger.error('Failed to attach event handler', { element, events, error: error.message });
                return () => {};
            }
        },

        /**
         * Cleanup all utilities
         */
        cleanup() {
            this.clearCache();
            this._mediaQueryLists.forEach(({ mediaQuery, handler }) => {
                mediaQuery.removeListener(handler);
            });
            this._mediaQueryLists.clear();
            
            if (this._resizeObserver) {
                this._resizeObserver.disconnect();
                this._resizeObserver = null;
            }
        },

        /**
         * Performance monitoring utilities
         */
        performance: {
            _marks: new Map(),
            
            /**
             * Start performance measurement
             * @param {string} name - Measurement name
             */
            mark(name) {
                if (window.performance && window.performance.mark) {
                    window.performance.mark(`space-theme-${name}-start`);
                    this._marks.set(name, Date.now());
                }
            },

            /**
             * End performance measurement and log result
             * @param {string} name - Measurement name
             */
            measure(name) {
                if (window.performance && window.performance.mark && this._marks.has(name)) {
                    const startTime = this._marks.get(name);
                    const endTime = Date.now();
                    const duration = endTime - startTime;
                    
                    window.performance.mark(`space-theme-${name}-end`);
                    
                    if (window.performance.measure) {
                        window.performance.measure(
                            `space-theme-${name}`,
                            `space-theme-${name}-start`,
                            `space-theme-${name}-end`
                        );
                    }
                    
                    Logger.info(`Performance: ${name} completed in ${duration}ms`);
                    this._marks.delete(name);
                    
                    return duration;
                }
                return 0;
            }
        },

        /**
         * Memory leak detection
         */
        memoryMonitor: {
            _initialMemory: null,
            
            /**
             * Start memory monitoring
             */
            start() {
                if (window.performance && window.performance.memory) {
                    this._initialMemory = {
                        used: window.performance.memory.usedJSHeapSize,
                        total: window.performance.memory.totalJSHeapSize,
                        limit: window.performance.memory.jsHeapSizeLimit
                    };
                }
            },

            /**
             * Check for potential memory leaks
             */
            check() {
                if (window.performance && window.performance.memory && this._initialMemory) {
                    const current = {
                        used: window.performance.memory.usedJSHeapSize,
                        total: window.performance.memory.totalJSHeapSize,
                        limit: window.performance.memory.jsHeapSizeLimit
                    };

                    const growth = current.used - this._initialMemory.used;
                    const growthMB = (growth / 1024 / 1024).toFixed(2);
                    
                    if (growth > 10 * 1024 * 1024) { // 10MB threshold
                        Logger.warn(`Potential memory leak detected: ${growthMB}MB growth`, {
                            initial: this._initialMemory,
                            current: current
                        });
                    }
                    
                    return { growth, growthMB, current };
                }
                return null;
            }
        }
    };

    /**
     * Enhanced input tracking with better keyboard detection
     */
    const InputTracker = {
        lastInteractionType: 'keyboard',
        keyboardKeys: Object.values(KEYBOARD_KEYS),
        _cleanupFunctions: [],

        init() {
            try {
                // Enhanced mouse/touch interaction tracking
                const mouseHandler = () => {
                    this.lastInteractionType = 'mouse';
                };

                // Enhanced keyboard interaction tracking
                const keyboardHandler = (e) => {
                    if (this.keyboardKeys.includes(e.key) || 
                        e.key.startsWith('Arrow') || 
                        /^[a-zA-Z0-9]$/.test(e.key)) {
                        this.lastInteractionType = 'keyboard';
                    }
                };

                // Touch interaction tracking
                const touchHandler = () => {
                    this.lastInteractionType = 'touch';
                };

                this._cleanupFunctions.push(
                    Utils.safeEventHandler($(document), 'mousedown click', mouseHandler),
                    Utils.safeEventHandler($(document), 'keydown', keyboardHandler),
                    Utils.safeEventHandler($(document), 'touchstart', touchHandler)
                );

                Logger.info('InputTracker initialized');
            } catch (error) {
                Logger.error('InputTracker initialization failed', { error: error.message });
            }
        },

        isLastInteractionKeyboard() {
            return this.lastInteractionType === 'keyboard';
        },

        cleanup() {
            this._cleanupFunctions.forEach(cleanup => cleanup());
            this._cleanupFunctions = [];
        }
    };

    /**
     * Enhanced preference toggle with better error handling and validation
     */
    const PreferenceToggle = {
        _activeToggles: new Map(),

        /**
         * Create enhanced preference toggle
         * @param {Object} config - Configuration object
         * @returns {Function} Cleanup function
         */
        create(config) {
            const {
                buttonSelector,
                bodyClass,
                htmlClass = null,
                checked = false,
                onActivate = null,
                onDeactivate = null,
                validateState = null
            } = config;

            if (!buttonSelector || !bodyClass) {
                Logger.error('PreferenceToggle: Missing required configuration', config);
                return () => {};
            }

            const button = Utils.safeSelector(buttonSelector);
            if (!button.length) {
                Logger.warn(`PreferenceToggle: Button not found: ${buttonSelector}`);
                return () => {};
            }

            const preference = button.attr('data-preference');
            if (!preference) {
                Logger.warn(`PreferenceToggle: No data-preference attribute found for: ${buttonSelector}`);
                return () => {};
            }

            const toggleId = `${buttonSelector}-${bodyClass}`;
            
            const clickHandler = async () => {
                try {
                    const isActive = $('body').hasClass(bodyClass);
                    const newState = !isActive;

                    // Validate state change if validator provided
                    if (validateState && !validateState(newState)) {
                        Logger.warn('PreferenceToggle: State change validation failed', { preference, newState });
                        return;
                    }
                    
                    // Update UI state
                    this._updateToggleState(button, bodyClass, htmlClass, checked, newState);
                    
                    // Save preference
                    await UserRepository.setUserPreference(preference, newState);
                    
                    // Execute callbacks
                    if (newState && onActivate) {
                        await onActivate();
                    } else if (!newState && onDeactivate) {
                        await onDeactivate();
                    }

                    Logger.info('PreferenceToggle: State updated', { preference, newState });
                } catch (error) {
                    Logger.error(`PreferenceToggle: Error toggling preference for ${buttonSelector}`, {
                        error: error.message,
                        preference
                    });
                    // Revert UI state on error
                    this._revertToggleState(button, bodyClass, htmlClass, checked);
                }
            };

            const cleanup = Utils.safeEventHandler(button, 'click', clickHandler);
            this._activeToggles.set(toggleId, { cleanup, button, preference });
            
            return cleanup;
        },

        /**
         * Update toggle state
         * @private
         */
        _updateToggleState(button, bodyClass, htmlClass, checked, isActive) {
            const $body = $('body');
            const $html = $('html');

            if (isActive) {
                $body.addClass(bodyClass);
                if (htmlClass) $html.addClass(htmlClass);
                button.addClass(CSS_CLASSES.ACTIVE);
                if (checked) button.attr('checked', 'true');
            } else {
                $body.removeClass(bodyClass);
                if (htmlClass) $html.removeClass(htmlClass);
                button.removeClass(CSS_CLASSES.ACTIVE);
                if (checked) button.attr('checked', 'false');
            }
        },

        /**
         * Revert toggle state on error
         * @private
         */
        _revertToggleState(button, bodyClass, htmlClass, checked) {
            const wasActive = button.hasClass(CSS_CLASSES.ACTIVE);
            this._updateToggleState(button, bodyClass, htmlClass, checked, !wasActive);
        },

        /**
         * Get active toggle by preference name
         * @param {string} preference - Preference name
         * @returns {Object|null} Toggle object
         */
        getToggle(preference) {
            for (const [id, toggle] of this._activeToggles) {
                if (toggle.preference === preference) {
                    return toggle;
                }
            }
            return null;
        },

        /**
         * Cleanup all toggles
         */
        cleanup() {
            this._activeToggles.forEach(({ cleanup }) => cleanup());
            this._activeToggles.clear();
        }
    };

    /**
     * Enhanced drawer management with better state tracking
     */
    const DrawerManager = {
        _state: {
            mobileNavOpen: false,
            leftDrawerOpen: false,
            rightDrawerOpen: false
        },
        _cleanupFunctions: [],

        init() {
            try {
                this.initDrawerToggles();
                this.initMobileNavigation();
                this.initResponsiveHandlers();
                Logger.info('DrawerManager initialized');
            } catch (error) {
                Logger.error('DrawerManager initialization failed', { error: error.message });
            }
        },

        initDrawerToggles() {
            // Enhanced drawer close handlers
            this._cleanupFunctions.push(
                Utils.safeEventHandler(
                    `${SELECTORS.DRAWER_COURSE_LEFT} ${SELECTORS.DRAWER_TOGGLE}`,
                    'click',
                    () => this.closeDrawers()
                )
            );

            // Mobile-specific drawer handlers
            if (Utils.isMobile()) {
                this._cleanupFunctions.push(
                    Utils.safeEventHandler(SELECTORS.DRAWER_TOGGLE, 'click', () => this.closeDrawers())
                );
            }

            // Right drawer toggle with responsive behavior
            this._cleanupFunctions.push(
                Utils.safeEventHandler(SELECTORS.DRAWER_RIGHT_TOGGLE, 'click', () => {
                    if (Utils.getScreenWidth() <= BREAKPOINTS.MOBILE) {
                        this.closeDrawers();
                    }
                })
            );

            // Left drawer button with state tracking
            this._cleanupFunctions.push(
                Utils.safeEventHandler(SELECTORS.BTN_DRAWER_LEFT, 'click', () => this.openLeftDrawer())
            );
        },

        initMobileNavigation() {
            const mobileNavSelector = `${SELECTORS.MOBILE_NAV}, ${SELECTORS.MOBILE_NAV_BTN_CLOSE}`;
            this._cleanupFunctions.push(
                Utils.safeEventHandler(mobileNavSelector, 'click', () => this.toggleMobileNav())
            );
        },

        initResponsiveHandlers() {
            // Handle responsive breakpoint changes
            const cleanup = Utils.onMediaChange(`(max-width: ${BREAKPOINTS.MOBILE}px)`, (matches) => {
                if (!matches) {
                    // Desktop view - ensure mobile nav is closed
                    this.closeMobileNav();
                }
            });
            this._cleanupFunctions.push(cleanup);
        },

        openLeftDrawer() {
            $('body')
                .addClass(CSS_CLASSES.DRAWER_COURSEINDEX_OPEN)
                .addClass(CSS_CLASSES.DRAWER_OPEN_INDEX_OPEN);
            this._state.leftDrawerOpen = true;
            this._announceStateChange('Left drawer opened');
        },

        closeDrawers() {
            $('body')
                .removeClass(CSS_CLASSES.DRAWER_COURSEINDEX_OPEN)
                .removeClass(CSS_CLASSES.DRAWER_OPEN_INDEX_OPEN);
            this._state.leftDrawerOpen = false;
            this._state.rightDrawerOpen = false;
            this._announceStateChange('Drawers closed');
        },

        toggleMobileNav() {
            const topBar = Utils.safeSelector(SELECTORS.TOP_BAR);
            const isOpen = topBar.hasClass(CSS_CLASSES.OPENED);
            
            topBar.toggleClass(CSS_CLASSES.OPENED);
            this._state.mobileNavOpen = !isOpen;
            
            this._announceStateChange(isOpen ? 'Mobile navigation closed' : 'Mobile navigation opened');
        },

        closeMobileNav() {
            Utils.safeSelector(SELECTORS.TOP_BAR).removeClass(CSS_CLASSES.OPENED);
            this._state.mobileNavOpen = false;
        },

        /**
         * Announce state changes for screen readers
         * @private
         */
        _announceStateChange(message) {
            // Create temporary announcement for screen readers
            const announcement = $(`<div class="sr-only" aria-live="polite">${message}</div>`);
            $('body').append(announcement);
            setTimeout(() => announcement.remove(), 1000);
        },

        getState() {
            return { ...this._state };
        },

        cleanup() {
            this._cleanupFunctions.forEach(cleanup => cleanup());
            this._cleanupFunctions = [];
        }
    };

    /**
     * Enhanced UI utilities with better performance
     */
    const UIEnhancements = {
        _cleanupFunctions: [],

        init() {
            try {
                this.initBlockAreaToggle();
                this.initAdminNavFormatting();
                this.initGeneralEnhancements();
                Logger.info('UIEnhancements initialized');
            } catch (error) {
                Logger.error('UIEnhancements initialization failed', { error: error.message });
            }
        },

        initBlockAreaToggle() {
            this._cleanupFunctions.push(
                Utils.safeEventHandler(SELECTORS.SHOW_BLOCK_AREA, 'click', () => {
                    const $body = $('body');
                    const $button = Utils.safeSelector(SELECTORS.SHOW_BLOCK_AREA);
                    
                    $body.toggleClass(CSS_CLASSES.RUI_EDIT_AREAS);
                    $button.toggleClass(CSS_CLASSES.ACTIVE);
                    
                    const isActive = $button.hasClass(CSS_CLASSES.ACTIVE);
                    $button.attr('aria-pressed', isActive.toString());
                })
            );
        },

        initAdminNavFormatting() {
            const adminLinks = Utils.safeSelector(SELECTORS.ADMIN_NAV_LINK);
            if (adminLinks.length) {
                adminLinks.each(function() {
                    const $this = $(this);
                    let text = $this.html();
                    if (text.includes('(') && text.includes(')')) {
                        text = text.replace('(', '<span class="mt-1 small d-block">');
                        text = text.replace(')', '</span>');
                        $this.html(text);
                    }
                });
            }
        },

        initGeneralEnhancements() {
            // Add loading states
            $('body').addClass(CSS_CLASSES.INITIALIZING);
            
            // Remove loading state after initialization
            setTimeout(() => {
                $('body')
                    .removeClass(CSS_CLASSES.INITIALIZING)
                    .addClass(CSS_CLASSES.INITIALIZED);
            }, 100);

            // Enhanced button states
            $('button, .btn').each(function() {
                const $btn = $(this);
                if (!$btn.attr('aria-pressed') && $btn.hasClass('toggle')) {
                    $btn.attr('aria-pressed', 'false');
                }
            });
        },

        cleanup() {
            this._cleanupFunctions.forEach(cleanup => cleanup());
            this._cleanupFunctions = [];
        }
    };

    /**
     * Focus management for accessibility
     */
    const FocusManager = {
        /**
         * Create a focus management handler for drawers
         */
        createDrawerFocusHandler: function(config) {
            const {
                isOpenCheck,
                containerSelector,
                closeButtonSelector
            } = config;

            let lastFocusedElement = null;
            let focusTimeout = null;

            const isElementInContainer = (element) => {
                return $(element).closest(containerSelector).length > 0;
            };

            const handleFocusManagement = () => {
                if (!isOpenCheck()) return;

                const currentFocused = $(document.activeElement);
                
                if (lastFocusedElement && 
                    isElementInContainer(lastFocusedElement) && 
                    !isElementInContainer(currentFocused[0]) &&
                    currentFocused[0] !== document.body &&
                    InputTracker.isLastInteractionKeyboard()) {
                    
                    clearTimeout(focusTimeout);
                    focusTimeout = setTimeout(() => {
                        const closeBtn = Utils.safeSelector(closeButtonSelector);
                        if (closeBtn.length > 0) {
                            closeBtn.trigger('click');
                        }
                    }, DELAYS.FOCUS_MANAGEMENT);
                }

                if (isElementInContainer(currentFocused[0])) {
                    lastFocusedElement = currentFocused[0];
                }
            };

            // Event listeners
            $(document).on('focusin', handleFocusManagement);
            $(document).on('keydown', (e) => {
                if (e.key === 'Tab') {
                    setTimeout(handleFocusManagement, DELAYS.MUTATION_OBSERVER);
                }
            });

            return {
                reset: () => {
                    lastFocusedElement = null;
                    clearTimeout(focusTimeout);
                },
                handleFocusManagement
            };
        }
    };

    /**
     * Advanced focus trap for mobile navigation
     */
    const TopBarFocusTrap = {
        isActive: false,
        focusableElements: null,
        firstFocusableElement: null,
        lastFocusableElement: null,
        previouslyFocusedElement: null,
        lastKeyWasTab: false,
        lastKeyWasArrow: false,

        /**
         * Get all focusable elements within topBar
         */
        getFocusableElements: function() {
            const focusableSelectors = [
                'a[href]:not([disabled])',
                'button:not([disabled])',
                'textarea:not([disabled])',
                'input[type="text"]:not([disabled])',
                'input[type="radio"]:not([disabled])',
                'input[type="checkbox"]:not([disabled])',
                'select:not([disabled])',
                '[tabindex]:not([tabindex="-1"]):not([disabled])',
                '[contenteditable]:not([disabled])'
            ].join(',');

            this.focusableElements = Utils.safeSelector(SELECTORS.TOP_BAR)
                .find(focusableSelectors).filter(':visible');
            this.firstFocusableElement = this.focusableElements.first();
            this.lastFocusableElement = this.focusableElements.last();
        },

        /**
         * Activate the focus trap
         */
        activate: function() {
            if (this.isActive) return;
            
            this.isActive = true;
            this.previouslyFocusedElement = $(document.activeElement);
            this.getFocusableElements();

            // Add accessibility attributes and prevent scrolling
            $('.rui-navbar-lang, .rui-mobile-nav').attr('aria-hidden', 'true');
            $('body').addClass(CSS_CLASSES.NO_SCROLL);

            // Focus the first focusable element
            if (this.firstFocusableElement.length > 0) {
                this.firstFocusableElement.focus();
            }
        },

        /**
         * Deactivate the focus trap
         */
        deactivate: function() {
            if (!this.isActive) return;
            
            this.isActive = false;
            
            // Remove accessibility attributes and restore scrolling
            $('.rui-navbar-lang, .rui-mobile-nav').removeAttr('aria-hidden');
            $('body').removeClass(CSS_CLASSES.NO_SCROLL);
            
            // Return focus to previously focused element
            if (this.previouslyFocusedElement && this.previouslyFocusedElement.length > 0) {
                this.previouslyFocusedElement.focus();
            }
        },

        /**
         * Handle tab key navigation
         */
        handleTabKey: function(e) {
            if (!this.isActive) return;

            this.getFocusableElements();
            if (this.focusableElements.length === 0) return;

            if (e.shiftKey) {
                if ($(document.activeElement).is(this.firstFocusableElement)) {
                    e.preventDefault();
                    this.lastFocusableElement.focus();
                }
            } else {
                if ($(document.activeElement).is(this.lastFocusableElement)) {
                    e.preventDefault();
                    this.firstFocusableElement.focus();
                }
            }
        },

        /**
         * Handle escape key
         */
        handleEscapeKey: function() {
            if (!this.isActive) return;
            
            const closeBtn = Utils.safeSelector(SELECTORS.MOBILE_BTN_CLOSE);
            if (closeBtn.length > 0) {
                closeBtn.trigger('click');
            }
        },

        /**
         * Detect if screen reader is likely being used
         */
        detectScreenReader: function() {
            return !!(
                window.speechSynthesis ||
                navigator.userAgent.includes('NVDA') ||
                document.querySelector('[aria-live]') ||
                window.matchMedia('(prefers-contrast: high)').matches ||
                window.matchMedia('(-ms-high-contrast: active)').matches
            );
        },

        /**
         * Handle arrow key navigation for screen readers
         */
        handleArrowNavigation: function(e) {
            if (!this.isActive) return;

            const isArrowKey = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key);
            
            if (isArrowKey) {
                this.lastKeyWasArrow = true;
                this.lastKeyWasTab = false;
                
                setTimeout(() => {
                    const currentFocus = $(document.activeElement);
                    
                    if (!currentFocus.closest(SELECTORS.TOP_BAR).length && 
                        currentFocus[0] !== document.body &&
                        this.lastKeyWasArrow) {
                        
                        this.deactivate();
                        const closeBtn = Utils.safeSelector(SELECTORS.MOBILE_BTN_CLOSE);
                        if (closeBtn.length > 0) {
                            closeBtn.trigger('click');
                        }
                    }
                }, DELAYS.ARROW_NAVIGATION);
            }
        },

        /**
         * Enhanced focus change handler
         */
        handleEnhancedFocusChange: function(e) {
            if (!this.isActive) return;

            const target = $(e.target);
            const isScreenReaderLikely = this.detectScreenReader();
            
            if (!target.closest(SELECTORS.TOP_BAR).length && 
                !target.is(SELECTORS.MOBILE_BTN_CLOSE) && 
                e.target !== document.body) {
                
                if ((isScreenReaderLikely || this.lastKeyWasArrow || !this.lastKeyWasTab) && 
                    InputTracker.isLastInteractionKeyboard()) {
                    this.deactivate();
                    const closeBtn = Utils.safeSelector(SELECTORS.MOBILE_BTN_CLOSE);
                    if (closeBtn.length > 0) {
                        closeBtn.trigger('click');
                    }
                    return;
                }
                
                if (this.lastKeyWasTab && InputTracker.isLastInteractionKeyboard()) {
                    e.preventDefault();
                    this.getFocusableElements();
                    if (this.firstFocusableElement.length > 0) {
                        this.firstFocusableElement.focus();
                    }
                }
            }
        },

        /**
         * Initialize focus trap
         */
        init: function() {
            // Monitor topBar state changes
            const topBarElement = document.getElementById('topBar');
            if (topBarElement) {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const topBar = Utils.safeSelector(SELECTORS.TOP_BAR);
                            if (topBar.hasClass(CSS_CLASSES.OPENED) && !this.isActive) {
                                setTimeout(() => this.activate(), DELAYS.DRAWER_TOGGLE);
                            } else if (!topBar.hasClass(CSS_CLASSES.OPENED) && this.isActive) {
                                this.deactivate();
                            }
                        }
                    });
                });

                observer.observe(topBarElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            // Global event handlers
            $(document).on('keydown', (e) => {
                if (!this.isActive) return;

                this.lastKeyWasTab = (e.key === 'Tab');

                switch(e.key) {
                    case 'Tab':
                        this.handleTabKey(e);
                        break;
                    case 'Escape':
                        e.preventDefault();
                        this.handleEscapeKey();
                        break;
                    case 'ArrowUp':
                    case 'ArrowDown':
                    case 'ArrowLeft':
                    case 'ArrowRight':
                        this.handleArrowNavigation(e);
                        break;
                }
            });

            $(document).on('focusin', (e) => {
                this.handleEnhancedFocusChange(e);
            });

            $(document).on('click', SELECTORS.MOBILE_BTN_CLOSE + ', .rui-mobile-nav-btn-close', () => {
                if (this.isActive) {
                    this.deactivate();
                }
            });
        }
    };

    /**
     * ARIA Hidden Manager for accessibility
     */
    const AriaHiddenManager = {
        /**
         * Check if any drawer/navigation is currently open
         */
        isAnyDrawerOpen: function() {
            return $('body').hasClass(CSS_CLASSES.DRAWER_OPEN_LEFT) ||
                   Utils.safeSelector(SELECTORS.TOP_BAR).hasClass(CSS_CLASSES.OPENED) ||
                   Utils.safeSelector(SELECTORS.SPACE_DRAWERS_BLOCKS).hasClass(CSS_CLASSES.SHOW);
        },

        /**
         * Update aria-hidden state for main content
         */
        updateAriaHidden: function() {
            const shouldHide = this.isAnyDrawerOpen();
            const targets = [SELECTORS.PAGE, SELECTORS.EDITING_SWITCH_FORM];
            
            targets.forEach(selector => {
                const element = Utils.safeSelector(selector);
                if (element.length) {
                    if (shouldHide) {
                        element.attr('aria-hidden', 'true');
                    } else {
                        element.removeAttr('aria-hidden');
                    }
                }
            });
        },

        /**
         * Initialize ARIA hidden management
         */
        init: function() {
            const debouncedUpdate = Utils.debounce(() => {
                this.updateAriaHidden();
            }, DELAYS.MUTATION_OBSERVER);

            // Monitor elements for class changes
            const elementsToWatch = [
                { element: document.body, attribute: 'class' },
                { element: document.getElementById('topBar'), attribute: 'class' },
                { element: document.getElementById('space-drawers-blocks'), attribute: 'class' }
            ];

            elementsToWatch.forEach(({ element, attribute }) => {
                if (element) {
                    const observer = new MutationObserver(debouncedUpdate);
                    observer.observe(element, {
                        attributes: true,
                        attributeFilter: [attribute]
                    });
                }
            });

            // Manual toggle events
            const toggleSelectors = [
                SELECTORS.MAIN_NAV,
                '.btn-drawer--left',
                SELECTORS.MOBILE_BTN_CLOSE,
                '.rui-mobile-nav-btn-close',
                '#drawerCloseButtonspace-drawers-blocks'
            ];

            $(document).on('click', toggleSelectors.join(', '), () => {
                setTimeout(debouncedUpdate, DELAYS.DRAWER_TOGGLE);
            });

            // Initial check
            this.updateAriaHidden();
        }
    };

    /**
     * Enhanced main initialization with better error recovery
     */
    const SpaceTheme = {
        _initialized: false,
        _components: [],
        _cleanupFunctions: [],

        /**
         * Enhanced initialization with comprehensive error handling
         */
        async init() {
            if (this._initialized) {
                Logger.warn('SpaceTheme already initialized');
                return;
            }

            try {
                Logger.info('Initializing Space Theme');
                
                // Start performance monitoring
                Utils.performance.mark('theme-init');
                Utils.memoryMonitor.start();
                
                // Initialize core utilities first
                InputTracker.init();
                this._components.push(InputTracker);

                // Dark Mode Toggle Configuration
                const DarkModeBtn = $(document.getElementById("darkModeBtn"));
                const DarkModePreference = DarkModeBtn.attr('data-preference');
                
                DarkModeBtn.on('click', function() {
                    const IsDarkMode = $('body').hasClass('theme-dark');
                    $('body').toggleClass('theme-dark', !IsDarkMode);
                    $('html').attr('data-bs-theme', !IsDarkMode ? 'dark' : 'light');
                    DarkModeBtn.toggleClass('active', !IsDarkMode);
                    UserRepository.setUserPreference(DarkModePreference, !IsDarkMode);
                });

                // Initialize preference toggles with enhanced config
                const toggleConfigs = [
                    {
                        buttonSelector: SELECTORS.MY_COURSES_BTN,
                        bodyClass: CSS_CLASSES.MYCOURSES_ON
                    },
                    {
                        buttonSelector: SELECTORS.MY_COURSES_HIDDEN,
                        bodyClass: CSS_CLASSES.MYCOURSES_HIDDEN_ON,
                        checked: true
                    },
                    {
                        buttonSelector: SELECTORS.MY_COURSES_INPROGRESS,
                        bodyClass: CSS_CLASSES.MYCOURSES_INPROGRESS_ON,
                        checked: true
                    }
                ];

                toggleConfigs.forEach(config => {
                    this._cleanupFunctions.push(PreferenceToggle.create(config));
                });

                // Initialize components
                const components = [DrawerManager, UIEnhancements];
                
                for (const component of components) {
                    try {
                        await component.init();
                        this._components.push(component);
                    } catch (error) {
                        Logger.error(`Failed to initialize component: ${component.constructor.name}`, {
                            error: error.message
                        });
                    }
                }

                // Initialize accessibility features (keeping existing complex code)
                this.initAccessibilityFeatures();

                // Setup global error handler
                this.setupGlobalErrorHandler();

                // Setup cleanup on page unload
                this.setupCleanup();

                this._initialized = true;
                Logger.info('Space Theme initialization completed successfully');

                // Dispatch custom event for other scripts
                $(document).trigger('spaceThemeInitialized', { theme: this });

                // Complete performance monitoring
                const initTime = Utils.performance.measure('theme-init');
                Logger.info(`Theme initialization completed in ${initTime}ms`);
                
                // Set up periodic memory monitoring (every 30 seconds in development)
                if (window.location.hostname === 'localhost' || window.location.hostname.includes('dev')) {
                    setInterval(() => {
                        const memoryInfo = Utils.memoryMonitor.check();
                        if (memoryInfo) {
                            Logger.info(`Memory usage: ${memoryInfo.growthMB}MB growth since init`);
                        }
                    }, 30000);
                }

            } catch (error) {
                Logger.error('Space Theme initialization failed', { 
                    error: error.message,
                    stack: error.stack 
                });
                this.handleInitializationError(error);
            }
        },

        initAccessibilityFeatures() {
            // Initialize existing accessibility features
            TopBarFocusTrap.init();
            AriaHiddenManager.init();

            // Initialize focus handlers with enhanced error handling
            try {
                const navDrawerFocusHandler = FocusManager.createDrawerFocusHandler({
                    isOpenCheck: () => $('body').hasClass(CSS_CLASSES.DRAWER_OPEN_LEFT),
                    containerSelector: SELECTORS.NAV_DRAWER,
                    closeButtonSelector: SELECTORS.MAIN_NAV
                });

                const blocksDrawerFocusHandler = FocusManager.createDrawerFocusHandler({
                    isOpenCheck: () => Utils.safeSelector(SELECTORS.SPACE_DRAWERS_BLOCKS).hasClass(CSS_CLASSES.SHOW),
                    containerSelector: SELECTORS.SPACE_DRAWERS_BLOCKS,
                    closeButtonSelector: '#drawerCloseButtonspace-drawers-blocks'
                });

                // Setup mutation observers with error handling
                this.setupMutationObservers(navDrawerFocusHandler, blocksDrawerFocusHandler);
                
            } catch (error) {
                Logger.error('Failed to initialize accessibility features', { error: error.message });
            }
        },

        setupMutationObservers(navDrawerFocusHandler, blocksDrawerFocusHandler) {
            try {
                // Body observer for drawer state changes
                const bodyObserver = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            if (!$('body').hasClass(CSS_CLASSES.DRAWER_OPEN_LEFT)) {
                                navDrawerFocusHandler.reset();
                            }
                        }
                    });
                });

                bodyObserver.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });

                // Blocks drawer observer
                const blocksElement = document.getElementById('space-drawers-blocks');
                if (blocksElement) {
                    const blocksObserver = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (!Utils.safeSelector(SELECTORS.SPACE_DRAWERS_BLOCKS).hasClass(CSS_CLASSES.SHOW)) {
                                    blocksDrawerFocusHandler.reset();
                                }
                            }
                        });
                    });

                    blocksObserver.observe(blocksElement, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                }

                // Setup drawer toggle handlers
                this.setupDrawerToggleHandlers(navDrawerFocusHandler, blocksDrawerFocusHandler);

            } catch (error) {
                Logger.error('Failed to setup mutation observers', { error: error.message });
            }
        },

        setupDrawerToggleHandlers(navDrawerFocusHandler, blocksDrawerFocusHandler) {
            this._cleanupFunctions.push(
                Utils.safeEventHandler(`${SELECTORS.MAIN_NAV}, ${SELECTORS.BTN_DRAWER_LEFT}`, 'click', () => {
                    setTimeout(() => {
                        if (!$('body').hasClass(CSS_CLASSES.DRAWER_OPEN_LEFT)) {
                            navDrawerFocusHandler.reset();
                        }
                    }, DELAYS.DRAWER_TOGGLE);
                }),

                Utils.safeEventHandler('#drawerCloseButtonspace-drawers-blocks', 'click', () => {
                    setTimeout(() => {
                        if (!Utils.safeSelector(SELECTORS.SPACE_DRAWERS_BLOCKS).hasClass(CSS_CLASSES.SHOW)) {
                            blocksDrawerFocusHandler.reset();
                        }
                    }, DELAYS.DRAWER_TOGGLE);
                })
            );
        },

        setupGlobalErrorHandler() {
            window.addEventListener('error', (event) => {
                Logger.error('Global JavaScript error', {
                    message: event.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno,
                    stack: event.error?.stack
                });
            });

            window.addEventListener('unhandledrejection', (event) => {
                Logger.error('Unhandled promise rejection', {
                    reason: event.reason,
                    stack: event.reason?.stack
                });
            });
        },

        setupCleanup() {
            const cleanup = () => {
                Logger.info('Cleaning up Space Theme');
                this.cleanup();
            };

            $(window).on('beforeunload', cleanup);
            this._cleanupFunctions.push(() => $(window).off('beforeunload', cleanup));
        },

        handleInitializationError(error) {
            // Fallback initialization for critical features
            try {
                Logger.info('Attempting fallback initialization');
                
                // Basic toggle functionality
                $(SELECTORS.DARK_MODE_BTN).on('click', function() {
                    $('body').toggleClass(CSS_CLASSES.THEME_DARK);
                    $('html').toggleClass(CSS_CLASSES.DARK_MODE);
                });

                Logger.info('Fallback initialization completed');
            } catch (fallbackError) {
                Logger.error('Fallback initialization also failed', { 
                    error: fallbackError.message 
                });
            }
        },

        /**
         * Comprehensive cleanup
         */
        cleanup() {
            try {
                // Cleanup all registered functions
                this._cleanupFunctions.forEach(cleanup => {
                    try {
                        cleanup();
                    } catch (error) {
                        Logger.warn('Cleanup function failed', { error: error.message });
                    }
                });

                // Cleanup components
                this._components.forEach(component => {
                    if (component.cleanup) {
                        try {
                            component.cleanup();
                        } catch (error) {
                            Logger.warn(`Component cleanup failed: ${component.constructor.name}`, { 
                                error: error.message 
                            });
                        }
                    }
                });

                // Cleanup utilities
                Utils.cleanup();
                PreferenceToggle.cleanup();

                this._cleanupFunctions = [];
                this._components = [];
                this._initialized = false;

                Logger.info('Space Theme cleanup completed');
            } catch (error) {
                Logger.error('Cleanup failed', { error: error.message });
            }
        },

        /**
         * Get theme status
         */
        getStatus() {
            return {
                initialized: this._initialized,
                components: this._components.map(c => c.constructor.name),
                cleanupFunctions: this._cleanupFunctions.length
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        SpaceTheme.init();
    });

    // Export for potential external use
    return SpaceTheme;
});
/* jshint ignore:end */
