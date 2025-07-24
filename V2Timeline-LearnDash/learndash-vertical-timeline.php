<?php
/**
 * Plugin Name: LearnDash Vertical Timeline
 * Description: Custom roadmap-style timeline with animated background.
 * Version: 1.2.0
 * Author: Khalil Haimer
 */

function ldvt_enqueue_assets() {
    wp_enqueue_style('ldvt-style', plugin_dir_url(__FILE__) . 'assets/css/timeline-styles.css', array(), '1.2.0');
    wp_enqueue_script('ldvt-scripts', plugin_dir_url(__FILE__) . 'assets/js/timeline-scripts.js', array(), '1.2.0', true);
}
add_action('wp_enqueue_scripts', 'ldvt_enqueue_assets');

function ldvt_shortcode($atts) {
    $atts = shortcode_atts([
        'course_id' => 0
    ], $atts);

    $course_id = intval($atts['course_id']);
    if (!$course_id) return '';

    $post = get_post($course_id);
    if (!$post || $post->post_type !== 'sfwd-courses') return '';

    // Check if LearnDash is active
    if (!function_exists('learndash_get_course_lessons_list')) {
        return '<p>LearnDash plugin is required for this timeline.</p>';
    }

    // Get lessons using LearnDash function
    $lessons = learndash_get_course_lessons_list($course_id);
    
    // Ensure lessons are properly ordered
    if (!empty($lessons)) {
        // Try to get the proper lesson order from LearnDash course builder
        $course_lessons_ordered = array();
        
        // Method 1: Try to get from course builder steps
        if (function_exists('learndash_course_get_steps_by_type')) {
            $lesson_ids = learndash_course_get_steps_by_type($course_id, 'sfwd-lessons');
            if (!empty($lesson_ids)) {
                foreach ($lesson_ids as $lesson_id) {
                    foreach ($lessons as $lesson) {
                        if ($lesson['post']->ID == $lesson_id) {
                            $course_lessons_ordered[] = $lesson;
                            break;
                        }
                    }
                }
                if (count($course_lessons_ordered) === count($lessons)) {
                    $lessons = $course_lessons_ordered;
                }
            }
        }
        
        // Method 2: Fallback - if course builder method didn't work
        if (empty($course_lessons_ordered) || count($course_lessons_ordered) !== count($lessons)) {
            // Sort by multiple criteria for proper ordering
            usort($lessons, function($a, $b) {
                // First priority: menu_order (if set and not 0)
                $order_a = isset($a['post']->menu_order) ? (int)$a['post']->menu_order : 0;
                $order_b = isset($b['post']->menu_order) ? (int)$b['post']->menu_order : 0;
                
                if ($order_a !== $order_b && ($order_a > 0 || $order_b > 0)) {
                    return $order_a - $order_b;
                }
                
                // Second priority: post_date (creation date - older first)
                $date_a = strtotime($a['post']->post_date);
                $date_b = strtotime($b['post']->post_date);
                
                if ($date_a !== $date_b) {
                    return $date_a - $date_b;
                }
                
                // Third priority: post_title alphabetical
                $title_compare = strcmp($a['post']->post_title, $b['post']->post_title);
                if ($title_compare !== 0) {
                    return $title_compare;
                }
                
                // Final fallback: ID
                return (int)$a['post']->ID - (int)$b['post']->ID;
            });
        }
    }

    if (empty($lessons)) {
        return '<p>No lessons found for this course.</p>';
    }

    $lesson_count = count($lessons);
    $quiz_count = count(learndash_get_course_quiz_list($course_id));
    $duration = get_post_meta($course_id, 'course_duration', true);
    $students = get_post_meta($course_id, 'enrolled_count', true);
    
    // Get user progress
    $user_id = get_current_user_id();
    $completed_lessons = 0;
    foreach ($lessons as $lesson) {
        if (learndash_is_lesson_complete($user_id, $lesson['post']->ID, $course_id)) {
            $completed_lessons++;
        }
    }
    $progress_percentage = $lesson_count > 0 ? round(($completed_lessons / $lesson_count) * 100) : 0;

    ob_start();
    ?>
    <div class="ldvt-wrapper">
      <canvas id="bubbleCanvas"></canvas>
      <div class="ldvt-container">
        <div class="ldvt-course-overview">
          <div class="ldvt-overview-main">
            <h1 class="ldvt-course-title"><?php echo esc_html(get_the_title($course_id)); ?></h1>
            <div class="ldvt-course-meta">
              <div class="ldvt-meta-item"><i class="ldvt-icon-lessons"></i> <?php echo $lesson_count; ?> Lessons</div>
              <div class="ldvt-meta-item"><i class="ldvt-icon-quizzes"></i> <?php echo $quiz_count; ?> Quizzes</div>
              <div class="ldvt-meta-item"><i class="ldvt-icon-duration"></i> <?php echo esc_html($duration ?: 'Self-paced'); ?></div>
              <div class="ldvt-meta-item"><i class="ldvt-icon-students"></i> <?php echo esc_html($students ?: '100+'); ?> Students</div>
            </div>
            <div class="ldvt-course-excerpt">
              <?php echo wpautop(get_the_excerpt($course_id)); ?>
            </div>
          </div>

          <div class="ldvt-overview-progress">
            <h3>Your Progress</h3>
            <div class="ldvt-progress-circle-container">
              <svg class="ldvt-progress-circle" width="150" height="150">
                <circle class="ldvt-progress-circle-bg" cx="75" cy="75" r="65"></circle>
                <circle class="ldvt-progress-circle-fill" cx="75" cy="75" r="65" stroke-dasharray="410" stroke-dashoffset="<?php echo 410 - (410 * $progress_percentage / 100); ?>"></circle>
              </svg>
              <div class="ldvt-progress-text">
                <span class="ldvt-progress-number"><?php echo $progress_percentage; ?>%</span>
                <span class="ldvt-progress-label">Completed</span>
              </div>
            </div>
            <div class="ldvt-progress-stats">
              <div class="ldvt-stat"><span class="ldvt-stat-value"><?php echo $completed_lessons; ?>/<?php echo $lesson_count; ?></span><span class="ldvt-stat-label">Lessons</span></div>
              <div class="ldvt-stat"><span class="ldvt-stat-value"><?php echo $quiz_count; ?></span><span class="ldvt-stat-label">Quizzes</span></div>
              <div class="ldvt-stat"><span class="ldvt-stat-value"><?php echo esc_html($duration ?: '5h'); ?></span><span class="ldvt-stat-label">Duration</span></div>
            </div>
            <p class="ldvt-last-activity">Last active: <?php echo human_time_diff(get_user_meta($user_id, 'ldvt_last_activity', true) ?: time() - 172800, time()); ?> ago</p>
          </div>

          <div class="ldvt-overview-certificate">
            <h3>Certificate</h3>
            <div class="ldvt-certificate-preview">
              <div class="ldvt-icon-certificate"></div>
              <p>Earn your certificate after 100% completion</p>
              <div class="ldvt-certificate-progress">
                <div class="ldvt-certificate-progress-bar">
                  <div class="ldvt-certificate-progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                </div>
                <span><?php echo $progress_percentage; ?>% Complete</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Lessons Timeline -->
        <div class="ldvt-lessons-timeline">
          <div class="ldvt-timeline-header">
            <h2>Course Roadmap</h2>
            <p>Follow your learning path step by step</p>
          </div>
          <div class="ldvt-roadmap-container">
            <?php 
            $lesson_index = 1;
            foreach ($lessons as $lesson) {
              $lesson_id = $lesson['post']->ID;
              $lesson_post = $lesson['post'];
              $is_completed = learndash_is_lesson_complete($user_id, $lesson_id, $course_id);
              
              // Get lesson topics/steps
              $lesson_topics = learndash_get_topic_list($lesson_id, $course_id);
              $lesson_quizzes = learndash_get_lesson_quiz_list($lesson_id, $user_id, $course_id);
              
              // Calculate lesson progress
              $total_steps = count($lesson_topics) + count($lesson_quizzes);
              if ($total_steps == 0) $total_steps = 1; // At least show the main lesson
              
              ?>
              <div class="ldvt-roadmap-level <?php echo $is_completed ? 'completed' : ''; ?>" data-lesson-id="<?php echo $lesson_id; ?>">
                <!-- Level Header -->
                <div class="ldvt-level-header">
                  <div class="ldvt-level-number"><?php echo $lesson_index; ?></div>
                  <div class="ldvt-level-info">
                    <h3>Level <?php echo $lesson_index; ?></h3>
                    <h2><?php echo esc_html($lesson_post->post_title); ?></h2>
                  </div>
                </div>
                
                <div class="ldvt-level-description">
                  <?php 
                  $description = $lesson_post->post_excerpt ?: $lesson_post->post_content;
                  echo wp_trim_words($description, 20); 
                  ?>
                </div>
                
                <!-- Lesson Card -->
                <div class="ldvt-lesson-card" data-expandable="true">
                  <div class="ldvt-lesson-header">
                    <div class="ldvt-lesson-icon">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z" fill="currentColor"/>
                      </svg>
                    </div>
                    <div class="ldvt-lesson-info">
                      <h4><?php echo esc_html($lesson_post->post_title); ?> <span class="ldvt-lesson-badge">🔷</span></h4>
                      <p>Learn Course • <?php echo $total_steps; ?> Problems</p>
                    </div>
                    <a href="<?php echo get_permalink($lesson_id); ?>" class="ldvt-start-btn">Start</a>
                  </div>
                  
                  <!-- Expandable Content -->
                  <div class="ldvt-lesson-content">
                    <?php if (!empty($lesson_topics)): ?>
                      <?php foreach ($lesson_topics as $topic): 
                        $topic_completed = learndash_is_topic_complete($user_id, $topic['post']->ID, $lesson_id);
                        ?>
                        <div class="ldvt-lesson-item <?php echo $topic_completed ? 'completed' : ''; ?>">
                          <div class="ldvt-chevron">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                              <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                          </div>
                          <span><?php echo esc_html($topic['post']->post_title); ?></span>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($lesson_quizzes)): ?>
                      <?php foreach ($lesson_quizzes as $quiz): 
                        $quiz_completed = learndash_is_quiz_complete($user_id, $quiz['post']->ID, $lesson_id);
                        ?>
                        <div class="ldvt-lesson-item <?php echo $quiz_completed ? 'completed' : ''; ?>">
                          <div class="ldvt-chevron">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                              <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                          </div>
                          <span><?php echo esc_html($quiz['post']->post_title); ?> (Quiz)</span>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (empty($lesson_topics) && empty($lesson_quizzes)): ?>
                      <div class="ldvt-lesson-item">
                        <div class="ldvt-chevron">
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2"/>
                          </svg>
                        </div>
                        <span>Main Lesson Content</span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Connection Line -->
                <?php if ($lesson_index < count($lessons)): ?>
                  <div class="ldvt-connection-line"></div>
                <?php endif; ?>
              </div>
              <?php 
              $lesson_index++;
            } ?>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ld_vertical_timeline', 'ldvt_shortcode');
