<?php
/**
 * Plugin Name: LearnDash Vertical Timeline
 * Description: Custom roadmap-style timeline with animated background.
 * Version: 1.1
 * Author: Your Name
 */

function ldvt_enqueue_assets() {
    wp_enqueue_style('ldvt-style', plugin_dir_url(__FILE__) . 'assets/css/timeline-styles.css');
    wp_enqueue_script('ldvt-scripts', plugin_dir_url(__FILE__) . 'assets/js/timeline-scripts.js', array(), null, true);
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

    $lessons = learndash_get_lesson_list($course_id, ['num' => 0]);
    $lesson_count = count($lessons);
    $quiz_count = count(learndash_get_course_quiz_list($course_id));
    $duration = get_post_meta($course_id, 'course_duration', true);
    $students = get_post_meta($course_id, 'enrolled_count', true);

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
            <div class="ldvt-action-buttons">
              <a href="<?php echo esc_url(get_permalink($course_id)); ?>" class="ldvt-btn ldvt-btn-primary">Start Roadmap</a>
              <a href="#" class="ldvt-btn ldvt-btn-secondary">View Reviews</a>
            </div>
          </div>

          <div class="ldvt-overview-progress">
            <h3>Your Progress</h3>
            <div class="ldvt-progress-circle-container">
              <svg class="ldvt-progress-circle" width="150" height="150">
                <circle class="ldvt-progress-circle-bg" cx="75" cy="75" r="65"></circle>
                <circle class="ldvt-progress-circle-fill" cx="75" cy="75" r="65" stroke-dasharray="410" stroke-dashoffset="120"></circle>
              </svg>
              <div class="ldvt-progress-text">
                <span class="ldvt-progress-number">71%</span>
                <span class="ldvt-progress-label">Completed</span>
              </div>
            </div>
            <div class="ldvt-progress-stats">
              <div class="ldvt-stat"><span class="ldvt-stat-value"><?php echo $lesson_count; ?></span><span class="ldvt-stat-label">Lessons</span></div>
              <div class="ldvt-stat"><span class="ldvt-stat-value"><?php echo $quiz_count; ?></span><span class="ldvt-stat-label">Quizzes</span></div>
              <div class="ldvt-stat"><span class="ldvt-stat-value">5h</span><span class="ldvt-stat-label">Duration</span></div>
            </div>
            <p class="ldvt-last-activity">Last active: 2 days ago</p>
          </div>

          <div class="ldvt-overview-certificate">
            <h3>Certificate</h3>
            <div class="ldvt-certificate-preview">
              <div class="ldvt-icon-certificate"></div>
              <p>Earn your certificate after 100% completion</p>
              <div class="ldvt-certificate-progress">
                <div class="ldvt-certificate-progress-bar">
                  <div class="ldvt-certificate-progress-fill" style="width: 71%;"></div>
                </div>
                <span>71% Complete</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ld_vertical_timeline', 'ldvt_shortcode');
