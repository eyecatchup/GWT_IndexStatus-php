<?php
require_once('GWT_IndexStatus.php');

/**
 * Example 3
 * Get Index-Status data for all domains registered in a GWT account
 * and save resultsets to separated CSV files - one CSV file per domain.
 */
try {
  $indx = new GWT_IndexStatus();

  $json = $indx->getDataAllDomains();
  $domains = json_decode($json);

  foreach ($domains as $domain) {
    $data = $domain->indexData;
    $colCount = sizeof($data->cols);

    // Get column label names for CSV headline
    $labels = Array();
    for ($i=0; $i < $colCount; $i++) {
    $labels[] = $data->cols[$i]->label;
    }
    $cols = implode(';', $labels);

    // Start new CSV output string.
    $csv = "$cols\n";

    // Iterate through all resultsets and add rows to CSV output string
    // containing the columns as defined per instance.
    foreach ($data->rows as $r) {
    $row = Array();
    for ($i=0; $i < $colCount; $i++) {
        $row[] = $r->c[$i]->v;
    }
    $csv .= implode(';', $row) ."\n";
    }

    // Write CSV string to file.
    $filename = 'GWT_IndexStatus_' .
      parse_url($domain->domain, PHP_URL_HOST) .'_'.
      date("m-d-Y") .'.csv';

    if (file_put_contents($filename, $csv))
      print "Saved $filename\n";
  }
}
catch (Exception $e) {
  die( $e->getMessage() );
}