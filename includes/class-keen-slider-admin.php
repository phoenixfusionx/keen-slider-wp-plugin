<?php
/**
 * Keen Slider Admin - Custom Post Type & Slide Builder UI
 */

if (!defined('ABSPATH')) {
    exit;
}

class Keen_Slider_Admin {

    const CPT = 'keen_slider';
    const META_SLIDES = '_keen_slider_slides';
    const META_SETTINGS = '_keen_slider_settings';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::CPT, [$this, 'save_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('manage_' . self::CPT . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::CPT . '_posts_custom_column', [$this, 'column_content'], 10, 2);
    }

    public function register_post_type() {
        register_post_type(self::CPT, [
            'labels' => [
                'name'               => __('Sliders', 'keen-slider'),
                'singular_name'      => __('Slider', 'keen-slider'),
                'add_new'            => __('Add New', 'keen-slider'),
                'add_new_item'       => __('Add New Slider', 'keen-slider'),
                'edit_item'          => __('Edit Slider', 'keen-slider'),
                'new_item'           => __('New Slider', 'keen-slider'),
                'view_item'          => __('View Slider', 'keen-slider'),
                'search_items'       => __('Search Sliders', 'keen-slider'),
                'not_found'          => __('No sliders found', 'keen-slider'),
                'not_found_in_trash' => __('No sliders found in Trash', 'keen-slider'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-slides',
            'menu_position'       => 25,
            'capability_type'     => 'post',
            'supports'            => ['title'],
            'has_archive'         => false,
            'rewrite'             => false,
            'show_in_rest'        => true,
            'rest_base'           => 'keen_sliders',
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'keen_slider_slides',
            __('Slides', 'keen-slider'),
            [$this, 'render_slides_meta_box'],
            self::CPT,
            'normal'
        );
        add_meta_box(
            'keen_slider_settings',
            __('Slider Settings', 'keen-slider'),
            [$this, 'render_settings_meta_box'],
            self::CPT,
            'side'
        );
        add_meta_box(
            'keen_slider_shortcode',
            __('How to Use', 'keen-slider'),
            [$this, 'render_shortcode_meta_box'],
            self::CPT,
            'side'
        );
    }

    public function render_slides_meta_box($post) {
        wp_nonce_field('keen_slider_save', 'keen_slider_nonce');
        $slides = get_post_meta($post->ID, self::META_SLIDES, true);
        $slides = is_array($slides) ? $slides : [];

        ?>
        <div class="keen-slider-admin">
            <p class="description"><?php esc_html_e('Add review slides below. Drag to reorder. Each slide: user name, avatar (or first initial if not set), star rating, and review content.', 'keen-slider'); ?></p>
            <div id="keen-slider-slides-list" class="keen-slider-slides-list">
                <?php
                foreach ($slides as $i => $slide) {
                    $this->render_slide_row($i, $slide);
                }
                ?>
            </div>
            <p>
                <button type="button" class="button keen-slider-add-slide" id="keen-slider-add-slide">
                    <?php esc_html_e('+ Add Slide', 'keen-slider'); ?>
                </button>
            </p>
            <script type="text/template" id="keen-slider-slide-template">
                <div class="keen-slider-slide-row template" data-index="{{INDEX}}">
                    <span class="keen-slider-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e('Drag to reorder', 'keen-slider'); ?>"></span>
                    <div class="keen-slider-slide-preview">
                        <div class="keen-slider-thumb-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                            <span><?php esc_html_e('No avatar', 'keen-slider'); ?></span>
                        </div>
                        <input type="hidden" name="keen_slider_slides[{{INDEX}}][image_id]" value="" class="keen-slider-image-id">
                        <div class="keen-slider-image-actions">
                            <button type="button" class="button button-small keen-slider-upload-image"><?php esc_html_e('Select Avatar', 'keen-slider'); ?></button>
                        </div>
                    </div>
                    <div class="keen-slider-slide-fields">
                        <p>
                            <label><?php esc_html_e('User Name', 'keen-slider'); ?></label>
                            <input type="text" name="keen_slider_slides[{{INDEX}}][title]" value="" class="widefat" placeholder="<?php esc_attr_e('e.g. John D.', 'keen-slider'); ?>">
                        </p>
                        <p>
                            <label><?php esc_html_e('Star Rating', 'keen-slider'); ?></label>
                            <select name="keen_slider_slides[{{INDEX}}][star_count]" class="widefat">
                                <option value="5">5 <?php esc_html_e('stars', 'keen-slider'); ?></option>
                                <option value="4">4 <?php esc_html_e('stars', 'keen-slider'); ?></option>
                                <option value="3">3 <?php esc_html_e('stars', 'keen-slider'); ?></option>
                                <option value="2">2 <?php esc_html_e('stars', 'keen-slider'); ?></option>
                                <option value="1">1 <?php esc_html_e('star', 'keen-slider'); ?></option>
                            </select>
                        </p>
                        <p>
                            <label><?php esc_html_e('Review Content', 'keen-slider'); ?></label>
                            <textarea name="keen_slider_slides[{{INDEX}}][desc]" rows="3" class="widefat" placeholder="<?php esc_attr_e('Customer review text...', 'keen-slider'); ?>"></textarea>
                        </p>
                    </div>
                    <button type="button" class="button keen-slider-remove-slide" title="<?php esc_attr_e('Remove slide', 'keen-slider'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </script>
        </div>
        <?php
    }

    private function render_slide_row($index, $slide = [], $is_template = false) {
        $slide = wp_parse_args($slide, [
            'image_id'   => '',
            'title'     => '',
            'desc'      => '',
            'star_count' => '5',
        ]);
        $image_url = $slide['image_id'] ? wp_get_attachment_image_url($slide['image_id'], 'thumbnail') : '';
        $row_class = $is_template ? 'keen-slider-slide-row template' : 'keen-slider-slide-row';
        ?>
        <div class="<?php echo esc_attr($row_class); ?>" data-index="<?php echo esc_attr($index); ?>">
            <span class="keen-slider-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e('Drag to reorder', 'keen-slider'); ?>"></span>
            <div class="keen-slider-slide-preview">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="" class="keen-slider-thumb">
                <?php else : ?>
                    <div class="keen-slider-thumb-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                        <span><?php esc_html_e('No avatar', 'keen-slider'); ?></span>
                    </div>
                <?php endif; ?>
                <input type="hidden" name="keen_slider_slides[<?php echo esc_attr($index); ?>][image_id]" value="<?php echo esc_attr($slide['image_id']); ?>" class="keen-slider-image-id">
                <div class="keen-slider-image-actions">
                    <button type="button" class="button button-small keen-slider-upload-image"><?php esc_html_e('Select Avatar', 'keen-slider'); ?></button>
                    <?php if ($image_url) : ?>
                        <button type="button" class="button button-small keen-slider-remove-image"><?php esc_html_e('Remove', 'keen-slider'); ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="keen-slider-slide-fields">
                <p>
                    <label><?php esc_html_e('User Name', 'keen-slider'); ?></label>
                    <input type="text" name="keen_slider_slides[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($slide['title']); ?>" class="widefat" placeholder="<?php esc_attr_e('e.g. John D.', 'keen-slider'); ?>">
                </p>
                <p>
                    <label><?php esc_html_e('Star Rating', 'keen-slider'); ?></label>
                    <select name="keen_slider_slides[<?php echo esc_attr($index); ?>][star_count]" class="widefat">
                        <?php for ($s = 5; $s >= 1; $s--) : ?>
                            <option value="<?php echo $s; ?>" <?php selected($slide['star_count'], (string) $s); ?>><?php echo $s . ' ' . esc_html(_n('star', 'stars', $s, 'keen-slider')); ?></option>
                        <?php endfor; ?>
                    </select>
                </p>
                <p>
                    <label><?php esc_html_e('Review Content', 'keen-slider'); ?></label>
                    <textarea name="keen_slider_slides[<?php echo esc_attr($index); ?>][desc]" rows="3" class="widefat" placeholder="<?php esc_attr_e('Customer review text...', 'keen-slider'); ?>"><?php echo esc_textarea($slide['desc']); ?></textarea>
                </p>
            </div>
            <button type="button" class="button keen-slider-remove-slide" title="<?php esc_attr_e('Remove slide', 'keen-slider'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <?php
    }

    public function render_settings_meta_box($post) {
        $settings = get_post_meta($post->ID, self::META_SETTINGS, true);
        $settings = wp_parse_args(is_array($settings) ? $settings : [], [
            'loop'            => '1',
            'arrows'          => '1',
            'dots'            => '1',
            'autoplay'        => '0',
            'modal'           => '0',
            'interval'        => 5000,
            'slides_per_view' => '1',
            'spacing'         => '16',
        ]);
        ?>
        <p>
            <label for="keen_slider_spacing"><?php esc_html_e('Spacing between slides (px)', 'keen-slider'); ?></label>
            <input type="number" id="keen_slider_spacing" name="keen_slider_settings[spacing]" value="<?php echo esc_attr($settings['spacing']); ?>" min="0" max="48" step="4" class="small-text">
            <span class="description"><?php esc_html_e('Gap between items. Keen-slider adjusts slide width automatically.', 'keen-slider'); ?></span>
        </p>
        <p>
            <label for="keen_slider_per_view"><?php esc_html_e('Slides visible at once', 'keen-slider'); ?></label>
            <input type="number" id="keen_slider_per_view" name="keen_slider_settings[slides_per_view]" value="<?php echo esc_attr($settings['slides_per_view']); ?>" min="1" max="10" class="small-text">
            <span class="description"><?php esc_html_e('1 = one slide at a time (recommended)', 'keen-slider'); ?></span>
        </p>
        <p>
            <label>
                <input type="checkbox" name="keen_slider_settings[loop]" value="1" <?php checked($settings['loop'], '1'); ?>>
                <?php esc_html_e('Loop (infinite scroll)', 'keen-slider'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="keen_slider_settings[arrows]" value="1" <?php checked($settings['arrows'], '1'); ?>>
                <?php esc_html_e('Show arrows', 'keen-slider'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="keen_slider_settings[dots]" value="1" <?php checked($settings['dots'], '1'); ?>>
                <?php esc_html_e('Show dots', 'keen-slider'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="keen_slider_settings[autoplay]" value="1" <?php checked($settings['autoplay'], '1'); ?>>
                <?php esc_html_e('Autoplay', 'keen-slider'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="keen_slider_settings[modal]" value="1" <?php checked($settings['modal'], '1'); ?>>
                <?php esc_html_e('Modal (click slide to open in lightbox)', 'keen-slider'); ?>
            </label>
        </p>
        <p>
            <label for="keen_slider_interval"><?php esc_html_e('Autoplay interval (ms)', 'keen-slider'); ?></label>
            <input type="number" id="keen_slider_interval" name="keen_slider_settings[interval]" value="<?php echo esc_attr($settings['interval']); ?>" min="1000" step="500" class="small-text">
        </p>
        <?php
    }

    public function render_shortcode_meta_box($post) {
        if ($post->post_status !== 'publish') {
            echo '<p>' . esc_html__('Publish the slider first to get the shortcode.', 'keen-slider') . '</p>';
            return;
        }
        $shortcode = '[keen_slider id="' . $post->ID . '"]';
        ?>
        <p><?php esc_html_e('Copy this shortcode and paste it into any page or post:', 'keen-slider'); ?></p>
        <code class="keen-slider-shortcode-copy"><?php echo esc_html($shortcode); ?></code>
        <p><button type="button" class="button button-small keen-slider-copy-shortcode"><?php esc_html_e('Copy to clipboard', 'keen-slider'); ?></button></p>
        <?php
    }

    public function save_meta($post_id, $post) {
        if (!isset($_POST['keen_slider_nonce']) || !wp_verify_nonce($_POST['keen_slider_nonce'], 'keen_slider_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['keen_slider_slides']) && is_array($_POST['keen_slider_slides'])) {
            $slides = [];
            foreach ($_POST['keen_slider_slides'] as $slide) {
                $slides[] = [
                    'image_id'   => absint($slide['image_id'] ?? 0),
                    'title'     => sanitize_text_field($slide['title'] ?? ''),
                    'desc'      => sanitize_textarea_field($slide['desc'] ?? ''),
                    'star_count' => max(1, min(5, (int) ($slide['star_count'] ?? 5))),
                ];
            }
            update_post_meta($post_id, self::META_SLIDES, $slides);
        }

        if (isset($_POST['keen_slider_settings']) && is_array($_POST['keen_slider_settings'])) {
            $settings = [
                'loop'            => !empty($_POST['keen_slider_settings']['loop']) ? '1' : '0',
                'arrows'          => !empty($_POST['keen_slider_settings']['arrows']) ? '1' : '0',
                'dots'            => !empty($_POST['keen_slider_settings']['dots']) ? '1' : '0',
                'autoplay'        => !empty($_POST['keen_slider_settings']['autoplay']) ? '1' : '0',
                'modal'           => !empty($_POST['keen_slider_settings']['modal']) ? '1' : '0',
                'interval'        => absint($_POST['keen_slider_settings']['interval'] ?? 5000),
                'slides_per_view' => max(1, min(10, (int) ($_POST['keen_slider_settings']['slides_per_view'] ?? 1))),
                'spacing'         => max(0, min(48, (int) ($_POST['keen_slider_settings']['spacing'] ?? 16))),
            ];
            update_post_meta($post_id, self::META_SETTINGS, $settings);
        }
    }

    public function enqueue_assets($hook) {
        global $post_type;
        if ($post_type !== self::CPT || !in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'keen-slider-admin',
            KEEN_SLIDER_URL . 'assets/admin/keen-slider-admin.css',
            [],
            KEEN_SLIDER_VERSION
        );
        wp_enqueue_script(
            'keen-slider-admin',
            KEEN_SLIDER_URL . 'assets/admin/keen-slider-admin.js',
            ['jquery', 'jquery-ui-sortable'],
            KEEN_SLIDER_VERSION,
            true
        );
    }

    public function columns($columns) {
        $new = [];
        $new['cb'] = $columns['cb'];
        $new['title'] = $columns['title'];
        $new['slides_count'] = __('Slides', 'keen-slider');
        $new['shortcode'] = __('Shortcode', 'keen-slider');
        $new['date'] = $columns['date'];
        return $new;
    }

    public function column_content($column, $post_id) {
        if ($column === 'slides_count') {
            $slides = get_post_meta($post_id, self::META_SLIDES, true);
            $count = is_array($slides) ? count($slides) : 0;
            echo esc_html($count);
        }
        if ($column === 'shortcode') {
            echo '<code>[keen_slider id="' . esc_attr($post_id) . '"]</code>';
        }
    }
}
