<?php
/**
 * Keen Slider Gutenberg Block
 */

if (!defined('ABSPATH')) {
    exit;
}

class Keen_Slider_Block {

    public function __construct() {
        add_action('init', [$this, 'register_block']);
    }

    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        register_block_type(KEEN_SLIDER_PATH . 'blocks/keen-slider', [
            'render_callback' => [$this, 'render'],
        ]);
    }

    public function render($attributes) {
        $id = isset($attributes['sliderId']) ? absint($attributes['sliderId']) : 0;
        if (!$id) {
            return '';
        }
        return do_shortcode('[keen_slider id="' . $id . '"]');
    }
}
