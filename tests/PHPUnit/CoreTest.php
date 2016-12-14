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
use Growella\TableOfContents\ReturnEarlyException;

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
		$expected  = '<nav class="growella-table-of-contents"><h2>My title</h2><ul>';
		$expected .= '<li><a href="#first-heading">First heading</a></li>';
		$expected .= '<li><a href="#sub-heading">Sub-heading</a></li>';
		$expected .= '<li><a href="#second-heading">Second heading</a></li>';
		$expected .= '</ul></nav>';

		M::wpFunction( 'shortcode_atts', array(
			'return' => array(
				'tags'  => 'h1,h2,h3',
				'title' => 'My title',
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
		M::wpPassthruFunction( '_x' );
		M::wpPassthruFunction( 'esc_html' );

		$this->assertEquals( $expected, render_shortcode( array() ) );
	}

	/**
	 * Since WP_Mock::onFilter() doesn't support wildcard with() calls, we'll mock apply_filters()
	 * instead. The separate process prevents this from wreaking havoc on other tests.
	 *
	 * @runInSeparateProcess
	 */
	public function testRenderShortcodeFiltersDefaultShortcodeAttributes() {
		M::wpFunction( __NAMESPACE__ . '\apply_filters', array(
			'args' => array( 'growella_table_of_contents_shortcode_defaults', '*' ),
			'return' => array( 'foo' ),
		) );

		M::wpFunction( 'shortcode_atts', array(
			'times'  => 1,
			'args'   => array( array( 'foo' ), array(), 'toc' ),
			'return' => function () {
				throw new ReturnEarlyException;
			}
		) );

		M::wpPassthruFunction( '_x' );

		try {
			render_shortcode( array() );

		} catch ( ReturnEarlyException $e ) {
			return;
		}

		$this->fail( 'shortcode_atts() did not receive the expected defaults argument' );
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

	/**
	 * Since WP_Mock::onFilter() doesn't support wildcard with() calls, we'll mock apply_filters()
	 * instead. The separate process prevents this from wreaking havoc on other tests.
	 *
	 * @runInSeparateProcess
	 */
	public function testRenderShortcodeOffersFilterJustBeforeReturning() {
		M::wpFunction( 'shortcode_atts', array(
			'return' => array(
				'tags'  => 'h1,h2,h3',
				'title' => false,
			),
		) );

		M::wpFunction( 'get_the_content', array(
			'return' => '<h2 id="first-heading">First heading</h2>',
		) );

		M::wpFunction( __NAMESPACE__ . '\build_link_list', array(
			'return' => array(
				'<a href="#first-heading">First heading</a>',
			),
		) );

		M::wpFunction( __NAMESPACE__ . '\apply_filters', array(
			'times'  => 1,
			'args'   => array( 'growella_table_of_contents_render_shortcode', '*', '*', '*' ),
			'return' => 'my-toc',
		) );

		M::wpPassthruFunction( __NAMESPACE__ . '\apply_filters' );

		M::wpPassthruFunction( 'Growella\TableOfContents\Headings\inject_heading_ids' );
		M::wpPassthruFunction( '_x' );

		$this->assertEquals( 'my-toc', render_shortcode( array() ) );
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

		$this->assertEquals( $expected, build_link_list( $xpath->query( '//h2[@id]|//h3[@id]' ) ) );
	}
}
