import icon, { CircProgressIcon, LinearProgressIcon } from "./icons";

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks;
const { BlockControls, InspectorControls, PanelColorSettings, RichText } =
	wp.blockEditor || wp.editor;

const {
	Button,
	ButtonGroup,
	ToolbarGroup,
	ToolbarItem,
	ToolbarButton,
	RangeControl,
	PanelBody,
	PanelRow,
	DropdownMenu,
} = wp.components;

const { withSelect } = wp.data;

import Circle from "./Circle";
import Line from "./Line";

registerBlockType("ub/progress-bar", {
	title: __("Progress Bar"),
	icon: icon,
	category: "ultimateblocks",
	keywords: [__("Progress Bar"), __("Ultimate Blocks")],

	attributes: {
		blockID: {
			type: "string",
			default: "",
		},
		percentage: {
			type: "number",
			default: -1,
		},
		barType: {
			type: "string",
			default: "linear", //choose between linear and circular
		},
		detail: {
			type: "string",
			default: "",
		},
		detailAlign: {
			type: "string",
			default: "left",
		},
		barColor: {
			type: "string",
			default: "#2DB7F5",
		},
		barBackgroundColor: {
			type: "string",
			default: "#d9d9d9",
		},
		barThickness: {
			type: "number",
			default: 1,
		},
		circleSize: {
			type: "number",
			default: 150,
		},
		labelColor: {
			type: "string",
			default: "",
		},
	},

	edit: withSelect((select, ownProps) => {
		const { getBlock, getClientIdsWithDescendants } =
			select("core/block-editor") || select("core/editor");

		return {
			block: getBlock(ownProps.clientId),
			getBlock,
			getClientIdsWithDescendants,
		};
	})(function (props) {
		const {
			attributes: {
				blockID,
				percentage,
				barType,
				detail,
				detailAlign,
				barColor,
				barBackgroundColor,
				barThickness,
				circleSize,
				labelColor,
			},
			isSelected,
			setAttributes,
			block,
			getBlock,
			getClientIdsWithDescendants,
		} = props;

		if (blockID === "") {
			setAttributes({ blockID: block.clientId, percentage: 75 });
		} else {
			if (percentage === -1) {
				setAttributes({ percentage: 25 });
			}
			if (
				getClientIdsWithDescendants().some(
					(ID) =>
						"blockID" in getBlock(ID).attributes &&
						getBlock(ID).attributes.blockID === blockID
				)
			) {
				setAttributes({ blockID: block.clientId });
			}
		}

		const progressBarAttributes = {
			percent: percentage,
			barColor,
			barBackgroundColor,
			barThickness,
			labelColor,
		};

		return [
			isSelected && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							isPressed={barType === "linear"}
							showTooltip={true}
							label={__("Horizontal")}
							onClick={() => setAttributes({ barType: "linear" })}
						>
							<LinearProgressIcon />
						</ToolbarButton>
						<ToolbarButton
							isPressed={barType === "circular"}
							showTooltip={true}
							label={__("Circular")}
							onClick={() => setAttributes({ barType: "circular" })}
						>
							<CircProgressIcon />
						</ToolbarButton>
					</ToolbarGroup>
					<ToolbarGroup>
						<ToolbarItem
							as={RangeControl}
							className="ub_progress_bar_value"
							value={percentage}
							onChange={(value) => setAttributes({ percentage: value })}
							min={0}
							max={100}
							allowReset
						/>
					</ToolbarGroup>
					{/*ToolbarDropdownMenu doesn't work outside of Gutenberg. Do not convert until it's part of Wordpress core. */}
					<DropdownMenu
						icon={`editor-${
							detailAlign === "justify" ? detailAlign : "align" + detailAlign
						}`}
						controls={["left", "center", "right", "justify"].map((a) => ({
							icon: `editor-${a === "justify" ? a : "align" + a}`,
							onClick: () => setAttributes({ detailAlign: a }),
						}))}
					/>
				</BlockControls>
			),
			isSelected && (
				<InspectorControls>
					<PanelBody title={__("Style")}>
						<PanelRow>
							<p>{__("Progress Bar Type")}</p>
							<ButtonGroup>
								<Button
									isPressed={barType === "linear"}
									showTooltip={true}
									label={__("Horizontal")}
									onClick={() => setAttributes({ barType: "linear" })}
								>
									<LinearProgressIcon size={30} />
								</Button>
								<Button
									isPressed={barType === "circular"}
									showTooltip={true}
									label={__("Circular")}
									onClick={() => setAttributes({ barType: "circular" })}
								>
									<CircProgressIcon size={30} />
								</Button>
							</ButtonGroup>
						</PanelRow>
						<RangeControl
							label={__("Thickness")}
							value={barThickness}
							onChange={(value) => setAttributes({ barThickness: value })}
							min={1}
							max={5}
							allowReset
						/>
						{barType === "circular" && (
							<RangeControl
								label={__("Circle size")}
								value={circleSize}
								onChange={(value) => setAttributes({ circleSize: value })}
								min={50}
								max={600}
								allowReset
							/>
						)}
					</PanelBody>
					<PanelBody title={__("Value")}>
						<RangeControl
							className="ub_progress_bar_value"
							value={percentage}
							onChange={(value) => setAttributes({ percentage: value })}
							min={0}
							max={100}
							allowReset
						/>
					</PanelBody>
					<PanelColorSettings
						title={__("Color")}
						initialOpen={false}
						colorSettings={[
							{
								value: barColor,
								onChange: (barColor) => setAttributes({ barColor }),
								label: "Progress Bar Color",
							},
							{
								value: barBackgroundColor,
								onChange: (barBackgroundColor) =>
									setAttributes({ barBackgroundColor }),
								label: "Background Bar Color",
							},
							{
								value: labelColor,
								onChange: (labelColor) => setAttributes({ labelColor }),
								label: "Label Color",
							},
						]}
					/>
				</InspectorControls>
			),
			<div className="ub_progress-bar">
				<div className="ub_progress-bar-text">
					<RichText
						tagName="p"
						style={{ textAlign: detailAlign }}
						placeholder={__("Progress bar description")}
						value={detail}
						onChange={(text) => setAttributes({ detail: text })}
						keepPlaceholderOnFocus={true}
					/>
				</div>
				{barType === "linear" ? (
					<Line {...progressBarAttributes} />
				) : (
					<Circle
						{...progressBarAttributes}
						alignment={detailAlign}
						size={circleSize}
					/>
				)}
			</div>,
		];
	}),

	save: () => null,
});
