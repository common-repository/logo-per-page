import { BaseControl, Notice } from '@wordpress/components'

const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;

const NotAvailablePlugin = () => (
	<PluginDocumentSettingPanel name='Logo Per Page' className="lpp-logo-per-page-plugin" title="Logo Per Page">
		<BaseControl>
			<Notice isDismissible={false} status="warning">{wp.i18n.__('Logo Per Page cannot be applied. The active theme does not support custom logos.', 'logo-per-page')}</Notice>
		</BaseControl>
	</PluginDocumentSettingPanel>
);

registerPlugin( 'lpp-logo-per-page', { render: NotAvailablePlugin } );