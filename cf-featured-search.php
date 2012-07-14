<?php
/*
Plugin Name: CF Featured Search
Plugin URI: http://crowdfavorite.com
Description: Featured Search
Version: 1.0.3
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

// Constants
define('CFFS_VERSION', '1.0.3');
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
		background:#FFFFFF url("<?php bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif") no-repeat scroll right center;
	}
	.cffs-item-value-input-negative {
		background:#FFFFFF url("<?php bloginfo('wpurl'); ?>/wp-includes/images/smilies/icon_mrgreen.gif") no-repeat scroll right center;
	}
	.cffs-item-value-input-positive {
		background:#FFFFFF url("<?php bloginfo('wpurl'); ?>/wp-includes/images/smilies/icon_exclaim.gif") no-repeat scroll right center;
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
function cffs_enqueue_scripts($hook_suffix) {
	$ones_were_looking_for = array(
		'post.php',
		'page.php',
		'post-new.php',
		'page-new.php',
	);
	if (in_array($hook_suffix, $ones_were_looking_for)) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('cffs-admin-js', trailingslashit(get_bloginfo('url')).'?cf_action=cffs_admin_js', array('jquery'), CFFS_VERSION);
		wp_enqueue_style('cffs-admin-css',	trailingslashit(get_bloginfo('url')).'?cf_action=cffs_admin_css', array(), CFFS_VERSION, 'screen');
	}
}
add_action('admin_enqueue_scripts', 'cffs_enqueue_scripts');



// Admin Display

function cffs_admin_menu() {
	add_submenu_page(
		'edit.php',
		__('CF Featured Search', 'cffs'),
		__('CF Featured Search', 'cffs'),
		'edit_pages',
		'cf-featured-search',
		'cffs_options'
	);
}
add_action('admin_menu', 'cffs_admin_menu');

function cffs_options() {
	// Alex wants it done this way, there is no worry about this page becoming too big "right now", so no effort has been put into
	// this page not crashing the server. --SK 3/31/10 6:00 PM
	global $wpdb;
	$ids = $wpdb->get_results($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_cffs-featured-search'"));

	$by_title = array();
	$by_term = array();

	if (is_array($ids) && !empty($ids)) {
		foreach ($ids as $id) {
			$title = get_the_title($id->post_id);
			$terms = get_post_meta($id->post_id, '_cffs-featured-search', true);
			$post_edit_link = get_edit_post_link($id->post_id, 'not_display');
			$display_link = get_permalink($id->post_id);

			if (is_array($terms) && !empty($terms)) {
				$by_title[sanitize_title($title)] = array(
					'id' => $id->post_id,
					'title' => $title,
					'terms' => $terms,
					'post_edit_link' => $post_edit_link,
					'display_link' => $display_link
				);

				foreach ($terms as $term) {
					if (empty($by_term[$term])) {
						$by_term[$term] = array(
							'term' => $term,
							'title' => $title,
							'post_edit_link' => $post_edit_link,
							'display_link' => $display_link
						);
					}
				}
				ksort($by_title);
				ksort($by_term);
			}
		}
	}
	?>
	<div class="wrap">
		<?php echo screen_icon().'<h2>'.__('CF Featured Search Terms', 'cffs').'</h2>'; ?>
		<p>
			<?php _e('This page displays all of the Featured Search terms for all of the posts/pages.', 'cffs'); ?>
		</p>
		<?php if (is_array($by_title) && !empty($by_title)) { ?>
		<div class="cffs-sort-by-title" style="float:left; width:45%; padding:20px;">
		<h3><?php _e('Terms By Title', 'cffs'); ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th style="width:25%;">
						<?php _e('Post Title', 'cffs'); ?>
					</th>
					<th>
						<?php _e('Terms', 'cffs'); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width:25%;">
						<?php _e('Post Title', 'cffs'); ?>
					</th>
					<th>
						<?php _e('Terms', 'cffs'); ?>
					</th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				foreach ($by_title as $key => $data) {
					?>
					<tr>
						<td>
							<?php echo $data['title']; ?> | <a href="<?php echo $data['post_edit_link']; ?>"><?php _e('Edit', 'cffs'); ?></a> | <a href="<?php echo $data['display_link']; ?>"><?php _e('View', 'cffs'); ?></a>
						</td>
						<td>
							<?php
							if (is_array($data['terms']) && !empty($data['terms'])) {
								$count = 0;
								foreach ($data['terms'] as $term) {
									$count++;
									echo $term;
									if ($count < count($data['terms'])) {
										echo '<br />';
									}
								}
							}
							else {
								echo $data['terms'];
							}
							?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		</div>
		<?php } ?>
		<?php if (is_array($by_term) && !empty($by_term)) { ?>
		<div class="cffs-sort-by-term" style="float:left; width:45%; padding:20px;">
		<h3><?php _e('Terms By Term', 'cffs'); ?></h3>
		<table class="widefat">
			<thead>
				<tr>
					<th style="width:25%;">
						<?php _e('Term', 'cffs'); ?>
					</th>
					<th>
						<?php _e('Post Title', 'cffs'); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th style="width:25%;">
						<?php _e('Term', 'cffs'); ?>
					</th>
					<th>
						<?php _e('Post Title', 'cffs'); ?>
					</th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				foreach ($by_term as $key => $data) {
					?>
					<tr>
						<td>
							<?php echo $data['term']; ?>
						</td>
						<td>
							<?php echo $data['title']; ?> | <a href="<?php echo $data['post_edit_link']; ?>"><?php _e('Edit', 'cffs'); ?></a> | <a href="<?php echo $data['display_link']; ?>"><?php _e('View', 'cffs'); ?></a>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		</div>
		<?php } ?>
		<div class="clear"></div>
	</div>
	<?php
}

// Post/Page Functions

function cffs_postpage_init() {
	foreach (cffs_get_enabled_post_types() as $post_type) {
		add_meta_box('cffs', __('CF Featured Search', 'cffs'), 'cffs_postpage_box', $post_type, 'advanced', 'high');
	}
}
add_action('admin_head', 'cffs_postpage_init');

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

/* Allow multiple post types to have a featured search term */
function cffs_get_enabled_post_types() {
	return (array) apply_filters('cffs_post_types', array('post', 'page'));
}

function cffs_save_post($post_id, $post) {
	if (!empty($_POST['cffs-active']) && $_POST['cffs-active'] == 'yes') {
		if (in_array($post->post_type, cffs_get_enabled_post_types())){
			unset($_POST['cffs']['###CFFS###']);
			cffs_save_meta($post_id, $_POST['cffs']);
		}
	}
}
add_action('save_post', 'cffs_save_post', 10, 2);

function cffs_save_meta($post_id, $meta = array()) {
	if (empty($post_id) || $post_id == 0) { return; }

	$cffs = array();
	if (is_array($meta) && !empty($meta)) {
		foreach ($meta as $key => $value) {
			if (empty($value)) { continue; }
			$check = cffs_check_meta(strtolower(stripslashes($value)));
			if ($check == 0 || $check == $post_id) {
				$cffs[] = strtolower(stripslashes($value));
			}
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
						return $post_id;
					}
				}
			}
		}
	}
	return false;
}


// Search Functions

function cffs_get_featured_search() {
	global $cffs_featured_id;

	if ($cffs_featured_id == 0) { return false; }
	// Use the Featured ID found in the posts_request function and search for that post/page id to be featured.
	$featured = new WP_Query(array(
		'p' => $cffs_featured_id,
		'post_type' => 'any'
	));

	if ($featured->have_posts()) {
		$featured->the_post();
		ob_start();
		edit_post_link('Edit', '', ' | ');
		$edit_link = ob_get_contents();
		ob_end_clean();
		ob_start();
		comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;');
		$comments_link = ob_get_contents();
		ob_end_clean();

		$featured_content = apply_filters('cffs_get_featured_search', '
		<div id="cffs-featured-search-'.get_the_ID().'" class="cffs-featured-search">
			<h3 id="post-'.get_the_ID().'"><a href="'.get_permalink().'" rel="bookmark" title="Permanent Link to '.the_title_attribute(array('echo' => false)).'">'.get_the_title().'</a></h3>
		</div>
		', get_the_ID());
		wp_reset_postdata();
		return $featured_content;
	}
	return false;
}

function cffs_featured_search() {
	echo apply_filters('cffs_featured_search', cffs_get_featured_search());
}

function cffs_posts_request($posts_query) {
	global $wp_the_query, $search, $cffs_featured_id;

	if (is_search()) {
		$cffs_featured_id = cffs_check_meta(str_replace('+', ' ', $wp_the_query->query_vars['s']));
		if ($cffs_featured_id != 0) {
			// Exclude the featured post from the search display
			array_push($wp_the_query->query_vars['post__not_in'], $cffs_featured_id);
			$begin = substr($posts_query, 0, strpos($posts_query, 'ORDER BY'));
			$end = substr($posts_query, strpos($posts_query, 'ORDER BY'), strlen($posts_query));
			$posts_query = $begin." AND wp_posts.ID != '$cffs_featured_id' ".$end;
		}
	}
	remove_action('posts_request', 'cffs_posts_request');
	return $posts_query;
}
add_action('posts_request', 'cffs_posts_request');

?>
