<?php
// Modified Version of 'Wp Theme Plugin Download' by Abhay Yadav
// ADD DOWNLOAD LINK FOR THEMES AND PLUGINS
add_action('plugins_loaded', 'bh_downloads');


/**
 * Hooks some actions and filters,
 * Handles the download link
 *
 * @return  void
 */
function bh_downloads()
{
	// Hook for the plugin action links
	add_filter('plugin_action_links', 'bh_plugin_download_links', 10, 4);
	// Hook for the theme action links in wordpress.
	add_filter('theme_action_links', 'bh_theme_download_links', 10, 2);
	// Hook for adding javasctript on the themes page.
	add_action('admin_footer-themes.php', 'bh_script', 99);

	// Check, if there is a 'abdnd' Get param, and there is a proper nonce set...
	if (isset($_GET['abdnd']) && wp_verify_nonce($_GET['_wpnonce'], 'ab-download')) {
		// perform downloading
		bh_download();
	}
}


/**
 * Displays "Download" link on the Plugins Page
 */
function bh_plugin_download_links($links, $file, $context)
{
	if ('dropins' === $context)
		return $links;
	if ('mustuse' === $context)
		$what = 'muplugin';
	else
		$what = 'plugin';

	$dowload_query = build_query(array('abdnd' => $what, 'object' => $file));
	$download_link = sprintf(
		'<a href="%s">%s</a>',
		wp_nonce_url(admin_url('?' . $dowload_query), 'ab-download'),
		__('Download')
	);

	array_push($links, $download_link);
	return $links;
}


/**
 * Displays "Download" link on the Themes Page.
 */
function bh_theme_download_links($links, $theme)
{
	$dowload_query = build_query(array('abdnd' => 'theme', 'object' => $theme->get_stylesheet()));
	$download_link = sprintf(
		'<a href="%s">%s</a>',
		wp_nonce_url(admin_url('?' . $dowload_query), 'ab-download'),
		__('Download')
	);

	array_push($links, $download_link);
	return $links;
}


/**
 * Displays javascript code in the footer of themes.php
 *
 * @return  void
 */
function bh_script()
{
	// Download URL
	$query = build_query(array('abdnd' => 'theme', 'object' => '_obj_'));
	$url = wp_nonce_url(admin_url('?' . $query), 'ab-download');
	// Label used for download links
	$label = __('Download');
	// Current theme
	$current_theme = get_stylesheet();

	$script_template = '<script type="text/javascript" id="wp-downloader">
			(function($){
				var url = "%s",
					label = "%s",
					current = "%s",
					button = \'<a class="button button-primary download hide-if-no-js" href="\' + url + \'">\' + label + \'</a>\';
				
				
				$(window).load(function(){			
					// For current theme in wordpress <3.8
					$("#current-theme .theme-options").after(\'<div class="theme-options"><a href="\' + url.replace("_obj_", current) + \'">\' + label + \'</a></div>\');
	
					// Add download button for each theme on the themes page
					$("#wpbody .theme .theme-actions .load-customize").each(function(i, e){
						var btn = $(button),
							$e = $(e),
							href = $e.prop("href");
	
						btn.prop("href", url.replace("_obj_", href.replace(/.*theme=(.*)(&|$)/, "$1")));
	
						$e.parent().append(btn);
					});
				});
	
				// Modify single theme template to add the download button
				var d = $("#tmpl-theme-single").html(),
					   ar = new RegExp(\'(<div class="active-theme">)(([\n\t]*(<#|<a).*[\n\t]*)*)(</div>)\', "mi");
					   ir = new RegExp(\'(<div class="inactive-theme">)(([\n\t]*(<#|<a).*[\n\t]*)*)(</div>)\', "mi");
	
				d = d.replace(ar, "$1$2" + button + "$5");
				d = d.replace(ir, "$1$2" + button + "$5");
	
				$("#tmpl-theme-single").html(d);
	
				$(document).on("click", "a.button.download", function(e){
					e.preventDefault();
					var $this = $(this),
						href = $(this).parent().find(".load-customize").attr("href"),
						theme;
	
					theme = href.replace(/.*theme=(.*)(&|$)/, "$1");
					href = url.replace("_obj_", theme).replace(new RegExp("&amp;", "g"), "&");
					
					window.location = href;
				});
			}(jQuery))
		</script>';

	// Print javascript
	printf($script_template, $url, $label, $current_theme);
}


/**
 * Handles the Download
 *
 * @return  array
 */
function bh_download()
{
	if (!class_exists('PclZip')) {
		// Load class file if it's not loaded yet
		include ABSPATH . 'wp-admin/includes/class-pclzip.php';
	}

	// Kind of object we download (theme or plugin)
	$what = $_GET['abdnd'];
	// The name of object
	$object = $_GET['object'];

	// Prepare object name and root path for given object type
	switch ($what) {
		case 'plugin':
			if (strpos($object, '/')) {
				$object = dirname($object);
			}
			$root = WP_PLUGIN_DIR;
			break;
		case 'muplugin':
			if (strpos($object, '/')) {
				$object = dirname($object);
			}
			$root = WPMU_PLUGIN_DIR;
			break;
		case 'theme':
			$root = get_theme_root($object);
			break;
		default:
			// bad URL
			wp_die();
	}

	$object = sanitize_file_name($object);
	if (empty($object))
		// No object name
		wp_die();

	// Prepare full path do the desired object
	$path = $root . '/' . $object;
	// Filename for a zip package
	$fileName = $object . '.zip';

	// Temporary file name in upload dir
	$upload_dir = wp_upload_dir();
	$tmpFile = trailingslashit($upload_dir['path']) . $fileName;

	// Create new archive
	$archive = new PclZip($tmpFile);
	// Add entire folder to the archive
	$archive->add($path, PCLZIP_OPT_REMOVE_PATH, $root);

	// Set headers for the zip archive
	header('Content-type: application/zip');
	header('Content-Disposition: attachment; filename="' . $fileName . '"');

	// Read file content directly
	readfile($tmpFile);
	// Remove zip file
	unlink($tmpFile);

	// Exit. No wp_die(), it produces HTML
	exit;
}
