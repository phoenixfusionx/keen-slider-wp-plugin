# Keen Slider WordPress Plugin

A dynamic slider plugin powered by [keen-slider](https://keen-slider.io/). Supports **visual admin UI** for non-technical users, shortcodes, and dynamic content from WordPress posts.

**Author:** r@ndi  
**Copywriter:** r@ndi — [randylistrud352@gmail.com](mailto:randylistrud352@gmail.com)  
**Version:** 1.0.0  
**License:** GNU General Public License v2 or later

---

## Installation

1. Copy this folder to `wp-content/plugins/keen-slider`
2. Activate the plugin in WordPress Admin → Plugins

## Admin UI (Recommended for non-technical users)

1. Go to **Sliders** in the WordPress admin menu
2. Click **Add New** to create a slider
3. Give it a title (e.g. "Homepage Hero")
4. In the **Slides** box:
   - Click **+ Add Slide** for each slide
   - Use **Select Image** to choose an image from the media library
   - Add optional title, description, and link URL
   - Drag the ☰ handle to reorder slides
5. In **Slider Settings** (sidebar): choose loop, arrows, dots, autoplay
6. Click **Publish**
7. Copy the shortcode from **How to Use** and paste it into any page

Example shortcode: `[keen_slider id="42"]`

### Using the block editor

1. Edit a page with the block editor (Gutenberg)
2. Click **+** to add a block
3. Search for "Keen Slider" or find it under Media
4. Add the block, then select your slider from the dropdown in the block settings (right sidebar)

## Manual slides (shortcode content)

```
[keen_slider]
  [keen_slide]<img src="image1.jpg" alt="Slide 1">[/keen_slide]
  [keen_slide]<img src="image2.jpg" alt="Slide 2">[/keen_slide]
  [keen_slide]<h3>Slide 3</h3><p>Any HTML content</p>[/keen_slide]
[/keen_slider]
```

### Dynamic content from posts

```
[keen_slider source="posts" count="5"]
```

Shows the 5 most recent published posts with thumbnail, title, and excerpt.

### Dynamic from custom post type

```
[keen_slider source="posts" post_type="portfolio" count="6"]
```

### Options

| Attribute         | Default   | Description                                                              |
| ----------------- | --------- | ------------------------------------------------------------------------ |
| `source`          | `content` | `content` = inner shortcode slides, `posts` / `recent` = dynamic from WP |
| `post_type`       | `post`    | Post type for dynamic source                                             |
| `count`           | `5`       | Number of items for dynamic source                                       |
| `loop`            | `true`    | Infinite loop                                                            |
| `arrows`          | `true`    | Prev/next arrow buttons                                                  |
| `dots`            | `true`    | Dot navigation                                                           |
| `autoplay`        | `false`   | Auto-advance slides                                                      |
| `modal`           | `false`   | Click slide to open in lightbox/modal                                    |
| `interval`        | `5000`    | Autoplay interval (ms)                                                   |
| `slides_per_view` | `1`       | Visible slides at once                                                   |
| `spacing`         | `0`       | Gap between slides (px)                                                  |
| `class`           |           | Extra CSS class for wrapper                                              |

### Examples

```
[keen_slider autoplay="true" interval="4000"]
[keen_slider source="posts" count="3" slides_per_view="2" spacing="16"]
[keen_slider arrows="false" dots="true" class="my-hero-slider"]
[keen_slider modal="true"]
```
