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
	);

	public function setUp() {
		M::wpPassthruFunction( 'add_shortcode' );

		parent::setUp();
	}
}
