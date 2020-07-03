/**
 * BLOCK: blocks
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
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
//var ServerSideRender = wp.components.ServerSideRender;
//import ServerSideRender from '@wordpress/server-side-render';

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
registerBlockType('bos/badgeos-single-achievement-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __('Single Achievement - block'), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'badgeos-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__('Single Achievement - block'),
		__('block'),
		__('Single Achievement'),
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
		achievement: {
			type: 'string',
			default: '',
		},
		show_description: {
			type: "string",
			default: "true"
		},
		show_thumb: {
			type: "string",
			default: "true"
		},
		show_title: {
			type: "string",
			default: "true"
		},
		show_steps: {
			type: "string",
			default: ""
		},
		image_width: {
			type: "string",
			default: ""
		},
		image_height: {
			type: "string",
			default: ""
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
	edit: (props) => {
		const { attributes: { achievement, image_width, image_height, show_description, show_thumb, show_title, show_steps }, setAttributes } = props;
		let achievements = [];
		wp.apiFetch({ path: 'badgeos/achievements' }).then(
			posts => posts.map(function (post) {
				achievements.push(post)
			})
		);

		let selectedAchievements = [];
		if (null !== achievement && achievement != '') {
			selectedAchievements = JSON.parse(achievement);
		}

		//const handleRoleChange = ( role ) => setAttributes( { role: JSON.stringify( role ) } );
		function handleAchievementChange(achievement) {
			props.setAttributes({ 'achievement': JSON.stringify(achievement) })
		}

		// Creates a <p class='wp-block-bos-block-blocks'></p>.
		return [
			el("div", {
				className: "badgeos-editor-container",
				style: { textAlign: "center" }
			},
				el(ServerSideRender, {
					block: 'bos/badgeos-single-achievement-block',
					attributes: props.attributes
				})
			),
			<InspectorControls>
				<PanelBody title={__('Achievement ID', 'badgeos')} className="bos-block-inspector">
					<PanelRow>
						<label htmlFor="bos-block-roles" className="bos-block-inspector__label">
							{__('Render a single achievement.', 'badgeos')}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name='bos-achievement-id'
							value={selectedAchievements}
							onChange={handleAchievementChange}
							options={achievements}
							menuPlacement="auto"
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Title Field", "badgeos")}
							value={show_title}
							options={[
								{ label: __("True", "badgeos"), value: "true" },
								{ label: __("False", "badgeos"), value: "false" }
							]}
							onChange={show_title => {
								setAttributes({ show_title });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Thumbnail", "badgeos")}
							value={show_thumb}
							options={[
								{ label: __("True", "badgeos"), value: "true" },
								{ label: __("False", "badgeos"), value: "false" }
							]}
							onChange={show_thumb => {
								setAttributes({ show_thumb });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Description", "badgeos")}
							value={show_description}
							options={[
								{ label: __("True", "badgeos"), value: "true" },
								{ label: __("False", "badgeos"), value: "false" }
							]}
							onChange={show_description => {
								setAttributes({ show_description });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Steps", "badgeos")}
							value={show_steps}
							options={[
								{ label: __("True", "badgeos"), value: "true" },
								{ label: __("False", "badgeos"), value: "false" }
							]}
							onChange={show_steps => {
								setAttributes({ show_steps });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("Image Width", "badgeos")}
							value={image_width}
							onChange={image_width => setAttributes({ image_width })}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("Image Height", "badgeos")}
							value={image_height}
							onChange={image_height => setAttributes({ image_height })}
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
	save: (props) => {
		return (
			<div>Content</div>
		);
	},
});
