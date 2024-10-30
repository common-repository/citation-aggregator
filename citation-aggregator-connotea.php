<?php
/*
 * connotea feed reader - A component of the Citation Aggregator plugin for WordPress
 * Copyright (C) 2008 - 2009 Corey Wallis <techxplorer@gmail.com>
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

/**
 * citation_aggregator_connotea class contains all of the code for parsing of a del.icio.us feed
 */

if (!class_exists('citation_aggregator_connotea')) {

	class citation_aggregator_connotea {
	
		// Declare global variables for the class
		var $feed_data = '';
		var $version = '1.0';
		var $feed_type = 'connotea';
	
		/**
		 * Function to parse the connotea feed
		 * @param array $options array of options retrieved from database
		 * @return boolean 
		 */
		function parse_feed() {
		
			// scope the parent object
			global $cit_aggie;
		
			if(!isset($cit_aggie)) {
				return FALSE;
			}
			
			// see if we even should be checking a delicious feed
			if(get_option($cit_aggie->admin_opt_name . '_enable_connotea') != 'yes') {
				return FALSE;
			}
			
			// if we get this far we should be parsing the feed
			$feed_url = get_option($cit_aggie->admin_opt_name . '_connotea_tag');
			if($feed_url == FALSE || $feed_url == '') {
				return FALSE;
			}
			
			// Build the URL of the feed
			$feed_url = 'http://www.connotea.org/rss/tag/' . $feed_url;
			
			// check to ensure the SimplePie Class is avaialable
			$cit_aggie->check_simple_pie();
			
			// Attempt to parse the feed
			// Suppress warning about being unable to write to the cache
			@$rss = new SimplePie($feed_url);
			
			// Make sure something was returned
			if($rss == FALSE) {
				// fetch failed
				return FALSE;
			}
			
			// Store the feed data for later manipulation
			$this->feed_data = $rss;
			
			// Return true status
			return TRUE;		
		} // end parse feed function
		
		/**
		 * Function to return a list of items that we're interested in
		 * @param array $options array of options retrieved from database
		 * @return array
		 */
		function get_items($debug = FALSE) {
		
			// get the parent object
			global $cit_aggie;
		
			// Debug code			
			// Loop through each items
#			foreach($this->feed_data->get_items() as $item) {
#				$debug .= '##' . $item->get_title() . '##<br/>';
#				$debug .= '@@' . $item->get_description() . '@@<br/>';
#				$debug .= '!!' . $item->get_author()->get_name() . '!!<br/>';
#				$debug .= '$$' . $item->get_link() . '$$<br/>';
#			}
#			
#			return $debug;
			
			// start processing and not debugging
			
			// Build a list of users
			$users = get_option($cit_aggie->admin_opt_name . '_connotea_users');
			$users = explode(',',$users); // get the users stored so we can filter the list
				
			// Build a list of items
			$saved_items = array();
			foreach($this->feed_data->get_items() as $item) {
				if(in_array($item->get_author()->get_name(), $users)) {
				
					// Build the description
					$description = $item->get_description();
					$pos = strpos($description, 'Posted by ' . $item->get_author()->get_name());
					
					if ($pos !== FALSE && $pos > 1) {
						$description = substr($description, 0, $pos - 1);
					} else {
						$description = '';
					}
					
					if($uri == '') {
						$uri = 'Error: Unable to locate direct link to item';
					} 
					
					$saved_items[] = array('title' => $item->get_title(),
										   'link' => $item->get_link(),
										   'description' => $description,
										   'user' => $item->get_author()->get_name()
										  );
				}
			}
			
			// return the saved items
			return $saved_items;		
		
		} // end of the get_items function
		
	} // end class definition for citation_aggregator_connotea
	
} // end class definition

?>
