<?php
/*
Plugin Name: BH Plugin Tools
Plugin URI: https://elite-star-services.com/plugins/
Description: Modified from Better Plugins Plugin by Russell Heimlich for use as a Plugin API & Management Tool
Version: 0.9.2
Requires at least: 3.0.1
Requires PHP: 5.6
Author: Elite Star Services
Author URI: https://elite-star-services.com
*/


// Make the lists of plugins filterable as you type thanks to this handy snippit of JavaScript.
function bpp_plugins_footer()
{
?>
	<script>
		// via https://github.com/charliepark/faq-patrol
		// extend :contains to be case-insensitive; via http://stackoverflow.com/questions/187537/
		jQuery.expr[':'].contains = function(a, i, m) {
			return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
		};

		jQuery(document).ready(function($) {
			$('#plugin-search-input').keyup(function() {
				$val = $(this).val();
				if ($val.length < 2) {
					$("#the-list > tr").show();
				} else {
					$("#the-list > tr").hide();
					$("#the-list .plugin-title strong:contains(" + $val + ")").parent().parent().show();
				}
			}).focus();

		});
	</script>
<?php
}
add_action('admin_footer-plugins.php', 'bpp_plugins_footer');


// CHECK IF UPGRADE AVAILABLE
require 'bh-update/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$MyUpdateChecker = PucFactory::buildUpdateChecker(
	'https://cs.elite-star-services.com/wp-repo/?action=get_metadata&slug=bh-plugin-tools', //Metadata URL.
	__FILE__, //Full path to the main plugin file.
	'bh-plugin-tools' //Plugin slug. Usually it's the same as the name of the directory.
);


// List Site Plugin Information to Dashboard, as JSON and BH Plugins API.
if (is_admin()) {
	include 'bh-site-plugins.php';
}

// Network Plugins Report - Find which plugins aren't used on any site in your network from a single page.
if (is_network_admin()) {
	include 'bh-plugins-report.php';
}


// CHECK IF REQUIRED PLUGIN SCRIPT LOADED
if (!function_exists('is_plugin_active')) {
	include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

// CHECK IF Plugin Installer from public URL PLUGIN ACTIVE & LOAD IF NOT
if (!is_plugin_active('plugin-installer-from-public-url/plugin-installer-from-public-url.php')) {
	include 'inc/bh-plugin-url-install.php';
}

// CHECK IF Wp Theme plugin Download PLUGIN ACTIVE & LOAD IF NOT
if (!is_plugin_active('wp-theme-plugin-download/wp-theme-plugin-download.php')) {
		include 'inc/bh-theme-plugin-download.php';
}


// CHECK IF Plugins Condition PLUGIN ACTIVE & LOAD IF NOT
if (!is_plugin_active('plugins-condition/pluginscondition.php')) {

	if ( ! class_exists('PluginsConditionAdmin') ) {
		require_once ('inc/bh-pluginsconditionadmin.php');
	}
	if ( ! class_exists('PluginsCondition') ) {
		require_once ('inc/bh-pluginscondition.php');
	}

}
