<?php
/*
Plugin Name: CF Featured Search
Plugin URI: http://crowdfavorite.com 
Description: Featured Search
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

// Constants
define('CFFS_VERSION', '1.0');
define('CFFS_DIR',trailingslashit(realpath(dirname(__FILE__))));

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}

// Includes


// Init Functions

load_plugin_textdomain('cffs');

function cffs_request_handler() {
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
		}
	}
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cffs_check_meta':
				if (!empty($_GET['s'])) {
					echo intval(cffs_check_meta($_GET['s']));
				}
				else {
					echo intval(true);
				}
				die();
				break;
			case 'cffs_admin_js':
				cffs_admin_js();
				die();
				break;
			case 'cffs_admin_css':
				cffs_admin_css();
				die();
				break;
		}
	}
}
add_action('init', 'cffs_request_handler');

function cffs_admin_js() {
	header('Content-type: text/javascript');
	do_action('cffs-admin-js');
	echo file_get_contents(CFFS_DIR.'js/cf-result.js');
	?>
	;(function($) {
		$(function() {
			$("#cffs-add-new").live("click", function() {
				var unique = new Date().valueOf();
				var id = unique.toString();
				$("#cffs-items-list").append($("#cffs-item-add-input").html().replace(/###CFFS###/g, id));
				$(".cffs-notify").show();
				$("#cffs-item-value-input-"+id).cf_result("index.php?cf_action=cffs_check_meta&post_id="+$("#post_ID").val(), {
					searchingClass: "cffs-item-value-input-searching",
					negativeClass: "cffs-item-value-input-negative",
					positiveClass: "cffs-item-value-input-positive"
				});
			});
			$(".cffs-item-value-delete").live("click", function() {
				if(confirm('Are you sure you want to delete this?')) {
					var _this = $(this);
					var id = _this.attr('id').replace('cffs-item-delete-','');

					$("#_cffs-match-"+id).remove();
					$(".cffs-notify").show();
				}
			});

			$(".cffs-item-value-input").cf_result("index.php?cf_action=cffs_check_meta&post_id="+$("#post_ID").val(), {
				searchingClass: "cffs-item-value-input-searching",
				negativeClass: "cffs-item-value-input-negative",
				positiveClass: "cffs-item-value-input-positive"
			});
		});
	})(jQuery);
	<?php
	die();
}

function cffs_admin_css() {
	header('Content-type: text/css');
	do_action('cffs-admin-css');
	?>
	.cffs-items {
		margin:10px;
		-moz-border-radius: 6px;
		-khtml-border-radius: 6px;
		-webkit-border-radius: 6px;
		border-radius: 6px;
		border: 1px solid #DFDFDF;
		background: #F9F9F9 none repeat scroll 0 0;
	}
	.cffs-items-title {
		background:#F1F1F1 none repeat scroll 0 0;
		border-bottom:1px solid #DFDFDF;
		font-size:11px;
		font-weight:bold;
		margin:0;
		padding:5px 8px 8px;
	}
	fieldset.type_block {
		border-bottom:1px solid #DFDFDF;
	}
	.cffs-item {
		clear:both;
	}
	.cffs-item-value-input {
		width:65%;
	}
	.cffs-item-description {
		float:left;
		width:24%;
	}
	.cffs-item-add-button {
	}
	.cffs-item-value-input-searching {
		background:#FFFFFF url(../../wp-admin/images/wpspin_light.gif) no-repeat scroll right center;
	}
	.cffs-item-value-input-negative {
		background:#FFFFFF url(../../wp-includes/images/smilies/icon_mrgreen.gif) no-repeat scroll right center;
	}
	.cffs-item-value-input-positive {
		background:#FFFFFF url(../../wp-includes/images/smilies/icon_exclaim.gif) no-repeat scroll right center;
	}
	.cffs-notify {
		background-color:#FFFFE0;
		border:1px solid #E6DB55;
		-moz-border-radius: 3px;
		-khtml-border-radius: 3px;
		-webkit-border-radius: 3px;
		border-radius: 3px;
		margin:5px 15px;
		color:#333333;
		padding:5px;
	}
	<?php
	die();
}

// Add the CSS and JS to the proper places in the WP Admin
if (basename($_SERVER['SCRIPT_FILENAME']) == 'post.php' || basename($_SERVER['SCRIPT_FILENAME']) == 'page.php' || basename($_SERVER['SCRIPT_FILENAME']) == 'post-new.php' || basename($_SERVER['SCRIPT_FILENAME']) == 'page-new.php') {
	wp_enqueue_script('jquery');
	wp_enqueue_script('cffs-admin-js', trailingslashit(get_bloginfo('url')).'?cf_action=cffs_admin_js', array('jquery'), CFFS_VERSION);
	wp_enqueue_style('cffs-admin-css',	trailingslashit(get_bloginfo('url')).'?cf_action=cffs_admin_css', array(), CFFS_VERSION, 'screen');
	
	add_action('admin_head', 'cffs_postpage_init');
}

// Post/Page Functions

function cffs_postpage_init() {
	add_meta_box('cffs', __('CF Featured Search', 'cffs'), 'cffs_postpage_box', 'post', 'advanced', 'high');
	add_meta_box('cffs', __('CF Featured Search', 'cffs'), 'cffs_postpage_box', 'page', 'advanced', 'high');
}

function cffs_postpage_box() {
	global $post;
	$cffs = get_post_meta($post->ID, '_cffs-featured-search', true);
	?>
	<div class="cffs-box">
		<div class="cffs-description">
			<p>
				<?php _e('The CF Featured Search items are controlled here.  Any text string entered here will be caught in the exact search and this post will be featured in that search.', 'cffs'); ?>
			</p>
			<p>
				<small>
					<?php _e('NOTE: Exact searches will only be allowed on one post.  Any duplicate exact searches will be removed upon post submission.', 'cffs'); ?>
				</small>
			</p>
			<p class="cffs-notify" style="display:none;">
				<?php _e('You have unsaved changes on this page, make sure you save changes when finished.', 'cffs'); ?>
			</p>
		</div>
		<div class="cffs-items">
			<div class="cffs-items-title">
				<?php _e('Exact Matches', 'cffs'); ?>
			</div>
			<div id="cffs-items-list" class="cffs-items-list">
				<?php
				if (is_array($cffs) && !empty($cffs)) {
					foreach ($cffs as $key => $value) {
						?>
						<fieldset id="_cffs-match-<?php echo $key; ?>" class="type_block">
							<p id="cffs-item-<?php echo $key; ?>" class="cffs-item">
								<label for="cffs-item-value-input-<?php echo $key; ?>" class="cffs-item-description">
									<?php _e('Match: ', 'cffs'); ?>
								</label>
								<input type="text" name="cffs[<?php echo $key; ?>]" value="<?php echo $value; ?>" id="cffs-item-value-input-<?php echo $key; ?>" class="cffs-item-value-input widefat" />
								<input type="button" name="cffs-<?php echo $key; ?>-delete" value="Delete" id="cffs-item-delete-<?php echo $key; ?>" class="cffs-item-value-delete button" />
							</p>
						</fieldset>
						<?php
					}
				}
				?>
			</div>
			<p class="cffs-item-add-button">
				<input type="button" value="<?php _e('Add New Match', 'cffs'); ?>" id="cffs-add-new" class="cffs-add-new button" />
			</p>
		</div>
		<div id="cffs-item-add-input" style="display:none;">
			<fieldset id="_cffs-match-###CFFS###" class="type_block">
				<p id="cffs-item-###CFFS###" class="cffs-item">
					<label for="cffs-item-value-input-###CFFS###" class="cffs-item-description">
						<?php _e('Match: ', 'cffs'); ?>
					</label>
					<input type="text" name="cffs[###CFFS###]" value="" id="cffs-item-value-input-###CFFS###" class="cffs-item-value-input widefat" />
					<input type="button" name="cffs-###CFFS###-delete" value="Delete" id="cffs-item-delete-###CFFS###" class="cffs-item-value-delete button" />
				</p>
			</fieldset>
		</div>
		<input type="hidden" name="cffs-active" value="yes" />
	</div>
	<?php
}

function cffs_save_post($post_id, $post) {
	if (!empty($_POST['cffs-active']) && $_POST['cffs-active'] == 'yes') {
		switch ($post->post_type) {
			case 'revision':
				return;
				break;
			case 'page':
			case 'post':
				unset($_POST['cffs']['###CFFS###']);
				cffs_save_meta($post_id, $_POST['cffs']);
				break;
		}
	}
}
add_action('save_post', 'cffs_save_post', 10, 2);

function cffs_save_meta($post_id, $meta = array()) {
	if (empty($post_id) || $post_id == 0) { return; }
	
	$cffs = array();
	if (is_array($meta) && !empty($meta)) {
		foreach ($meta as $key => $value) {
			$cffs[] = strtolower(stripslashes($value));
		}
	}
	update_post_meta($post_id, '_cffs-featured-search', $cffs);
}

function cffs_check_meta($search = '') {
	global $wpdb;
	$search = $wpdb->escape(strtolower(trim($search)));
	$results = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE 1 AND meta_key = '_cffs-featured-search' AND meta_value LIKE '%".$search."%'");
	if (is_array($results) && !empty($results)) {
		foreach ($results as $result) {
			$post_id = $result->post_id;
			$meta = maybe_unserialize($result->meta_value);
			if (is_array($meta) && !empty($meta)) {
				foreach ($meta as $key => $value) {
					if ($value == $search) {
						return true;
					}
				}
			}
		}
	}
	return false;
}













?>