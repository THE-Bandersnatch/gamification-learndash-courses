<?php
/**
 * Plugin Name: LearnDash Visual Roadmap - Gift Box Progress
 * Plugin URI: https://academy.labgenz.com
 * Description: Beautiful gift box progress tracker for LearnDash courses
 * Version: 2.3.0
 * Author: LabGenz Academy
 * License: GPL v2 or later
 * Text Domain: ld-visual-roadmap
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LDVR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LDVR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LDVR_VERSION', '2.3.0');

class LearnDashVisualRoadmap {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ld_gift_progress', array($this, 'render_gift_progress_shortcode'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_ldvr_complete_lesson', array($this, 'ajax_complete_lesson'));
        add_action('wp_ajax_nopriv_ldvr_complete_lesson', array($this, 'ajax_complete_lesson'));
    }
    
    public function enqueue_scripts() {
        // Google Fonts
        wp_enqueue_style('ldvr-google-fonts', 'https://fonts.googleapis.com/css?family=Nunito:400,900', array(), null);
        
        // Plugin styles
        wp_enqueue_style('ldvr-styles', LDVR_PLUGIN_URL . 'assets/css/gift-progress-styles.css', array(), LDVR_VERSION);
        
        // Plugin scripts
        wp_enqueue_script('ldvr-scripts', LDVR_PLUGIN_URL . 'assets/js/gift-progress-scripts.js', array('jquery'), LDVR_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ldvr-scripts', 'ldvr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ldvr_nonce')
        ));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'learndash-lms',
            'Gift Box Progress',
            'Gift Box Progress',
            'manage_options',
            'ld-gift-progress',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('LearnDash Gift Box Progress Settings', 'ld-visual-roadmap'); ?></h1>
            
            <div class="ldvr-admin-container">
                <h2>Gift Box Progress Tracker</h2>
                
                <div class="ldvr-shortcode-box">
                    <h3>Basic Usage</h3>
                    <code>[ld_gift_progress course_id="123"]</code>
                    <p>Display a gift box progress tracker for your course</p>
                </div>
                
                <div class="ldvr-shortcode-box">
                    <h3>Full Screen Mode (Recommended)</h3>
                    <code>[ld_gift_progress course_id="123" fullscreen="true"]</code>
                    <p>Shows the progress tracker in full screen mode with arrow navigation</p>
                </div>
                
                <h2>Parameters</h2>
                <ul>
                    <li><strong>course_id:</strong> The ID of your LearnDash course (required)</li>
                    <li><strong>show_descriptions:</strong> Show lesson excerpts (true/false)</li>
                    <li><strong>animation:</strong> Enable animations (true/false)</li>
                    <li><strong>fullscreen:</strong> Enable fullscreen mode (true/false)</li>
                </ul>
                
                <h2>How It Works</h2>
                <p>The gift box builds as students progress through the course:</p>
                <ul>
                    <li>Each lesson represents a step in building the gift</li>
                    <li>Navigate using arrow buttons or clicking on lesson numbers</li>
                    <li>The gift box moves to show the current lesson</li>
                    <li>Completed lessons are marked with green checkmarks</li>
                    <li>When all lessons are complete, celebrate with confetti!</li>
                </ul>
                
                <h2>Notes</h2>
                <p>The tracker automatically syncs with your LearnDash course lessons. Add or remove lessons in LearnDash, and they'll appear in the tracker.</p>
            </div>
        </div>
        
        <style>
            .ldvr-admin-container {
                background: white;
                padding: 20px;
                margin-top: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .ldvr-shortcode-box {
                background: #f5f5f5;
                padding: 15px;
                margin: 15px 0;
                border-radius: 5px;
            }
            .ldvr-shortcode-box code {
                display: block;
                padding: 10px;
                background: white;
                margin: 10px 0;
                border: 1px solid #ddd;
                font-family: monospace;
            }
        </style>
        <?php
    }
    
    public function render_gift_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'course_id' => get_the_ID(),
            'show_descriptions' => 'true',
            'animation' => 'true',
            'fullscreen' => 'false'
        ), $atts);
        
        $course_id = intval($atts['course_id']);
        $user_id = get_current_user_id();
        
        // Check if course exists
        if (!$course_id || get_post_type($course_id) !== 'sfwd-courses') {
            return '<p>Invalid course ID provided.</p>';
        }
        
        // Check if LearnDash is active
        if (!function_exists('learndash_get_course_lessons_list')) {
            return '<p>LearnDash plugin is required for this progress tracker.</p>';
        }
        
        $lessons = learndash_get_course_lessons_list($course_id);
        
        if (empty($lessons)) {
            return '<p>No lessons found for this course.</p>';
        }
        
        // Get all lessons (no limit)
        $total_lessons = count($lessons);
        
        // Calculate which step is currently active
        $current_step = 1;
        $completed_count = 0;
        $last_completed_index = 0;
        
        foreach ($lessons as $index => $lesson) {
            if (learndash_is_lesson_complete($user_id, $lesson['post']->ID)) {
                $completed_count++;
                $last_completed_index = $index + 1;
            }
        }
        
        // Set current step to the next uncompleted lesson
        if ($completed_count < $total_lessons) {
            $current_step = $completed_count + 1;
        } else {
            $current_step = $total_lessons;
        }
        
        ob_start();
        ?>
        
        <div class="ldvr-gift-progress <?php echo $atts['fullscreen'] === 'true' ? 'fullscreen-mode' : ''; ?>" 
             data-animation="<?php echo esc_attr($atts['animation']); ?>"
             data-course-id="<?php echo esc_attr($course_id); ?>"
             data-total-lessons="<?php echo esc_attr($total_lessons); ?>"
             data-completed-lessons="<?php echo esc_attr($completed_count); ?>">
            
            <!-- Fullscreen Toggle Button (shown when not in fullscreen) -->
            <button class="ldvr-fullscreen-toggle" onclick="toggleFullscreen(true)" title="Enter Fullscreen (F11)">
            </button>
            
            <!-- Close Fullscreen Button (shown when in fullscreen) -->
            <button class="ldvr-close-fullscreen" onclick="toggleFullscreen(false)" title="Close Fullscreen (ESC)">
            </button>
            
            <!-- Keyboard navigation hints (shown only in fullscreen) -->
            <div class="keyboard-hint">
                Use <span class="key">←</span> <span class="key">→</span> arrow keys to navigate • <span class="key">ESC</span> to close • <span class="key">F11</span> for fullscreen
            </div>
            
            <!-- Course Progress Summary - Moved to top -->
            <div class="ldvr-progress-summary top">
                <div class="progress-stat">
                    <span class="stat-label">Progress</span>
                    <span class="stat-value"><?php echo $completed_count; ?>/<?php echo $total_lessons; ?></span>
                </div>
                <div class="progress-stat">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?php echo round(($completed_count / $total_lessons) * 100); ?>%</span>
                </div>
                <?php if ($completed_count >= $total_lessons): ?>
                    <div class="progress-stat celebration">
                        <span class="stat-label">🎉 Course Complete!</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="progress">
                <div class="progress_inner">
                    <!-- Navigation Arrows -->
                    <button class="nav-arrow nav-arrow-left" onclick="navigateStep('prev')" title="Previous Lesson" <?php echo $current_step <= 1 ? 'disabled' : ''; ?>>
                    </button>
                    <button class="nav-arrow nav-arrow-right" onclick="navigateStep('next')" title="Next Lesson" <?php echo $current_step >= $total_lessons ? 'disabled' : ''; ?>>
                    </button>
                    
                    <!-- Step Labels -->
                    <div class="steps-container">
                        <?php foreach ($lessons as $index => $lesson): 
                            $step_num = $index + 1;
                            $lesson_id = $lesson['post']->ID;
                            $is_completed = learndash_is_lesson_complete($user_id, $lesson_id);
                            $is_available = $index === 0 || learndash_is_lesson_complete($user_id, $lessons[$index - 1]['post']->ID);
                            $is_current = ($step_num == $current_step);
                        ?>
                            <div class="progress_inner__step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo !$is_available ? 'locked' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>"
                                 data-step="<?php echo $step_num; ?>"
                                 data-lesson-id="<?php echo $lesson_id; ?>"
                                 style="width: <?php echo (100 / $total_lessons); ?>%;">
                                <label for="step-<?php echo $step_num; ?>">
                                    <?php echo esc_html(get_the_title($lesson_id)); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Progress Bars - Inside steps container for proper alignment -->
                        <div class="progress-bars-container">
                            <div class="progress_inner__bar--set"></div>
                            <div class="progress_inner__bar" data-completed="<?php echo $completed_count; ?>"></div>
                        </div>
                    </div>
                    
                    <!-- Radio Inputs for Step Control -->
                    <?php for ($i = 1; $i <= $total_lessons; $i++): ?>
                        <input type="radio" 
                               name="step" 
                               id="step-<?php echo $i; ?>" 
                               data-step-number="<?php echo $i; ?>"
                               <?php echo $i == $current_step ? 'checked="checked"' : ''; ?>>
                    <?php endfor; ?>
                    
                    <!-- Tab Content -->
                    <div class="progress_inner__tabs">
                        <?php foreach ($lessons as $index => $lesson): 
                            $lesson_id = $lesson['post']->ID;
                            $is_completed = learndash_is_lesson_complete($user_id, $lesson_id);
                            $is_available = $index === 0 || learndash_is_lesson_complete($user_id, $lessons[$index - 1]['post']->ID);
                            $lesson_url = get_permalink($lesson_id);
                        ?>
                            <div class="tab tab-<?php echo $index; ?>" data-lesson-id="<?php echo $lesson_id; ?>">
                                <h1><?php echo esc_html(get_the_title($lesson_id)); ?></h1>
                                
                                <?php if ($atts['show_descriptions'] === 'true'): ?>
                                    <p><?php echo wp_trim_words(get_the_excerpt($lesson_id), 20) ?: 'Begin your learning journey with this lesson.'; ?></p>
                                <?php endif; ?>
                                
                                <div class="lesson-actions">
                                    <?php if ($is_completed): ?>
                                        <span class="status-badge completed">✓ Completed</span>
                                        <a href="<?php echo esc_url($lesson_url); ?>" 
                                           class="lesson-link review" 
                                           data-lesson-id="<?php echo $lesson_id; ?>">Review Lesson</a>
                                    <?php elseif ($is_available): ?>
                                        <a href="<?php echo esc_url($lesson_url); ?>" 
                                           class="lesson-link start" 
                                           data-lesson-id="<?php echo $lesson_id; ?>">Start Lesson</a>
                                    <?php else: ?>
                                        <span class="status-badge locked">🔒 Complete previous lesson first</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Gift Box Animation Status -->
                    <div class="progress_inner__status" data-current-step="<?php echo $current_step; ?>">
                        <div class="box_base"></div>
                        <div class="box_lid"></div>
                        <div class="box_ribbon"></div>
                        <div class="box_bow">
                            <div class="box_bow__left"></div>
                            <div class="box_bow__right"></div>
                        </div>
                        <div class="box_item"></div>
                        <div class="box_tag"></div>
                        <div class="box_string"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function toggleFullscreen() {
            document.querySelector('.ldvr-gift-progress').classList.remove('fullscreen-mode');
        }
        
        function navigateStep(direction) {
            const currentStep = parseInt(document.querySelector('input[name="step"]:checked').dataset.stepNumber);
            const totalLessons = parseInt(document.querySelector('.ldvr-gift-progress').dataset.totalLessons);
            let newStep = currentStep;
            
            if (direction === 'prev' && currentStep > 1) {
                newStep = currentStep - 1;
            } else if (direction === 'next' && currentStep < totalLessons) {
                newStep = currentStep + 1;
            }
            
            if (newStep !== currentStep) {
                document.getElementById('step-' + newStep).checked = true;
                document.getElementById('step-' + newStep).dispatchEvent(new Event('change'));
            }
        }
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    public function ajax_complete_lesson() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ldvr_nonce')) {
            wp_die('Security check failed');
        }
        
        $lesson_id = intval($_POST['lesson_id']);
        $user_id = get_current_user_id();
        
        if ($user_id && $lesson_id) {
            // Mark lesson as complete (if using LearnDash functions)
            learndash_process_mark_complete($user_id, $lesson_id);
            
            wp_send_json_success(array(
                'message' => 'Lesson marked as complete',
                'completed' => true
            ));
        }
        
        wp_send_json_error('Failed to complete lesson');
    }
}

// Initialize the plugin
LearnDashVisualRoadmap::get_instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    update_option('ldvr_version', LDVR_VERSION);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});