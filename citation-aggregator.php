<?php
/*
Plugin Name: Citation Aggregator
Plugin URI: http://techxplorer.com/projects/citation-aggregator/
Version: 2.1
Author: techxplorer
Author URI: http://techxplorer.com
Description: A plugin to aggregate feeds from various websites to create lists of links (citations) in a post
*/
/*
 * Citation Aggregator - A plugin to aggregate feeds from various citation management websites
 * and create posts on a regular schedule
 * Copyright (C) 2008 - 2009 Corey Wallis <corey@techxplorer.com>
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

//Debug Code
//error_reporting(E_ALL);
//ini_set('display_errors', 'true');

// define a constant for the WordPress User who owns the links added by the plugin
// only need to change this value if you don't use the primary admin account
if(!defined('CITATION_AGGREGATOR_USER')) {
	define('CITATION_AGGREGATOR_USER', 1);
}

/**
 * citation-aggregator class contains all of the code for the operation of the plugin
 */
if (!class_exists('citation_aggregator')) {

	class citation_aggregator {
	
		// Name of the options variable stored in the database
		var $admin_opt_name = 'citation-aggregator';
		
		/**
		 * Private function to see if the SimplePie class
		 * @returns bool
		 */
		function check_simple_pie() {
			
			// check if the class exists
			if(!class_exists('SimplePie')) {
				// class is missing, so can we load the version included with WordPress?
				
				// build path
				$simplepie_path = ABSPATH . WPINC . '/simplepie.inc';
				
				// check to see it exists
				if(is_file($simplepie_path) && is_readable($simplepie_path)) {
					// include the file
					require_once($simplepie_path);
				} else {
					// it appears to be missing, include our copy
					require_once(dirname(__FILE__) . '/includes/simplepie/simplepie.inc');
				}
			}
			
			// double check to ensure everything is now loaded
			if(!class_exists('SimplePie')) {
				// Class doesn't appear to be here
				return FALSE;
			} else {
				return TRUE;
			}
		} // end simple pie class check
					
		
	
		/**
		 * Function to build and display the admin page
		 */
		function display_admin_page() {
				
			// Check to ensure the SimplePie class is available
			if($this->check_simple_pie() == FALSE) {
				print '<div id="message" class="error fade"><p><strong>Error: </strong>The SimplePie class is missing, either the version included with WordPress or the one included with this plugin could not be loaded.';
				print '<br/>This class is required for the plugin to work. Please check the necessary files and permissions to see why the class is missing.</p></div>';
			}
			
			// start output of the page
			print '<div class="wrap"><h2>Citation Aggregator</h2><form method="post" action="options.php">';
			print '<table class="form-table">';
			
			// output hidden fields
			settings_fields($this->admin_opt_name . '-options');
			
			// collect the email address
			print '<tr valign="top"><th scope="row">Email Address</th><td>';
			print '<input type="text" name="' . $this->admin_opt_name . '_email_address" value="' . get_option($this->admin_opt_name . '_email_address') . '" />';
			print '<br/>Enter an email address that will receive email notifications when links are successfully added<br/>Leave blank to disable this feature</td></tr>';
			
			// get the default category
			print '<tr valign="top"><th scope="row">Default Link Category</th><td>';
			print $this->get_categories($this->admin_opt_name . '_default_category', get_option($this->admin_opt_name . '_default_category'));
			print '<br/>Select the default category for all links aggregated by the plugin';
			print '<br/>It is recommended that you <a href="/wp-admin/edit-link-categories.php" title="Link Categories Admin page">create a new link category</a> specifically for this purpose</td></tr>';
			
			// display promo link
			print '<tr valign="top"><th scope="row">Display Plugin Link</th><td>';
			
			if(get_option($this->admin_opt_name . '_promo_link') == 'yes') {			
				print '<input type="checkbox" checked="checked" name="' . $this->admin_opt_name . '_promo_link" value="yes"/>';			
			} else {			
				print '<input type="checkbox" name="' . $this->admin_opt_name . '_promo_link" value="yes"/>';			
			}
			
			print '<br/>Tick this box to display a link to the plugin project page at the end of the list of links<br/>(disabled by default)</td></tr>';
					
			// enable Delicious aggregator
			print '<tr valign="top"><th scope="row">Enable Delicious Feed</th><td>';
			
			if(get_option($this->admin_opt_name . '_enable_delicious') == 'yes') {			
				print '<input type="checkbox" id="delicious_enable" checked="checked" name="' . $this->admin_opt_name . '_enable_delicious" value="yes"/>';			
			} else {			
				print '<input type="checkbox" id="delicious_enable" name="' . $this->admin_opt_name . '_enable_delicious" value="yes"/>';			
			}
			
			print '<br/>Tick this box to enable aggregation of a Delicious feed</td></tr>';
			
			// tag to aggregate
			print '<tr valign="top" class="delicious_options"><th scope="row">Delicious Tag</th><td>';
			print '<input type="text" name="' . $this->admin_opt_name . '_delicious_tag" value="' . get_option($this->admin_opt_name . '_delicious_tag') . '" />';
			print '<br/>Enter the tag that identifies items as those which you want to aggregate</td></tr>';
			
			// users to confirm
			print '<tr valign="top" class="delicious_options"><th scope="row">Delicious Users</th><td>';
			print '<input type="text" name="' . $this->admin_opt_name . '_delicious_users" value="' . get_option($this->admin_opt_name . '_delicious_users') . '" />';
			print '<br/>Enter the Delicious users that you trust to identify items for aggregation<br/>Any users not in this will have their links discarded</td></tr>';
			
			// get the delicious category
			print '<tr valign="top" class="delicious_options"><th scope="row">Delicious Link Category</th><td>';
			print $this->get_categories($this->admin_opt_name . '_delicious_category', get_option($this->admin_opt_name . '_delicious_category'));
			print '<br/>Select the default category for Delicious links aggregated by the plugin';
			print '<br/>It is recommended that you <a href="/wp-admin/edit-link-categories.php" title="Link Categories Admin page">create a new link category</a> specifically for this purpose</td></tr>';

			// enable Connotea aggregator
			print '<tr valign="top"><th scope="row">Enable Connotea Feed</th><td>';
			
			if(get_option($this->admin_opt_name . '_enable_connotea') == 'yes') {			
				print '<input type="checkbox" id="connotea_enable" checked="checked" name="' . $this->admin_opt_name . '_enable_connotea" value="yes"/>';			
			} else {			
				print '<input type="checkbox" id="connotea_enable" name="' . $this->admin_opt_name . '_enable_connotea" value="yes"/>';
			}
			
			print '<br/>Tick this box to enable aggregation of a Connotea feed</td></tr>';
			
			// tag to aggregate
			print '<tr valign="top" class="connotea_options"><th scope="row">Connotea Tag</th><td>';
			print '<input type="text" name="' . $this->admin_opt_name . '_connotea_tag" value="' . get_option($this->admin_opt_name . '_connotea_tag') . '" />';
			print '<br/>Enter the tag that identifies items as those which you want to aggregate</td></tr>';
			
			// users to confirm
			print '<tr valign="top" class="connotea_options"><th scope="row">Connotea Users</th><td>';
			print '<input type="text" name="' . $this->admin_opt_name . '_connotea_users" value="' . get_option($this->admin_opt_name . '_connotea_users') . '" />';
			print '<br/>Enter the Connotea users that you trust to identify items for aggregation<br/>Any users not in this will have their links discarded</td></tr>';
			
			// get the connotea category
			print '<tr valign="top" class="connotea_options"><th scope="row">Connotea Link Category</th><td>';
			print $this->get_categories($this->admin_opt_name . '_connotea_category', get_option($this->admin_opt_name . '_connotea_category'));
			print '<br/>Select the default category for Connotea links aggregated by the plugin';
			print '<br/>It is recommended that you <a href="/wp-admin/edit-link-categories.php" title="Link Categories Admin page">create a new link category</a> specifically for this purpose</td></tr>';
			
			// enable Google Reader Aggregation
			print '<tr valign="top"><th scope="row">Enable Google Reader Shared Item feeds</th><td>';
			
			if(get_option($this->admin_opt_name . '_enable_google_reader') == 'yes') {			
				print '<input type="checkbox" id="google_reader_enable" checked="checked" name="' . $this->admin_opt_name . '_enable_google_reader" value="yes"/>';			
			} else {			
				print '<input type="checkbox" id="google_reader_enable" name="' . $this->admin_opt_name . '_enable_google_reader" value="yes"/>';			
			}
			
			print '<br/>Tick this box to enable aggregation of Google Reader Shared Item feeds</td></tr>';
			
			// get the Google Reader Shared Items Category
			print '<tr valign="top" class="google_reader"><th scope="row">Google Reader Shared Item Pages</th><td>';
			print $this->get_categories($this->admin_opt_name . '_google_reader_source_category', get_option($this->admin_opt_name . '_google_reader_source_category'));
			print '<br/>Select the category that contains links to the Google Reader Shared Item pages';
			print '<br/>This category will need to contain links to the Google Reader Shared Item pages, and the URL to the RSS feed containing the item';
			print '<br/>More information avilable on the <a href="http://techxplorer.com/projects/citation-aggregator/" title="Help pages for this plugin">Citation Aggregator</a> website';
			print '<br/>It is recommended that you <a href="/wp-admin/edit-link-categories.php" title="Link Categories Admin page">create a new link category</a> specifically for this purpose</td></tr>';
			
			// get the Google Reader link category
			print '<tr valign="top" class="google_reader"><th scope="row">Google Reader Link Category</th><td>';
			print $this->get_categories($this->admin_opt_name . '_google_reader_category', get_option($this->admin_opt_name . '_google_reader_category'));
			print '<br/>Select the default category for Google Reader links aggregated by the plugin';
			print '<br/>It is recommended that you <a href="/wp-admin/edit-link-categories.php" title="Link Categories Admin page">create a new link category</a> specifically for this purpose</td></tr>';
			
			// finalise table
			print '</table>';
			
			// finalise page
			if($this->check_simple_pie() != FALSE) {
				print '<p class="submit"> <input type="submit" name="Submit" value="' . __('Save Changes') . '" /> </p> </form> </div>';
			}
			
		} // end the display_admin_page function
		
		/**
		 * Private function to get a list of categories and return a select html element
		 * if a category ID matches that provided it is marked as selected
		 */
		private function get_categories($field_name, $cat_id = NULL) {
		
			// get the list of existing link categories
			$categories = get_categories('type=link&hide_empty=0&orderby=name&order=ASC');
			
			// start building the select tag
			$tag = '<select name="' . $field_name . '" size="1">';
			$tag .= '<option value="null">Select a Category</option>';
			
			foreach($categories as $category) {
				if($category->term_id == $cat_id) {
					$tag .= '<option value="' . $category->term_id . '" selected="selected">' . $category->name . '</option>';
				} else {
					$tag .= '<option value="' . $category->term_id . '">' . $category->name . '</option>';
				}
			}
			
			// finalise the tag
			$tag .= '</select>';
			
			return $tag;
		
		} // end function to get the categories
		
		// function to add the javascript the options page
		function admin_page_header() {
		
			print "<!-- JavaScript and includes for the citation-aggregator plugin -->\n";
			wp_enqueue_script('citation-aggregator-options', plugins_url('/citation-aggregator/citation-aggregator-options.js'), array('jquery'), '1.0');
			wp_print_scripts();
		
		} // end function to add javascript to the options page
		
		// function to save aggregated items to the wp_links table
		// return FALSE on failure
		// return NULL when nothing to do
		// return array of saved items if something happened
		private function save_items($primary_category = NULL, $feed_category = NULL, $items = NULL) {
						
			// Scope database class appropriately
			global $wpdb;
			
			// check the passed parameters
			if($primary_category === NULL || $feed_category === NULL || $items === NULL) {
				return FALSE;
			}
			
			// process each item in turn
			foreach($items as $item) {
				// check if the link is in the table already
				$link_check = $wpdb->query($wpdb->prepare("SELECT link_url FROM $wpdb->links WHERE link_url = %s", $item['link']));
				
				if($link_check === 0) {
				
					// link isn't in the table so we can add it
					// declare helper variables
					$link_note = 'Please do not edit this note, it is needed by the Citation Aggregator plugin.<br/>';
					$link_note .= 'This link aggregated by the Citation Aggregator plugin and was recommended by: ' . $item['user'];
					
					// prepare the link categories
					$link_categories = array($primary_category, $feed_category);
					$link_categories = array_map( 'intval', $link_categories );
					$link_categories = array_unique( $link_categories );

					// insert the link					
					$link_id = $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->links (link_url, link_name, link_description, link_visible,
															link_notes, link_owner, link_updated) VALUES (%s, %s, %s, %s, %s, %s, NOW())",
															$item['link'], $item['title'], $item['description'], 'N', $link_note, CITATION_AGGREGATOR_USER));
					
					// check on insert
					if($link_id === FALSE) {
						return FALSE;
					} else {
						// get the id of the just inserted link
						$link_id = (int) $wpdb->insert_id;
						
						// add the link categories
						wp_set_object_terms( $link_id, $link_categories, 'link_category' );
						
						// save this item for reuse in notifications
						$saved_items[] = $item;						
					}
				}
			}
			
			// check the saved_items array
			// return appropriate value
			if(isset($saved_items)) {
				return $saved_items;
			} else {
				return NULL;
			}				
		
		} // end function to save items
		
		/**
		 * Function to update citations
		 */
		function update_citations() {
					
			// get additional helper variables
			$blog_name = get_bloginfo('name');
			$default_category = get_option($this->admin_opt_name . '_default_category');
			$email_address    = get_option($this->admin_opt_name . '_email_address');
			
			// update del.icio.us citations
			if (get_option($this->admin_opt_name . '_enable_delicious') == 'yes') {
				
				// get the additional items
				$feed_category    = get_option($this->admin_opt_name . '_delicious_category');
				
				if($default_category == FALSE || $feed_category == FALSE) {
					return FALSE;
				}
			
				// bring in the required support file
				include_once('citation-aggregator-delicious.php');
				
				// Attempt to parse the feed
				$delicious = new citation_aggregator_delicious();
				
				$status = $delicious->parse_feed();
				
				if($status != FALSE) {
		
					// parsing the feed worked
					// get the items in the feed
					$items = $delicious->get_items();
					
					// save any new items in the list
					$saved_items = $this->save_items($default_category, $feed_category, $items);
					
					if($saved_items === FALSE) {
						// saving of items failed
						if($email_address != FALSE && $email_address != '') {
							// send an email to indicate that the aggregation failed
							$message = "The Citation Aggregator plugin failed to update Delicious items.\nCheck the plugin settings and try again.";
							
							// send the message
							wp_mail($email_address, "[{$blog_name}] Citation Aggregator - Error Message", $message);
						}
					} elseif($saved_items !== NULL) {
						// saving items worked and there were new items
						if($email_address != FALSE && $email_address != '') {
							// send an email to indicate that the aggregation failed
							$message = "New items have been found in the Delicious feed.\nNew items:\n\n";
							
							// add new items to the email message
							foreach($saved_items as $item) {
								$message .= html_entity_decode($item['title']) . "\n";
								$message .= $item['link'] . "\n\n";
							}
							// send the message
							wp_mail($email_address, "[{$blog_name}] Citation Aggregator - New Items (Delicious)", $message);
						}
					}
				} else {
					// parsing the feed failed for some reason
					if($email_address != FALSE && $email_address != '') {
						// send an email to indicate that the aggregation failed
						$message = "The Citation Aggregator plugin failed to parse the Delicious feed.\nCheck the plugin settings and try again.";
						
						// send the message
						wp_mail($email_address, "[{$blog_name}] Citation Aggregator - Error Message", $message);
					}
				}
			} // end working with the Delicious feed
			
			// check for updates from the connotea feed
			if (get_option($this->admin_opt_name . '_enable_connotea') == 'yes') {
			
				// get the additional items
				$feed_category    = get_option($this->admin_opt_name . '_connotea_category');
				
				if($default_category == FALSE || $feed_category == FALSE) {
					return FALSE;
				}
			
				// bring in the required support file
				include_once('citation-aggregator-connotea.php');
				
				// Attempt to parse the feed
				$connotea = new citation_aggregator_connotea();
				$status = $connotea->parse_feed();
				
				if($status != FALSE) {
					// parsing the feed worked
					// get the items in the feed
					$items = $connotea->get_items();
					
					// save any new items in the list
					$saved_items = $this->save_items($default_category, $feed_category, $items);
					
					if($saved_items === FALSE) {
						// saving of items failed
						if($email_address != FALSE && $email_address != '') {
							// send an email to indicate that the aggregation failed
							$message = "The Citation Aggregator plugin failed to update Connotea items.\nCheck the plugin settings and try again.";
							
							// send the message
							wp_mail($email_address, "[{$blog_name}] Citation Aggregator - Error Message", $message);
						}
					} elseif($saved_items !== NULL) {
						// saving items worked and there were new items
						if($email_address != FALSE && $email_address != '') {
							// send an email to indicate that the aggregation failed
							$message = "New items have been found in the Connotea feed.\nNew items:\n\n";
							
							// add new items to the email message
							foreach($saved_items as $item) {
								$message .= html_entity_decode($item['title']) . "\n";
								$message .= $item['link'] . "\n\n";
							}
							
							// send the message
							wp_mail($email_address, "[{$blog_name}] Citation Aggregator - New Items (Connotea)", $message);
						}
					}
				} else {
					// parsing the feed failed for some reason
					if($email_address != FALSE && $email_address != '') {
						// send an email to indicate that the aggregation failed
						$message = "The Citation Aggregator plugin failed to parse the Connotea feed.\nCheck the plugin settings and try again.";
						
						// send the message
						wp_mail($email_address, "[{$blog_name}] Citation Aggregator - Error Message", $message);
					}
				}
			} // end working with the Connotea feed
			
			// check for updates from the Google Reader Feeds
			if (get_option($this->admin_opt_name . '_enable_google_reader') == 'yes') {
			
				// get the additional items
				$feed_category    = get_option($this->admin_opt_name . '_google_reader_category');
				
				if($default_category == FALSE || $feed_category == FALSE) {
					return FALSE;
				}
			
				// bring in the required support file
				include_once('citation-aggregator-google-reader.php');
				
				// Attempt to parse the feed
				$g_reader = new citation_aggregator_google_reader();
				$status = $g_reader->parse_feed();				
				
				if($status != FALSE) {
					// parsing the feeds worked
					// get the items in the feeds
					$items = $g_reader->get_items();
					
					// save any new items in the list
					$saved_items = $this->save_items($default_category, $feed_category, $items);
					
					if($saved_items === FALSE) {
						// saving of items failed
						if($email_address != FALSE && $email_address != '') {
							// send an email to indicate that the aggregation failed
							$message = "The Citation Aggregator plugin failed to update Google Reader items.\nCheck the plugin settings and try again.";
							
							// send the message
							wp_mail($email_address, "[{$blog_name}] Citation Aggregator - Error Message", $message);
						}
					} elseif($saved_items !== NULL) {
						// saving items worked and there were new items
						if($email_address != FALSE && $email_address != '') {
							// send an email to indicate that the aggregation failed
							$message = "New items have been found in the Google Reader feed.\nNew items:\n\n";
							
							// add new items to the email message
							foreach($saved_items as $item) {
								$message .= html_entity_decode($item['title']) . "\n";
								$message .= $item['link'] . "\n\n";
							}
							
							// send the message
							wp_mail($email_address, "[{$blog_name}] Citation Aggregator - New Items (Google Reader)", $message);
						}
					}
				} else {
					// parsing the feed failed for some reason
					if($email_address != FALSE && $email_address != '') {
						// send an email to indicate that the aggregation failed
						$message = "The Citation Aggregator plugin failed to parse the Google Reader feed.\nCheck the plugin settings and try again.";
						
						// send the message
						wp_mail($email_address, "[{$blog_name}] Citation Aggregator - Error Message", $message);
					}
				}
			} // end working with the Connotea feed
			
		} // end the update citations function
		
		/**
		 * Function to process the shortcode and return a list of category based RSS feeds
		 *
		 * @param atts - array of attributes
		 * @param content - content enclosed by the shortcode
		 *
		 * @returns - the generated list of citations
		 */
		function generate_list($atts, $content = NULL) {
		
			// declare helper variables
			global $post, $wpdb;
			$error_start = '<p><strong>Citation Aggregator Error:</strong> ';
			$base_categories = get_categories('type=link&hide_empty=0');
		
			// process the list of attributes
			// based on idiom at: http://codex.wordpress.org/Shortcode_API
			$options = shortcode_atts(array(
				'order' => 'title',
				'type' => 'bullet',
				'start' => '',
				'end' => '',
				'category' => ''
				), $atts);
				
			// get additional helper variables
			$default_category   = get_option($this->admin_opt_name . '_default_category');
			$delicious_enabled  = get_option($this->admin_opt_name . '_enable_delicious');
			$delicious_category = get_option($this->admin_opt_name . '_delicious_category');
			$connotea_enabled   = get_option($this->admin_opt_name . '_enable_connotea');
			$connotea_category  = get_option($this->admin_opt_name . '_connotea_category');
			$gr_enabled         = get_option($this->admin_opt_name . '_enable_google_reader');
			$gr_category        = get_option($this->admin_opt_name . '_google_reader_category');
			$pomo_link          = get_option($this->admin_opt_name . '_promo_link');
			
			// prepare the options
			foreach($options as $key => $value) {
				$temp[$key] = strtolower($value);
			}
			$options = $temp;
			unset($temp);
			
			// double check the options
			if($options['category'] != '') { // category of links to display
				// check to ensure that the category variable contains valid text				
				if($options['category'] != 'delicious' && $options['category'] != 'connotea' && $options['category'] != 'google_reader') {
					// user set category 
					// double check the user set category
					$found = FALSE;
					
					foreach($base_categories as $category) {
						if(strtolower($category->name) == $options['category']) {
							$found = TRUE;
							$options['category'] = $category->term_id;
						}
					}
					
					if($found == FALSE) {
						// user supplied category is wrong
						return $error_start . 'Unable to verify the "category" parameter, please check the shortcode and try again. Default is all categories';
					}
				}
			}
			
			// finalise the category parameter
			switch($options['category']) {
				case 'delicious':
					$options['category'] = $delicious_category;
					break;
				case 'connotea':
					$options['category'] = $connotea_category;
					break;
				case 'google_reader':
					$options['category'] = $gr_category;
				case '':
					$options['category'] = $default_category;
					break;
			}
			
			// check order parameter			
			if($options['order'] != 'title' && $options['order'] != 'time') {
				return $error_start . 'Unable to verify the "order" parameter. It must be either "title" or "time" to order the list of citations by title or then the link was added. Default is "title".</p>';
			}
			
			// check list style
			if($options['type'] != 'bullet' && $options['type'] != 'number') {
				return $error_start . 'Unable to verify the "type" parameter. It must be either "bullet" or "number". Default is "bullet".</p>';
			}
			
			// check the start date
			if($options['start'] != '') {
				if(strlen($options['start']) != 8 || is_numeric($options['start']) == FALSE) {
					return $error_start . 'Unable to verify the "start" parameter. It must in the format yyyymmdd or blank. If left blank the date this post was published will be used.</p>';
				}
			} else {
				$options['start'] = str_replace('-', '', substr($post->post_date, 0, 10));
			}
			
			// check the end date
			if($options['end'] != '') {
				if(strlen($options['end']) != 8 || is_numeric($options['end']) == FALSE) {
					return $error_start . 'Unable to verify the "end" parameter. It must in the format yyyymmdd or blank. If left blank 7 days prior to the post date will be used.</p>';
				}
			} else {
				//calculate the end date
				$date = strtotime(substr($post->post_date, 0, 10) . ' -7 days');
				if($date === FALSE) {
					return $error_start . 'Unable to calculate the "end" parameter. Please check the parameters and try again</p>';
				} else {
					$options['end'] = date('Ymd', $date);
				}
			}
			
			// finalise the dates
			// make sure end is less than start
			if($options['start'] > $options['end']) {
				$temp             = $options['start'];
				$options['start'] = $options['end'];
				$options['end']   = $temp;
			}
			$options['start'] = $options['start'] . ' 00:00:00';
			$options['end']   = $options['end']   . ' 23:59:59';
			
			// finalise the order
			if($options['order'] == 'title') {
				$options['order'] = ' ORDER BY l.link_name';
			} else {
				$options['order'] = ' ORDER BY l.link_updated';
			}
			
			// start building the list
			if($options['type'] == 'bullet') {
				$list = '<ul class="citation_list">';
			} else {
				$list = '<ol class="citation_list">';
			}
			
			// build the sql query
			$sql = "SELECT l.link_url, l.link_name, l.link_description
					FROM wp_links l, wp_terms t, wp_term_taxonomy tt, wp_term_relationships tr
					WHERE l.link_updated BETWEEN STR_TO_DATE('{$options['start']}', '%Y%m%d %T') AND STR_TO_DATE('{$options['end']}', '%Y%m%d %T')
					AND t.term_id = {$options['category']}
					AND t.term_id = tt.term_id
					AND tt.term_taxonomy_id = tr.term_taxonomy_id
					AND tr.object_id = l.link_id " . $options['order'];
			
			// execute the query and get the results		
			$citations = $wpdb->get_results($sql, ARRAY_A);
			
			// check to see if something was returned
			if(is_array($citations)) {
				// process each returned citation in turn
				foreach($citations as $citation) {
					// build the link
					$list .= '<li><a href="' . $citation['link_url'] . '" title="Link to cited item">' . $citation['link_name'] . '</a>';
					
					// add a description if present
					if($citation['link_description'] != '') {
						$list .= '<br/>' . $citation['link_description'];
					}
					
					// finish this item
					$list .= '</li>';
				}
			} else {
				$list = '<p>No citations could be found using the supplied criteria</p>';
			}
			
			// add the promotional link
			if($promo_link = 'yes') {
				$list .= '<p class="citation_list_promo">This list generated by the <a href="http://techxplorer.com/projects/citation-aggregator/" title="">Citation Aggregator</a> plugin for <a href="http://wordpress.org" title="">WordPress</a></p>';
			}
			
			// return the list
			return $list;
			
		} // end function to generate a list of citations
		
		// function to add the RSS link to the blog header
		function add_rss_link() {
			print '<link rel="alternate" type="application/rss+xml" title="Citations Aggregated at - ' . get_bloginfo('name') . '" href="' . plugins_url('/citation-aggregator/citation-aggregator-rss.php') . '" />';
		}

	
	} // end class definition for citation_aggregator
	
} // end class definition

//Initialise the class
if(class_exists('citation_aggregator')) {
	$cit_aggie = new citation_aggregator();
}

// Function to print the admin options panel
if(!function_exists('citation_aggregator_options')) {
	function citation_aggregator_options() {
		global $cit_aggie;
		
		if (!isset($cit_aggie)) {
			// class instance is missing so just return
			return;
		}
		
		if(function_exists('add_options_page')) {
			$page = add_options_page('Citation Aggregator', 'Citation Aggregator', 9, basename(__FILE__), array(&$cit_aggie, 'display_admin_page'));
			add_action("admin_head-$page", array(&$cit_aggie, 'admin_page_header'));
		}
	}
}

// Add an hourly schedule to run updates
if(!function_exists('citation_aggregator_update')) {
	function citation_aggregator_update() {

		if(class_exists('citation_aggregator')) {
			$aggregator = new citation_aggregator();
		}
		
		// update the citations
		$aggregator->update_citations();
	
	}
}

// function to activate the plugin
if(!function_exists('citation_aggregator_activate')) {
	function citation_aggregator_activate() {
	
		// schedule the update event	
		wp_schedule_event(time(), 'hourly', 'citation_aggregator_schedule_hook');
	}
}

// function to deactivate the plugin
if(!function_exists('citation_aggregator_deactivate')) {
	function citation_aggregator_deactivate() {
	
		// schedule the update event	
		wp_clear_scheduled_hook('citation_aggregator_schedule_hook');
	}
}

// function to register our options
if(!function_exists('citation_aggregator_register_options')) {
	function citation_aggregator_register_options() {
	
		global $cit_aggie;
		
		if(!isset($cit_aggie)) {
			$cit_aggie = new citation_aggregator();
		}
		
		$option_prefix = $cit_aggie->admin_opt_name;
		
		register_setting($option_prefix . '-options', $option_prefix . '_email_address', 'sanitize_email');
		register_setting($option_prefix . '-options', $option_prefix . '_enable_delicious', 'citation_aggregator_options_filter');
		register_setting($option_prefix . '-options', $option_prefix . '_delicious_tag', 'wp_filter_nohtml_kses');
		register_setting($option_prefix . '-options', $option_prefix . '_delicious_users', 'citation_aggregator_sanitize_user_list');
		register_setting($option_prefix . '-options', $option_prefix . '_enable_connotea', 'citation_aggregator_options_filter');
		register_setting($option_prefix . '-options', $option_prefix . '_connotea_tag', 'wp_filter_nohtml_kses');
		register_setting($option_prefix . '-options', $option_prefix . '_connotea_users', 'citation_aggregator_sanitize_user_list');
		register_setting($option_prefix . '-options', $option_prefix . '_default_category', 'intval');
		register_setting($option_prefix . '-options', $option_prefix . '_delicious_category', 'intval');
		register_setting($option_prefix . '-options', $option_prefix . '_connotea_category', 'intval');
		register_setting($option_prefix . '-options', $option_prefix . '_promo_link', 'citation_aggregator_options_filter');
		register_setting($option_prefix . '-options', $option_prefix . '_enable_google_reader', 'citation_aggregator_options_filter');
		register_setting($option_prefix . '-options', $option_prefix . '_google_reader_source_category', 'intval');
		register_setting($option_prefix . '-options', $option_prefix . '_google_reader_category', 'intval');			
	
	}
} // end function to register options

// function to filter yes/no options
if(!function_exists('citation_aggregator_options_filter')) {
	// option values should be only yes / no
	function citation_aggregator_options_filter($value) {
		if(strtolower($value) == 'yes') {
			return 'yes';
		} else {
			return 'no';
		}
	}
} 

// function to filter list of allowed usernames
if(!function_exists('citation_aggregator_sanitize_user_list')) {
	function citation_aggregator_sanitize_user_list($value) {
		$value = wp_filter_nohtml_kses($value);
		
		$users = explode(',', $value);
		
		$value = '';
		
		foreach($users as $user) {
			$value .= strtolower(trim($user)) . ',';
		}
		
		// tidy up the value
		$value = trim($value, ',');
		
		return $value;
	}
}
		

// Associate with appropriate actions and filters
if(isset($cit_aggie)) {

	// Actions
	// activation / deactivation hooks	
	register_activation_hook(__FILE__, 'citation_aggregator_activate');
	register_deactivation_hook(__FILE__, 'citation_aggregator_deactivate');
	
	// add schedule function
	add_action('citation_aggregator_schedule_hook', 'citation_aggregator_update');
	
	// admin page action
	add_action('admin_menu', 'citation_aggregator_options');
	
	// admin init function - register our options
	add_action('admin_init', 'citation_aggregator_register_options');
	
	// header page action
	add_action('wp_head', array(&$cit_aggie, 'add_rss_link'));
	
	// Filters
	// add filter for this short code
	if(function_exists('add_shortcode')) {
		add_shortcode('citation-list', array(&$cit_aggie, 'generate_list')); 
	}	
}

?>
