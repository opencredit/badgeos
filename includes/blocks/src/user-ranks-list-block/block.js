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
registerBlockType('bos/badgeos-ranks-list-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __('Ranks list - block'), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'badgeos-blocks', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__('Ranks list - block'),
		__('block'),
		__('Ranks list'),
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
		types: {
			type: 'string',
			default: '',
		},
		limit: {
			type: 'string',
			default: '',
		},
		show_search: {
			type: 'string',
			default: 'false',
		},
		order: {
			type: 'string',
			default: '',
		},
		orderby: {
			type: 'string',
			default: '',
		},
		user_id: {
			type: "string",
			default: ""
		},
		show_description: {
			type: 'string',
			default: 'true',
		},
		show_thumb: {
			type: 'string',
			default: 'true',
		},
		show_title: {
			type: 'string',
			default: 'true',
		},
		default_view: {
			type: 'string',
			default: 'true',
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
		const { attributes: { types, limit, show_search, order, orderby, user_id, show_description, show_thumb, show_title, default_view, image_width, image_height }, setAttributes } = props;
		let ranks = [];
		wp.apiFetch({ path: 'badgeos/rank-types' }).then(
			posts => posts.map(function (post) {
				ranks.push(post)
			})
		);

		let selectedRanks = [];
		if (null !== types && types != '') {
			selectedRanks = JSON.parse(types);
		}

		//const handleRoleChange = ( role ) => setAttributes( { role: JSON.stringify( role ) } );
		function handleRankChange(types) {
			props.setAttributes({ 'types': JSON.stringify(types) })
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
			<div className="badgeos-user-earned-ranks-admin-block">
				{__("Output of this block can only be displayed on the frontend.", 'badgeos')}
			</div>,
			<InspectorControls>
				<PanelBody title={__('Rank List', 'badgeos')} className="bos-block-inspector">
					<PanelRow>
						<label htmlFor="bos-block-roles" className="bos-block-inspector__label">
							{__('Rank Types.', 'badgeos')}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name='bos-rank-types'
							value={selectedRanks}
							onChange={handleRankChange}
							options={ranks}
							menuPlacement="auto"
							isMulti='true'
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("Limit", 'badgeos')}
							value={limit}
							onChange={(limit) => setAttributes({ limit })}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Search", 'badgeos')}
							value={show_search}
							options={[
								{ label: __('False', 'badgeos'), value: 'false' },
								{ label: __('True', 'badgeos'), value: 'true' },
							]}
							onChange={(show_search) => { setAttributes({ show_search }) }}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Order", 'badgeos')}
							value={order}
							options={[
								{ label: __('Ascending', 'badgeos'), value: 'ASC' },
								{ label: __('Descending', 'badgeos'), value: 'DESC' },
							]}
							onChange={(order) => { setAttributes({ order }) }}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Order By", 'badgeos')}
							value={orderby}
							options={[
								{ label: __('Rank ID', 'badgeos'), value: 'rank_id' },
								{ label: __('Rank Title', 'badgeos'), value: 'rank_title' },
								{ label: __('Award Date', 'badgeos'), value: 'dateadded' },
								{ label: __('Random', 'badgeos'), value: 'rand()' },
							]}
							onChange={(orderby) => { setAttributes({ orderby }) }}
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
							label={__("Show Title Field", 'badgeos')}
							value={show_title}
							options={[
								{ label: __('True', 'badgeos'), value: 'true' },
								{ label: __('False', 'badgeos'), value: 'false' },
							]}
							onChange={(show_title) => { setAttributes({ show_title }) }}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Thumbnail", 'badgeos')}
							value={show_thumb}
							options={[
								{ label: __('True', 'badgeos'), value: 'true' },
								{ label: __('False', 'badgeos'), value: 'false' },
							]}
							onChange={(show_thumb) => { setAttributes({ show_thumb }) }}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Description", 'badgeos')}
							value={show_description}
							options={[
								{ label: __('True', 'badgeos'), value: 'true' },
								{ label: __('False', 'badgeos'), value: 'false' },
							]}
							onChange={(show_description) => { setAttributes({ show_description }) }}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Default View", 'badgeos')}
							value={default_view}
							options={[
								{ label: "", value: "" },
								{ label: __("List", "badgeos"), value: "list" },
								{ label: __("Grid", "badgeos"), value: "grid" }
							]}
							onChange={default_view => {
								setAttributes({ default_view });
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