<?php
/**
 * Tests for the main plugin functionality.
 *
 * @package Growella\TableOfContents
 * @author  Growella
 */

namespace Growella\TableOfContents\Headings;

use WP_Mock as M;
use Growella\TableOfContents;

class HeadingsTest extends \Growella\TableOfContents\TestCase {

	protected $testFiles = array(
		'headings.php',
	);

	public function testMaybePrepareContent() {
		M::wpFunction( 'has_shortcode', array(
			'return' => true,
		) );

		M::expectFilterAdded( 'the_content', __NAMESPACE__ . '\inject_heading_ids', 9 );

		maybe_prepare_content( 'content' );
	}

	public function testMaybePrepareContentDoesntTouchContent() {
		$content = uniqid();

		M::wpFunction( 'has_shortcode', array(
			'return' => true,
		) );

		$this->assertEquals( $content, maybe_prepare_content( $content ), 'maybe_prepare_content() should not alter the post content' );
	}

	public function testInjectHeadingIds() {
		$content  = <<<EOT
<p>First paragraph.</p>
<h2>First heading</h2>
<p>Second paragraph.</p>
EOT;
		$expected = <<<EOT
<p>First paragraph.</p>
<h2 id="first-heading">First heading</h2>
<p>Second paragraph.</p>
EOT;

		M::wpFunction( 'wp_parse_args', array(
			'return' => array(
				'tags' => 'h1,h2,h3,h4,h5,h6',
			),
		) );

		M::wpFunction( __NAMESPACE__ . '\get_heading_id', array(
			'args'   => array( 'First heading' ),
			'return' => 'first-heading',
		) );

		$this->assertEquals( $expected, inject_heading_ids( $content ) );
	}

	public function testInjectHeadingIdsGetsAllHeadings() {
		$content  = <<<EOT
<h1>Heading 1</h1>
<h2>Heading 2</h2>
<h3>Heading 3</h3>
<h4>Heading 4</h4>
<h5>Heading 5</h5>
<h6>Heading 6</h6>
EOT;
		$expected = <<<EOT
<h1 id="heading-1">Heading 1</h1>
<h2 id="heading-2">Heading 2</h2>
<h3 id="heading-3">Heading 3</h3>
<h4 id="heading-4">Heading 4</h4>
<h5 id="heading-5">Heading 5</h5>
<h6 id="heading-6">Heading 6</h6>
EOT;

		M::wpFunction( 'wp_parse_args', array(
			'return' => array(
				'tags' => 'h1,h2,h3,h4,h5,h6',
			),
		) );

		M::wpFunction( __NAMESPACE__ . '\get_heading_id', array(
			'return' => function ( $text ) {
				return str_replace( 'Heading ', 'heading-', $text );
			}
		) );

		$this->assertEquals( $expected, inject_heading_ids( $content ) );
	}

	public function testInjectHeadingIdsRespectsTagsArg() {
		$content  = <<<EOT
<h1>Heading 1</h1>
<h2>Heading 2</h2>
<blockquote>Some quote</blockquote>
EOT;
		$expected = <<<EOT
<h1>Heading 1</h1>
<h2 id="heading-2">Heading 2</h2>
<blockquote id="some-quote">Some quote</blockquote>
EOT;

		M::wpFunction( 'wp_parse_args', array(
			'return' => array(
				'tags' => 'h2,blockquote',
			),
		) );

		M::wpFunction( __NAMESPACE__ . '\get_heading_id', array(
			'args'   => array( 'Heading 2' ),
			'return' => 'heading-2',
		) );

		M::wpFunction( __NAMESPACE__ . '\get_heading_id', array(
			'args'   => array( 'Some quote' ),
			'return' => 'some-quote',
		) );

		$this->assertEquals( $expected, inject_heading_ids( $content ) );
	}

	public function testInjectHeadingIdsDoesntAffectOtherAttributes() {
		$content  = '<h2 class="foo bar" data-foo="bar" property>First heading</h2>';
		$expected = '<h2 class="foo bar" data-foo="bar" property id="first-heading">First heading</h2>';

		M::wpFunction( 'wp_parse_args', array(
			'return' => array(
				'tags' => 'h1,h2,h3,h4,h5,h6',
			),
		) );

		M::wpFunction( __NAMESPACE__ . '\get_heading_id', array(
			'return' => 'first-heading',
		) );

		$this->assertEquals( $expected, inject_heading_ids( $content ) );
	}

	public function testInjectHeadingIdsDoesntOverrideExistingIds() {
		$content  = '<h2 id="my-custom-id">First heading</h2>';

		M::wpFunction( 'wp_parse_args', array(
			'return' => array(
				'tags' => 'h1,h2,h3,h4,h5,h6',
			),
		) );

		M::wpFunction( __NAMESPACE__ . '\get_heading_id', array(
			'return' => 'first-heading',
		) );

		$this->assertEquals( $content, inject_heading_ids( $content ) );
	}

	public function testInjectHeadingIdsReturnsEarlyIfXMLExtensionIsNotLoaded() {
		$this->markTestIncomplete( 'Need to emulate the extension being unavailable' );
		M::wpFunction( __NAMESPACE__ . '\extension_loaded', array(
			'return' => false,
		) );

		M::wpPassthruFunction( 'esc_html__' );

		inject_heading_ids( 'foo bar' );
	}

	public function testGetHeadingId() {
		M::wpFunction( 'sanitize_title', array(
			'times'  => 1,
			'return' => 'my-heading-text',
		) );

		$this->assertEquals( 'my-heading-text', get_heading_id( 'My Heading Text' ) );
	}

	public function testGetHeadingIdAppliesFilter() {
		M::wpFunction( 'sanitize_title', array(
			'return' => 'my-heading-text',
		) );

		M::onFilter( 'gtoc_get_heading_id' )
			->with( 'my-heading-text', 'My Heading Text' )
			->reply( 'my-alternate-text' );

		$this->assertEquals( 'my-alternate-text', get_heading_id( 'My Heading Text' ) );
	}
}
