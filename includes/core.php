<?php
/**
 * Core plugin functionality.
 *
 * @package Growella\TableOfContents
 * @author  Growella
 */

namespace Growella\TableOfContents\Core;

/**
 * Render the Table of Contents.
 *
 * @param array $atts Shortcode attributes.
 * @return string The rendered table of contents.
 */
function render_shortcode( $atts ) {
	return '';
}
add_shortcode( 'toc', __NAMESPACE__ . '\render_shortcode' );
