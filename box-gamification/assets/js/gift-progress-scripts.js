/**
 * LearnDash Gift Box Progress Tracker - JavaScript Version 2.3.0
 * Handles interactions and animations with dynamic lesson support
 */

(function($) {
    'use strict';
    
    // Wait for document ready
    $(document).ready(function() {
        initializeGiftProgress();
    });
    
    /**
     * Initialize Gift Progress Tracker
     */
    function initializeGiftProgress() {
        const $tracker = $('.ldvr-gift-progress');
        if (!$tracker.length) return;
        
        // Get total lessons and completed lessons dynamically
        window.totalLessons = parseInt($tracker.data('total-lessons')) || 5;
        window.completedLessons = parseInt($tracker.data('completed-lessons')) || 0;
        
        // Handle step navigation
        setupStepNavigation();
        
        // Handle lesson completion
        setupLessonCompletion();
        
        // Add animations
        if ($tracker.data('animation') === 'true') {
            addAnimations();
        }
        
        // Handle responsive behavior
        handleResponsive();
        
        // Initialize gift box position
        initializeGiftBoxPosition();
        
        // Setup arrow navigation
        setupArrowNavigation();
        
        // Initialize progress bar
        initializeProgressBar();
    }
    
    /**
     * Initialize progress bar to show completed lessons
     */
    function initializeProgressBar() {
        const completedLessons = window.completedLessons;
        const totalLessons = window.totalLessons;
        
        if (completedLessons > 0) {
            // Fix: Calculate progress to stop at the center of the last completed lesson
            const stepWidth = 100 / totalLessons;
            const progressPercent = ((completedLessons - 1) * stepWidth) + (stepWidth / 2);
            $('.progress_inner__bar').css('width', progressPercent + '%');
        } else {
            $('.progress_inner__bar').css('width', '0%');
        }
    }
    
    /**
     * Setup arrow navigation
     */
    function setupArrowNavigation() {
        // Arrow buttons are handled by inline onclick, but we can add keyboard support
        $(document).on('keydown', function(e) {
            if ($('.ldvr-gift-progress').is(':visible')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    window.navigateStep('prev');
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    window.navigateStep('next');
                } else if (e.key === 'Escape' && $('.ldvr-gift-progress').hasClass('fullscreen-mode')) {
                    e.preventDefault();
                    window.toggleFullscreen(false);
                } else if (e.key === 'F11' && !$('.ldvr-gift-progress').hasClass('fullscreen-mode')) {
                    e.preventDefault();
                    window.toggleFullscreen(true);
                }
            }
        });
        
        // Add focus states for better accessibility
        $('.nav-arrow').attr('tabindex', '0');
        $('.ldvr-close-fullscreen').attr('tabindex', '0');
        $('.ldvr-fullscreen-toggle').attr('tabindex', '0');
        
        // Make the main container focusable for keyboard navigation
        $('.ldvr-gift-progress').attr('tabindex', '-1');
    }
    
    /**
     * Setup step navigation
     */
    function setupStepNavigation() {
        // Click on step circles to navigate
        $('.progress_inner__step').on('click', function(e) {
            e.preventDefault();
            
            const $step = $(this);
            const stepNumber = parseInt($step.data('step'));
            const isLocked = $step.hasClass('locked');
            const isCompleted = $step.hasClass('completed');
            const isCurrent = $step.hasClass('current');
            
            // If locked, show message
            if (isLocked) {
                showLockedMessage();
                return;
            }
            
            // Navigate to this step
            navigateToStep(stepNumber);
        });
        
        // Update when radio changes
        $('input[name="step"]').on('change', function() {
            const stepNum = parseInt($(this).data('step-number'));
            updateActiveTab(stepNum);
            updateGiftBoxPosition(stepNum);
            updateNavigationButtons(stepNum);
            updateStepVisuals(stepNum);
        });
    }
    
    /**
     * Navigate to specific step
     */
    function navigateToStep(stepNumber) {
        $('#step-' + stepNumber).prop('checked', true).trigger('change');
    }
    
    /**
     * Global navigation function for arrows
     */
    window.navigateStep = function(direction) {
        const currentStep = parseInt($('input[name="step"]:checked').data('step-number'));
        const totalLessons = window.totalLessons;
        let newStep = currentStep;
        
        if (direction === 'prev' && currentStep > 1) {
            newStep = currentStep - 1;
        } else if (direction === 'next' && currentStep < totalLessons) {
            newStep = currentStep + 1;
        }
        
        if (newStep !== currentStep) {
            navigateToStep(newStep);
        }
    };
    
    /**
     * Update navigation buttons state
     */
    function updateNavigationButtons(currentStep) {
        const totalLessons = window.totalLessons;
        
        // Update left arrow
        if (currentStep <= 1) {
            $('.nav-arrow-left').prop('disabled', true);
        } else {
            $('.nav-arrow-left').prop('disabled', false);
        }
        
        // Update right arrow
        if (currentStep >= totalLessons) {
            $('.nav-arrow-right').prop('disabled', true);
        } else {
            $('.nav-arrow-right').prop('disabled', false);
        }
    }
    
    /**
     * Initialize gift box to correct position
     */
    function initializeGiftBoxPosition() {
        const currentStep = parseInt($('.progress_inner__status').data('current-step')) || 1;
        
        // Set initial position without animation
        const $status = $('.progress_inner__status');
        $status.css('transition', 'none');
        updateGiftBoxPosition(currentStep);
        
        // Re-enable animation after a brief delay
        setTimeout(function() {
            $status.css('transition', '');
        }, 100);
        
        updateActiveTab(currentStep);
        updateNavigationButtons(currentStep);
        updateStepVisuals(currentStep);
    }
    
    /**
     * Update step visual states
     */
    function updateStepVisuals(activeStep) {
        $('.progress_inner__step').each(function() {
            const $step = $(this);
            const stepNum = parseInt($step.data('step'));
            
            if (stepNum === activeStep) {
                $step.addClass('active');
            } else {
                $step.removeClass('active');
            }
        });
    }
    
    /**
     * Update the active tab display
     */
    function updateActiveTab(stepNum) {
        if (!stepNum) return;
        
        const tabIndex = stepNum - 1;
        
        // Hide all tabs
        $('.progress_inner__tabs .tab').removeClass('active').css({
            'opacity': '0',
            'top': '40px',
            'pointer-events': 'none'
        });
        
        // Show active tab with delay
        setTimeout(function() {
            $('.progress_inner__tabs .tab-' + tabIndex).addClass('active').css({
                'opacity': '1',
                'top': '0',
                'pointer-events': 'auto'
            });
        }, 100);
    }
    
    /**
     * Update gift box position
     */
    function updateGiftBoxPosition(step) {
        if (!step) return;
        
        const $status = $('.progress_inner__status');
        const totalLessons = window.totalLessons;
        
        // Calculate position based on total lessons
        // Position should be centered under the active step
        const stepWidth = 100 / totalLessons;
        const stepCenter = (step - 1) * stepWidth + (stepWidth / 2);
        
        // Adjust for gift box centering
        const boxWidthPercent = 5; // Approximate width of box as percentage
        const leftPosition = stepCenter - (boxWidthPercent / 2);
        
        // Apply the position
        $status.css('left', leftPosition + '%');
        
        // Trigger gift box build animation
        animateGiftBoxBuild(step);
    }
    
    /**
     * Animate gift box building based on progress
     */
    function animateGiftBoxBuild(step) {
        const $status = $('.progress_inner__status');
        const totalLessons = window.totalLessons;
        const completedLessons = window.completedLessons;
        
        // Calculate build stage based on completed lessons, not current step
        const progressPercent = (completedLessons / totalLessons) * 100;
        
        // Update progress bar to match completed lessons
        if (completedLessons > 0) {
            const stepWidth = 100 / totalLessons;
            const barProgressPercent = ((completedLessons - 1) * stepWidth) + (stepWidth / 2);
            $('.progress_inner__bar').css('width', barProgressPercent + '%');
        }
        
        // Reset all elements first
        $status.find('div').css('opacity', '0');
        
        // Progressive build based on percentage
        if (progressPercent >= 20) {
            // Show box base
            setTimeout(() => {
                $status.find('.box_base').css({'opacity': '1', 'top': '50%', 'left': '0'});
            }, 300);
        }
        
        if (progressPercent >= 40) {
            // Show box base and item
            $status.find('.box_base').css({'opacity': '1', 'top': '50%', 'left': '0'});
            setTimeout(() => {
                $status.find('.box_item').css({'opacity': '1', 'top': '-10px', 'left': '0'});
            }, 300);
        }
        
        if (progressPercent >= 60) {
            // Show box base, item, and lid
            $status.find('.box_base').css({'opacity': '1', 'top': '50%', 'left': '0'});
            $status.find('.box_item').css({'opacity': '1', 'top': '10px', 'left': '0'});
            setTimeout(() => {
                $status.find('.box_lid').css({'opacity': '1', 'top': '-1px', 'left': '0'});
            }, 300);
        }
        
        if (progressPercent >= 80) {
            // Show all including ribbon and bow
            $status.find('.box_base').css({'opacity': '1', 'top': '50%', 'left': '0'});
            $status.find('.box_item').css({'opacity': '1', 'top': '10px', 'left': '0'});
            $status.find('.box_lid').css({'opacity': '1', 'top': '-1px', 'left': '0'});
            setTimeout(() => {
                $status.find('.box_ribbon').css({'opacity': '1', 'top': '50%', 'left': '0'});
                $status.find('.box_bow').css({'opacity': '1', 'top': '-10px', 'left': '0'});
            }, 300);
        }
        
        if (progressPercent >= 100) {
            // Show everything including tag
            $status.find('.box_base').css({'opacity': '1', 'top': '50%', 'left': '0'});
            $status.find('.box_item').css({'opacity': '1', 'top': '10px', 'left': '0'});
            $status.find('.box_lid').css({'opacity': '1', 'top': '-1px', 'left': '0'});
            $status.find('.box_ribbon').css({'opacity': '1', 'top': '50%', 'left': '0'});
            $status.find('.box_bow').css({'opacity': '1', 'top': '-10px', 'left': '0'});
            setTimeout(() => {
                $status.find('.box_tag').css({'opacity': '1', 'top': '10px', 'left': '20px'});
                $status.find('.box_string').css({'opacity': '1', 'top': '10px', 'left': '20px'});
                
                // Check if all lessons are completed
                if (completedLessons >= totalLessons) {
                    celebrateCompletion();
                }
            }, 300);
        }
        
        // Add bounce animation
        $status.addClass('bouncing');
        setTimeout(function() {
            $status.removeClass('bouncing');
        }, 800);
    }
    
    /**
     * Setup lesson completion handling
     */
    function setupLessonCompletion() {
        // Ensure links work properly
        $('.lesson-link').on('click', function(e) {
            const $link = $(this);
            const lessonId = $link.data('lesson-id');
            
            // Add loading state briefly
            $link.addClass('loading').text('Loading...');
        });
    }
    
    /**
     * Add entrance animations
     */
    function addAnimations() {
        // Animate steps on load
        $('.progress_inner__step').each(function(index) {
            const $step = $(this);
            setTimeout(function() {
                $step.addClass('animated');
            }, index * 50); // Faster animation for more steps
        });
        
        // Get current step and animate gift box
        const currentStep = parseInt($('.progress_inner__status').data('current-step')) || 1;
        setTimeout(() => {
            animateGiftBoxBuild(currentStep);
        }, 500);
    }
    
    /**
     * Show locked message
     */
    function showLockedMessage() {
        // Remove any existing toasts
        $('.ldvr-toast').remove();
        
        // Create toast notification
        const $toast = $('<div class="ldvr-toast">Please complete the previous lesson first!</div>');
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 10);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Celebrate course completion
     */
    function celebrateCompletion() {
        // Add celebration class
        $('.ldvr-gift-progress').addClass('completed');
        
        // Create confetti effect
        createConfetti();
        
        // Show completion message
        setTimeout(function() {
            if (!$('.completion-message').length) {
                const $message = $('<div class="completion-message">🎉 Congratulations! You\'ve completed the course!</div>');
                $('.ldvr-gift-progress').append($message);
                
                setTimeout(function() {
                    $message.addClass('show');
                }, 100);
            }
        }, 500);
    }
    
    /**
     * Create confetti effect
     */
    function createConfetti() {
        const colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', 
                       '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', 
                       '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722'];
        const confettiCount = 50;
        
        for (let i = 0; i < confettiCount; i++) {
            createConfettiPiece(colors[Math.floor(Math.random() * colors.length)]);
        }
    }
    
    /**
     * Create individual confetti piece
     */
    function createConfettiPiece(color) {
        const $confetti = $('<div class="confetti"></div>');
        
        $confetti.css({
            'background-color': color,
            'left': Math.random() * 100 + '%',
            'animation-delay': Math.random() * 3 + 's',
            'animation-duration': (Math.random() * 3 + 2) + 's'
        });
        
        $('.ldvr-gift-progress').append($confetti);
        
        setTimeout(function() {
            $confetti.remove();
        }, 5000);
    }
    
    /**
     * Handle responsive behavior
     */
    function handleResponsive() {
        // Adjust on resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                adjustForMobile();
            }, 250);
        });
        
        // Initial check
        adjustForMobile();
    }
    
    /**
     * Adjust layout for mobile devices
     */
    function adjustForMobile() {
        const $tracker = $('.ldvr-gift-progress');
        const windowWidth = $(window).width();
        
        if (windowWidth <= 768) {
            $tracker.addClass('mobile');
        } else {
            $tracker.removeClass('mobile');
        }
    }
    
    // Global function for fullscreen toggle
    window.toggleFullscreen = function(enterFullscreen) {
        const $tracker = $('.ldvr-gift-progress');
        
        if (enterFullscreen === true || (enterFullscreen === undefined && !$tracker.hasClass('fullscreen-mode'))) {
            // Enter fullscreen mode
            $tracker.addClass('fullscreen-mode');
            
            // Disable body scroll
            $('body').css('overflow', 'hidden');
            
            // Focus on the tracker for keyboard navigation
            $tracker.focus();
            
        } else {
            // Exit fullscreen mode
            $tracker.removeClass('fullscreen-mode');
            
            // Re-enable body scroll
            $('body').css('overflow', '');
        }
    };
    
    // Add required CSS for animations
    const animationStyles = `
        <style>
            .ldvr-toast {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                background: #ff5252;
                color: white;
                padding: 15px 30px;
                border-radius: 25px;
                font-weight: 600;
                z-index: 100000;
                transition: transform 0.3s ease;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            }
            
            .ldvr-toast.show {
                transform: translateX(-50%) translateY(0);
            }
            
            .progress_inner__status.bouncing {
                animation: bounce 0.8s ease;
            }
            
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            .progress_inner__step.animated {
                animation: stepIn 0.5s ease forwards;
            }
            
            .progress_inner__step.active label {
                color: #1ea4ec;
            }
            
            @keyframes stepIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .animate-in {
                animation: elementIn 0.6s ease forwards;
            }
            
            @keyframes elementIn {
                from {
                    opacity: 0;
                    transform: scale(0.8) rotate(10deg);
                }
                to {
                    opacity: 1;
                    transform: scale(1) rotate(0deg);
                }
            }
            
            .completion-message {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0);
                background: white;
                padding: 30px 50px;
                border-radius: 20px;
                font-size: 24px;
                font-weight: 900;
                color: #10b981;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                z-index: 100;
                transition: transform 0.5s ease;
            }
            
            .completion-message.show {
                transform: translate(-50%, -50%) scale(1);
            }
            
            .confetti {
                position: absolute;
                width: 10px;
                height: 10px;
                top: -10px;
                animation: confettiFall linear forwards;
                z-index: 200;
            }
            
            @keyframes confettiFall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
            
            .lesson-link.loading {
                opacity: 0.6;
                cursor: wait;
            }
        </style>
    `;
    
    // Append animation styles to head
    $('head').append(animationStyles);
    
})(jQuery);