<?php
/**
 * Tests for the main plugin functionality.
 *
 * @package Growella\TableOfContents
 * @author  Growella
 */

namespace Growella\TableOfContents\Core;

use WP_Mock as M;
use Growella\TableOfContents;

class CoreTest extends \Growella\TableOfContents\TestCase {

	protected $testFiles = array(
		'core.php',
		'headings.php',
	);

	public function setUp() {
		M::wpPassthruFunction( 'add_shortcode' );

		parent::setUp();
	}

	public function testRenderShortcode() {
		$content  = <<<EOT
<h2 id="first-heading">First heading</h2>
<p>Paragraph</p>
<h3 id="sub-heading">Sub-heading</h3>
<p>Another paragraph</p>
<h2 id="second-heading">Second heading</h2>
<p>Last paragraph</p>
EOT;
		$expected  = '<nav class="growella-table-of-contents"><ul>';
		$expected .= '<li><a href="#first-heading">First heading</a></li>';
		$expected .= '<li><a href="#sub-heading">Sub-heading</a></li>';
		$expected .= '<li><a href="#second-heading">Second heading</a></li>';
		$expected .= '</ul></nav>';

		M::wpFunction( 'shortcode_atts', array(
			'return' => array(
				'tags' => 'h1,h2,h3',
			),
		) );

		M::wpFunction( 'get_the_content', array(
			'return' => $content,
		) );

		M::wpFunction( __NAMESPACE__ . '\build_link_list', array(
			'return' => array(
				'<a href="#first-heading">First heading</a>',
				'<a href="#sub-heading">Sub-heading</a>',
				'<a href="#second-heading">Second heading</a>',
			),
		) );

		M::wpPassthruFunction( 'Growella\TableOfContents\Headings\inject_heading_ids' );

		$this->assertEquals( $expected, render_shortcode( array() ) );
	}

	public function testRenderShortcodeReturnsEmptyNullIfNoLinksFound() {
		M::wpFunction( 'shortcode_atts', array(
			'return' => array(
				'tags' => 'h1,h2,h3',
			),
		) );

		M::wpFunction( 'get_the_content', array(
			'return' => 'no links here',
		) );

		M::wpFunction( __NAMESPACE__ . '\build_link_list', array(
			'return' => array(),
		) );

		M::wpPassthruFunction( 'Growella\TableOfContents\Headings\inject_heading_ids' );

		$this->assertNull( render_shortcode( array() ) );
	}

	public function testBuildLinkList() {
		$dom   = new \DOMDocument;
		$dom->loadHTML( '<h1 id="my-heading">My heading</h1>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new \DOMXpath( $dom );

		$expected = array(
			'<a href="#my-heading">My heading</a>'
		);

		M::wpPassthruFunction( 'esc_html' );
		M::wpPassthruFunction( 'esc_attr' );

		$this->assertEquals( $expected, build_link_list( $xpath->query( '//h1[@id]' ) ) );
	}

	public function testBuildLinkListWithMultipleHeadings() {
				$content  = <<<EOT
<div>
	<h2 id="first-heading">First heading</h2>
	<p>Paragraph</p>
	<h3 id="sub-heading">Sub-heading</h3>
	<p>Another paragraph</p>
	<h2 id="second-heading">Second heading</h2>
	<p>Last paragraph</p>
</div>
EOT;
		$dom   = new \DOMDocument;
		$dom->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$xpath = new \DOMXpath( $dom );

		$expected = array(
			'<a href="#first-heading">First heading</a>',
			'<a href="#sub-heading">Sub-heading</a>',
			'<a href="#second-heading">Second heading</a>',
		);

		M::wpPassthruFunction( 'esc_html' );
		M::wpPassthruFunction( 'esc_attr' );

		$this->assertEquals( $expected, build_link_list( $xpath->query( '//h2[@id]|h3[@id]' ) ) );
	}
}
