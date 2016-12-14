<?php
/**
 * Mocking a function and want to be able to return early? Just throw a ReturnEarlyException!
 *
 * Example:
 *
 *   \WP_Mock::wpFunction( 'some_function', array(
 *     'return' => function () {
 *       throw new ReturnEarlyException;
 *     },
 *   ) );
 *
 *   try {
 *     my_function();
 *
 *   } catch ( ReturnEarlyException $e ) {
 *     return;
 *   }
 *
 *   $this->fail( 'Something went wrong!' );
 */

namespace Growella\TableOfContents;

class ReturnEarlyException extends \Exception {}
