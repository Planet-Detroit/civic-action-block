import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { formTitle, formSubtitle } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Form Settings', 'civic-action-block' ) }>
					<TextControl
						label={ __( 'Form Title', 'civic-action-block' ) }
						value={ formTitle }
						onChange={ ( value ) => setAttributes( { formTitle: value } ) }
					/>
					<TextControl
						label={ __( 'Subtitle', 'civic-action-block' ) }
						value={ formSubtitle }
						onChange={ ( value ) => setAttributes( { formSubtitle: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="ask-pd-preview">
					<div className="ask-pd-preview__icon">📬</div>
					<h3 className="ask-pd-preview__title">{ formTitle }</h3>
					<p className="ask-pd-preview__subtitle">{ formSubtitle }</p>
					<div className="ask-pd-preview__form-placeholder">
						<div className="ask-pd-preview__field">Question textarea</div>
						<div className="ask-pd-preview__row">
							<div className="ask-pd-preview__field ask-pd-preview__field--flex">Name</div>
							<div className="ask-pd-preview__field ask-pd-preview__field--small">Zip</div>
						</div>
						<div className="ask-pd-preview__row">
							<div className="ask-pd-preview__field ask-pd-preview__field--flex">Email</div>
							<div className="ask-pd-preview__btn">Ask</div>
						</div>
					</div>
					<p className="ask-pd-preview__note">
						{ __( 'Form is live on the frontend — readers can submit questions to reporters.', 'civic-action-block' ) }
					</p>
				</div>
			</div>
		</>
	);
}
