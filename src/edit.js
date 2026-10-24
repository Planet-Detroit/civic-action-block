import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

// Written by PHP (includes/ad-placement.php) before this script loads:
// which in-story ad plugin the site runs, and whether this block handles it.
function getAdPluginInfo() {
	const info = typeof window !== 'undefined' ? window.civicActionBlockAds : null;
	return info && typeof info === 'object' ? info : { slug: 'none', name: '', handled: false };
}

// Sidebar panel explaining how in-story ads interact with a floated box.
// See spec-ad-aware-placement.md. Plain words: editors are not developers.
function AdsPanel( { align, adFreeParagraphs, setAttributes } ) {
	const ads = getAdPluginInfo();
	const floated = align === 'left' || align === 'right';

	let body;
	if ( ! floated ) {
		body = (
			<p>
				{ __( 'A centered box does not float, so the story does not wrap around it and there is nothing to keep clear of ads.', 'civic-action-block' ) }
			</p>
		);
	} else if ( ads.slug === 'none' ) {
		body = (
			<p>
				{ __( 'No in-story ad plugin was found on this site, so ads will not interrupt the text wrapping around the box.', 'civic-action-block' ) }
			</p>
		);
	} else if ( ! ads.handled ) {
		body = (
			<p>
				{ __( 'This site uses', 'civic-action-block' ) } <strong>{ ads.name }</strong>{ ' ' }
				{ __( "for in-story ads. This block can't yet keep its ads clear of the box, so an ad may push the story below the box. Use center alignment, or ask Planet Detroit to add support.", 'civic-action-block' ) }
			</p>
		);
	} else {
		body = (
			<>
				<p>
					{ __( 'This site uses', 'civic-action-block' ) } <strong>{ ads.name }</strong>.{ ' ' }
					{ __( 'Ads that would land beside the box are moved to after the paragraphs below, so the story keeps wrapping. Raise the number for a long box.', 'civic-action-block' ) }
				</p>
				<TextControl
					label={ __( 'Paragraphs to keep ad-free', 'civic-action-block' ) }
					type="number"
					min={ 1 }
					max={ 30 }
					value={ String( adFreeParagraphs ?? 8 ) }
					onChange={ ( value ) => {
						const n = parseInt( value, 10 );
						if ( ! Number.isNaN( n ) ) {
							setAttributes( { adFreeParagraphs: Math.min( 30, Math.max( 1, n ) ) } );
						}
					} }
				/>
			</>
		);
	}

	return (
		<PanelBody title={ __( 'Ads beside the box', 'civic-action-block' ) } initialOpen={ true }>
			{ body }
		</PanelBody>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { civicHtml, align, adFreeParagraphs } = attributes;
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
				{ civicHtml && ! isReplacing && (
					<PanelBody
						title={ __( 'HTML Code', 'civic-action-block' ) }
						initialOpen={ false }
					>
						<textarea
							className="civic-action-sidebar-html"
							value={ isEditDirty ? editValue : civicHtml }
							onChange={ ( e ) => {
								setEditValue( e.target.value );
								setIsEditDirty( true );
							} }
							onBlur={ handleEditBlur }
							rows={ 12 }
							aria-label={ __( 'HTML Code', 'civic-action-block' ) }
						/>
					</PanelBody>
				) }
				{ /* Always shown (1.5.1): editors pick alignment before pasting, and
				     the panel's main job is to say what happens with ads on this site. */ }
				<AdsPanel align={ align } adFreeParagraphs={ adFreeParagraphs } setAttributes={ setAttributes } />
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
							{ __( 'Paste the box HTML from the Civic Action Toolbox (Publish tab → Copy HTML).', 'civic-action-block' ) }
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
						<div
							className="civic-action-preview__content"
							dangerouslySetInnerHTML={ { __html: civicHtml } }
						/>
					</div>
				) }
			</div>
		</>
	);
}
