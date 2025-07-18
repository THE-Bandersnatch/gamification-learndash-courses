<?php
/**
 * Plugin Name: LearnDash Visual Roadmap
 * Plugin URI: https://academy.labgenz.com
 * Description: Beautiful visual course roadmaps for LearnDash with customizable paths and gamification
 * Version: 1.0.0
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
define('LDVR_VERSION', '1.0.0');

class LearnDashVisualRoadmap {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('ld_visual_roadmap', [$this, 'render_roadmap_shortcode']);
        add_shortcode('ld_curved_roadmap', [$this, 'render_curved_roadmap_shortcode']);
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('ld-visual-roadmap', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('ldvr-styles', LDVR_PLUGIN_URL . 'assets/css/roadmap-styles.css', [], LDVR_VERSION);
        wp_enqueue_script('ldvr-scripts', LDVR_PLUGIN_URL . 'assets/js/roadmap-scripts.js', ['jquery'], LDVR_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('ldvr-scripts', 'ldvr_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ldvr_nonce')
        ]);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'learndash-lms',
            'Visual Roadmap',
            'Visual Roadmap',
            'manage_options',
            'ld-visual-roadmap',
            [$this, 'admin_page']
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('LearnDash Visual Roadmap Settings', 'ld-visual-roadmap'); ?></h1>
            
            <div class="ldvr-admin-container">
                <h2>Available Shortcodes</h2>
                
                <div class="ldvr-shortcode-box">
                    <h3>Grid Roadmap</h3>
                    <code>[ld_visual_roadmap course_id="123" style="modern" columns="3"]</code>
                    <p>Modern grid-based course roadmap</p>
                </div>
                
                <div class="ldvr-shortcode-box">
                    <h3>Curved Path Roadmap</h3>
                    <code>[ld_curved_roadmap course_id="123" theme="adventure"]</code>
                    <p>Adventure-style curved path with themed zones</p>
                </div>
                
                <h2>Customization Guide</h2>
                <ul>
                    <li><strong>course_id:</strong> The ID of your LearnDash course</li>
                    <li><strong>style:</strong> modern, classic, minimal, gamified</li>
                    <li><strong>theme:</strong> adventure, space, ocean, forest</li>
                    <li><strong>columns:</strong> 1-4 (for grid layout)</li>
                    <li><strong>show_progress:</strong> true/false</li>
                    <li><strong>animation:</strong> true/false</li>
                </ul>
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
            }
        </style>
        <?php
    }
    
    public function render_curved_roadmap_shortcode($atts) {
        $atts = shortcode_atts([
            'course_id' => get_the_ID(),
            'theme' => 'adventure',
            'show_progress' => 'true',
            'animation' => 'true',
            'fullscreen' => 'false', // Add fullscreen option
            'auto_enroll' => 'false' // Auto-enroll user if not enrolled
        ], $atts);
        
        $course_id = intval($atts['course_id']);
        $user_id = get_current_user_id();
        
        // Check if course exists
        if (!$course_id || get_post_type($course_id) !== 'sfwd-courses') {
            return '<p>Invalid course ID provided.</p>';
        }
        
        // Check if LearnDash is active
        if (!function_exists('learndash_get_course_lessons_list')) {
            return '<p>LearnDash plugin is required for this roadmap.</p>';
        }
        
        // Auto-enroll user if specified
        if ($atts['auto_enroll'] === 'true' && $user_id && !sfwd_lms_has_access($course_id, $user_id)) {
            ld_update_course_access($user_id, $course_id, false);
        }
        
        $lessons = learndash_get_course_lessons_list($course_id);
        
        if (empty($lessons)) {
            return '<p>No lessons found for this course.</p>';
        }
        
        // Add body class for fullscreen
        if ($atts['fullscreen'] === 'true') {
            add_action('wp_footer', function() {
                echo '<script>document.body.classList.add("has-roadmap");</script>';
            });
        }
        
        ob_start();
        ?>
        
        <div class="ldvr-curved-roadmap <?php echo $atts['fullscreen'] === 'true' ? 'fullscreen' : ''; ?> theme-<?php echo esc_attr($atts['theme']); ?>" data-animation="<?php echo esc_attr($atts['animation']); ?>">
            
            <?php if ($atts['fullscreen'] === 'true'): ?>
                <button class="ldvr-fullscreen-toggle" onclick="toggleRoadmapFullscreen()">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            <?php endif; ?>
            
            <div class="ldvr-landscape">
                <!-- Themed Zones with decorations -->
                <div class="ldvr-zones">
                    <div class="ldvr-zone zone-1" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">
                        <div class="zone-decoration">🏔️</div>
                        <div class="zone-decoration" style="top: 70%; left: 20%; font-size: 60px;">🌄</div>
                    </div>
                    <div class="ldvr-zone zone-2" style="background: linear-gradient(135deg, #f87171, #ef4444);">
                        <div class="zone-decoration">🌋</div>
                        <div class="zone-decoration" style="bottom: 20%; right: 30%; font-size: 40px;">🔥</div>
                    </div>
                    <div class="ldvr-zone zone-3" style="background: linear-gradient(135deg, #60a5fa, #3b82f6);">
                        <div class="zone-decoration">🌊</div>
                        <div class="zone-decoration" style="top: 30%; left: 60%; font-size: 50px;">⛵</div>
                    </div>
                    <div class="ldvr-zone zone-4" style="background: linear-gradient(135deg, #a78bfa, #8b5cf6);">
                        <div class="zone-decoration">🌌</div>
                        <div class="zone-decoration" style="bottom: 40%; left: 40%; font-size: 45px;">✨</div>
                    </div>
                    <div class="ldvr-zone zone-5" style="background: linear-gradient(135deg, #34d399, #10b981);">
                        <div class="zone-decoration">🌲</div>
                        <div class="zone-decoration" style="top: 60%; right: 20%; font-size: 55px;">🏕️</div>
                    </div>
                </div>
                
                <!-- Lesson Modules positioned along the path -->
                <div class="ldvr-modules-container">
                    <?php 
                    $index = 0;
                    $total_lessons = count($lessons);
                    $completed_count = 0;
                    $module_positions = []; // Store positions for SVG path generation
                    
                    foreach ($lessons as $lesson): 
                        $lesson_id = $lesson['post']->ID;
                        $is_completed = learndash_is_lesson_complete($user_id, $lesson_id);
                        
                        if ($is_completed) {
                            $completed_count++;
                        }
                        
                        // Check if user has access to the course
                        $has_access = sfwd_lms_has_access($course_id, $user_id);
                        
                        // For first lesson, always make it available
                        if ($index === 0) {
                            $is_available = true;
                        } else {
                            // Check prerequisites
                            $is_available = $has_access && learndash_lesson_progression_enabled() 
                                ? learndash_is_lesson_complete($user_id, $lessons[$index - 1]['post']->ID)
                                : $has_access;
                        }
                        
                        // Calculate position along the curve
                        $position = $this->calculate_curve_position($index, $total_lessons);
                        
                        // Store position for SVG path generation (convert % to SVG coordinates)
                        $svg_x = ($position['x'] / 100) * 1200; // Convert % to SVG viewBox coordinates
                        $svg_y = ($position['y'] / 100) * 800;
                        $module_positions[] = ['x' => $svg_x, 'y' => $svg_y, 'completed' => $is_completed];
                        
                        $module_class = 'ldvr-path-module';
                        if ($is_completed) {
                            $module_class .= ' completed';
                            $icon = '✅';
                        } elseif (!$is_available) {
                            $module_class .= ' locked';
                            $icon = '🔒';
                        } else {
                            $module_class .= ' current';
                            $icon = '📍';
                        }
                        
                        $index++;
                    ?>
                        <div class="<?php echo $module_class; ?>" 
                             style="left: <?php echo $position['x']; ?>%; top: <?php echo $position['y']; ?>%;"
                             data-lesson-id="<?php echo $lesson_id; ?>"
                             data-svg-x="<?php echo $svg_x; ?>"
                             data-svg-y="<?php echo $svg_y; ?>">
                            
                            <div class="ldvr-module-marker" 
                                 onclick="<?php echo $is_available ? "window.location.href='" . get_permalink($lesson_id) . "'" : "showLockedMessage()"; ?>">
                                <span class="marker-icon"><?php echo $icon; ?></span>
                                <span class="marker-number"><?php echo $index; ?></span>
                            </div>
                            
                            <div class="ldvr-module-popup">
                                <h4>Lesson <?php echo $index; ?>: <?php echo get_the_title($lesson_id); ?></h4>
                                <p><?php echo wp_trim_words(get_the_excerpt($lesson_id), 15) ?: 'Begin your learning journey with this lesson.'; ?></p>
                                <?php if ($is_completed): ?>
                                    <span class="status-badge completed">✓ Completed</span>
                                <?php elseif (!$is_available): ?>
                                    <span class="status-badge locked">🔒 Complete previous lesson</span>
                                <?php else: ?>
                                    <span class="status-badge available">👉 Start Now</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- SVG Path that connects through all lesson modules -->
                <svg class="ldvr-path-svg" viewBox="0 0 1200 800" preserveAspectRatio="none">
                    <!-- Background path (will be drawn by JavaScript) -->
                    <path class="ldvr-main-path" d="" fill="none" />
                    <!-- Progress path (will be drawn by JavaScript) -->
                    <path class="ldvr-progress-path" d="" fill="none" />
                </svg>
                
                <!-- Character/Avatar with progress -->
                <?php 
                $progress_percentage = $total_lessons > 0 ? ($completed_count / $total_lessons) * 100 : 0;
                
                // Calculate the actual position of the last completed lesson
                $last_completed_index = max(0, $completed_count - 1);
                if ($completed_count > 0) {
                    $last_completed_position = $this->calculate_curve_position($last_completed_index, $total_lessons);
                    $character_position = $last_completed_position['x'];
                } else {
                    $character_position = 5; // Start position
                }
                
                // Calculate progress path length based on completed lessons
                $progress_path_percentage = $completed_count > 0 ? ($completed_count / $total_lessons) : 0;
                ?>
                <div class="ldvr-character" style="left: <?php echo $character_position; ?>%;">
                    <span>🚶‍♂️</span>
                </div>
                
                <!-- Add progress path data for JavaScript -->
                <script>
                    if (typeof window.roadmapData === 'undefined') {
                        window.roadmapData = {};
                    }
                    window.roadmapData.progressPercentage = <?php echo $progress_path_percentage; ?>;
                    window.roadmapData.completedLessons = <?php echo $completed_count; ?>;
                    window.roadmapData.totalLessons = <?php echo $total_lessons; ?>;
                </script>
            </div>
            
            <!-- Progress Stats -->
            <div class="ldvr-stats-bar">
                <div class="stat-item">
                    <span class="stat-label">📊 Progress</span>
                    <span class="stat-value"><?php echo round($progress_percentage); ?>%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">✅ Completed</span>
                    <span class="stat-value"><?php echo $completed_count; ?>/<?php echo $total_lessons; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">🎯 Next Goal</span>
                    <span class="stat-value"><?php echo $total_lessons - $completed_count; ?> lessons to go</span>
                </div>
            </div>
        </div>
        
        <script>
        function toggleRoadmapFullscreen() {
            document.body.classList.remove('has-roadmap');
            window.history.back();
        }
        
        function showLockedMessage() {
            alert('Complete the previous lesson to unlock this one!');
        }
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    // Helper functions
    private function calculate_curve_position($index, $total) {
        $progress = $index / max($total - 1, 1);
        
        // Create a sine wave pattern for the path
        $x = $progress * 90 + 5; // 5% to 95% horizontally
        $y = 50 + sin($progress * M_PI * 2) * 30; // Sine wave vertically
        
        return ['x' => $x, 'y' => $y];
    }
    
    private function get_user_progress_position($user_id, $course_id) {
        $progress = learndash_course_progress([
            'user_id' => $user_id,
            'course_id' => $course_id,
            'array' => true
        ]);
        
        return isset($progress['percentage']) ? $progress['percentage'] : 0;
    }
    
    private function get_course_progress($user_id, $course_id) {
        $progress = learndash_course_progress([
            'user_id' => $user_id,
            'course_id' => $course_id,
            'array' => true
        ]);
        
        return isset($progress['percentage']) ? $progress['percentage'] : 0;
    }
    
    private function get_completed_lessons_count($user_id, $course_id) {
        $lessons = learndash_get_course_lessons_list($course_id);
        $completed = 0;
        
        foreach ($lessons as $lesson) {
            if (learndash_is_lesson_complete($user_id, $lesson['post']->ID)) {
                $completed++;
            }
        }
        
        return $completed;
    }
    
    // Original grid roadmap method
    public function render_roadmap_shortcode($atts) {
        $atts = shortcode_atts([
            'course_id' => get_the_ID(),
            'style' => 'modern',
            'columns' => 3,
            'show_progress' => 'true',
            'animation' => 'true'
        ], $atts);
        
        $course_id = intval($atts['course_id']);
        
        // Check if course exists
        if (!$course_id || get_post_type($course_id) !== 'sfwd-courses') {
            return '<p>Invalid course ID provided.</p>';
        }
        
        // Check if LearnDash is active
        if (!function_exists('learndash_get_course_lessons_list')) {
            return '<p>LearnDash plugin is required for this roadmap.</p>';
        }
        
        $lessons = learndash_get_course_lessons_list($course_id);
        $user_id = get_current_user_id();
        
        if (empty($lessons)) {
            return '<p>No lessons found for this course.</p>';
        }
        
        ob_start();
        ?>
        
        <div class="ldvr-grid-roadmap style-<?php echo esc_attr($atts['style']); ?> columns-<?php echo esc_attr($atts['columns']); ?>" 
             data-animation="<?php echo esc_attr($atts['animation']); ?>">
            
            <div class="ldvr-roadmap-header">
                <h2 class="roadmap-title"><?php echo get_the_title($course_id); ?> Learning Path</h2>
                <div class="roadmap-progress">
                    <span>Your Progress: <?php echo $this->get_course_progress($user_id, $course_id); ?>%</span>
                </div>
            </div>
            
            <div class="ldvr-lessons-grid">
                <?php 
                $index = 0;
                foreach ($lessons as $lesson): 
                    $lesson_id = $lesson['post']->ID;
                    $is_completed = learndash_is_lesson_complete($user_id, $lesson_id);
                    $is_available = !learndash_is_lesson_notcomplete($lesson_id, $user_id);
                    
                    $lesson_class = 'ldvr-lesson-card';
                    if ($is_completed) {
                        $lesson_class .= ' completed';
                    } elseif (!$is_available) {
                        $lesson_class .= ' locked';
                    } else {
                        $lesson_class .= ' available';
                    }
                    
                    $index++;
                ?>
                    <div class="<?php echo $lesson_class; ?>" data-lesson-id="<?php echo $lesson_id; ?>">
                        <div class="lesson-number"><?php echo $index; ?></div>
                        
                        <div class="lesson-thumbnail">
                            <?php if (has_post_thumbnail($lesson_id)): ?>
                                <?php echo get_the_post_thumbnail($lesson_id, 'medium'); ?>
                            <?php else: ?>
                                <div class="default-thumbnail">
                                    <span class="lesson-icon">📚</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lesson-content">
                            <h3 class="lesson-title"><?php echo get_the_title($lesson_id); ?></h3>
                            <p class="lesson-excerpt"><?php echo wp_trim_words(get_the_excerpt($lesson_id), 15); ?></p>
                            
                            <div class="lesson-status">
                                <?php if ($is_completed): ?>
                                    <span class="status-badge completed">✅ Completed</span>
                                <?php elseif (!$is_available): ?>
                                    <span class="status-badge locked">🔒 Locked</span>
                                <?php else: ?>
                                    <span class="status-badge available">▶️ Start</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_available): ?>
                                <a href="<?php echo get_permalink($lesson_id); ?>" class="lesson-button">
                                    <?php echo $is_completed ? 'Review' : 'Start Lesson'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
// add_action('plugins_loaded', function() {
LearnDashVisualRoadmap::get_instance();
// });

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create necessary database tables or options if needed
    update_option('ldvr_version', LDVR_VERSION);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

