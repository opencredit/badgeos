/**
 * BLOCK: blocks
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

import "./editor.scss";
import "./style.scss";
import React from "react";
import Select from "react-select";
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

registerBlockType("bos/badgeos-achievements-list-block", {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __("Achievement List - block"), // Block title.
	icon: "shield", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: "badgeos-blocks", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__("Single Achievement - block"),
		__("block"),
		__("Single Achievement")
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
		achievement_types: {
			type: "string",
			default: ""
		},
		limit: {
			type: "string",
			default: ""
		},
		show_filter: {
			type: "string",
			default: ""
		},
		show_search: {
			type: "string",
			default: ""
		},
		order: {
			type: "string",
			default: ""
		},
		orderby: {
			type: "string",
			default: ""
		},
		user_id: {
			type: "string",
			default: ""
		},
		include: {
			type: "string",
			default: ""
		},
		exclude: {
			type: "string",
			default: ""
		},
		multisite: {
			type: "string",
			default: ""
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
		default_view: {
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
	edit: props => {
		const {
			attributes: {
				achievement_types,
				limit,
				show_filter,
				show_search,
				order,
				orderby,
				user_id,
				include,
				exclude,
				multisite,
				show_description,
				show_thumb,
				show_title,
				show_steps,
				default_view,
				image_width,
				image_height
			},
			setAttributes
		} = props;
		let incl_achievements = [];
		let excl_achievements = [];
		let achievements_types = [];
		let user_lists = [];
		wp.apiFetch({ path: "badgeos/achievement-types" }).then(posts =>
			posts.map(function (post) {
				achievements_types.push(post);
			})
		);
		wp.apiFetch({ path: "badgeos/achievements" }).then(posts =>
			posts.map(function (post) {
				incl_achievements.push(post);
				excl_achievements.push(post);
			})
		);
		wp.apiFetch({ path: "badgeos/user-lists" }).then(posts =>
			posts.map(function (post) {
				user_lists.push(post);
			})
		);
		let selectedUsers = [];
		if (null !== user_id && user_id != "") {
			selectedUsers = JSON.parse(user_id);
		}

		function handleUsersChange(user_id) {
			props.setAttributes({ user_id: JSON.stringify(user_id) });
		}

		let selectedExcludeAchievements = [];
		if (null !== exclude && exclude != "") {
			selectedExcludeAchievements = JSON.parse(exclude);
		}

		function handleExcludeAchievementChange(exclude) {
			props.setAttributes({ exclude: JSON.stringify(exclude) });
		}

		let selectedIncludeAchievements = [];
		if (null !== include && include != "") {
			selectedIncludeAchievements = JSON.parse(include);
		}

		function handleIncludeAchievementChange(include) {
			props.setAttributes({ include: JSON.stringify(include) });
		}

		let selectedAchievements = [];
		if (null !== achievement_types && achievement_types != "") {
			selectedAchievements = JSON.parse(achievement_types);
		}

		//const handleRoleChange = ( role ) => setAttributes( { role: JSON.stringify( role ) } );
		function handleAchievementChange(achievement_types) {
			props.setAttributes({
				achievement_types: JSON.stringify(achievement_types)
			});
		}

		// Creates a <p class='wp-block-bos-block-blocks'></p>.
		return [
			<div className="badgeos-user-earned-achievements-admin-block">
				{__(
					"Output of this block can only be displayed on the frontend.",
					"badgeos"
				)}
			</div>,
			<InspectorControls>
				<PanelBody
					title={__("Achievement List", "badgeos")}
					className="bos-block-inspector"
				>
					<PanelRow>
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("Achievement Types.", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-achievement-types"
							value={selectedAchievements}
							onChange={handleAchievementChange}
							options={achievements_types}
							menuPlacement="auto"
							isMulti="true"
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__("Limit", "badgeos")}
							value={limit}
							onChange={limit => setAttributes({ limit })}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Filter", "badgeos")}
							value={show_filter}
							options={[
								{ label: __("True", "badgeos"), value: "true" },
								{ label: __("False", "badgeos"), value: "false" }
							]}
							onChange={show_filter => {
								setAttributes({ show_filter });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Show Search", "badgeos")}
							value={show_search}
							options={[
								{ label: __("True", "badgeos"), value: "true" },
								{ label: __("False", "badgeos"), value: "false" }
							]}
							onChange={show_search => {
								setAttributes({ show_search });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Order", "badgeos")}
							value={order}
							options={[
								{ label: __("Ascending", "badgeos"), value: "ASC" },
								{ label: __("Descending", "badgeos"), value: "DESC" }
							]}
							onChange={order => {
								setAttributes({ order });
							}}
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Order By", "badgeos")}
							value={orderby}
							options={[
								{ label: __("Menu Order", "badgeos"), value: "menu_order" },
								{ label: __("Achievement ID", "badgeos"), value: "ID" },
								{ label: __("Achievement Title", "badgeos"), value: "title" },
								{ label: __("Published Date", "badgeos"), value: "date" },
								{ label: __("Last Modified Date", "badgeos"), value: "modified" },
								{ label: __("Achievement Author", "badgeos"), value: "author" },
								{ label: __("Random", "badgeos"), value: "rand" }
							]}
							onChange={orderby => {
								setAttributes({ orderby });
							}}
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
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("Include Achievements", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-include-achievements"
							value={selectedIncludeAchievements}
							onChange={handleIncludeAchievementChange}
							options={incl_achievements}
							menuPlacement="auto"
							isMulti="true"
						/>
					</PanelRow>
					<PanelRow>
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("Exclude Achievements", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-exclude-achievements"
							value={selectedExcludeAchievements}
							onChange={handleExcludeAchievementChange}
							options={excl_achievements}
							menuPlacement="auto"
							isMulti="true"
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							label={__("Include Multisite Achievements", "badgeos")}
							value={multisite}
							options={[
								{ label: __("False", "badgeos"), value: "false" },
								{ label: __("True", "badgeos"), value: "true" }
							]}
							onChange={multisite => {
								setAttributes({ multisite });
							}}
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
						<SelectControl
							label={__("Default View", "badgeos")}
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
	save: props => {
		return <div>Content</div>;
	}
});
