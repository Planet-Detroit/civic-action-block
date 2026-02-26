import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { civicHtml, boxTitle } = attributes;
	const [ pasteValue, setPasteValue ] = useState( '' );
	const [ isReplacing, setIsReplacing ] = useState( false );
	// Track local edits to the HTML textarea so we only save on blur
	const [ editValue, setEditValue ] = useState( '' );
	const [ isEditDirty, setIsEditDirty ] = useState( false );
	const blockProps = useBlockProps();

	const showPasteUI = ! civicHtml || isReplacing;

	function handleApply() {
		const trimmed = pasteValue.trim();
		if ( trimmed ) {
			setAttributes( { civicHtml: trimmed } );
			setPasteValue( '' );
			setIsReplacing( false );
		}
	}

	function handleClear() {
		setAttributes( { civicHtml: '' } );
		setPasteValue( '' );
		setIsReplacing( false );
		setEditValue( '' );
		setIsEditDirty( false );
	}

	// Save edits when the HTML textarea loses focus
	function handleEditBlur() {
		if ( isEditDirty ) {
			setAttributes( { civicHtml: editValue } );
			setIsEditDirty( false );
		}
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Civic Action Settings', 'civic-action-block' ) }>
					<TextControl
						label={ __( 'Box Title', 'civic-action-block' ) }
						value={ boxTitle }
						onChange={ ( value ) => setAttributes( { boxTitle: value } ) }
					/>
					{ civicHtml && ! isReplacing && (
						<>
							<Button
								variant="secondary"
								onClick={ () => setIsReplacing( true ) }
								style={ { marginRight: '8px' } }
							>
								{ __( 'Replace Content', 'civic-action-block' ) }
							</Button>
							<Button
								isDestructive
								variant="secondary"
								onClick={ handleClear }
							>
								{ __( 'Clear', 'civic-action-block' ) }
							</Button>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ showPasteUI ? (
					<div className="civic-action-placeholder">
						<div className="civic-action-placeholder__icon">
							<span className="dashicons dashicons-megaphone"></span>
						</div>
						<p className="civic-action-placeholder__label">
							{ __( 'Civic Action Box', 'civic-action-block' ) }
						</p>
						<p className="civic-action-placeholder__instructions">
							{ __( 'Paste your civic action box HTML from the civic-action-builder tool.', 'civic-action-block' ) }
						</p>
						<textarea
							className="civic-action-placeholder__textarea"
							placeholder={ __( 'Paste HTML here...', 'civic-action-block' ) }
							value={ pasteValue }
							onChange={ ( e ) => setPasteValue( e.target.value ) }
							rows={ 6 }
						/>
						<div className="civic-action-placeholder__actions">
							<Button
								variant="primary"
								onClick={ handleApply }
								disabled={ ! pasteValue.trim() }
							>
								{ __( 'Apply', 'civic-action-block' ) }
							</Button>
							{ isReplacing && (
								<Button
									variant="tertiary"
									onClick={ () => setIsReplacing( false ) }
								>
									{ __( 'Cancel', 'civic-action-block' ) }
								</Button>
							) }
						</div>
					</div>
				) : (
					<div className="civic-action-preview">
						{ boxTitle && (
							<h3 className="civic-action-preview__title">{ boxTitle }</h3>
						) }
						<div
							className="civic-action-preview__content"
							dangerouslySetInnerHTML={ { __html: civicHtml } }
						/>
						<label className="civic-action-preview__html-label">
							{ __( 'HTML Code', 'civic-action-block' ) }
							<textarea
								className="civic-action-preview__html-editor"
								value={ isEditDirty ? editValue : civicHtml }
								onChange={ ( e ) => {
									setEditValue( e.target.value );
									setIsEditDirty( true );
								} }
								onBlur={ handleEditBlur }
								rows={ 6 }
							/>
						</label>
					</div>
				) }
			</div>
		</>
	);
}
