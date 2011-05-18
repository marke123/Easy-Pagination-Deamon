<?php
/*
Plugin Name:	Easy Pagination Deamon
Plugin URI:		http://wordpress.org/extend/plugins/
Description:	Offers the <code>oxo_pagination( $args );</code> template tag for a semantically correct, 
				seo-ready (well performing) pagination.
Author:			Franz Josef Kaiser
Author URI: 	http://say-hello-code.com
Version:		0.3.
License:		extended MIT/Expat license

(c) Copyright 2010-2011 - Franz Josef Kaiser
*/

// Secure: doesn't allow to load this file directly
if( ! class_exists('WP') ) 
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
function oxo_pagination( $args ) 
{
	return new oxoPagination( $args );
}



if ( ! class_exists('oxoPagination') ) 
{

class oxoPagination 
{
	/**
	 * Plugin root path
	 * @var unknown_type
	 */
	protected $path;

	/**
	 * Plugin version
	 * @var integer
	 */
	protected $version;

	/**
	 * Default arguments
	 * @var array
	 */
	protected $defaults = array( 
		 'classes'	=> ''
		,'range'	=> 5
		,'wrapper'			=> 'li' // element in which we wrap the link 
		,'highlight'		=> 'current' // class for the current page
		,'before'			=> ''
		,'after'			=> ''
		,'link_before'		=> ''
		,'link_after'		=> ''
		,'next_or_number'	=> 'number'
		,'nextpagelink'		=> 'Next'
		,'previouspagelink'	=> 'Prev'
		,'pagelink'			=> '%'
	);

	/**
	 * Input arguments
	 * @var array
	 */
	protected $args;

	/**
	 * Constant for the texdomain (i18n)
	 */
	const LANG = 'pagination_textdomain';


	public function __construct( $args ) 
	{
		// Set root path variable
		$this->path = $this->get_root_path();

		// Set version
		# $this->version = get_plugin_data();

		# >>>> defaults & arguments

			// apply the "wp_list_pages_args" wordpress native filter also to the custom "page_links" function.
			$this->defaults = apply_filters( 'wp_link_pages_args', $this->defaults );

			// merge defaults with input arguments
			$this->args = wp_parse_args( $args, $this->defaults );

		# <<<< defaults & arguments

		// Help placing the template tag at the right position (inside/outside loop).
		$this->help();

		// Css
		$this->register_styles();
		// Load stylesheet into the 'wp_head()' hook of your theme.
		add_action( 'wp_head', array( &$this, 'print_styles' ) );

		// RENDER
		$this->render( $this->args );
	}


	/**
	 * Plugin root
	 */
	function get_root_path() 
	{
		$path = trailingslashit( WP_PLUGIN_URL.'/'.str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) );
		$path = apply_filters( 'config_pagination_url', $path );

		return $this->path = $path;
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
			// Search for a stylesheet
			$name = 'pagination.css';
			if ( file_exists( STYLESHEETPATH.$name ) )
			{
				$file = STYLESHEETPATH.$name;
			}
			elseif ( file_exists( TEMPLATEPATH.$name ) )
			{
				$file = TEMPLATEPATH.$name;
			}
			elseif ( file_exists( $this->path.$name ) )
			{
				$file = $this->path.$name;
			}
			else 
			{
				return;
			}

			// try to avoid caching stylesheets if they changed
			$version = filemtime( $file );
			// If no change was found, use the plugins version number
			if ( ! $version )
				$version = $this->version;

			wp_register_style( 'pagination', $file, false, $version, 'screen' );
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
		/*
		if ( is_single() && ! in_the_loop() )
		{
			$output = sprintf( __( 'You should place the %1$s template tag inside the loop on singular templates.', self::LANG ), __CLASS__ );
		}
		else

		_doing_it_wrong( 'Class: '.__CLASS__.' function: '.__FUNCTION__, 'error message' );
		*/
		if ( ! is_single() && in_the_loop() )
		{
			$output = sprintf( __( 'You shall not place the %1$s template tag inside the loop on list/archives/search/etc templates.', self::LANG ), __CLASS__ );
		}

		if ( ! isset( $output ) )
			return;

		// error
		$message = new WP_Error( 
			 __CLASS__
			,$output 
		);

		// render
		if ( is_wp_error( $message ) ) 
		{ 
		?>
			<div id="oxo-error-<?php echo $message->get_error_code(); ?>" class="error oxo-error prepend-top clear">
				<strong>
					<?php echo $message->get_error_message(); ?>
				</strong>
			</div>
		<?php 
		}
	}


	/**
	 * Replacement for the native wp_link_page() function
	 * 
	 * @author original version: Thomas Scholz (toscho.de)
	 * @link http://wordpress.stackexchange.com/questions/14406/how-to-style-current-page-number-wp-link-pages/14460#14460
	 * 
	 * @param (mixed) array $args
	 */
	public function page_links( $args )
	{
		global $page, $numpages, $multipage, $more, $pagenow;

		$args = wp_parse_args( $args, $this->defaults );
		extract( $args, EXTR_SKIP );

		if ( ! $multipage )
			return;

		# >>>> css classes
		$start_classes = isset( $classes ) ? ' class="' : '';
		$end_classes = isset( $classes ) ? '"' : '';
		# <<<< css classes

		$output = $before;
		switch ( $next_or_number ) 
		{
			case 'next' :
				if ( $more ) 
				{
					# >>>> [prev]
					$i = $page - 1;
					if ( $i && $more ) 
					{
						# >>>> <li class="custom-class">
						$output .= '<'.$wrapper.$start_classes.$classes.$end_classes.'>';
							$output .= _wp_link_page( $i ).$link_before.$previouspagelink.$link_after.'</a>';
						$output .= '</'.$wrapper.'>';
						# <<<< </li>
					}
					# <<<< [prev]

					# >>>> [next]
					$i = $page + 1;
					if ( $i <= $numpages && $more ) 
					{
						# >>>> <li class="custom-class">
						$output .= '<'.$wrapper.$start_classes.$classes.$end_classes.'>';
							$output .= _wp_link_page( $i ).$link_before.$nextpagelink.$link_after.'</a>';
						$output .= '</'.$wrapper.'>';
						# <<<< </li>
					}
					# <<<< [next]
				}
				break;

			case 'number' :
				for ( $i = 1; $i < ( $numpages + 1 ); $i++ )
				{
					$classes = isset( $this->args['classes'] ) ? $this->args['classes'] : '';
					if ( $page === $i && isset( $this->args['highlight'] ) )
						 $classes .= ' '.$this->args['highlight'];

					# >>>> <li class="current custom-class">
					$output .= '<'.$wrapper.$start_classes.$classes.$end_classes.'>';

						# >>>> [1] [2] [3] [4]
						$j = str_replace( '%', $i, $pagelink );

						if ( $page !== $i || ( ! $more && $page == true ) )
						{
							$output .= _wp_link_page( $i ).$link_before.$j.$link_after.'</a>';
						}

						// the current page must not have a link to itself
						else
						{
							$output .= $link_before.'<span>'.$j.'</span>'.$link_after;
						}
						# <<<< [next]/[prev] | [1] [2] [3] [4]

					$output .= '</'.$wrapper.'>';
					# <<<< </li>
				}
				break;

			default :
				// in case you can imagine some funky way to paginate
				do_action( 'hook_pagination_next_or_number', $page_links, $classes );
				break;
		}
		$output .= $after;

		return $output;
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
	function render( $args = array( 'classes', 'range' ) ) 
	{
		// $paged - number of the current page
		global $wp_query, $paged, $numpages;

		extract( $args, EXTR_SKIP );

		# ============================================================== #

		// How much pages do we have?
		$max_page = (int) $wp_query->max_num_pages;

		// We need the pagination only if there is more than 1 page
		if ( $max_page > (int) 1 )
			$paged = ! $wp_query->query_vars['paged'] ? (int) 1 : $wp_query->query_vars['paged'];

		$classes = isset( $classes ) ? ' '.$classes : '';
		?>

		<ul class="pagination">

			<?php 
			// *******************************************************
			// To the first / previous page
			// On the first page, don't put the first / prev page link
			// *******************************************************
			if ( $paged !== (int) 1 && $paged !== (int) 0 && ! is_page() ) 
			{
				?>
				<li class="pagination-first <?php echo $classes; ?>">
					<?php
					$first_post_link = get_pagenum_link( 1 ); 
					?>
					<a href=<?php echo $first_post_link; ?> rel="first">
						<?php _e( 'First', self::LANG ); ?>
					</a>
				</li>

				<li class="pagination-prev <?php echo $classes; ?>">
					<?php 
						# let's use the native fn instead of the previous_/next_posts_link() alias
						# get_adjacent_post( $in_same_cat = false, $excluded_categories = '', $previous = true )

						// Get the previous post object
						$in_same_cat	= is_category() || is_tag() || is_tax() ? true : false;
						$prev_post_obj	= get_adjacent_post( $in_same_cat );
						// Get the previous posts ID
						$prev_post_ID	= isset( $prev_post_obj->ID ) ? $prev_post_obj->ID : '';

						// Set title & link for the previous post
						if ( is_single() )
						{
							if ( isset( $prev_post_obj ) )
							{
								$prev_post_link		= get_permalink( $prev_post_ID );
								$prev_post_title	= '&laquo;'; // equals "»"
								# $prev_post_title	= __( 'Prev', self::LANG ).': '.mb_substr( $prev_post_obj->post_title, 0, 6 );
							}
						}
						else 
						{
							$prev_post_link		= home_url().'/?s='.get_search_query().'&paged='.( $paged-1 );
							$prev_post_title	= '&laquo;'; // equals "»"
						}
						?>
					<!-- Render Link to the previous post -->
					<a href="<?php echo $prev_post_link; ?>" rel="prev">
						<?php echo $prev_post_title; ?>
					</a>
					<?php # previous_posts_link(' &laquo; '); // « ?>
				</li>
				<?php 
			}

			// Render, as long as there are more posts found, than we display per page
			if ( ! $wp_query->query_vars['posts_per_page'] < $wp_query->found_posts )
			{

				// *******************************************************
				// We need the sliding effect only if there are more pages than is the sliding range
				// *******************************************************
				if ( $max_page > $range ) 
				{
					// When closer to the beginning
					if ( $paged < $range ) 
					{
						for ( $i = 1; $i <= ( $range+1 ); $i++ ) 
						{ 
							$current = '';
							// Apply the css class "current" if it's the current post
							if ( $paged === (int) $i )
							{
								$current = ' current';
								# echo _wp_link_page( $i ).'</a>';
							}
							?>
							<li class="pagination-num<?php echo $classes.$current; ?>">
								<!-- Render page number Link -->
								<a href="<?php echo get_pagenum_link( $i ); ?>">
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
							$current = '';
							// Apply the css class "current" if it's the current post
							$current = ( $paged === (int) $i ) ? ' current' : '';

							?>
							<li class="pagination-num<?php echo $classes.$current; ?>">
								<!-- Render page number Link -->
								<a href="<?php echo get_pagenum_link( $i ); ?>">
									<?php echo $i; ?>
								</a>
							</li>
							<?php 
						}
					}
					// Somewhere in the middle
					elseif ( $paged >= $range && $paged < ( $max_page - ceil( $range/2 ) ) ) 
					{
						for ( $i = ( $paged - ceil( $range/2 ) ); $i <= ( $paged + ceil( $range/2 ) ); $i++ ) 
						{
							$current = '';
							// Apply the css class "current" if it's the current post
							$current = ( $paged === (int) $i ) ? ' current' : '';

							?>
							<li class="pagination-num<?php echo $classes.$current; ?>">
								<!-- Render page number Link -->
								<a href="<?php echo get_pagenum_link( $i ); ?>">
									<?php echo $i; ?>
								</a>
							</li>
							<?php 
						}
					}
				}
				// Less pages than the range, no sliding effect needed
				else 
				{
					for ( $i = 1; $i <= $max_page; $i++ ) 
					{
						$current = '';
						// Apply the css class "current" if it's the current post
						$current = ( $paged === (int) $i ) ? ' current' : '';

						?>
						<li class="pagination-num<?php echo $classes.$current; ?>">
							<!-- Render page number Link -->
							<a href="<?php echo get_pagenum_link( $i ); ?>">
								<?php echo $i; ?>
							</a>
						</li>
						<?php 
					}
				} // endif;
			} // endif; there are more posts found, than we display per page 


			// *******************************************************
			// to the last / next page of a paged post
			// This only get's used on posts/pages that use the <!--nextpage--> quicktag
			// *******************************************************
			if ( is_singular() && $numpages > 1 )
			{
				$echo = false;
				echo $this->page_links( $this->args );
			}


			// *******************************************************
			// to the last / next page
			// On the last page: don't show the link to the last/next page
			// *******************************************************
			if ( $paged !== (int) 0 && $paged !== (int) $max_page && $max_page !== (int) 0 && ! is_page() )
			{ 
				?>
				<li class="pagination-next<?php echo $classes; ?>">
					<?php 
					# let's use the native fn instead of the previous_/next_posts_link() alias
					# get_adjacent_post( $in_same_cat = false, $excluded_categories = '', $previous = true )

					// Get the next post object
					$in_same_cat	= is_category() || is_tag() || is_tax() ? true : false;
					$next_post_obj	= get_adjacent_post( $in_same_cat, '', false );
					// Get the next posts ID
					$next_post_ID	= isset( $next_post_obj->ID ) ? $next_post_obj->ID : '';

					// Set title & link for the next post
					if ( is_single() )
					{
						if ( isset( $next_post_obj ) )
						{
							# $next_post_link = get_next_posts_link();
							# $next_post_paged_link = get_next_posts_page_link();
							$next_post_link		= get_permalink( $next_post_ID );
							$next_post_title	= '&raquo;'; // equals "»"
							# $next_post_title	= __( 'Next', self::LANG ).mb_substr( $next_post_obj->post_title, 0, 6 );
						}
					}
					else 
					{
						$next_post_link		= home_url().'/?s='.get_search_query().'&paged='.( $paged+1 );
						$next_post_title	= '&raquo;'; // equals "»"
					}

					if ( isset ( $next_post_obj ) )
					{
						?>
						<!-- Render Link to the next post -->
						<a href="<?php echo $next_post_link; ?>" rel="next">
							<?php echo $next_post_title; ?>
						</a>
						<?php
					} 
					else 
					{
						next_posts_link(' &raquo; '); // »
					} 
					?>
				</li>

				<li class="pagination-last<?php echo $classes; ?>">
					<?php
					$last_post_link = get_pagenum_link( $max_page ); 
					?>
					<!-- Render Link to the last post -->
					<a href="<?php echo $last_post_link; ?>" rel="last">
						<?php _e( 'Last', self::LANG ); ?>
					</a>
				</li>
				<?php 
			} // endif;
		?>
		</ul>
		<?php
	}

} // END Class oxoPagination

} // endif;
?>