import { MediaUpload } from "@wordpress/block-editor";

const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
const { useSelect, useDispatch } = wp.data
const { Button } = wp.components;

const LPPMediaUpload = ( props ) => {
	const { media } = useSelect( ( select ) => {
		const id = select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ '_lpp_custom_logo_id' ];
		return {
			media: id && select( 'core' ).getMedia(id)
		}
	}, [ '_lpp_custom_logo_id' ] );

	const { editPost } = useDispatch( 'core/editor' );

	return (
		<MediaUpload
			label={props.label}
			onSelect={(media) => {
				editPost( { meta: { ['_lpp_custom_logo_id']: media.id } } )
			}}
			multiple={false}
			render={({open}) => {
				if (media) {
					return <>
						<img src={media.source_url} />
						<hr/>
						<Button onClick={ () => {
							editPost( { meta: { ['_lpp_custom_logo_id']: null } } )
						}}>Remove Logo</Button>
					</>;
				}
				return <Button onClick={open}>Select Logo</Button>;
			}}
			/>
	)

};

const LogoPerPagePlugin = () => (
	<PluginDocumentSettingPanel name='Logo Per Page' className="lpp-logo-per-page-plugin" title="Logo Per Page">
		<LPPMediaUpload label="Select or Upload Logo" />
	</PluginDocumentSettingPanel>
);

registerPlugin( 'lpp-logo-per-page', { render: LogoPerPagePlugin } );