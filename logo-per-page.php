<?php
/*
 * Plugin Name: Logo Per Page
 * Plugin URI: https://wordpress.org/plugins/logo-per-page
 * Description: Logo Per page: Set a different site logo depending on the page that is being viewed.
 * Version: 1.0.0
 * Author: Diana van de Laarschot
 * Author URI: https://wordpress.telodelic.nl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.4
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Text Domain: logo-per-page
 */

/*  Copyright 2024 Diana van de Laarschot (email : mail@telodelic.nl)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

class LPP_LogoPerPage
{
	const OPTION_NAME = 'lpp_options';
	const VERSION = '1.0.0';
	const CSS_VERSION = '1.0.0';
	const JS_VERSION = '1.0.0';

	protected $options = null;
	protected $defaults = array('version' => self::VERSION);

	public function __construct()
	{
		add_action( 'init', function(){
			register_meta( 
				'post',
				'_lpp_custom_logo_id', // Prefix with underscore to prevent field from also showing in Custom Fields
				array(
					'object_subtype' => 'page',
					'type'           => 'number',
					'single'         => true,
					'show_in_rest'   => true,
					'auth_callback' => function (){
						return current_user_can('edit_posts');
					}
				 )
			);		
		});
		add_filter('enqueue_block_editor_assets', array(&$this, 'lpp_enqueue_block_editor_assets_js'), 10);
		add_filter('theme_mod_custom_logo', array(&$this, 'lpp_theme_mod_custom_logo_id'), 10, 1);
		add_action('admin_notices', array(&$this, 'lpp_admin_notices'), 10, 0);
	} // function

	function LPP_LogoPerPage()
	{
		$this->get_options();
		$this->__construct();
	} // function

	private function get_options()
	{
		// already did the checks
		if (isset($this->options)) {
			return $this->options;
		}

		// first call, get the options
		$options = get_option(self::OPTION_NAME /* 'lpp_options' */);

		// options exist
		if ($options !== false) {
			$new_version = version_compare($options['version'], self::VERSION, '<');
			$desync = array_diff_key($this->defaults, $options) !== array_diff_key($options, $this->defaults);

			// update options if version changed, or we have missing/extra (out of sync) option entries 
			if ($new_version || $desync) {
				$new_options = array();

				// check for new options and set defaults if necessary
				foreach ($this->defaults as $option => $value) {
					$new_options[$option] = isset($options[$option]) ? $options[$option] : $value;
				}

				// update version info
				$new_options['version'] = self::VERSION;

				update_option(self::OPTION_NAME /* 'lpp_options' */, $new_options);
				$this->options = $new_options;
			} else // no update required
			{
				$this->options = $options;
			}
		} else // either new install or version from before versioning existed 
		{
			update_option(self::OPTION_NAME /* 'lpp_options' */, $this->defaults);
			$this->options = $this->defaults;
		}

		return $this->options;
	}

	/*
	 * Notify the user if the theme doesn't support a custom logo
	 */
	function lpp_admin_notices()
	{
		if (!$this->lpp_supports_custom_logo()) {
			$class = 'notice notice-error is-dismissible';
			$message = __('Logo Per Page cannot be applied. The active theme does not support custom logos.', 'logo-per-page');
			printf('<div class="%1$s"><p><span>%2$s</span></p></div>', esc_attr($class), esc_html($message));
		}
	}

	/* 
	 * Add JS for div.cpcm-description and other CPCM fields site-editor.php (Appearance > Editor, the block editor)
	 */
	function lpp_enqueue_block_editor_assets_js( /* no $hook parameter in this case */)
	{
		$screen = get_current_screen();
		if( $screen && $screen->post_type === 'page') {
			if ($this->lpp_supports_custom_logo()) {
				wp_enqueue_script(
					'lpp_enqueue_custom_logo_panel_js',
					plugins_url('src/blocks/lpp_custom_logo/build/index.js', __FILE__),
					['wp-blocks'],
					self::JS_VERSION,
					true
				);
			} else {
				wp_enqueue_script(
					'lpp_enqueue_not_available_panel_js',
					plugins_url('src/blocks/lpp_not_available/build/index.js', __FILE__),
					['wp-blocks'],
					self::JS_VERSION,
					true
				);
			}
		}
	} // function

	function lpp_supports_custom_logo(){
		return wp_is_block_theme() || get_theme_support('custom-logo');
	}

	function lpp_theme_mod_custom_logo_id($logo_id)
	{
		if ($this->lpp_supports_custom_logo()) {
			$post = get_post();
			if (!empty($post)) {
				if ($post->post_type === 'page') {
					$meta_logo_id = get_post_meta($post->ID, '_lpp_custom_logo_id', true);
					if ($meta_logo_id){
						$exists = wp_get_attachment_image_src($meta_logo_id);
						if ($exists) {
							return $meta_logo_id;
						}
					}
				}
			}
		}

		return $logo_id;
	}

	static function lpp_uninstall()
	{
		// We're uninstalling, so delete all custom data that the LPP plugin added
		delete_metadata('post', null, '_lpp_custom_logo_id', '', true);
		delete_option(self::OPTION_NAME /* 'lpp_options' */);
	} // function
}

$logo_per_page = new LPP_LogoPerPage();

// Register the uninstall hook. Should be done after the class has been defined.
register_uninstall_hook(__FILE__, array('LogoPerPage', 'lpp_uninstall'));
