/**
 * Keen Slider WordPress Plugin - Initialization
 */
(function () {
  'use strict';

  function getKeenSlider() {
    if (typeof KeenSlider !== 'undefined') return KeenSlider;
    if (typeof window.KeenSlider !== 'undefined') return window.KeenSlider;
    return null;
  }

  var initRetryCount = 0;
  var maxInitRetries = 50;

  function initSliders() {
    const KeenSliderConstructor = getKeenSlider();
    if (!KeenSliderConstructor) {
      if (initRetryCount < maxInitRetries) {
        initRetryCount++;
        setTimeout(initSliders, 100);
      }
      return;
    }

    const wrappers = document.querySelectorAll('.keen-slider-wrapper');
    wrappers.forEach(function (wrapper) {
      if (wrapper.dataset.initialized === 'true') return;

      const sliderEl = wrapper.querySelector('.keen-slider');
      const configEl = wrapper.dataset.keenConfig;
      if (!sliderEl || !configEl) return;

      const slides = sliderEl.querySelectorAll('.keen-slider__slide');
      if (!slides.length) return;

      let config;
      try {
        config = JSON.parse(configEl);
      } catch (e) {
        config = {};
      }

      var desktopPerView = Math.max(1, config.slidesPerView || 1);
      var spacing = config.spacing || 0;

      var opts = {
        loop: config.loop !== false,
        slides: {
          perView: desktopPerView,
          spacing: spacing,
        },
        breakpoints: {
          '(max-width: 767px)': {
            slides: { perView: 1, spacing: spacing },
          },
          '(min-width: 768px) and (max-width: 1023px)': {
            slides: { perView: 2, spacing: spacing },
          },
        },
        slideChanged: function (slider) {
          updateDots(wrapper, slider);
          updateArrows(wrapper, slider);
        },
        created: function (slider) {
          updateDots(wrapper, slider);
          updateArrows(wrapper, slider);
        },
      };

      var slider = new KeenSliderConstructor(sliderEl, opts);

      wrapper._keenSlider = slider;

      // Recalc when container size changes (fixes empty space / last slide cut-off)
      if (typeof ResizeObserver !== 'undefined') {
        var ro = new ResizeObserver(function () {
          slider.update();
        });
        ro.observe(wrapper);
      }

      // Arrows
      const prevBtn = wrapper.querySelector('.keen-slider-arrow--prev');
      const nextBtn = wrapper.querySelector('.keen-slider-arrow--next');
      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          slider.prev();
        });
      }
      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          slider.next();
        });
      }

      // Dots
      const dotsEl = wrapper.querySelector('.keen-slider-dots');
      if (dotsEl && config.dots !== false) {
        const slideCount = slider.slides.length;
        dotsEl.innerHTML = '';
        for (let i = 0; i < slideCount; i++) {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.setAttribute('role', 'tab');
          btn.setAttribute('aria-label', 'Go to slide ' + (i + 1));
          btn.dataset.index = String(i);
          btn.className = 'keen-slider-dot';
          btn.addEventListener('click', function () {
            slider.moveToIdx(parseInt(btn.dataset.index, 10));
          });
          dotsEl.appendChild(btn);
        }
        updateDots(wrapper, slider);
      }

      // Autoplay
      if (config.autoplay) {
        const interval = config.interval || 5000;
        let autoplayTimer;
        function startAutoplay() {
          autoplayTimer = setInterval(function () {
            slider.next();
          }, interval);
        }
        function stopAutoplay() {
          clearInterval(autoplayTimer);
        }
        startAutoplay();
        wrapper.addEventListener('mouseenter', stopAutoplay);
        wrapper.addEventListener('mouseleave', startAutoplay);
      }

      // Modal (lightbox) – click slide to open in overlay
      if (config.modal) {
        var modalOverlay = document.createElement('div');
        modalOverlay.className = 'keen-slider-modal-overlay';
        modalOverlay.setAttribute('role', 'dialog');
        modalOverlay.setAttribute('aria-modal', 'true');
        modalOverlay.setAttribute('aria-label', 'Slide preview');
        modalOverlay.innerHTML = '<div class="keen-slider-modal-backdrop"></div><div class="keen-slider-modal-content"><button type="button" class="keen-slider-modal-close" aria-label="Close">&times;</button><div class="keen-slider-modal-body"></div></div>';
        var modalBody = modalOverlay.querySelector('.keen-slider-modal-body');
        var modalClose = modalOverlay.querySelector('.keen-slider-modal-close');
        var backdrop = modalOverlay.querySelector('.keen-slider-modal-backdrop');

        function openModal(slideEl) {
          var inner = slideEl.querySelector('.keen-slider__slide-inner') || slideEl;
          modalBody.innerHTML = '';
          modalBody.appendChild(inner.cloneNode(true));
          document.body.appendChild(modalOverlay);
          document.body.classList.add('keen-slider-modal-open');
          modalClose.focus();
        }
        function closeModal() {
          if (modalOverlay.parentNode) {
            modalOverlay.parentNode.removeChild(modalOverlay);
          }
          document.body.classList.remove('keen-slider-modal-open');
        }

        modalClose.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        modalOverlay.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeModal();
        });

        slides.forEach(function (slideEl) {
          slideEl.style.cursor = 'pointer';
          slideEl.addEventListener('click', function () {
            openModal(slideEl);
          });
        });
      }

      wrapper.dataset.initialized = 'true';
    });
  }

  function updateDots(wrapper, slider) {
    const dotsEl = wrapper.querySelector('.keen-slider-dots');
    if (!dotsEl) return;
    const details = slider.track.details;
    const idx = details ? details.rel : 0;
    const dots = dotsEl.querySelectorAll('.keen-slider-dot');
    dots.forEach(function (dot, i) {
      dot.setAttribute('aria-selected', i === idx ? 'true' : 'false');
      dot.classList.toggle('is-active', i === idx);
    });
  }

  function updateArrows(wrapper, slider) {
    if (slider.options.loop) return;
    const prevBtn = wrapper.querySelector('.keen-slider-arrow--prev');
    const nextBtn = wrapper.querySelector('.keen-slider-arrow--next');
    const details = slider.track.details;
    if (!details) return;
    const maxIdx = slider.slides.length - 1;
    if (prevBtn) {
      prevBtn.disabled = details.rel <= 0;
    }
    if (nextBtn) {
      nextBtn.disabled = details.rel >= maxIdx;
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSliders);
  } else {
    initSliders();
  }

  // Re-init when dynamic content loads (e.g. AJAX)
  document.addEventListener('keen-slider-refresh', initSliders);
})();
