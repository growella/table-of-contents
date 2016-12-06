<?php
/**
 * Core plugin functionality.
 *
 * @package Growella\TableOfContents
 * @author  Growella
 */

namespace Growella\TableOfContents\Core;

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
	if ( ! extension_loaded( 'xml' ) ) {
		trigger_error(
			esc_html__( 'Unable to inject id attributes, as the PHP XML extension is not loaded', 'growella-table-of-contents' ),
			E_USER_NOTICE
		);
		return;
	}

	$args = wp_parse_args( $args, array(
		'tags' => 'h1,h2,h3,h4,h5,h6',
	) );

	/*
	 * DOMDocument expects a root-level container, but that's not usually the case with WordPress
	 * content. To get around this, we'll manually inject (then later remove) a div#gtoc-root element
	 * around the post content.
	 */
	$content = '<div id="gtoc-root">' . $content . '</div>';

	// Load the document into DOMDocument.
	$dom = new \DOMDocument();
	$dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

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
	$output = $dom->saveHTML();
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
