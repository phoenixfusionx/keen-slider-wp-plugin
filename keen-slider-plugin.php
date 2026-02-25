<?php
/**
 * Plugin Name: Keen Slider
 * Description: A dynamic slider plugin powered by keen-slider. Visual admin UI for non-technical users, shortcodes, and dynamic post queries.
 * Version: 1.0.0
 * Author: Custom
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KEEN_SLIDER_VERSION', '1.0.0');
define('KEEN_SLIDER_PATH', plugin_dir_path(__FILE__));
define('KEEN_SLIDER_URL', plugin_dir_url(__FILE__));

require_once KEEN_SLIDER_PATH . 'includes/class-keen-slider-admin.php';
new Keen_Slider_Admin();
require_once KEEN_SLIDER_PATH . 'includes/class-keen-slider-block.php';
new Keen_Slider_Block();

/**
 * Enqueue keen-slider assets
 */
function keen_slider_enqueue_assets() {
    wp_enqueue_style(
        'keen-slider',
        'https://cdn.jsdelivr.net/npm/keen-slider@6.8.5/keen-slider.min.css',
        [],
        '6.8.5'
    );
    wp_enqueue_script(
        'keen-slider',
        'https://cdn.jsdelivr.net/npm/keen-slider@6.8.5/keen-slider.min.js',
        [],
        '6.8.5',
        true
    );
    wp_enqueue_style(
        'keen-slider-plugin',
        KEEN_SLIDER_URL . 'assets/keen-slider-plugin.css',
        ['keen-slider'],
        KEEN_SLIDER_VERSION
    );
    wp_enqueue_script(
        'keen-slider-plugin',
        KEEN_SLIDER_URL . 'assets/keen-slider-plugin.js',
        ['keen-slider'],
        KEEN_SLIDER_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'keen_slider_enqueue_assets');

/**
 * Shortcode: [keen_slide] - Individual slide (used inside keen_slider)
 */
function keen_slider_slide_shortcode($atts, $content = null) {
    return '<div class="keen-slider__slide">' . do_shortcode($content) . '</div>';
}
add_shortcode('keen_slide', 'keen_slider_slide_shortcode');

/**
 * Shortcode: [keen_slider] - Main slider container
 *
 * Attributes:
 * - source: 'content' (default) | 'posts' | 'recent'
 * - post_type: Post type for dynamic source (default: 'post')
 * - count: Number of items for dynamic source (default: 5)
 * - loop: true|false (default: true)
 * - arrows: true|false (default: true)
 * - dots: true|false (default: true)
 * - autoplay: true|false (default: false)
 * - modal: true|false (default: false) – click slide to open in lightbox
 * - interval: Autoplay interval in ms (default: 5000)
 * - slides_per_view: Number of visible slides (default: 1)
 * - spacing: Gap between slides in px (default: 0)
 * - id: Post ID of a saved slider (from admin UI)
 */
function keen_slider_shortcode($atts, $content = null) {
    $atts = shortcode_atts([
        'id'             => '',
        'source'         => 'content',
        'post_type'      => 'post',
        'count'          => 5,
        'loop'           => 'true',
        'arrows'         => 'true',
        'dots'           => 'true',
        'autoplay'       => 'false',
        'modal'          => 'false',
        'interval'       => 5000,
        'slides_per_view' => 1,
        'spacing'        => 0,
        'class'          => '',
    ], $atts, 'keen_slider');

    $id = 'keen-slider-' . uniqid();
    $slides_html = '';

    // Load from saved slider (admin UI)
    if (!empty($atts['id'])) {
        $slides_html = keen_slider_get_saved_slides($atts['id']);
        $saved = keen_slider_get_saved_settings($atts['id']);
        if ($saved) {
            $atts = array_merge($atts, $saved);
        }
    } elseif ($atts['source'] === 'content' && !empty($content)) {
        // Parse inner [keen_slide] shortcodes
        $slides = preg_split('/\[\s*keen_slide\s*\]/i', $content);
        $slide_parts = preg_split('/\[\s*\/\s*keen_slide\s*\]/i', $content);
        
        // Alternative: extract slides by regex
        preg_match_all('/\[\s*keen_slide\s*\](.*?)\[\s*\/\s*keen_slide\s*\]/is', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $slide_content) {
                $slides_html .= '<div class="keen-slider__slide">' . do_shortcode(trim($slide_content)) . '</div>';
            }
        } else {
            // Fallback: wrap entire content as single slide or split by delimiter
            $slides_html = '<div class="keen-slider__slide">' . do_shortcode($content) . '</div>';
        }
    } elseif (in_array($atts['source'], ['posts', 'recent'], true)) {
        $slides_html = keen_slider_get_dynamic_slides($atts);
    }

    if (empty($slides_html)) {
        return '<!-- Keen Slider: No slides to display -->';
    }

    $config = [
        'loop'           => filter_var($atts['loop'], FILTER_VALIDATE_BOOLEAN),
        'arrows'         => filter_var($atts['arrows'], FILTER_VALIDATE_BOOLEAN),
        'dots'           => filter_var($atts['dots'], FILTER_VALIDATE_BOOLEAN),
        'autoplay'       => filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN),
        'modal'          => filter_var($atts['modal'], FILTER_VALIDATE_BOOLEAN),
        'interval'       => absint($atts['interval']),
        'slidesPerView'  => max(1, (int) $atts['slides_per_view']),
        'spacing'        => max(0, absint($atts['spacing'])),
    ];

    $wrapper_class = trim('keen-slider-wrapper ' . esc_attr($atts['class']));

    ob_start();
    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>" data-keen-config="<?php echo esc_attr(wp_json_encode($config)); ?>">
        <?php if ($config['arrows']) : ?>
            <button type="button" class="keen-slider-arrow keen-slider-arrow--prev" aria-label="<?php esc_attr_e('Previous', 'keen-slider'); ?>">
                <span aria-hidden="true">‹</span>
            </button>
        <?php endif; ?>
        <div id="<?php echo esc_attr($id); ?>" class="keen-slider">
            <?php echo $slides_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php if ($config['arrows']) : ?>
            <button type="button" class="keen-slider-arrow keen-slider-arrow--next" aria-label="<?php esc_attr_e('Next', 'keen-slider'); ?>">
                <span aria-hidden="true">›</span>
            </button>
        <?php endif; ?>
        <?php if ($config['dots']) : ?>
            <div class="keen-slider-dots" role="tablist"></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('keen_slider', 'keen_slider_shortcode');

/**
 * Fetch dynamic slides from WordPress posts
 */
function keen_slider_get_dynamic_slides($atts) {
    $query_args = [
        'post_type'      => sanitize_key($atts['post_type']),
        'posts_per_page' => max(1, (int) $atts['count']),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($query_args);
    $html = '';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $thumbnail = get_the_post_thumbnail(get_the_ID(), 'large');
            $title = get_the_title();
            $excerpt = get_the_excerpt();
            $link = get_permalink();

            $html .= '<div class="keen-slider__slide keen-slider__slide--dynamic">';
            $html .= '<div class="keen-slider__slide-inner">';
            if ($thumbnail) {
                $html .= '<a href="' . esc_url($link) . '" class="keen-slider__slide-image">' . $thumbnail . '</a>';
            }
            $html .= '<div class="keen-slider__slide-content">';
            $html .= '<h3 class="keen-slider__slide-title"><a href="' . esc_url($link) . '">' . esc_html($title) . '</a></h3>';
            if ($excerpt) {
                $html .= '<p class="keen-slider__slide-excerpt">' . esc_html($excerpt) . '</p>';
            }
            $html .= '</div></div></div>';
        }
        wp_reset_postdata();
    }

    return $html;
}

/**
 * Google G icon SVG for review cards
 */
function keen_slider_google_icon_svg() {
    return '<svg class="keen-slider__google-g" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>';
}

/**
 * Get a consistent color from a string (for avatar initial background)
 */
function keen_slider_avatar_color($name) {
    $colors = ['#0ea5e9', '#14b8a6', '#8b5cf6', '#f59e0b', '#ef4444', '#ec4899', '#6366f1', '#22c55e', '#e11d48', '#0d9488'];
    $hash = crc32($name);
    return $colors[abs($hash) % count($colors)];
}

/**
 * Get slides from a saved slider (admin UI)
 */
function keen_slider_get_saved_slides($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) {
        return '';
    }
    $slides = get_post_meta($post_id, '_keen_slider_slides', true);
    if (!is_array($slides) || empty($slides)) {
        return '';
    }
    $html = '';
    foreach ($slides as $slide) {
        $slide = wp_parse_args($slide, ['image_id' => '', 'title' => '', 'desc' => '', 'star_count' => 5]);
        $user_name = esc_html($slide['title']);
        $review = nl2br(esc_html($slide['desc']));
        $stars = max(1, min(5, (int) $slide['star_count']));
        $initial = $user_name ? mb_substr($user_name, 0, 1) : '?';
        $bg_color = keen_slider_avatar_color($user_name ?: 'default');

        $html .= '<div class="keen-slider__slide keen-slider__slide--saved keen-slider__slide--review">';
        $html .= '<div class="keen-slider__slide-inner keen-slider__review-card">';
        $html .= '<span class="keen-slider__google-icon" aria-hidden="true">' . keen_slider_google_icon_svg() . '</span>';
        $html .= '<div class="keen-slider__review-header">';
        if ($slide['image_id']) {
            $avatar_html = wp_get_attachment_image($slide['image_id'], 'thumbnail', false, ['class' => 'keen-slider__avatar-img']);
            $html .= '<div class="keen-slider__avatar">' . $avatar_html . '</div>';
        } else {
            $html .= '<div class="keen-slider__avatar keen-slider__avatar--initial" style="background-color:' . esc_attr($bg_color) . '">' . esc_html(strtoupper($initial)) . '</div>';
        }
        $html .= '<div class="keen-slider__review-meta">';
        $html .= '<span class="keen-slider__review-name">' . $user_name . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="keen-slider__review-stars" aria-label="' . esc_attr(sprintf(__('%d out of 5 stars', 'keen-slider'), $stars)) . '">';
        for ($i = 0; $i < 5; $i++) {
            $html .= '<span class="keen-slider__star' . ($i < $stars ? ' keen-slider__star--filled' : '') . '">★</span>';
        }
        $html .= '</div>';
        $html .= '<div class="keen-slider__review-content">' . $review . '</div>';
        $html .= '</div></div>';
    }
    return $html;
}

/**
 * Get saved slider settings for shortcode overrides
 */
function keen_slider_get_saved_settings($post_id) {
    $settings = get_post_meta($post_id, '_keen_slider_settings', true);
    if (!is_array($settings)) {
        return [];
    }
    return [
        'loop'            => !empty($settings['loop']) ? 'true' : 'false',
        'arrows'          => !empty($settings['arrows']) ? 'true' : 'false',
        'dots'            => !empty($settings['dots']) ? 'true' : 'false',
        'autoplay'        => !empty($settings['autoplay']) ? 'true' : 'false',
        'modal'           => !empty($settings['modal']) ? 'true' : 'false',
        'interval'        => isset($settings['interval']) ? (int) $settings['interval'] : 5000,
        'slides_per_view' => isset($settings['slides_per_view']) ? max(1, (int) $settings['slides_per_view']) : 1,
        'spacing'         => isset($settings['spacing']) ? max(0, (int) $settings['spacing']) : 16,
    ];
}
