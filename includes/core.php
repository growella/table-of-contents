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
 * @param array $atts Shortcode attributes.
 * @return string The rendered table of contents.
 */
function render_shortcode( $atts ) {
	$atts  = shortcode_atts( array(
		'tags' => 'h1,h2,h3',
	), $atts, 'toc' );

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
	$output .= '<ul>';

	foreach ( $links as $link ) {
		$output .= '<li>' . $link . '</li>';
	}

	$output .= '</ul>';
	$output .= '</nav>';

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
