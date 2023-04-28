<?php

class BPP_Compare_Site_Plugins
{

	/**
	 * Plugin details for the current site.
	 * @var array
	 */
	public $sites_plugins = array();
	public function _construct()
	{
	}

	/**
	 * Hook in to actions to get things going.
	 */
	public function setup()
	{
		add_action('admin_enqueue_scripts', array($this, 'register_admin_styles'));
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	/**
	 * Register the CSS file for the plugin.
	 */
	public function register_admin_styles()
	{
		wp_register_style('bpp-compare-site-plugins-styles', plugin_dir_url(__FILE__) . 'css/bpp-compare-site-plugins.css', array(), '1', 'all');
	}


	/**
	 * Add BH Plugin Tool Menu Item in the Plugins Admin Menu.
	 */
	public function admin_menu()
	{
		add_plugins_page('BH Plugin Tools', 'BH Plugin Tools', 'activate_plugins', 'bh-site-plugins', array($this, 'compare_plugins_admin_page'));
	}


	/**
	 * BH Plugin Tool Content Start.
	 * @return [type] [description]
	 */
	public function compare_plugins_admin_page()
	{
		wp_enqueue_style('bpp-compare-site-plugins-styles');



		/*
		if(
			( isset( $_GET['bulk-activate'] ) && $_GET['bulk-activate'] == true ) ||
			( isset( $_GET['bulk-network-activate'] ) && $_GET['bulk-network-activate'] == true )
		) {
			pew_compare_site_plugins_bulk_activate();
		}
		*/



		// Get Plugin Details for the Current Site.
		$this->sites_plugins = array(
			'name' => get_bloginfo('name'),
			'all' => get_plugins(),
			// 'all' => array_keys( get_plugins() ),
			'active' => get_option('active_plugins'),
		);


		?>
		<div class="wrap">
		<h2>BH Plugin Tools</h2>
		<hr>
		<?php



		// CHECK IF Plugins Condition PLUGIN ACTIVE (Duplicates Functionality)
		if (is_plugin_active('plugins-condition/pluginscondition.php')) {
			echo '<b>Plugins Condition</b> Plugin is ACTIVE But May Not Be Needed - This Plugin Includes Similar Functionality<hr>';

		}


		// CHECK IF Plugin Installer from public URL PLUGIN ACTIVE (Duplicates Functionality)
		if (is_plugin_active('plugin-installer-from-public-url/plugin-installer-from-public-url.php')) {
			echo '<b>Plugin Installer from public URL</b> Plugin is ACTIVE But Not Needed - This Plugin Includes Plugin URL Install Functionality<hr>';
		}

		// CHECK IF Wp Theme plugin Download PLUGIN ACTIVE (Duplicates Functionality)
		if (is_plugin_active('wp-theme-plugin-download/wp-theme-plugin-download.php')) {
			echo '<b>Wp Theme plugin Download</b> Plugin is ACTIVE But Not Needed - This Plugin Includes Plugin & Theme Download Functionality<hr>';
		}



		//echo 'ALL PLUGINS ARRAY<pre>';
		//var_dump(get_plugins());
		//echo '</pre><hr>';


		// GET ACTIVE PLUGINS
		$isActive = (get_option('active_plugins'));
		//echo 'ACTIVE PLUGINS ARRAY<pre>';
		//var_dump($isActive);
		//echo '</pre><hr>';


		if (is_multisite()) {
			$this->sites_plugins['network_active'] = array_keys(get_site_option('active_sitewide_plugins'));
			//echo 'NETWORK ACTIVE PLUGINS ARRAY<pre>';
			//var_dump($this->sites_plugins['network_active']);
			//echo '</pre><hr>';
		}


		//if (!in_array($plu, $isActive) && !in_array($plu, $this->sites_plugins['network_active'])) {


		$this->admin_page_step_1();



		/*
		$posted_site_id = 0;
		if( isset( $_POST['site_id'] ) ) {
			$posted_site_id = $_POST['site_id'];
		}

		if(
			( $posted_site_id > 0 || isset( $_POST['plugins'] ) ) &&
			isset( $_POST['nonce'] ) &&
			wp_verify_nonce( $_POST['nonce'], get_current_user_id() )
		) {
			$this->admin_page_step_2();
		} else {
			$this->admin_page_step_1();
		}
		*/
	}



	/**
	 * Default Page View.
	 */
	public function admin_page_step_1()
	{
		$user_id = get_current_user_id();
		$user_sites = get_blogs_of_user($user_id);


		// ARRAY TO PARSE
		$json = json_encode($this->sites_plugins);



		

		// ADD UNIQUE FILE TOKEN TO WP-CONFIG IF NOT EXISTS
		require ABSPATH . 'wp-config.php';
		if (!defined('BH_KEY')) {

			// WRITE KEY TO WP-CONFIG
			$key = "-" . substr(md5(uniqid(mt_rand(), true)), 0, 16);

			$fk = fopen(ABSPATH . 'wp-config.php', 'a') or die("Unable to open wp-config!");
			fwrite($fk, "\r\r// BH Plugin Key\r");
			fwrite($fk, "define('BH_KEY', '");
			fwrite($fk, $key);
			fwrite($fk, "');");
			fclose($fk);

			$fileKey = $key;
		}


		// DETERMINE PLUGIN JSON DATA FILE NAME
		if (!$fileKey) {
			$fileKey = BH_KEY;
		}

		// CHECK IF MULTISITE
		if (is_multisite()) {
			echo 'Multisite Site ID: ' . get_current_blog_id() . '<hr>';
			$fileKey = $fileKey . '-' . get_current_blog_id();
			switch_to_blog(1);
			$base_url = get_bloginfo('wpurl');
			restore_current_blog();
		} else {
			$base_url = get_bloginfo('wpurl');
		}


		//echo "Testing FileName Suffix: ";
		//echo $fileKey."<hr>";


		?>
		<form action="" method="post">
			<label>JSON File URL:</label>
			<textarea onclick="this.select()" rows="1" cols="150"><?php echo $base_url . '/wp-content/plugins/bh-plugin-tools/data/plugins' . $fileKey . '.json'; ?></textarea>
		</form>
		<hr>
		<?php







		// LOAD WORDPRESS PLUGIN DATA API
		if (!function_exists('plugins_api')) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}



		// PARSE PLUGIN JSON
		$bhPlugin = array();
		$decoded_json = json_decode($json, true);
		foreach ($decoded_json as $sec => $arr) {
			//echo 'Section is: '.$sec.'<br>';
			if ($sec == "name") {
				echo "<h3>Plugin Details | " . $arr . "</h3>";
				$bhPlugin['name'] = $arr;
				$bhPlugin['date'] = time();
			}

			if ($sec == "all") {
				$cnt = 0;
				$skp = 0;
				foreach ($arr as $plu => $inf) {



					// GET PLUGIN SLUG FROM FILENAME STRING
					$piarr = explode("/", $plu);
					$slug = $piarr[0];
					//echo $slug.'<br>';


					// CALL WORDPRESS PLUGIN DATA API
					$call_api = plugins_api(
						'plugin_information',
						array(
							'slug' => $slug,
							'fields' => array(
								'short_description' => false,
								'description' => false,
								'sections' => false,
								'tested' => true,
								'requires' => false,
								'rating' => false,
								'ratings' => false,
								'downloaded' => true,
								'downloadlink' => true,
								'last_updated' => true,
								'added' => false,
								'tags' => false,
								'compatibility' => false,
								'homepage' => false,
								'versions' => true,
								'donate_link' => true,
								'reviews' => false,
								'banners' => false,
								'icons' => false,
								'active_installs' => true,
								'group' => false,
								'contributors' => false,
							),
						)
					);




					if (is_wp_error($call_api)) {
						$wpAPI = '<li>Plugin API Lookup Failed!</li>';



						// SKIP ENTRIES IF NO API & NOT ACTIVE - NEED TO VERIFY MULTISITE / SINGLE SITE OPERATION
						// MULTISITE DOES NOT PULL PROPER WP API DATA IF PLUGIN NOT ACTIVE
						if (!in_array($plu, $isActive) && !in_array($plu, $this->sites_plugins['network_active'])) {
							echo '<b>'.$slug.'</b> was Skipped | Plugin Not Active or No API Data Found!<br>&nbsp;<br>';
							$skp += 1;
							continue;
						}
					} else {
						$wpAPI = '';
						//echo 'ACTIVE PLUGINS API ARRAY<pre>';
						//var_dump($call_api);
						//echo '</pre><hr>';

						/*
							$call_apis = array(
								'tested' => $call_api->tested,
								'downloaded' => $call_api->downloaded,
								'downloadlink' => $call_api->download_link,
								'last_updated' => $call_api->last_updated,
								'donate_link' => $call_api->donate_link,
								'active_installs' => $call_api->active_installs,
								'external' => $call_api->external,
							);
							echo 'SPECIFIC PLUGINS API ARRAY<pre>';
							var_dump($call_apis);
							echo '</pre><hr>';
							*/
					}
					//echo 'ALL PLUGINS API ARRAY<pre>';
					//var_dump($call_api);
					//echo '</pre><hr>';



					// START ASSEMBLING PLUGIN DATA
					echo '<li>Plugin File: ' . $plu . '</li>';
					$cnt += 1;
					//$piNum = 0;

					foreach ($inf as $key => $value) {

						// SKIP THESE IF THEY EXIST
						if ($key == "wpDiscuz Update") {
							continue;
						}
						if ($key == "TextDomain") {
							continue;
						}
						if ($key == "DomainPath") {
							continue;
						}
						if ($key == "UpdateURI") {
							continue;
						}
						if ($key == "Network") {
							continue;
						}
						if ($key == "Title") {
							continue;
						}
						if ($key == "AuthorName") {
							continue;
						}



						// CREATE PLUGIN NAME AND DOWNLOAD LINKS
						if ($key == "Name") {
							$piName = $value;
						}
						if ($key == "PluginURI") {
							if ($call_api->download_link == '') {
								$piDownload = '';
							} else {
								// MAKE SURE HTTPS USED
								$call_api->download_link = str_replace('http:', 'https:', $call_api->download_link);
								$piDownload = ' | <a style="text-decoration:none;font-weight:bold;" href="' . $call_api->download_link . '">Download</a>';
							}
							$piURL = $value;
							$tst = substr($piURL, 0, 4);
							if ($tst == "http") {
								$piNameOut = '<a style="text-decoration:none;font-weight:bold;" href="' . $piURL . '">' . $piName . '</a>';
							} else {
								$piNameOut = $piName;
							}
							$bhPlugin['all'][$cnt - 1]['Name'] = $piName;
							$bhPlugin['all'][$cnt - 1]['Link'] = $piURL;
							$bhPlugin['all'][$cnt - 1]['Download'] = $call_api->download_link;
						}



						// CREATE AUTHOR NAME AS LINK
						if ($key == "Author") {
							$cvt = explode('|', $value);
							$out = explode(',', $cvt[0]);
							$aName = $out[0];
						}
						if ($key == "AuthorURI") {
							$aURL = $value;
							$chk = substr($aURL, 0, 4);
							if ($chk == "http") {
								$aNameOut = '<a style="text-decoration:none;font-weight:bold;" href="' . $aURL . '">' . $aName . '</a>';
							} else {
								$aNameOut = $aName;
							}
							$bhPlugin['all'][$cnt - 1]['Author'] = $aNameOut;
						}



						// CREATE SHORT DESCRIPTION (truncate at first period or 125 characters)
						if ($key == "Description") {
							$pre_desc = explode('.', $value);
							$sho_desc = $pre_desc[0];
							$sho_desc = wp_strip_all_tags($sho_desc);
							$pos = strpos($sho_desc, ' ', 120);
							$descOut = (strlen($sho_desc) > 120) ? substr($sho_desc, 0, $pos) . '...' : $sho_desc . '.';
							$bhPlugin['all'][$cnt - 1]['Description'] = $descOut;
						}



						// CREATE PLUGIN VERSION
						if ($key == "Version") {
							$versionOut = $value;
							$bhPlugin['all'][$cnt - 1][$key] = $value;
						}



						// CREATE MINIMUM WP VERSION REQUIRED
						if ($key == "RequiresWP") {
							$WPminOut = $value;
							$bhPlugin['all'][$cnt - 1][$key] = $value;
						}



						// CREATE MINIMUM WP VERSION REQUIRED
						if ($key == "RequiresPHP") {
							$PHPminOut = $value;
							$bhPlugin['all'][$cnt - 1][$key] = $value;
						}


						// TESTING STUFF HERE
						// SKIP IF NO VALUE
						//if ($value == "") { continue; }
						//echo '<li>'.$key.': '.$value.'</li>';


					}



					// DISPLAY PLUGIN INFORMATION

					echo '<li>Plugin Name: ' . $piNameOut . $piDownload . '</li>';
					echo '<li>Description: ' . $descOut . '</li>';
					echo '<li>Author Name: ' . $aNameOut . '</li>';
					echo '<li>Plugin Version: ' . $versionOut . '</li>';
					echo '<li>Minimum PHP Version: ' . $PHPminOut . '</li>';
					echo '<li>Minimum WP Version: ' . $WPminOut . '</li>';



					// CREATE & DISPLAY WP PLUGIN API OUTPUT
					if ($wpAPI != '') {
						echo $wpAPI;
					} else {



						// THIS SECTION ADAPTED FROM: Plugins Condition
						// Copyright (c) 2019- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
						$html_ver  = null;
						$html_date = null;
						//$caution   = null;
						if (!empty($call_api)) {
							$wp_ver = get_bloginfo('version');
							$value_split = preg_split('/[a-zA-Z-]/', $wp_ver);
							if ($value_split[0]) {
								$wp_ver = $value_split[0];
							}
							//echo date('n/j/Y', time());
							$pg_ver = $call_api->tested;
							if (version_compare($pg_ver, $wp_ver) >= 0) {
								$html_ver = '<span style="color: darkgreen;">' . $pg_ver . '</span>';
							} else {
								$html_ver = '<span style="color: firebrick;">' . $pg_ver . '</span>';
								//$caution .= '<span style="color: red;">' . $pg_ver . '</span>';
							}
							$pg_update_time = strtotime($call_api->last_updated);
							$now            = time();
							$time_lag       = $now - $pg_update_time;
							$time_lag_date  = $time_lag / 86400;
							if (1 > $time_lag_date) {
								$color_date = 'green';
								$pg_date    = __('Under 24 Hours Ago', 'bh-plugin-tools');
							} else if (1 <= $time_lag_date && 7 > $time_lag_date) {
								$color_date = 'green';
								$day        = floor($time_lag_date);
								if (1 == $day) {
									/* translators: day */
									$pg_date = sprintf(__('%1$d Day Ago', 'bh-plugin-tools'), 1);
								} else {
									/* translators: days */
									$pg_date = sprintf(__('%1$d Days Ago', 'bh-plugin-tools'), $day);
								}
							} else if (7 <= $time_lag_date && 30 > $time_lag_date) {
								$color_date = 'darkgreen';
								$week       = floor($time_lag_date / 7);
								if (1 == $week) {
									/* translators: week */
									$pg_date = sprintf(__('%1$d Week Ago', 'bh-plugin-tools'), 1);
								} else {
									/* translators: weeks */
									$pg_date = sprintf(__('%1$d Weeks Ago', 'bh-plugin-tools'), $week);
								}
							} else if (30 <= $time_lag_date && 365 > $time_lag_date) {
								$color_date = '#333';
								$month      = floor($time_lag_date / 30);
								if (1 == $month) {
									/* translators: month */
									$pg_date = sprintf(__('%1$d Month Ago', 'bh-plugin-tools'), 1);
								} else {
									/* translators: months */
									$pg_date = sprintf(__('%1$d Months Ago', 'bh-plugin-tools'), $month);
								}
							} else {
								$color_date = 'red';
								$year       = floor($time_lag_date / 365);
								if (1 == $year) {
									/* translators: year */
									$pg_date = sprintf(__('%1$d Year Ago', 'bh-plugin-tools'), 1);
								} else {
									/* translators: years */
									$pg_date = sprintf(__('%1$d Years Ago', 'bh-plugin-tools'), $year);
								}
								//$caution .= ' <span style="color: red;">' . $pg_date . '</span>';
							}
							$html_date = '<span style="color: ' . $color_date . ';">' . $pg_date . '</span>';
						} else {
							$html_ver = '<span style="color: indigo;">' . __('Unofficial', 'bh-plugin-tools') . '</span>';
							//$caution .= $html_ver;
						}



						echo '<li>Tested to WP Version: ' . $html_ver . '</li>';
						$bhPlugin['all'][$cnt - 1]['TestedWP'] = $call_api->tested;
						echo '<li>Last Updated: ' . $html_date . '</li>';
						$bhPlugin['all'][$cnt - 1]['LastUpdate'] = $pg_update_time;

						if ($call_api->downloaded > 0) {
							echo '<li>Plugin Downloads: ' . number_format($call_api->downloaded) . '</li>';
						}
						$bhPlugin['all'][$cnt - 1]['Downloads'] = $call_api->downloaded;
						if ($call_api->active_installs > 0) {
							echo '<li>Active Installs: ' . number_format($call_api->active_installs) . '</li>';
						}
						$bhPlugin['all'][$cnt - 1]['Active'] = $call_api->active_installs;

						if ($call_api->external == 1) {
							echo '<li>Private Plugin Repository</li>';
						}
						$bhPlugin['all'][$cnt - 1]['Repo'] = $call_api->external;
						if ($call_api->donate_link != '') {
							echo '<li><a href="' . $call_api->donate_link . '">Click to Donate</a></li>';
						}
						$bhPlugin['all'][$cnt - 1]['Donate'] = $call_api->donate_link;
					}
					echo "<br>";
				}
			}


			/*
			if ($sec == "active") {
    			echo '<hr><b>Active Plugins:</b><br>&nbsp;<br>';
        		foreach($arr as $key => $value) {
        			echo '<li>'.$key.': '.$value.'</li>';
        		}
			}
			*/
		}




		// CREATE CUSTOM ARRAY
		$bhPlugAPI = json_encode($bhPlugin);
		//echo '<hr>TEST CUSTOM ARRAY<pre>';
		//var_dump(json_decode($bhPlugAPI));
		//echo '</pre><hr>';



		// WRITE JSON FILE WITH SUFFIX
		$upload_dir = plugin_dir_path(__DIR__) . 'bh-plugin-tools/data/plugins' . $fileKey . '.json';
		//echo "JSON File Path:<br>";
		//echo $upload_dir."<hr>";
		file_put_contents($upload_dir, $bhPlugAPI);
		//file_put_contents($upload_dir, $json);





		// REPORT NUMBER OF PLUGINS & NON WP.org Plugins SKIPPED
		if ($skp > 0) {
			$skp = '| ' . $skp . ' Skipped';
		} else {
			$skp = "";
		}
		echo "<hr>" . $cnt . " Plugins Processed " . $skp;

		?>
		<hr>
		<form action="" method="post">
			<label>ALL PLUGIN INFORMATION | RAW JSON</label>
			<textarea onclick="this.select()" rows="2" cols="150"><?php echo json_encode($this->sites_plugins); ?></textarea>
		</form>
		</div>
		<?php


	}











	// KEEP BELOW HERE AS EXAMPLES FOR NOW


	/**
	 * The view for actually comparing plugins in the 'Compare' admin menu page.
	 */
	public function admin_page_step_2()
	{
		$site_id = intval($_POST['site_id']);
		$other_sites_plugins = unserialize(base64_decode($_POST['plugins']));

		if (!$other_sites_plugins[0] && $site_id > 0) {
			switch_to_blog($site_id);
			$blog_deets = get_blog_details($site_id);

			$other_sites_plugins = array(
				'name' => $blog_deets->blogname,
				'active' => get_option('active_plugins')
			);

			restore_current_blog();
		}

		$this_sites_plugins = $this->array_check($this->sites_plugins);
		$other_sites_plugins = $this->array_check($other_sites_plugins);
	?>

		<h2 class="nav-tab-wrapper">
			<a href="#this-site" class="nav-tab nav-tab-active">This Site</a>
			<a href="#other-site" class="nav-tab"><?php echo $other_sites_plugins['name']; ?></a>
		</h2>

		<div id="this-site">

			<?php
			$other_sites_plugin_keys = array_keys($other_sites_plugins['all']);
			$this_sites_plugin_keys = array_keys($this_sites_plugins['all']);

			$missing_plugins = false;
			if (!$site_id) {
				$missing_plugins = array_diff($other_sites_plugin_keys, $this_sites_plugin_keys);
			}

			if ($missing_plugins) : ?>
				<h2>Missing Plugins</h2>
				<p>The following plugins need to be downloaded for this site.</p>

				<?php $this->render_missing_plugin_table($missing_plugins, $other_sites_plugins); ?>

			<?php endif; ?>

			<?php
			$active_plugins = array_diff($other_sites_plugins['active'], $this_sites_plugins['active']);

			if ($missing_plugins && $active_plugins) {
				$active_plugins = array_diff($active_plugins, $missing_plugins);
			}

			if ($active_plugins) :
			?>
				<h2>Active Plugins</h2>
				<p>The following plugins need to be activated for this site.</p>

				<form action="plugins.php?page=bpp-compare-site-plugins&bulk-activate=true" method="post">
					<ol>
						<?php $this->render_plugin_list_items($active_plugins, false, true); ?>
					</ol>
					<input type="submit" class="button button-secondary" value="Activate Selected Plugins">
				</form>
			<?php endif; ?>

			<?php
			$network_plugins = array_diff($other_sites_plugins['network_active'], $this_sites_plugins['network_active']);
			if ($missing_plugins && $network_plugins) {
				$network_plugins = array_diff($network_plugins, $missing_plugins);
			}

			if ($network_plugins) : ?>
				<h2>Network Plugins</h2>
				<p>The following plugins need to be network activated for <?php echo $other_sites_plugins['name']; ?>.</p>

				<form action="plugins.php?page=bpp-compare-site-plugins&bulk-network-activate=true" method="post">
					<ol>
						<?php $this->render_plugin_list_items($network_plugins, false, true); ?>
					</ol>
					<input type="submit" class="button button-secondary" value="Network Activate Selected Plugins">
				</form>
			<?php endif; ?>

		</div>

		<div id="other-site" class="hide">

			<?php
			$other_sites_plugin_keys = array_keys($other_sites_plugins['all']);
			$this_sites_plugin_keys = array_keys($this_sites_plugins['all']);

			$missing_plugins = false;
			if (!$site_id) {
				$missing_plugins = array_diff($this_sites_plugin_keys, $other_sites_plugin_keys);
			}

			if ($missing_plugins) : ?>
				<h2>Missing Plugins</h2>
				<p>The following plugins need to be downloaded for <?php echo $other_sites_plugins['name']; ?>.</p>

				<?php $this->render_missing_plugin_table($missing_plugins, $this_sites_plugins); ?>
			<?php endif; ?>

			<?php
			$active_plugins = array_diff($this_sites_plugins['active'], $other_sites_plugins['active']);
			if ($missing_plugins && $active_plugins) {
				$active_plugins = array_diff($active_plugins, $missing_plugins);
			}

			if ($active_plugins) :
			?>
				<h2>Active Plugins</h2>
				<p>The following plugins need to be activated for <?php echo $other_sites_plugins['name']; ?>.</p>

				<ol>
					<?php $this->render_plugin_list_items($active_plugins); ?>
				</ol>
			<?php endif; ?>

			<?php
			$network_plugins = array_diff($this_sites_plugins['network_active'], $other_sites_plugins['network_active']);
			if ($missing_plugins && $network_plugins) {
				$network_plugins = array_diff($network_plugins, $missing_plugins);
			}

			if ($network_plugins && is_multisite() && !$site_id) : ?>
				<h2>Network Plugins</h2>
				<p>The following plugins need to be activated network-wide for <?php echo $other_sites_plugins['name'] ?>.</p>

				<ol>
					<?php $this->render_plugin_list_items($network_plugins); ?>
				</ol>
			<?php endif; ?>

		</div>

		<script>
			jQuery(document).ready(function($) {
				var $containers = $('#this-site, #other-site');
				$('.nav-tab-wrapper a').click(function(e) {
					e.preventDefault();
					$this = $(this);
					$this.parent().find('.nav-tab-active').removeClass('nav-tab-active');
					$this.addClass('nav-tab-active');

					$containers.addClass('hide');

					var id = this.href.split('#')[1];
					$('#' + id).removeClass('hide');
				});

				$containers.each(function(index, element) {
					$this = $(this);
					if ($this.children().length == 0) {
						$this.append('<p>Everything looks good here!</p>');
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Handles activating multiple plugins all at once.
	 */
	public function bulk_activate_plugins()
	{
		$network_wide = false;
		if ($_GET['bulk-network-activate'] == true) {
			$network_wide = true;
		}

		$checkboxes = $_POST['checkbox'];
		if (!$checkboxes || !is_array($checkboxes)) {
			wp_die('Nothing selected.');
		}

		$result = activate_plugins($checkboxes, $redirect = '', $network_wide);

		if (is_wp_error($result)) {
			echo '<div id="message" class="error"><p>' . $result->get_error_message() . '</p></div>';
		} else {
			global $updated_message;
			$label = 'plugins';
			if (count($checkboxes) == 1) {
				$label = 'plugin';
			}

			$network_label = '';
			if ($network_wide) {
				$network_label = 'Network ';
			}

			$updated_message = $network_label . 'Activated ' . count($checkboxes) . ' ' . $label;
		?>
			<div class="updated" id="message">
				<p><?php echo $updated_message; ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Given a list of $paths render <li> items containing a checkbox, a link to the plugin URI if available, and the plugin name.
	 * @param  array $paths            An array of plugin paths to get details and render as <li>s
	 * @param  boolean $plain          Whether to show a non-linked plain <li> item with no additional details. Defaults to false.
	 * @param  boolean $show_checkbox  Whether to show a checkbox in the list items or not.
	 */
	public function render_plugin_list_items($paths, $plain = false, $show_checkbox = false)
	{
		if (!is_array($paths)) {
			return false;
		}

		foreach ($paths as $path) :

			$checkbox = '';
			if ($show_checkbox) {
				$checkbox = "<input type='checkbox' value='$path' name='checkbox[]'> ";
			}

			if ($plain) {
			?>
				<li><?php echo $checkbox . $path; ?></li>
			<?php
			} else {
				$deets = get_plugin_data(trailingslashit(WP_PLUGIN_DIR) . $path);
				$plugin_url = $deets['PluginURI'];
				$plugin_name = $deets['Name'];
				// $plugin_description = $deets['Description'];

				if ($plugin_url) {
					$plugin_name = '<a href="' . esc_url($plugin_url) . '" target="_blank" title="' . esc_attr($path) . '">' . $plugin_name . '</a>';
				}
			?>
				<li><?php echo $checkbox;
					echo $plugin_name; ?></li>
		<?php }

		endforeach;
	}

	/**
	 * Render an HTML table of details about plugins that are missing from a site.
	 * @param  array $missing_plugins     An array of plugin paths that are misisng from the current site.
	 * @param  array $other_sites_plugins Array of all plugin details to compare against.
	 */
	public function render_missing_plugin_table($missing_plugins, $other_sites_plugins)
	{
		?>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th></th>
					<th>Plugin Name</th>
					<th>Path</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$count = 1;
				foreach ($missing_plugins as $plugin_path) :
					$plugin = $other_sites_plugins['all'][$plugin_path];
					$plugin_name = $plugin['Name'];
					if ($plugin['PluginURI']) {
						$plugin_name = '<a href="' . $plugin['PluginURI'] . '" target="_blank">' . $plugin_name . '</a>';
					}

					$class = 'alternate';
					if ($count % 2) {
						$class = '';
					}
				?>
					<tr class="<?php echo $class; ?>">
						<td><?php echo $count . '.'; ?></td>
						<td><?php echo $plugin_name; ?></td>
						<td><?php echo $plugin_path; ?></td>
					</tr>
				<?php
					$count++;
				endforeach;
				?>
			</tbody>
		</table>
<?php
	}

	/**
	 * Helper function to make sure the proper array keys are set to avoid PHP notices and errors.
	 * @param  array $arr The array to check.
	 */
	public function array_check($arr)
	{
		$keys = array('all', 'network_active', 'active');
		foreach ($keys as $key) {
			if (!is_array($arr[$key])) {
				$arr[$key] = array();
			}
		}

		return $arr;
	}
}

// Create the class and kick things off...
$bpp_compare_site_plugins = new BPP_Compare_Site_Plugins();
$bpp_compare_site_plugins->setup();