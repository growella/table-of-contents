<?php
/**
 * Core plugin functionality.
 *
 * @package Growella\TableOfContents
 * @author  Growella
 */

namespace Growella\TableOfContents\Core;

use Growella\TableOfContents\Headings as Headings;

/**
 * Render the Table of Contents.
 *
 * @param array $atts {
 *   Shortcode attributes. All values are optional.
 *
 *   @type int    $depth How deeply nested the resulting table of contents should go, with -1 being
 *                       all headings with no nesting, 0 being all top-level headings with no
 *                       nesting, 1 being all top-level headings and one-level of sub-headings,
 *                       etc. This argument is not currently being used but is planned for future
 *                       versions. Default is -1.
 *   @type string $tags  A comma-separated list of HTML elements that should be recognized as
 *                       headings. Default is 'h1,h2,h3'.
 *   @type string $title The title that should be rendered at the top of the generated table of
 *                       contents. Default is "Table of Contents". Passing a false-y value will
 *                       prevent this header from being included.
 * }
 * @return string The rendered table of contents.
 */
function render_shortcode( $atts ) {
	$defaults = array(
		'depth' => -1,
		'tags'  => 'h1,h2,h3',
		'title' => _x( 'Table of Contents', 'default title for Growella Table of Contents', 'growella-table-of-contents' ),
	);

	/**
	 * Modify default settings for the Growella Table of Contents [toc] shortcode.
	 *
	 * @param array $defaults Default shortcode attributes.
	 *
	 * @see Growella\TableOfContents\Core\render_shortcode()
	 */
	$defaults = apply_filters( 'growella_table_of_contents_shortcode_defaults', $defaults );

	// Merge the defaults in with user-supplied values.
	$atts = shortcode_atts( $defaults, $atts, 'toc' );

	// Parse the post content to get IDs.
	$content = new \DOMDocument();
	$content->loadHTML( Headings\inject_heading_ids( get_the_content(), $atts ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	// We'll parse the document using DOMXpath to get any of the whitelisted tags with ID attributes.
	$xpath = new \DOMXpath( $content );
	$query = array();

	foreach ( explode( ',', (string) $atts['tags'] ) as $tag ) {
		$query[] = sprintf( '//%s[@id]', $tag );
	}

	$headings = $xpath->query( implode( '|', $query ) );

	// Return early if we don't have any headings.
	if ( 0 === $headings->length ) {
		return;
	}

	// Assemble our output.
	$links = build_link_list( $headings );

	// No links mean we have no reason to build a table of contents.
	if ( empty( $links ) ) {
		return;
	}

	$output  = '<nav class="growella-table-of-contents">';

	// Begin with the heading.
	if ( $atts['title'] ) {
		$output .= sprintf( '<h2>%s</h2>', esc_html( $atts['title'] ) );
	}

	// Build the list of links.
	$output .= '<ul>';

	foreach ( $links as $link ) {
		$output .= '<li>' . $link . '</li>';
	}

	$output .= '</ul>';
	$output .= '</nav>';

	/**
	 * Filter the Growella Table of Contents just before returning the rendered shortcode.
	 *
	 * @param string $output The rendered Table of Contents.
	 * @param array  $atts   The shortcode attributes used to build the Table of Contents.
	 * @param array  $links  The links used to build the Table of Contents.
	 */
	$output = apply_filters( 'growella_table_of_contents_render_shortcode', $output, $atts, $links );

	return $output;
}
add_shortcode( 'toc', __NAMESPACE__ . '\render_shortcode' );

/**
 * Given a DOMNodeList, assemble an array of links to the IDs of each matched element.
 *
 * @param DOMNodeList $list A DOMNodeList to iterate through.
 * @return array A flat array of links.
 */
function build_link_list( $list ) {
	$links = array();

	foreach ( $list as $item ) {
		$links[] = sprintf(
			'<a href="#%s">%s</a>',
			esc_attr( $item->getAttribute( 'id' ) ),
			$item->nodeValue
		);
	}

	return $links;
}
