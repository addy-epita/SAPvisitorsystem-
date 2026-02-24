/**
 * SAP Visitor Management System - Kiosk JavaScript
 * Touch-friendly interactions and form handling
 */

(function() {
    'use strict';

    // ============================================
    // I18N - TRANSLATIONS
    // ============================================
    const translations = {
        fr: {
            // Navigation
            arrival: 'Arrivée',
            departure: 'Sortie',
            back: 'Retour',
            cancel: 'Annuler',
            confirm: 'Confirmer',
            submit: 'Valider',
            fullscreen: 'Plein écran',
            exitFullscreen: 'Quitter le plein écran',

            // Form labels
            firstName: 'Prénom',
            lastName: 'Nom',
            company: 'Société',
            reason: 'Motif de visite',
            host: 'Hôte',
            hostEmail: 'Email de l\'hôte',
            visitorEmail: 'Email visiteur (optionnel)',
            duration: 'Durée prévue',
            selectHost: 'Sélectionnez un hôte...',
            otherHost: 'Autre (saisir email)',

            // Duration options
            duration2h: '2 heures',
            duration3h: '3 heures',
            duration4h: '4 heures',
            duration6h: '6 heures',
            duration8h: '8 heures',

            // Validation messages
            required: 'Ce champ est obligatoire',
            invalidEmail: 'Veuillez saisir un email valide',
            minLength: 'Minimum {min} caractères requis',
            success: 'Enregistrement réussi !',
            error: 'Une erreur s\'est produite. Veuillez réessayer.',

            // Confirmation
            checkinSuccess: 'Bienvenue ! Votre arrivée a été enregistrée.',
            checkoutSuccess: 'Au revoir ! N\'oubliez pas de rendre votre badge.',
            hostNotified: 'Votre hôte a été notifié de votre arrivée.',

            // Site
            siteName: 'Système de Gestion des Visiteurs',
            welcome: 'Bienvenue chez SAP',
            instruction: 'Veuillez sélectionner votre action'
        },
        en: {
            // Navigation
            arrival: 'Check-in',
            departure: 'Check-out',
            back: 'Back',
            cancel: 'Cancel',
            confirm: 'Confirm',
            submit: 'Submit',
            fullscreen: 'Fullscreen',
            exitFullscreen: 'Exit Fullscreen',

            // Form labels
            firstName: 'First Name',
            lastName: 'Last Name',
            company: 'Company',
            reason: 'Reason for visit',
            host: 'Host',
            hostEmail: 'Host Email',
            visitorEmail: 'Visitor Email (optional)',
            duration: 'Expected Duration',
            selectHost: 'Select a host...',
            otherHost: 'Other (enter email)',

            // Duration options
            duration2h: '2 hours',
            duration3h: '3 hours',
            duration4h: '4 hours',
            duration6h: '6 hours',
            duration8h: '8 hours',

            // Validation messages
            required: 'This field is required',
            invalidEmail: 'Please enter a valid email',
            minLength: 'Minimum {min} characters required',
            success: 'Registration successful!',
            error: 'An error occurred. Please try again.',

            // Confirmation
            checkinSuccess: 'Welcome! Your arrival has been recorded.',
            checkoutSuccess: 'Goodbye! Please return your badge.',
            hostNotified: 'Your host has been notified of your arrival.',

            // Site
            siteName: 'Visitor Management System',
            welcome: 'Welcome to SAP',
            instruction: 'Please select your action'
        }
    };

    let currentLang = localStorage.getItem('vms_lang') || 'fr';

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================

    function $(selector) {
        return document.querySelector(selector);
    }

    function $$(selector) {
        return document.querySelectorAll(selector);
    }

    function t(key, replacements = {}) {
        const text = translations[currentLang][key] || translations['fr'][key] || key;
        return text.replace(/\{(\w+)\}/g, (match, name) => replacements[name] || match);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ============================================
    // LANGUAGE TOGGLE
    // ============================================

    function initLanguageToggle() {
        const langToggle = $('#langToggle');
        if (!langToggle) return;

        updateLanguageUI();

        langToggle.addEventListener('click', () => {
            currentLang = currentLang === 'fr' ? 'en' : 'fr';
            localStorage.setItem('vms_lang', currentLang);
            updateLanguageUI();
            updatePageTranslations();
        });
    }

    function updateLanguageUI() {
        const langIcon = $('#langIcon');
        const langText = $('#langText');

        if (langIcon) {
            langIcon.textContent = currentLang === 'fr' ? '🇫🇷' : '🇬🇧';
        }
        if (langText) {
            langText.textContent = currentLang.toUpperCase();
        }

        document.documentElement.lang = currentLang;
    }

    function updatePageTranslations() {
        const elements = $$('[data-i18n]');
        elements.forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (key) {
                el.textContent = t(key);
            }
        });

        // Update placeholders
        const inputs = $$('[data-i18n-placeholder]');
        inputs.forEach(input => {
            const key = input.getAttribute('data-i18n-placeholder');
            if (key) {
                input.placeholder = t(key);
            }
        });
    }

    // ============================================
    // FULLSCREEN TOGGLE
    // ============================================

    function initFullscreenToggle() {
        const fullscreenToggle = $('#fullscreenToggle');
        if (!fullscreenToggle) return;

        fullscreenToggle.addEventListener('click', toggleFullscreen);

        // Update button text when fullscreen changes
        document.addEventListener('fullscreenchange', updateFullscreenButton);
        document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
        document.addEventListener('mozfullscreenchange', updateFullscreenButton);
        document.addEventListener('MSFullscreenChange', updateFullscreenButton);
    }

    function toggleFullscreen() {
        const doc = document;
        const docEl = document.documentElement;

        if (!doc.fullscreenElement &&
            !doc.webkitFullscreenElement &&
            !doc.mozFullScreenElement &&
            !doc.msFullscreenElement) {
            // Enter fullscreen
            if (docEl.requestFullscreen) {
                docEl.requestFullscreen();
            } else if (docEl.webkitRequestFullscreen) {
                docEl.webkitRequestFullscreen();
            } else if (docEl.mozRequestFullScreen) {
                docEl.mozRequestFullScreen();
            } else if (docEl.msRequestFullscreen) {
                docEl.msRequestFullscreen();
            }
        } else {
            // Exit fullscreen
            if (doc.exitFullscreen) {
                doc.exitFullscreen();
            } else if (doc.webkitExitFullscreen) {
                doc.webkitExitFullscreen();
            } else if (doc.mozCancelFullScreen) {
                doc.mozCancelFullScreen();
            } else if (doc.msExitFullscreen) {
                doc.msExitFullscreen();
            }
        }
    }

    function updateFullscreenButton() {
        const fullscreenToggle = $('#fullscreenToggle');
        if (!fullscreenToggle) return;

        const isFullscreen = document.fullscreenElement ||
                            document.webkitFullscreenElement ||
                            document.mozFullScreenElement ||
                            document.msFullscreenElement;

        const span = fullscreenToggle.querySelector('span');
        if (span) {
            span.textContent = isFullscreen ? t('exitFullscreen') : t('fullscreen');
        }
    }

    // ============================================
    // CLOCK
    // ============================================

    function initClock() {
        const clock = $('#clock');
        if (!clock) return;

        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString(currentLang === 'fr' ? 'fr-FR' : 'en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
            const dateString = now.toLocaleDateString(currentLang === 'fr' ? 'fr-FR' : 'en-US', {
                weekday: 'short',
                day: 'numeric',
                month: 'short'
            });
            clock.innerHTML = `<span class="font-semibold">${timeString}</span> <span class="text-gray-400">|</span> ${dateString}`;
        }

        updateClock();
        setInterval(updateClock, 1000);
    }

    // ============================================
    // FORM VALIDATION
    // ============================================

    function initFormValidation() {
        const forms = $$('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', handleFormSubmit);

            // Real-time validation on blur
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', debounce(() => {
                    if (input.classList.contains('input-invalid')) {
                        validateField(input);
                    }
                }, 300));
            });
        });
    }

    function validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.required;
        const minLength = parseInt(field.getAttribute('minlength')) || 0;

        let isValid = true;
        let errorMessage = '';

        // Required check
        if (required && !value) {
            isValid = false;
            errorMessage = t('required');
        }

        // Email validation
        if (isValid && value && type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = t('invalidEmail');
            }
        }

        // Min length validation
        if (isValid && value && minLength > 0 && value.length < minLength) {
            isValid = false;
            errorMessage = t('minLength', { min: minLength });
        }

        // Update UI
        updateFieldValidationUI(field, isValid, errorMessage);

        return isValid;
    }

    function updateFieldValidationUI(field, isValid, errorMessage) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        const errorEl = formGroup.querySelector('.error-message');

        if (isValid) {
            field.classList.remove('input-invalid');
            field.classList.add('input-valid');
            if (errorEl) {
                errorEl.style.display = 'none';
            }
        } else {
            field.classList.remove('input-valid');
            field.classList.add('input-invalid');
            if (errorEl) {
                errorEl.textContent = errorMessage;
                errorEl.style.display = 'flex';
            }
        }
    }

    function validateForm(form) {
        const fields = form.querySelectorAll('input, select, textarea');
        let isValid = true;

        fields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    function handleFormSubmit(e) {
        const form = e.target;

        if (!validateForm(form)) {
            e.preventDefault();
            // Focus first invalid field
            const firstInvalid = form.querySelector('.input-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            setButtonLoading(submitBtn, true);
        }
    }

    // ============================================
    // BUTTON STATES
    // ============================================

    function setButtonLoading(button, loading) {
        if (loading) {
            button.classList.add('btn-loading');
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="loading-spinner"></span>';
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.innerHTML = button.dataset.originalText || button.textContent;
            button.disabled = false;
        }
    }

    // ============================================
    // HOST DROPDOWN HANDLING
    // ============================================

    function initHostDropdown() {
        const hostSelect = $('#hostSelect');
        const hostEmailInput = $('#hostEmail');

        if (!hostSelect || !hostEmailInput) return;

        hostSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const email = selectedOption.dataset.email;
            const isOther = this.value === 'other';

            if (isOther) {
                hostEmailInput.value = '';
                hostEmailInput.readOnly = false;
                hostEmailInput.placeholder = t('hostEmail');
                hostEmailInput.focus();
            } else if (email) {
                hostEmailInput.value = email;
                hostEmailInput.readOnly = true;
            } else {
                hostEmailInput.value = '';
                hostEmailInput.readOnly = true;
            }
        });
    }

    // ============================================
    // TOUCH FEEDBACK
    // ============================================

    function initTouchFeedback() {
        const touchElements = $$('.btn-kiosk, .kiosk-tile, button, a');

        touchElements.forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            }, { passive: true });

            el.addEventListener('touchend', function() {
                this.style.transform = '';
            }, { passive: true });

            el.addEventListener('touchcancel', function() {
                this.style.transform = '';
            }, { passive: true });
        });
    }

    // ============================================
    // KEYBOARD HANDLING
    // ============================================

    function initKeyboardHandling() {
        // Prevent zoom on double tap for iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });

        // Handle Enter key in forms
        const forms = $$('form');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach((input, index) => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const nextInput = inputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                        } else {
                            form.querySelector('button[type="submit"]')?.focus();
                        }
                    }
                });
            });
        });

        // Escape key to go back
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const backBtn = $('.btn-back');
                if (backBtn) {
                    backBtn.click();
                }
            }
        });
    }

    // ============================================
    // IDLE TIMER (Return to home after inactivity)
    // ============================================

    function initIdleTimer() {
        const IDLE_TIMEOUT = 60000; // 60 seconds
        let idleTimer;

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                // Redirect to home if not already there
                if (!window.location.pathname.endsWith('index.php') &&
                    !window.location.pathname.endsWith('/')) {
                    window.location.href = '/index.php';
                }
            }, IDLE_TIMEOUT);
        }

        // Reset timer on user interaction
        ['click', 'touchstart', 'keydown', 'scroll'].forEach(event => {
            document.addEventListener(event, resetIdleTimer, { passive: true });
        });

        resetIdleTimer();
    }

    // ============================================
    // AUTO-CAPITALIZE INPUTS
    // ============================================

    function initAutoCapitalize() {
        const capitalizeInputs = $$('input[data-capitalize]');
        capitalizeInputs.forEach(input => {
            input.addEventListener('blur', function() {
                this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
            });
        });

        // Auto-uppercase for company names
        const companyInputs = $$('input[name="company"]');
        companyInputs.forEach(input => {
            input.addEventListener('blur', function() {
                this.value = this.value.toUpperCase();
            });
        });
    }

    // ============================================
    // CONFIRMATION DIALOG
    // ============================================

    function showConfirmation(message, type = 'success') {
        const existing = $('.confirmation-toast');
        if (existing) {
            existing.remove();
        }

        const toast = document.createElement('div');
        toast.className = `confirmation-toast fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white px-12 py-8 rounded-2xl shadow-2xl text-2xl font-semibold animate-fade-in`;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.5s ease';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

    // ============================================
    // INITIALIZATION
    // ============================================

    function init() {
        initLanguageToggle();
        initFullscreenToggle();
        initClock();
        initFormValidation();
        initHostDropdown();
        initTouchFeedback();
        initKeyboardHandling();
        initIdleTimer();
        initAutoCapitalize();
        updatePageTranslations();

        console.log('SAP Visitor Management Kiosk initialized');
    }

    // Run initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose utilities globally
    window.VMS = {
        t: t,
        showConfirmation: showConfirmation,
        setButtonLoading: setButtonLoading,
        lang: () => currentLang
    };

})();
