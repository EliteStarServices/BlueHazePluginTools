<?php
/*
Plugin Name: BH Plugin Tools
Plugin URI: https://elite-star-services.com/plugins/
Description: Modified from Better Plugins Plugin by Russell Heimlich for use as a Plugin API & Management Tool
Version: 0.9.5
Requires at least: 4.9
Requires PHP: 5.6
Author: Elite Star Services
Author URI: https://elite-star-services.com
*/



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