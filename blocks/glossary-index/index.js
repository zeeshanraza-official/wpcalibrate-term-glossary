/**
 * WPCalibrate Term Glossary - Gutenberg Block Editor Script
 *
 * @package WPCalibrate\TermGlossary
 */

/* global wp */
( function( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.blockEditor || ! wp.components ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	registerBlockType( 'wpcalibrate/glossary-index', {
		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Glossary Index Settings', 'wpcalibrate-term-glossary' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Show Search Input', 'wpcalibrate-term-glossary' ),
							checked: attributes.search,
							onChange: function( val ) { setAttributes( { search: val } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Alphabet Navigation', 'wpcalibrate-term-glossary' ),
							checked: attributes.alphabet,
							onChange: function( val ) { setAttributes( { alphabet: val } ); }
						} ),
						el( RangeControl, {
							label: __( 'Terms Per Page', 'wpcalibrate-term-glossary' ),
							value: attributes.per_page,
							min: 1,
							max: 100,
							onChange: function( val ) { setAttributes( { per_page: val } ); }
						} ),
						el( SelectControl, {
							label: __( 'Sort Order', 'wpcalibrate-term-glossary' ),
							value: attributes.order,
							options: [
								{ label: __( 'A to Z (Ascending)', 'wpcalibrate-term-glossary' ), value: 'asc' },
								{ label: __( 'Z to A (Descending)', 'wpcalibrate-term-glossary' ), value: 'desc' }
							],
							onChange: function( val ) { setAttributes( { order: val } ); }
						} ),
						el( TextControl, {
							label: __( 'Category Slug (Optional)', 'wpcalibrate-term-glossary' ),
							value: attributes.category,
							help: __( 'Leave blank to display all categories.', 'wpcalibrate-term-glossary' ),
							onChange: function( val ) { setAttributes( { category: val } ); }
						} )
					)
				),
				el(
					'div',
					blockProps,
					ServerSideRender
						? el( ServerSideRender, {
							block: 'wpcalibrate/glossary-index',
							attributes: attributes
						} )
						: el(
							'div',
							{ className: 'wpctg-block-placeholder' },
							el( 'strong', null, __( 'WPCalibrate Glossary Index', 'wpcalibrate-term-glossary' ) ),
							el( 'p', null, __( 'Server-rendered A-Z glossary index will display here.', 'wpcalibrate-term-glossary' ) )
						)
				)
			);
		},
		save: function() {
			// Dynamic block rendered on server.
			return null;
		}
	} );
} )( window.wp );
