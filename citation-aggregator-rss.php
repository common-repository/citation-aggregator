<?php
/*
 * RSS feed of citations  - A component of the Citation Aggregator plugin for WordPress
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
 
// load the wordpress environment
require_once('../../../wp-load.php');

// scope the variables appropriately
global $wpdb;
global $cit_aggie;

// make sure everthing is as we expect
if(!isset($cit_aggie)) {
	print 'Unable to integrate with WordPress... Sorry';
	die;
}

// declare additional helper variables
$charset = get_bloginfo('charset');
$url = get_bloginfo('url');
$blog_name = get_bloginfo('name');
$link_category = get_option($cit_aggie->admin_opt_name . '_default_category');

// get the data
$citations = $wpdb->get_results("SELECT link_name, link_url, link_description
								 FROM {$wpdb->links}, {$wpdb->term_relationships}, {$wpdb->term_taxonomy}
								 WHERE link_id = {$wpdb->term_relationships}.object_id
								 AND {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id
								 AND {$wpdb->term_taxonomy}.term_id = {$link_category}
								 ORDER BY link_updated DESC
								 LIMIT 10
								", ARRAY_A);
								
//debug code
								
// output the header
header("Content-Type: text/xml; charset=$charset");
								
// output the start of the feed
print <<<_____EOS
<?xml version="1.0" encoding="{$charset}"?>
<rss version="2.0">
	<channel>
		<title>Citations from - {$blog_name}</title>
		<link>{$url}</link>
		<description>Most recent citations aggregated at - {$blog_name}</description>
_____EOS;

// output the ten items at most
foreach($citations as $citation) {


print <<<_____EOS
		<item>
			<title>{$citation['link_name']}</title>
			<link>{$citation['link_url']}</link>
			<description>{$citation['link_description']}</description>
			<guid>{$citation['link_url']}</guid>
		</item>
_____EOS;

}

// end the feed
print <<<_____EOS
	</channel>
</rss>
_____EOS;
?>
