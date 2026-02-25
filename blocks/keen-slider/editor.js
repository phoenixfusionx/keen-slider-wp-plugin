(function (blocks, element, blockEditor, components, data) {
  var el = element.createElement;
  var useSelect = data.useSelect;
  var useEntityProp = data.useEntityProp;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var Placeholder = components.Placeholder;

  blocks.registerBlockType('keen-slider/slider', {
    edit: function (props) {
      var sliderId = props.attributes.sliderId;
      var setAttributes = props.setAttributes;

      var sliders = useSelect(function (select) {
        return select('core').getEntityRecords('postType', 'keen_slider', {
          per_page: -1,
          status: 'publish',
          orderby: 'title',
          order: 'asc'
        });
      }, []);

      var options = [
        { value: 0, label: '— Select a slider —' }
      ];
      if (sliders) {
        sliders.forEach(function (s) {
          options.push({ value: s.id, label: s.title?.rendered || 'Slider #' + s.id });
        });
      }

      var inspector = el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: 'Slider Settings', initialOpen: true },
          el(SelectControl, {
            label: 'Slider',
            value: sliderId,
            options: options,
            onChange: function (val) {
              setAttributes({ sliderId: parseInt(val, 10) || 0 });
            }
          })
        )
      );

      var content = sliderId
        ? el('div', { className: 'keen-slider-block-preview' },
            'Slider will appear on the frontend.'
          )
        : el(
            Placeholder,
            {
              icon: 'slides',
              label: 'Keen Slider',
              instructions: 'Select a slider in the block settings (sidebar).'
            }
          );

      return el(
        element.Fragment,
        null,
        inspector,
        content
      );
    },
    save: function () {
      return null;
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.data
);
