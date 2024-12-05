import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';

wp.blocks.registerBlockVariation( 'core/paragraph', {
    name: 'hd-stats-counter',
    title: __( 'Stats Counter', 'hd-stats-counter' ),
    attributes: {
        makeCounter: true,
		counterDuration: 2000
},
    isActive: [ 'makeCounter', 'counterDuration' ],
} );

function addParagraphInspectorControls( BlockEdit ) {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		// Early return if the block is not the paragraph block.
		if ( name !== 'core/paragraph' ) {
			return <BlockEdit { ...props } />;
		}

		// Retrieve selected attributes from the block.
		const { counterDuration, makeCounter } = attributes;

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					{makeCounter && (
					<PanelBody
						title={ __(
							'Stats Counter Settings',
							'hd-stats-counter'
						) }
					>
						<PanelRow>
						<TextControl
							label={ __(
								'Counter Duration',
								'hd-stats-counter'
							) }
							onChange={ ( newValue ) => {
								setAttributes( {
									counterDuration: newValue,
								} );
							} }
							value={ counterDuration }
						/>
						</PanelRow>
					</PanelBody>
					)}
				</InspectorControls>
			</>
		);
	};
}

addFilter(
	'editor.BlockEdit',
	'hd/stats-counter',
	addParagraphInspectorControls
);