const { __ } = wp.i18n;
const { addQueryArgs } = wp.url;
const { ExternalLink } = wp.components;
const { addFilter } = wp.hooks;

function coblocksGravityFormsCallout() {

	const callToActionLink = addQueryArgs( 'admin.php', { page: 'gf_new_form' } );

	return (
		<div className="components-base-control components-coblocks-advanced-forms-cta">
			<h3>{ __( 'Need more?' ) }</h3>
			<p>{ __( 'Use your free Gravity Forms license to access technical features like advanced fields and captchas.' ) }</p>
			<p>{ __( 'Once you\'ve built a form, replace this block with a form shortcode.' ) }</p>
			<ExternalLink className="components-coblocks-advanced-forms-cta__link" href={ callToActionLink }>{ __( 'Create an advanced form' ) }</ExternalLink>
		</div>
	);
}
addFilter( 'coblocks.advanced_forms_cta', 'coblocks/advanced_forms_cta', coblocksGravityFormsCallout );
