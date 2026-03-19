(function (blocks, blockEditor, components, element, serverSideRender) {
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var RangeControl = components.RangeControl;
    var ServerSideRender = serverSideRender;

    blocks.registerBlockType('westin-test/events-list', {
        apiVersion: 2,
        title: 'Westin Events Listing',
        description: 'Render a live list of events without pasting a shortcode by hand.',
        icon: 'calendar-alt',
        category: 'widgets',
        keywords: ['events', 'listing', 'rsvp'],
        attributes: {
            postsPerPage: { type: 'number', default: 6 },
            type: { type: 'string', default: '' },
            audience: { type: 'string', default: '' },
            series: { type: 'string', default: '' },
            showExcerpt: { type: 'boolean', default: true }
        },
        edit: function (props) {
            var attrs = props.attributes;

            return [
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Listing settings', initialOpen: true },
                        el(RangeControl, {
                            label: 'Posts to show',
                            min: 1,
                            max: 24,
                            value: attrs.postsPerPage,
                            onChange: function (value) {
                                props.setAttributes({ postsPerPage: value || 6 });
                            }
                        }),
                        el(TextControl, {
                            label: 'Event type slug',
                            help: 'Optional. Example: workshop',
                            value: attrs.type,
                            onChange: function (value) {
                                props.setAttributes({ type: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Audience slug',
                            help: 'Optional. Example: members',
                            value: attrs.audience,
                            onChange: function (value) {
                                props.setAttributes({ audience: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Series slug',
                            help: 'Optional. Example: spring-summit',
                            value: attrs.series,
                            onChange: function (value) {
                                props.setAttributes({ series: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show excerpt',
                            checked: !!attrs.showExcerpt,
                            onChange: function (value) {
                                props.setAttributes({ showExcerpt: value });
                            }
                        })
                    )
                ),
                el('div', { className: props.className },
                    el(ServerSideRender, {
                        block: 'westin-test/events-list',
                        attributes: attrs
                    })
                )
            ];
        },
        save: function () {
            return null;
        }
    });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.serverSideRender);
