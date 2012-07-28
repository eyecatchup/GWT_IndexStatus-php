<?php
require_once('GWT_IndexStatus.php');

/**
 * Example 1
 * Get a JSON array containing Index-Status data
 * for a single domain that is registered in a GWT account.
 */
try {
  // Create a new class instance.
  $indx = new GWT_IndexStatus();

  $json = $indx->getDataByDomain('http://www.domain.tld/'); # NOTE: Must have a trailing slash!

  // Remember: You can use the optional 2nd parameter for `json_decode()`
  // to get a native PHP array, instead of an stdClass-object.
  // @see: http://www.php.net/manual/en/function.json-decode.php
  print '<pre>';
  print_r( json_decode($json) );
  print '</pre>';
}
catch (Exception $e) {
  die( $e->getMessage() );
}