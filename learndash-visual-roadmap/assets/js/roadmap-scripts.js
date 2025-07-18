/**
 * LearnDash Visual Roadmap - JavaScript
 * Handles interactions and animations
 */

(function($) {
    'use strict';
    
    // Wait for document ready
    $(document).ready(function() {
        initializeRoadmap();
        initializeCurvedRoadmap();
    });
    
    /**
     * Initialize Grid Roadmap
     */
    function initializeRoadmap() {
        // Animate progress bars on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const progressBar = entry.target.querySelector('.ld-progress-fill');
                    if (progressBar) {
                        const width = progressBar.getAttribute('data-width') || progressBar.style.width;
                        progressBar.style.width = '0%';
                        setTimeout(() => {
                            progressBar.style.width = width;
                        }, 100);
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe all modules
        $('.ld-roadmap-module').each(function() {
            observer.observe(this);
        });
        
        // Module click handling
        $('.ld-roadmap-module:not(.locked)').on('click', function(e) {
            if (!$(this).hasClass('locked')) {
                const lessonId = $(this).data('lesson-id');
                if (lessonId) {
                    // Add click animation
                    $(this).addClass('clicked');
                    setTimeout(() => {
                        $(this).removeClass('clicked');
                    }, 300);
                }
            }
        });
    }
    
    /**
     * Initialize Curved Roadmap
     */
    function initializeCurvedRoadmap() {
        const $roadmap = $('.ldvr-curved-roadmap');
        if (!$roadmap.length) return;
        
        // Generate and animate paths after modules are loaded
        setTimeout(function() {
            generateLessonPath();
            animateProgressPath();
        }, 100);
        
        // Enhanced module hover effects with better popup handling
        $('.ldvr-path-module').each(function() {
            const $module = $(this);
            const $popup = $module.find('.ldvr-module-popup');
            
            $module.on('mouseenter', function() {
                if (!$module.hasClass('locked')) {
                    $module.find('.ldvr-module-marker').addClass('hover');
                    $module.addClass('show-popup');
                    playHoverSound();
                }
            });
            
            $module.on('mouseleave', function() {
                $module.find('.ldvr-module-marker').removeClass('hover');
                $module.removeClass('show-popup');
            });
            
            // Also trigger on marker hover for better coverage
            $module.find('.ldvr-module-marker').on('mouseenter', function() {
                if (!$module.hasClass('locked')) {
                    $module.addClass('show-popup');
                }
            });
        });
        
        // Module click handling with animation
        $('.ldvr-path-module:not(.locked)').on('click', function(e) {
            const $module = $(this);
            if ($module.hasClass('locked')) {
                // Shake animation for locked modules
                $module.addClass('shake');
                setTimeout(() => $module.removeClass('shake'), 500);
                e.preventDefault();
                return;
            }
            
            // Success animation
            $module.addClass('success-click');
            playClickSound();
            
            // Navigate after animation
            setTimeout(() => {
                const lessonUrl = $module.find('a').attr('href');
                if (lessonUrl) {
                    window.location.href = lessonUrl;
                }
            }, 300);
        });
        
        // Parallax effect on mouse move
        if ($roadmap.data('animation') === 'true') {
            $(document).on('mousemove', function(e) {
                const mouseX = e.pageX / $(window).width();
                const mouseY = e.pageY / $(window).height();
                
                $('.zone-decoration').each(function(index) {
                    const speed = (index + 1) * 2;
                    const x = (mouseX - 0.5) * speed;
                    const y = (mouseY - 0.5) * speed;
                    
                    $(this).css('transform', `translate(${x}px, ${y}px)`);
                });
            });
        }
        
        // Progress update via AJAX
        $('.ldvr-path-module.current').on('complete', function() {
            updateProgress($(this).data('lesson-id'));
        });
        
        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if ($roadmap.is(':visible')) {
                const $current = $('.ldvr-path-module.current').first();
                
                switch(e.key) {
                    case 'ArrowRight':
                        navigateToNext($current);
                        break;
                    case 'ArrowLeft':
                        navigateToPrevious($current);
                        break;
                    case 'Enter':
                        $current.click();
                        break;
                }
            }
        });
    }
    
    /**
     * Update character position based on user progress
     */
    function updateCharacterPosition() {
        const $character = $('.ldvr-character');
        const $progressPath = $('.ldvr-progress-path');
        
        if ($character.length && $progressPath.length) {
            // Get progress percentage from data or calculate
            const progress = parseFloat($character.css('left')) || 0;
            
            // Smooth transition
            $character.css({
                'transition': 'left 2s cubic-bezier(0.4, 0, 0.2, 1)',
                'left': progress + '%'
            });
            
            // Add walking animation during movement
            $character.addClass('walking');
            setTimeout(() => {
                $character.removeClass('walking');
            }, 2000);
        }
    }
    
    /**
     * Generate the path that connects all lesson modules
     */
    function generateLessonPath() {
        const $mainPath = $('.ldvr-main-path');
        const $progressPath = $('.ldvr-progress-path');
        
        if (!$mainPath.length || !$progressPath.length) return;
        
        // Collect module positions from DOM
        const modules = [];
        $('.ldvr-path-module').each(function() {
            const $module = $(this);
            const rect = this.getBoundingClientRect();
            const container = $('.ldvr-landscape')[0].getBoundingClientRect();
            
            // Calculate relative position within the landscape
            const relativeX = ((rect.left + rect.width/2 - container.left) / container.width) * 1200;
            const relativeY = ((rect.top + rect.height/2 - container.top) / container.height) * 800;
            
            modules.push({ x: relativeX, y: relativeY });
        });
        
        if (modules.length === 0) return;
        
        // Generate simple path through all modules
        let pathData = `M ${modules[0].x} ${modules[0].y}`;
        
        for (let i = 1; i < modules.length; i++) {
            pathData += ` L ${modules[i].x} ${modules[i].y}`;
        }
        
        // Set the path for both main and progress
        $mainPath.attr('d', pathData);
        $progressPath.attr('d', pathData);
    }
    
    /**
     * Animate progress along the path based on completed lessons
     */
    function animateProgressPath() {
        const $progressPath = $('.ldvr-progress-path');
        if (!$progressPath.length) return;
        
        // Wait for path to be set
        setTimeout(function() {
            const pathElement = $progressPath[0];
            const pathLength = pathElement.getTotalLength();
            
            if (pathLength === 0) return;
            
            // Get completion data
            const completedLessons = window.roadmapData ? window.roadmapData.completedLessons : 0;
            const totalLessons = window.roadmapData ? window.roadmapData.totalLessons : 1;
            
            // Calculate progress to end exactly at the last completed lesson
            let visibleLength = 0;
            
            if (completedLessons > 0 && totalLessons > 1) {
                // Calculate the length to the center of the last completed lesson
                // This is the path length divided by (totalLessons - 1) * (completedLessons - 1)
                // because the path goes from lesson 1 to lesson N, which is (N-1) segments
                const segmentLength = pathLength / (totalLessons - 1);
                visibleLength = segmentLength * (completedLessons - 1);
            } else if (completedLessons === totalLessons) {
                // If all lessons are completed, show the entire path
                visibleLength = pathLength;
            }
            // If completedLessons is 0, visibleLength stays 0 (no progress shown)
            
            // Set stroke-dasharray to show only completed portion
            $progressPath.css({
                'stroke-dasharray': pathLength,
                'stroke-dashoffset': pathLength - visibleLength
            });
            
            // Animate the reveal
            $progressPath.animate({
                'stroke-dashoffset': pathLength - visibleLength
            }, {
                duration: 2000,
                easing: 'swing'
            });
        }, 200);
    }
    
    /**
     * Update progress via AJAX
     */
    function updateProgress(lessonId) {
        $.ajax({
            url: ldvr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ldvr_update_progress',
                lesson_id: lessonId,
                nonce: ldvr_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update UI with new progress
                    location.reload(); // Simple reload, or update dynamically
                }
            }
        });
    }
    
    /**
     * Navigate to next module
     */
    function navigateToNext($current) {
        const $next = $current.nextAll('.ldvr-path-module:not(.locked)').first();
        if ($next.length) {
            $next.find('.ldvr-module-marker').focus();
            highlightModule($next);
        }
    }
    
    /**
     * Navigate to previous module
     */
    function navigateToPrevious($current) {
        const $prev = $current.prevAll('.ldvr-path-module:not(.locked)').first();
        if ($prev.length) {
            $prev.find('.ldvr-module-marker').focus();
            highlightModule($prev);
        }
    }
    
    /**
     * Highlight a module temporarily
     */
    function highlightModule($module) {
        $module.addClass('highlight');
        setTimeout(() => {
            $module.removeClass('highlight');
        }, 1000);
    }
    
    /**
     * Play hover sound effect (optional)
     */
    function playHoverSound() {
        // Only play if sound is enabled
        if (window.ldvrSoundEnabled) {
            const audio = new Audio(ldvr_ajax.plugin_url + 'assets/sounds/hover.mp3');
            audio.volume = 0.3;
            audio.play().catch(() => {}); // Ignore errors
        }
    }
    
    /**
     * Play click sound effect (optional)
     */
    function playClickSound() {
        if (window.ldvrSoundEnabled) {
            const audio = new Audio(ldvr_ajax.plugin_url + 'assets/sounds/click.mp3');
            audio.volume = 0.5;
            audio.play().catch(() => {});
        }
    }
    
    // Additional animations and effects
    
    /**
     * Confetti effect on completion
     */
    window.ldvrCelebrate = function() {
        if (typeof confetti !== 'undefined') {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    };
    
    /**
     * Add shake animation CSS
     */
    $('<style>')
        .text(`
            .ldvr-path-module.shake {
                animation: shake 0.5s ease-in-out;
            }
            
            @keyframes shake {
                0%, 100% { transform: translate(-50%, -50%) rotate(0deg); }
                25% { transform: translate(-52%, -50%) rotate(-2deg); }
                75% { transform: translate(-48%, -50%) rotate(2deg); }
            }
            
            .ldvr-path-module.success-click .ldvr-module-marker {
                animation: successPulse 0.3s ease-out;
            }
            
            @keyframes successPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
            
            .ldvr-path-module.highlight .ldvr-module-marker {
                animation: highlight 1s ease-in-out;
            }
            
            @keyframes highlight {
                0%, 100% { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
                50% { box-shadow: 0 4px 30px rgba(59, 130, 246, 0.6); }
            }
            
            .ldvr-character.walking span {
                animation: walk 0.5s ease-in-out infinite;
            }
            
            .ld-roadmap-module.clicked {
                transform: scale(0.98);
                transition: transform 0.1s ease;
            }
        `)
        .appendTo('head');
    
    // Debug mode
    if (window.location.hash === '#ldvr-debug') {
        console.log('LearnDash Visual Roadmap Debug Mode');
        console.log('Modules found:', $('.ldvr-path-module').length);
        console.log('Current progress:', $('.ldvr-character').css('left'));
    }
    
    /**
     * Global Fullscreen Functions
     */
    window.toggleRoadmapFullscreen = function() {
        $('body').removeClass('has-roadmap');
        $('body').css('overflow', 'auto');
        
        // If history is available, go back, otherwise reload
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.reload();
        }
    };
    
    window.showLockedMessage = function() {
        alert('🔒 Complete the previous lesson to unlock this one!');
    };
    
    // Handle escape key to exit fullscreen
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('body').hasClass('has-roadmap')) {
            window.toggleRoadmapFullscreen();
        }
    });
    
    // Prevent scrolling when in fullscreen mode
    if ($('body').hasClass('has-roadmap')) {
        $('body').css('overflow', 'hidden');
    }

})(jQuery);