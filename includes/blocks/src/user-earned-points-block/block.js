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
registerBlockType('bos/badgeos-user-earned-points-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __('User Earned Points - block'), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'badgeos-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__('User Earned Points - block'),
		__('block'),
		__('User Earned Points'),
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
		point_type: {
			type: 'string',
			default: '',
		},
		user_id: {
			type: "string",
			default: ""
		},
		show_title: {
			type: 'string',
			default: 'true',
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
		const { attributes: { point_type, user_id, show_title }, setAttributes } = props;
		let point_types = [];
		wp.apiFetch({ path: 'badgeos/block-point-types' }).then(
			posts => posts.map(function (post) {
				point_types.push(post)
			})
		);

		let selectedPointTypes = [];
		if (null !== point_type && point_type != '') {
			selectedPointTypes = JSON.parse(point_type);
		}

		//const handleRoleChange = ( role ) => setAttributes( { role: JSON.stringify( role ) } );
		function handlePointTypesChange(point_type) {
			props.setAttributes({ 'point_type': JSON.stringify(point_type) })
		}

		let selectedUsers = [];
		let user_lists = [];
		if (null !== user_id && user_id != "") {
			selectedUsers = JSON.parse(user_id);
		}

		function handleUsersChange(user_id) {
			props.setAttributes({ user_id: JSON.stringify(user_id) });
		}

		wp.apiFetch({ path: "badgeos/user-lists" }).then(posts =>
			posts.map(function (post) {
				user_lists.push(post);
			})
		);

		// Creates a <p class='wp-block-bos-block-blocks'></p>.
		return [
			el("div", {
				className: "badgeos-editor-container",
				style: { textAlign: "center" }
			},
				el(ServerSideRender, {
					block: 'bos/badgeos-user-earned-points-block',
					attributes: props.attributes
				})
			),
			<InspectorControls>
				<PanelBody title={__('Point Type', 'badgeos')} className="bos-block-inspector">
					<PanelRow>
						<label htmlFor="bos-block-roles" className="bos-block-inspector__label">
							{__('Render a single Point Type.', 'badgeos')}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name='bos-point-type-id'
							value={selectedPointTypes}
							onChange={handlePointTypesChange}
							options={point_types}
							menuPlacement="auto"
						/>
					</PanelRow>
					<PanelRow>
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("Select User", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-user-id"
							value={selectedUsers}
							onChange={handleUsersChange}
							options={user_lists}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Title Field", "badgeos")}
							value={show_title}
							options={[
								{ label: __('True', "badgeos"), value: 'true' },
								{ label: __('False', "badgeos"), value: 'false' },
							]}
							onChange={(show_title) => { setAttributes({ show_title }) }}
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
