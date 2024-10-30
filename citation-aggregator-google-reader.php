<?php
/*
 * Google Reader feed reader - A component of the Citation Aggregator plugin for WordPress
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
 * citation_aggregator_google_reader class contains all of the code for parsing of a Google Reader feed
 */

if (!class_exists('citation_aggregator_google_reader')) {

	class citation_aggregator_google_reader {
	
		// Declare global variables for the class
		var $feed_data = array();
		var $version = '1.0';
		var $feed_type = 'google_reader';
	
		/**
		 * Function to parse the delicious feed
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
			if(get_option($cit_aggie->admin_opt_name . '_enable_google_reader') != 'yes') {
				return FALSE;
			}
			
			// if we get this far we should be parsing the feeds
			// get the list of Google Reader Feeds
			$source_category = get_option($cit_aggie->admin_opt_name . '_google_reader_source_category');
			
			if($source_category == '' || $source_category == FALSE) {
				// no category defined for source links so do nothing
				return FALSE;
			}
			
			// ensure the SimplePie Class is avaialable
			$cit_aggie->check_simple_pie();
			
			// get the list of sources
			$gr_sources = get_bookmarks('category=' . $source_category . '&hide_invisible=0');
			
			// loop through each of the bookmarks getting items as we go				
			foreach($gr_sources as $gr_source) {
				
				// attempt to parse the rss feed 
				// suppress any warnings
				@$rss = new SimplePie($gr_source->link_rss);
				
				if($rss != FALSE) {
					$this->feed_data[] = $rss;
				} else {
					return FALSE;
				}
			}
			
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
			
			// declare helper variables
			$saved_items = array();
			
			// loop through each of the saved feeds
			foreach($this->feed_data as $feed) {
				// loop through each of the items
				foreach($feed->get_items() as $item) {
					$saved_items[] = array('title' => $item->get_title(),
										   'link'  => $item->get_link(),
										   'description' => ''
										  );
				}
			}
			
			// return the flitered list of items
			return $saved_items;		
		
		} // end of the get_items function

	} // end class definition for citation_aggregator_delicious
	
} // end class definition

?>
