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
registerBlockType( 'bos/block-credly-assertion-page-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Credly Assertion Page - block' ), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'badgeos-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'Credly Assertion Page - block' ),
		__( 'block' ),
		__( 'Credly Assertion Page' ),
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
		width: {
			type:    'string',
			default: '560',
		},		
		height: {
			type:    'string',
			default: '1000',
		},		
		CID: {
			type:    'string',
			default: '',
		}		
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
		const { attributes: { width, height, CID }, setAttributes} = props;
		
		// Creates a <p class='wp-block-bos-block-blocks'></p>.
		return [
			el("div", {
				className: "badgeos-editor-container",
				style: {textAlign: "center"}
			  },
			  el( ServerSideRender, {
				block: 'bos/block-credly-assertion-page-block',
				attributes: props.attributes
			  } )
		  ),
		  <InspectorControls>
                <PanelBody title={ __( 'Credly Assertion', 'badgeos' ) } className="bos-block-inspector">
                    <PanelRow> 
						<TextControl
								label={ __( 'Width', 'badgeos' ) }
								value={ width }
								onChange={ ( width ) => setAttributes( { width } ) }
							/>
                    </PanelRow>
					<PanelRow> 
						<TextControl
								label={ __( 'Height', 'badgeos' ) }
								value={ height }
								onChange={ ( height ) => setAttributes( { height } ) }
							/>
                    </PanelRow>
					<PanelRow> 
						<TextControl
								label={ __( ' Credly Assertion ID ', 'badgeos' ) }
								value={ CID }
								onChange={ ( CID ) => setAttributes( { CID } ) }
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