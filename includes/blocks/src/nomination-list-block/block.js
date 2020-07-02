/**
 * BLOCK: blocks
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

import './editor.scss';
import './style.scss';
import React from 'react';
import Select from 'react-select';
const {
	PanelBody,
	PanelRow,
	ServerSideRender,
	TextControl,
	SelectControl 
} = wp.components;

var el = wp.element.createElement;
const { InspectorControls } = wp.editor;
const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */ 
registerBlockType( 'bos/badgeos-nominations-list-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Nominations List - block' ), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'badgeos-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'Nominations List - block' ),
		__( 'block' ),
		__( 'Nominations List' ),
	],
	supports: {
		// Turn off ability to edit HTML of block content
		html: false,
		// Turn off reusable block feature
		reusable: false,
		// Add alignwide and alignfull options
		align: false
	  },

	attributes: {
		limit: {
			type:    'string',
			default: '10',
		},		
		status: {
			type:    'string',
			default: 'all',
		},		
		show_filter: {
			type:    'string',
			default: 'true',
		},		
		show_search: {
			type:    'string',
			default: 'true',
		},				
	},
	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 *
	 * @param {Object} props Props.
	 * @returns {Mixed} JSX Component.
	 */
	edit: ( props ) => { 
		const { attributes: { limit, status, show_filter, show_search }, setAttributes} = props;
		
		// Creates a <p class='wp-block-bos-block-blocks'></p>.
		return [
			el("div", {
				className: "badgeos-editor-container",
				style: {textAlign: "center"}
			  },
			  el( ServerSideRender, {
				block: 'bos/badgeos-nominations-list-block',
				attributes: props.attributes
			  } )
		  ),
		  <InspectorControls>
                <PanelBody title={ __( 'Nominations list', 'badgeos' ) } className="bos-block-inspector">
					<PanelRow> 
						<TextControl
								label={ __( 'Limit', 'badgeos' ) }
								value={ limit }
								onChange={ ( limit ) => setAttributes( { limit } ) }
							/>
                    </PanelRow>
					<PanelRow> 
						<SelectControl
								label={ __( 'Status to Display', 'badgeos' ) }
								value={ status }
								options={ [
									{ label: __( 'All', 'badgeos' ), value: 'all' },
									{ label: __( 'Approved', 'badgeos' ), value: 'approved' },
									{ label: __( 'Denied', 'badgeos' ), value: 'denied' },
									{ label: __( 'Pending', 'badgeos' ), value: 'pending' },
								] }
								onChange={ ( status ) => { setAttributes( { status } ) } }
							/>
                    </PanelRow>
					<PanelRow> 
						<SelectControl
								label={ __( 'Show Filter', 'badgeos' ) }
								value={ show_filter }
								options={ [
									{ label: __( 'True', 'badgeos' ), value: 'true' },
									{ label: __( 'False', 'badgeos' ), value: 'false' }
								] }
								onChange={ ( show_filter ) => { setAttributes( { show_filter } ) } }
							/>
                    </PanelRow>
					<PanelRow> 
						<SelectControl
								label={ __( 'Show Search', 'badgeos' ) }
								value={ show_search }
								options={ [
									{ label: __( 'True', 'badgeos' ), value: 'true' },
									{ label: __( 'False', 'badgeos' ), value: 'false' }
								] }
								onChange={ ( show_search ) => { setAttributes( { show_search } ) } }
							/>
                    </PanelRow>
                </PanelBody>
			</InspectorControls>
		];
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 *
	 * @param {Object} props Props.
	 * @returns {Mixed} JSX Frontend HTML.
	 */
	save: ( props ) => {
		return(
			<div>Content</div>
		); 
	},
} );