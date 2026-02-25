/**
 * Keen Slider Admin - Slide Builder UI
 */
(function ($) {
  'use strict';

  var mediaFrame;
  var currentUploadBtn;

  function init() {
    var $list = $('#keen-slider-slides-list');
    if (!$list.length) return;

    // Sortable
    $list.sortable({
      handle: '.keen-slider-drag-handle',
      placeholder: 'keen-slider-slide-row keen-slider-placeholder',
      update: reindexSlides
    });

    // Add slide
    $('#keen-slider-add-slide').on('click', addSlide);

    // Remove slide
    $list.on('click', '.keen-slider-remove-slide', removeSlide);

    // Upload image
    $list.on('click', '.keen-slider-upload-image', openMediaUploader);

    // Remove image
    $list.on('click', '.keen-slider-remove-image', removeImage);

    // Copy shortcode
    $('.keen-slider-copy-shortcode').on('click', copyShortcode);
  }

  function addSlide() {
    var $list = $('#keen-slider-slides-list');
    var index = $list.find('.keen-slider-slide-row:not(.template)').length;
    var template = $('#keen-slider-slide-template').html();
    if (!template) return;
    var html = template.replace(/\{\{INDEX\}\}/g, index);
    $list.append(html);
    reindexSlides();
  }

  function removeSlide(e) {
    $(e.currentTarget).closest('.keen-slider-slide-row').remove();
    reindexSlides();
  }

  function reindexSlides() {
    $('#keen-slider-slides-list .keen-slider-slide-row:not(.template)').each(function (i) {
      var $row = $(this);
      $row.attr('data-index', i);
      $row.find('[name]').each(function () {
        var name = $(this).attr('name');
        if (name) {
          name = name.replace(/\[\d+\]/, '[' + i + ']');
          $(this).attr('name', name);
        }
      });
    });
  }

  function openMediaUploader(e) {
    e.preventDefault();
    currentUploadBtn = $(e.currentTarget);
    if (mediaFrame) {
      mediaFrame.open();
      return;
    }
    mediaFrame = wp.media({
      title: 'Select Slide Image',
      library: { type: 'image' },
      multiple: false,
      button: { text: 'Use Image' }
    });
    mediaFrame.on('select', function () {
      var attachment = mediaFrame.state().get('selection').first().toJSON();
      setSlideImage(currentUploadBtn, attachment.id, attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url);
    });
    mediaFrame.open();
  }

  function setSlideImage($btn, id, url) {
    var $row = $btn.closest('.keen-slider-slide-row');
    $row.find('.keen-slider-image-id').val(id);
    var $preview = $row.find('.keen-slider-slide-preview');
    $preview.find('.keen-slider-thumb-placeholder').remove();
    var $thumb = $preview.find('.keen-slider-thumb');
    if ($thumb.length) {
      $thumb.attr('src', url);
    } else {
      $preview.prepend('<img src="' + url + '" alt="" class="keen-slider-thumb">');
    }
    var $removeBtn = $preview.find('.keen-slider-remove-image');
    if (!$removeBtn.length) {
      $preview.find('.keen-slider-image-actions').append('<button type="button" class="button button-small keen-slider-remove-image">Remove</button>');
    }
  }

  function removeImage(e) {
    e.preventDefault();
    var $row = $(e.currentTarget).closest('.keen-slider-slide-row');
    $row.find('.keen-slider-image-id').val('');
    $row.find('.keen-slider-thumb').remove();
    var $placeholder = $('<div class="keen-slider-thumb-placeholder"><span class="dashicons dashicons-format-image"></span><span>No image</span></div>');
    $row.find('.keen-slider-slide-preview').prepend($placeholder);
    $row.find('.keen-slider-remove-image').remove();
  }

  function copyShortcode(e) {
    e.preventDefault();
    var $code = $('.keen-slider-shortcode-copy');
    var text = $code.text();
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        var $btn = $(e.currentTarget);
        var orig = $btn.text();
        $btn.text('Copied!');
        setTimeout(function () { $btn.text(orig); }, 2000);
      });
    } else {
      var $tmp = $('<input>').val(text).appendTo('body').select();
      document.execCommand('copy');
      $tmp.remove();
      var $btn = $(e.currentTarget);
      var orig = $btn.text();
      $btn.text('Copied!');
      setTimeout(function () { $btn.text(orig); }, 2000);
    }
  }

  $(document).ready(init);
})(jQuery);
