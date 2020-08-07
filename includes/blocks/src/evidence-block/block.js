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

registerBlockType("bos/badgeos-evidence-block", {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __("Evidence - block"), // Block title.
	icon: "shield", // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: "badgeos-blocks", // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__("Evidence - block"),
		__("block"),
		__("Evidence")
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
			type: "string",
			default: ""
		},
		user_id: {
			type: "string",
			default: ""
		},
		award_id: {
			type: "string",
			default: ""
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
	edit: props => {
		const {
			attributes: {
				achievement,
				user_id,
				award_id,
			},
			setAttributes
		} = props;

		let achievements_list = [];
		let entries = [];
		let user_lists = [];
		wp.apiFetch({ path: `badgeos/block-achievements-award-list/0/0`, method: 'GET' }).then(posts =>
			posts.map(function (post) {
				console.log(post);
				entries.push(post);
			})
		);
		wp.apiFetch({ path: "badgeos/achievements" }).then(posts =>
			posts.map(function (post) {
				achievements_list.push(post);
			})
		);
		wp.apiFetch({ path: "badgeos/user-lists" }).then(posts =>
			posts.map(function (post) {
				user_lists.push(post);
			})
		);

		let selectedAwardId = [];
		if (null !== award_id && award_id != "") {
			selectedAwardId = JSON.parse(award_id);
		}

		function handleAwardChange(award_id) {
			props.setAttributes({ award_id: JSON.stringify(award_id) });
		}

		function loadawardids() {
			entries = [];
			var achievement_val = 0;
			if (achievement) {
				var achievement_array = JSON.parse(achievement)
				achievement_val = achievement_array.value;
			}

			var user_id_val = 0;
			if (user_id) {
				var user_array = JSON.parse(user_id);
				user_id_val = user_array.value;
			}
			wp.apiFetch({ path: "badgeos/block-achievements-award-list/" + achievement_val + "/" + user_id_val + "", method: 'GET' }).then(posts =>
				posts.map(function (post) {
					entries.push(post);
				})
			);
		}

		let selectedUser = [];
		if (null !== user_id && user_id != "") {
			selectedUser = JSON.parse(user_id);
		}

		function handleUserChange(user_id) {
			props.setAttributes({ user_id: JSON.stringify(user_id) });
			loadawardids();
		}

		let selectedAchievement = [];
		if (null !== achievement && achievement != "") {
			selectedAchievement = JSON.parse(achievement);
		}

		function handleAchievementChange(achievement_val) {
			props.setAttributes({
				achievement: JSON.stringify(achievement_val)
			});
			loadawardids();
		}

		// Creates a <p class='wp-block-bos-block-blocks'></p>.
		return [
			el("div", {
				className: "badgeos-editor-container",
				style: { textAlign: "center" }
			},
				el(ServerSideRender, {
					block: 'bos/badgeos-evidence-block',
					attributes: props.attributes
				})
			),
			<InspectorControls>
				<PanelBody
					title={__("Achievement", "badgeos")}
					className="bos-block-inspector"
				>
					<PanelRow>
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("Achievement", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-achievement-types"
							value={selectedAchievement}
							onChange={handleAchievementChange}
							options={achievements_list}
							menuPlacement="auto"
						/>
					</PanelRow>
					<PanelRow>
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("User", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-achievement-types"
							value={selectedUser}
							onChange={handleUserChange}
							options={user_lists}
							menuPlacement="auto"
						/>
					</PanelRow>
					<PanelRow>
						<label
							htmlFor="bos-block-roles"
							className="bos-block-inspector__label"
						>
							{__("Award Id", "badgeos")}
						</label>
					</PanelRow>
					<PanelRow>
						<Select
							className="bos-block-inspector__control"
							name="bos-achievement-types"
							value={selectedAwardId}
							onChange={handleAwardChange}
							options={entries}
							menuPlacement="auto"
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
