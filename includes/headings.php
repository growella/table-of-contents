<?php
/**
 * Logic to automatically inject headings into post content.
 *
 * @package Growella\TableOfContents
 * @author  Growella
 */

namespace Growella\TableOfContents\Headings;

/**
 * Determine if the post content contains a [toc] shortcode and, if so, prepares the content.
 *
 * We only need to run inject_heading_ids() if the post contains a [toc] shortcode, so we can leave
 * the content alone if the shortcode isn't in use.
 *
 * @param string $content The post content.
 * @return string $content The unaltered post content.
 */
function maybe_prepare_content( $content ) {
	if ( has_shortcode( $content, 'toc' ) ) {

		// Important: this needs to be called before do_shortcode(), which is on priority 11.
		add_filter( 'the_content', __NAMESPACE__ . '\inject_heading_ids', 9 );
	}

	return $content;
}
add_filter( 'the_content', __NAMESPACE__ . '\maybe_prepare_content', 1 );

/**
 * Parse the contents of a post and apply IDs to all headings.
 *
 * This function will not override custom IDs already assigned to headings.
 *
 * @param string $content The post content.
 * @param array  $args {
 *   Optional. Additional arguments to control how content is processed.
 *
 *   @type string $tags A comma-separated list of HTML tags that should have IDs injected into
 *                      them. Default is 'h1,h2,h3,h4,h5,h6'.
 * }
 * @return string The $content string with id attributes injected into the headings.
 */
function inject_heading_ids( $content, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'tags' => 'h1,h2,h3,h4,h5,h6',
	) );

	// This should be set in render_shortcode(), but be super-explicit.
	libxml_use_internal_errors( true );

	/*
	 * DOMDocument expects a root-level container, but that's not usually the case with WordPress
	 * content. To get around this, we'll manually inject (then later remove) a div#gtoc-root element
	 * around the post content.
	 *
	 * Additionally, unless we explicitly pass a character encoding, loadHTML will attempt to
	 * read everything as ASCII, which will wreak havoc on content.
	 */
	$wrapped_content  = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>';
	$wrapped_content .= '<div id="gtoc-root">' . $content . '</div>';
	$wrapped_content .= '</body></html>';

	// Load the document into DOMDocument.
	$dom = new \DOMDocument;
	$dom->loadHTML( $wrapped_content, LIBXML_HTML_NODEFDTD );

	// Iterate through each tag.
	$heading_tags = explode( ',', $args['tags'] );

	foreach ( $heading_tags as $tag ) {
		$headings = $dom->getElementsByTagName( $tag );

		if ( ! $headings ) {
			continue;
		}

		foreach ( $headings as $heading ) {
			if ( ! $heading->getAttribute( 'id' ) ) {
				$heading->setAttribute( 'id', get_heading_id( $heading->nodeValue ) );
			}
		}
	}

	// Retrieve the output, then manually strip the #gtoc-root element.
	$output = $dom->saveHTML( $dom->getElementById( 'gtoc-root' ) );
	$output = preg_replace( '/^\<div id="gtoc-root"\>/', '', $output );
	$output = preg_replace( '/\<\/div\>$/', '', $output );

	return trim( $output );
}

/**
 * Generate the ID to use for a given header.
 *
 * @param string $anchor The anchor text of a header.
 * @return string An ID attribute to apply to a given header, without the leading "#".
 */
function get_heading_id( $anchor ) {
	$id = sanitize_title( $anchor );

	/**
	 * Filter the ID used for a heading within Growella Table of Contents.
	 *
	 * @param string $id     The ID attribute for the heading.
	 * @param string $anchor The anchor text of the heading.
	 */
	$id = apply_filters( 'gtoc_get_heading_id', $id, $anchor );

	return $id;
}
