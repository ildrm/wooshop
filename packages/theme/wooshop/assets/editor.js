(function (wp) {
    'use strict';
    const el = wp.element.createElement;
    const __ = wp.i18n.__;
    const Inspector = wp.blockEditor.InspectorControls;
    const C = wp.components;
    const ServerSideRender = wp.serverSideRender.default || wp.serverSideRender;
    const definitions = {
        'store-header': {
            title: __('Store header', 'wooshop'), icon: 'menu',
            attributes: { announcement: { type: 'string', default: '' }, dismissible: { type: 'boolean', default: false }, sticky: { type: 'boolean', default: true }, compact: { type: 'boolean', default: false }, logoWidth: { type: 'number', default: 160 }, logoId: { type: 'number', default: 0 } },
            controls: [ ['text', 'announcement', __('Announcement text (empty hides the notice)', 'wooshop')], ['toggle', 'dismissible', __('Allow announcement dismissal', 'wooshop')], ['toggle', 'sticky', __('Keep header visible on tall screens', 'wooshop')], ['toggle', 'compact', __('Compact commerce header', 'wooshop')], ['range', 'logoWidth', __('Maximum logo width', 'wooshop'), 80, 200] ]
        },
        'category-shortcuts': {
            title: __('Category shortcuts', 'wooshop'), icon: 'category',
            attributes: { count: { type: 'number', default: 8 }, title: { type: 'string', default: '' }, shape: { type: 'string', enum: ['square', 'circle', 'none'], default: 'square' } },
            controls: [ ['text', 'title', __('Heading', 'wooshop')], ['range', 'count', __('Maximum categories', 'wooshop'), 2, 16], ['select', 'shape', __('Category media', 'wooshop'), [ ['square', __('Square', 'wooshop')], ['circle', __('Circle', 'wooshop')], ['none', __('Text only', 'wooshop')] ]] ]
        },
        'product-collection': {
            title: __('Native product collection', 'wooshop'), icon: 'products',
            attributes: { title: { type: 'string', default: '' }, source: { type: 'string', enum: ['new', 'featured', 'bestselling', 'sale'], default: 'new' }, count: { type: 'number', default: 10 }, layout: { type: 'string', enum: ['grid', 'rail'], default: 'grid' }, density: { type: 'string', enum: ['comfortable', 'dense'], default: 'comfortable' } },
            controls: [ ['text', 'title', __('Heading (empty uses the collection label)', 'wooshop')], ['select', 'source', __('WooCommerce collection', 'wooshop'), [ ['new', __('New arrivals', 'wooshop')], ['featured', __('Featured', 'wooshop')], ['bestselling', __('Bestselling', 'wooshop')], ['sale', __('On sale', 'wooshop')] ]], ['range', 'count', __('Maximum products', 'wooshop'), 1, 20], ['select', 'layout', __('Layout', 'wooshop'), [ ['grid', __('Grid', 'wooshop')], ['rail', __('Manual scrolling rail', 'wooshop')] ]], ['select', 'density', __('Card spacing', 'wooshop'), [ ['comfortable', __('Comfortable', 'wooshop')], ['dense', __('Dense', 'wooshop')] ]] ]
        },
        'store-footer': { title: __('Store footer', 'wooshop'), icon: 'align-wide', attributes: { minimal: { type: 'boolean', default: false } }, controls: [ ['toggle', 'minimal', __('Minimal commerce footer', 'wooshop')] ] },
        'enhancement': { title: __('Commerce module surface', 'wooshop'), icon: 'admin-plugins', attributes: { surface: { type: 'string', enum: ['wishlist', 'compare', 'seller_dashboard', 'unsubscribe'], default: 'wishlist' } }, controls: [ ['select', 'surface', __('Plugin-owned surface', 'wooshop'), [ ['wishlist', __('Wishlist', 'wooshop')], ['compare', __('Comparison', 'wooshop')], ['seller_dashboard', __('Seller dashboard', 'wooshop')], ['unsubscribe', __('Notification unsubscribe', 'wooshop')] ]] ] }
    };
    Object.keys(definitions).forEach(function (slug) {
        const definition = definitions[slug];
        wp.blocks.registerBlockType('wooshop/' + slug, {
            apiVersion: 3, title: definition.title, icon: definition.icon, category: 'theme', attributes: definition.attributes,
            supports: { html: false },
            edit: function (props) {
                const controls = definition.controls.map(function (item) {
                    const key = item[1];
                    const common = { key: key, label: item[2], onChange: function (value) { props.setAttributes({ [key]: value }); } };
                    if (item[0] === 'toggle') return el(C.ToggleControl, { ...common, checked: props.attributes[key], __nextHasNoMarginBottom: true });
                    if (item[0] === 'range') return el(C.RangeControl, { ...common, value: props.attributes[key], min: item[3], max: item[4], __nextHasNoMarginBottom: true });
                    if (item[0] === 'select') return el(C.SelectControl, { ...common, value: props.attributes[key], options: item[3].map(function (option) { return { value: option[0], label: option[1] }; }), __nextHasNoMarginBottom: true });
                    return el(C.TextControl, { ...common, value: props.attributes[key], maxLength: 240, __nextHasNoMarginBottom: true });
                });
                // Private module views have no live editor preview.
                const preview = slug === 'enhancement' ? el(C.Placeholder, { label: definition.title }, __('The plugin renders this surface for the authorized visitor. No private records are previewed here.', 'wooshop')) : el(ServerSideRender, { block: 'wooshop/' + slug, attributes: props.attributes });
                if (slug === 'store-header') {
                    controls.push(el(wp.blockEditor.MediaUploadCheck, { key: 'logo' }, el(wp.blockEditor.MediaUpload, { allowedTypes: ['image'], value: props.attributes.logoId, onSelect: function (media) { props.setAttributes({ logoId: media.id }); }, render: function (media) { return el(C.Button, { onClick: media.open, variant: 'secondary' }, __('Choose header logo', 'wooshop')); } })));
                    if (props.attributes.logoId) controls.push(el(C.Button, { key: 'remove-logo', variant: 'tertiary', onClick: function () { props.setAttributes({ logoId: 0 }); } }, __('Use site identity logo', 'wooshop')));
                }
                const innerNavigation = (slug === 'store-header' && !props.attributes.compact) || slug === 'store-footer' ? el(wp.blockEditor.InnerBlocks, { allowedBlocks: ['core/navigation'], template: [['core/navigation', { overlayMenu: 'never', submenuVisibility: 'click', maxNestingLevel: 3 }]], templateLock: 'all' }) : null;
                return el(wp.element.Fragment, null, el(Inspector, null, el(C.PanelBody, { title: __('Presentation', 'wooshop') }, controls, el('p', null, __('Values use native saved block attributes. Responsive layout follows theme rules. Business settings belong to the commerce plugin.', 'wooshop')))), el('div', wp.blockEditor.useBlockProps(), preview, innerNavigation));
            },
            save: function () { return ['store-header', 'store-footer'].includes(slug) ? el(wp.blockEditor.InnerBlocks.Content) : null; }
        });
    });
})(window.wp);
