<?php
require_once('GWT_IndexStatus.php');

/**
 * Example 2
 * Get a JSON array containing Index-Status data for all domains
 * registered in a GWT account. But exclude a specific resultset.
 */
try {
  $indx = new GWT_IndexStatus();
  $indx->setParam('is-rbt', 0); # exclude 'blocked by robots.txt'-data

  $json = $indx->getDataAllDomains();

  print '<pre>';
  print_r( json_decode($json) );
  print '</pre>';
}
catch (Exception $e) {
  die( $e->getMessage() );
}