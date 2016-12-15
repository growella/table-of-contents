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
 *   @type string $class Extra HTML class names (space-separated) to apply to the table of
 *                       contents. Default is an empty string.
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
		'class' => '',
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
	$content  = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
	$content .= Headings\inject_heading_ids( get_the_content(), $atts );
	$content .= '</body></html>';
	$doc      = new \DOMDocument();
	$doc->loadHTML( $content, LIBXML_HTML_NODEFDTD );

	// We'll parse the document using DOMXpath to get any of the whitelisted tags with ID attributes.
	$xpath = new \DOMXpath( $doc );
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

	// Determine the classes to add to the output.
	$classes = array_filter( array_merge(
		array( 'growella-table-of-contents' ),
		is_array( $atts['class'] ) ? $atts['class'] : explode( ' ', (string) $atts['class'] )
	) );

	$output  = '<nav class="' . join( ' ', array_map( 'esc_attr', $classes ) ) . '">';

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
	 * Filter the Growella Table of Contents just before returning the rendered shortcode output.
	 *
	 * @param string $output The rendered table of contents.
	 * @param array  $atts   The shortcode attributes used to build the table of contents.
	 * @param array  $links  The links used to build the table of contents.
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

	foreach ( $list as $element ) {
		$anchor = $element->textContent;

		/**
		 * Filter the anchor text for a table of contents link before it's put into a link.
		 *
		 * @param string     $anchor  The anchor text to be used when building the link.
		 * @param DOMElement $element A DOMElement object representing the DOM node.
		 */
		$anchor = apply_filters( 'growella_table_of_contents_link_anchor_text', $anchor, $element );

		$links[] = sprintf(
			'<a href="#%s">%s</a>',
			esc_attr( $element->getAttribute( 'id' ) ),
			$anchor
		);
	}

	return $links;
}

/**
 * Split anchor text on the first newline to account for any parsing errors from libxml.
 *
 * @param string $anchor The anchor text to be used when building the link.
 * @return string The $anchor with anything not on the first line stripped.
 */
function strip_additional_lines( $anchor ) {

	// A \r, \n, or any combination of the two should be a good indication we have our one line.
	$regex = '/(.+?)[\r\n].*/s';

	return trim( preg_replace( $regex, '$1', $anchor ) );
}
add_filter( 'growella_table_of_contents_link_anchor_text', __NAMESPACE__ . '\strip_additional_lines', 1 );
