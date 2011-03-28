<?php
/*
Plugin Name:	Easy Pagination Deamon
Plugin URI:		http://wordpress.org/extend/plugins/
Description:	Offers the <code>get_pagination_links( $range );</code> template tag for a semantically correct, seo-ready (well performing) pagination.
Author:			Franz Josef Kaiser
Author URI: 	http://say-hello-code.com
Version:		0.1.3.3
License:		GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain:	pagination_deamon_lang

	(c) Copyright 20010-2011 - Franz Josef Kaiser

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

// Secure: doesn't allow to load this file directly
if( !class_exists('WP') ) 
{
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}


/**
 * TEMPLATE TAG
 * 
 * A wrapper/template tag for the pagination builder inside the class.
 * Write a call for this function with a "range" 
 * inside your template to display the pagination.
 * 
 * @param integer $range
 */
function get_pagination_links( $range ) 
{
	new PaginationDeamon( $range );
}



if ( !class_exists('PaginationDeamon') ) 
{

class PaginationDeamon 
{
	protected $path;

	protected $version;

	/**
	 * Range
	 * @var integer
	 */
	protected $range;

	/**
	 * Constant for the texdomain (i18n)
	 */
	const LANG = 'pagination_textdomain';


	public function __construct( $range ) 
	{
		// Set root path variable
		$this->path = $this->get_root_path();

		// Set version
		$this->version = get_plugin_data();

		// Set displayed range of page nav links taken from the input set at the template tag.
		$this->range = $range;

		// Help placing the template tag at the right position (inside/outside loop).
		$this->help();

		// Css
		$this->register_styles();
		// Load stylesheet into the 'wp_head()' hook of your theme.
		add_action( 'wp_head', array( &$this, 'print_styles' ) );

		// render
		$this->render( $this->range );
	}

	/**
	 * Plugin root
	 */
	function get_root_path() 
	{
		return $this->path = trailingslashit( WP_PLUGIN_URL.'/'.str_replace( basename( __FILE__), "", plugin_basename( __FILE__ ) ) );
	}

	/**
	 * Return plugin comment data
	 * 
	 * @since 0.1.3.3
	 * 
	 * @param $value string | default = 'Version' (Other input values: Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title)
	 * 
	 * @return string
	 */
	private function get_plugin_data( $value = 'Version' )
	{	
		$plugin_data = get_plugin_data( __FILE__ );

		return $plugin_data[ $value ];
	}

	/**
	 * Register styles
	 */
	function register_styles() 
	{
		if ( ! is_admin() )
		{
			wp_register_style( 'pagination', $this->path.'pagination.css', false, self::VERSION, 'screen' );
		}
	}

	/**
	 * Print styles
	 */
	function print_styles() 
	{
		if ( ! is_admin() )
		{
			wp_enqueue_style( 'pagination' );
		}
	}

	/**
	 * Help with placing the template tag right
	 */
	function help() 
	{
		if ( is_single() && ! in_the_loop() )
		{
		?>
			<div class="pagination-error">
				<strong><?php _e( 'You should place the pagination template tag inside the loop on singular templates.', self::LANG ); ?></strong>
			</div>
		<?php	
		}
		if ( ! is_single() && in_the_loop() )
		{
		?>
			<div class="pagination-error">
				<strong><?php _e( 'You shall not place the pagination template tag inside the loop on list/archives/search/etc templates.', self::LANG ); ?></strong>
			</div>
		<?php 
		}
	}

	/**
	 * Wordpress pagination for archives/search/etc.
	 * 
	 * Semantically correct pagination inside an unordered list
	 * 
	 * Displays: [First] [<<] [1] [2] [3] [4] [>>] [Last]
	 *	+ First/Last only appears if not on first/last page
	 *	+ Shows next/previous links [<<]/[>>]
	 * 
	 * Accepts a range attribute (default = 5) to adjust the number
	 * of direct page links that link to the pages above/below the current one.
	 * 
	 * @param (integer) $range
	 */
	function render( $range = 5 ) 
	{
		// $paged - number of the current page
		global $paged, $wp_query;

		// How much pages do we have?
		if ( ! $max_page )
		{
			$max_page = $wp_query->max_num_pages;
		}

		// We need the pagination only if there is more than 1 page
		if ( $max_page > 1 )
		{
			if ( ! $paged )
			{ 
				$paged = 1;
			}
		}

		?>
		<ul class="pagination">
		<?php 

			// *******************************************************
			// To the previous / first page
			// On the first page, don't put the first / prev page link
			// *******************************************************
			if ( $paged != 1 ) 
			{
				?>
				<li>
					<a href=<?php echo get_pagenum_link( 1 ); ?>>
						<?php _e( 'First', self::LANG ); ?>
					</a>
				</li>

				<li class="page-num page-num-prev">
					<?php 
					# let's use the native fn instead of the previous_/next_posts_link() alias
					# get_adjacent_post( $in_same_cat = false, $excluded_categories = '', $previous = true )

					// Get the previous post object
					$prev_post_obj	= get_adjacent_post();
					// Get the previous posts ID
					$prev_post_ID	= $next_post_obj->ID;

					// Set title & link for the previous post
					if ( is_single() )
					{
						$prev_post_link		= get_permalink( $prev_post_ID );
						$prev_post_title	= __( 'Next', self::LANG ).': '.$next_post_obj->post_title;
					}
					else 
					{
						$prev_post_link		= get_bloginfo( 'url' ).'/?s='.get_search_query().'&paged='.( $paged-1 );
						$prev_post_title	= '&raquo;'; // equals "»"
					}

					?>
					<!-- Render Link to the previous post -->
					<a href="<?php echo $prev_post_link; ?>">
						<?php echo $prev_post_title; ?>
					</a>
					<?php # previous_posts_link(' &laquo; '); // « ?>
				</li>
				<?php 
			}

			// *******************************************************
			// We need the sliding effect only if there are more pages than is the sliding range
			// *******************************************************
			if ( $max_page > $range ) 
			{
				// When closer to the beginning
				if ( $paged < $range ) 
				{
					for ( $i = 1; $i <= ( $range + 1 ); $i++ ) 
					{
						// Apply the css class "current" if it's the current post
						$class = ( $i == $paged ) ? 'current' : '';

						?>
						<li class="page-num">
							<!-- Render page number Link -->
							<a class="paged-num-link <?php echo $class; ?>" href="<?php echo get_pagenum_link( $i ); ?>">
								<?php echo $i; ?>
							</a>
						</li>
						<?php 
					}
				}
				// When closer to the end
				elseif ( $paged >= ( $max_page - ceil ( $range/2 ) ) ) 
				{
					for ( $i = $max_page - $range; $i <= $max_page; $i++ )
					{
						// Apply the css class "current" if it's the current post
						$class = ( $i == $paged ) ? 'current' : '';

						?>
						<li class="page-num">
							<!-- Render page number Link -->
							<a class="paged-num-link <?php echo $class; ?>" href="<?php echo get_pagenum_link( $i ); ?>">
								<?php echo $i; ?>
							</a>
						</li>
						<?php 
					}
				}
			}
			// Somewhere in the middle
			elseif ( $paged >= $range && $paged < ( $max_page - ceil( $range/2 ) ) ) 
			{
				for ( $i = ( $paged - ceil( $range/2 ) ); $i <= ( $paged + ceil( $range/2 ) ); $i++ ) 
				{
					// Apply the css class "current" if it's the current post
					$class = ( $i == $paged ) ? 'current' : '';

					?>
					<li class="page-num">
						<!-- Render page number Link -->
						<a class="paged-num-link <?php echo $class; ?>" href="<?php echo get_pagenum_link( $i ); ?>">
							<?php echo $i; ?>
						</a>
					</li>
					<?php 
				}
			}
			// Less pages than the range, no sliding effect needed
			else 
			{
				for ( $i = 1; $i <= $max_page; $i++ ) 
				{
					// Apply the css class "current" if it's the current post
					$class = ( $i == $paged ) ? 'current' : '';

					?>
					<li class="page-num">
						<!-- Render page number Link -->
						<a class="paged-num-link <?php echo $class; ?>" href="<?php echo get_pagenum_link( $i ); ?>">
							<?php echo $i; ?>
						</a>
					</li>
					<?php 
				}
			} // endif;

			// *******************************************************
			// to the last / next page
			// On the last page, don't put the last / next page link
			// *******************************************************
			if ( $paged != $max_page ) 
			{
			?>
				<li class="page-num page-num-next">
			<?php 
					# let's use the native fn instead of the previous_/next_posts_link() alias
					# get_adjacent_post( $in_same_cat = false, $excluded_categories = '', $previous = true )

					// Get the next post object
					$next_post_obj	= get_adjacent_post( false, '', false );
					// Get the next posts ID
					$next_post_ID	= $next_post_obj->ID;

					// Set title & link for the next post
					if ( is_single() )
					{
						$next_post_link		= get_permalink( $next_post_ID );
						$next_post_title	= __( 'Next', self::LANG ).': '.$next_post_obj->post_title;
					}
					else 
					{
						$next_post_link		= get_bloginfo( 'url' ).'/?s='.get_search_query().'&paged='.( $paged+1 );
						$next_post_title	= '&raquo;'; // equals "»"
					}

					?>
					<!-- Render Link to the next post -->
					<a href="<?php echo $next_post_link; ?>">
						<?php echo $next_post_title; ?>
					</a>
					<?php # next_posts_link(' &raquo; '); // » ?>
				</li>

				<li>
					<!-- Render Link to the last post -->
					<a href=<?php echo get_pagenum_link( $max_page ); ?>>
						<?php _e( 'Last', self::LANG ); ?>
					</a>
				</li>
				<?php 
			} // endif;
	
		?>
		</ul>
		<?php
	}

} // END Class PaginationDeamon

} // endif;
?>