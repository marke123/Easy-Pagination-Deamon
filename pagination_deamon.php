<?php
/*
Plugin Name:	Easy Pagination Deamon
Plugin URI:		http://wordpress.org/extend/plugins/stats/
Description:	Offers the get_pagination_links($range) template tag for a sematically correct pagination.
Author:			Franz Josef Kaiser
Author URI: 	http://say-hello-code.com
Version:		0.1.3
License:		GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain:	pagination_deamon_lang

Copyright 20010-2011 by Franz Josef Kaiser

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
*/

// Secure: don't load this file directly
if( !class_exists('WP') ) :
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
endif;

!defined('PAGE_LANG') ? define( 'PAGE_LANG', 'pagination_deamon_lang' ) : wp_die('The constant PAGE_LANG is already defined.');
!defined('PAGE_VERSION') ? define( 'PAGE_VERSION', 0.1.2 ) : wp_die('The constant PAGE_VERSION is already defined.');
!defined('PAGE_PATH') ? define( 'PAGE_PATH', trailingslashit(WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))) ) : wp_die('The constant PAGE_PATH is already defined.');

	// Register styles
	if ( !is_admin() )
		wp_register_style( 'pagination', PAGE_PATH.'pagination.css', false, PAGE_VERSION, 'screen' );

	if ( !function_exists('print_pagination_styles') ) :
	/**
	 * Print styles
	 */
	function print_pagination_styles() {
		if ( !is_admin() )
			wp_print_styles('pagination');
	}
	endif;

	// print the pagination styles outside admin UI pages
	if ( !is_admin() )
		print_pagination_styles();

	if ( !function_exists('get_pagination_links') ) :
	/**
	 * Wordpress pagination for archives/search/etc.
	 * 
	 * Semantically correct pagination inside an unordered list
	 * 
	 * Displays: First « 1 2 3 4 » Last
	 * First/Last only appears if not on first/last page
	 * Shows next/previous links «/»
	 * Accepts a range attribute (default = 5) to adjust the number
	 * of direct page links that link to the pages above/below the current one.
	 * 
	 * @param (int) $range
	 */
	function get_pagination_links( $range = 5 ) {
		// $paged - number of the current page
		global $paged, $wp_query;
		// How much pages do we have?
		if ( !$max_page )
			$max_page = $wp_query->max_num_pages;
		// We need the pagination only if there is more than 1 page
		if ( $max_page > 1 )
			if ( !$paged ) $paged = 1;
	
		echo "\n".'<ul class="pagination">'."\n";
			// On the first page, don't put the First page link
			if ( $paged != 1 )
				echo '<li class="page-num page-num-first"><a href='.get_pagenum_link(1).'>'.__('First', PAGE_LANG).' </a></li>'."\n";
		
			// To the previous page
			echo '<li class="page-num page-num-prev">';
				# let's use the native fn instead of the previous_/next_posts_link() alias
				# get_adjacent_post( $in_same_cat = false, $excluded_categories = '', $previous = true )
				echo get_adjacent_post( false, '', true );
				# previous_posts_link(' &laquo; '); // «
			echo '</li>';
		
			// We need the sliding effect only if there are more pages than is the sliding range
			if ( $max_page > $range ) :
				// When closer to the beginning
				if ( $paged < $range ) :
					for ( $i = 1; $i <= ($range + 1); $i++ ) {
						$class = $i == $paged ? 'current' : '';
						echo '<li class="page-num"><a class="paged-num-link '.$class.'" href="'.get_pagenum_link($i).'"> '.$i.' </a></li>'."\n";
					}
				// When closer to the end
				elseif ( $paged >= ( $max_page - ceil($range/2)) ) :
					for ( $i = $max_page - $range; $i <= $max_page; $i++ ){
						$class = $i == $paged ? 'current' : '';
						echo '<li class="page-num"><a class="paged-num-link '.$class.'" href="'.get_pagenum_link($i).'"> '.$i.' </a></li>'."\n";
					}
				endif;
			// Somewhere in the middle
			elseif ( $paged >= $range && $paged < ( $max_page - ceil($range/2)) ) :
				for ( $i = ($paged - ceil($range/2)); $i <= ($paged + ceil($range/2)); $i++ ) {
						$class = $i == $paged ? 'current' : '';
					echo '<li class="page-num"><a class="paged-num-link '.$class.'" href="'.get_pagenum_link($i).'"> '.$i.' </a></li>'."\n";
				}
			// Less pages than the range, no sliding effect needed
			else :
				for ( $i = 1; $i <= $max_page; $i++ ) {
					$class = $i == $paged ? 'current' : '';
					echo '<li class="page-num"><a class="paged-num-link '.$class.'" href="'.get_pagenum_link($i).'"> '.$i.' </a></li>'."\n";
				}
			endif;
		
			// Next page
			echo '<li class="page-num page-num-next">';
				# let's use the native fn instead of the previous_/next_posts_link() alias
				# get_adjacent_post( $in_same_cat = false, $excluded_categories = '', $previous = true )
				echo get_adjacent_post( false, '', false );
				# next_posts_link(' &raquo; '); // »
			echo '</li>'."\n";
		
			// On the last page, don't put the Last page link
			if ( $paged != $max_page )
				echo '<li class="page-num page-num-last"><a href='.get_pagenum_link($max_page).'> '.__('Last', PAGE_LANG).'</a></li>'."\n";
	
		echo '</ul>'."\n";
	}
	endif;
?>