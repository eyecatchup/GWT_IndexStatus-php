<?php
require_once('GWT_IndexStatus.php');

/**
 * Example 4
 * Login explicitly (using other credentials than defined in interface)
 * and list all domains registered in that GWT account.
 */
try {
  $indx = new GWT_IndexStatus();
  $indx->login('anotherusername@gmail.com', 'password');

  $sites = $indx->getSites();
  print_r( $sites );
}
catch (Exception $e) {
  die( $e->getMessage() );
}