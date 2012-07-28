# GWT_IndexStatus: Request Index Status data from Google Webmaster Tools.

## Introduction

Since Googlebot was born, webmasters around the world have been asking one question: Google, oh, Google, are my pages in the index? In July 2012 Google answered that question with a new feature in Webmaster Tools called 'Index Status'. Further information can be found here: http://googlewebmastercentral.blogspot.de/2012/07/behold-google-index-secrets-revealed.html.

This PHP class lets you request that information script-wise and returns the 'Index Status'-data in JSON format. Also, there's an examples how to automatically download the data to CSV files.

## Download

The latest stable version can be downloaded from the downloads tab, or using the following link:  
https://github.com/downloads/eyecatchup/GWT_IndexStatus-php/GWT_IndexStatus-php-1.0.0.zip

## Usage

To get started, the steps are as follows:

 - Download the php file GWT_IndexStatus.php.
 - Create a folder and add the GWT_IndexStatus.php script to it.

### Authentication

To request data from your Google Webmaster Tools Account you must authenticate requests using your Google Account login credentials. There are two ways to do so.

#### Authentication via Interface

The easiest way is to set your login credentials using the class interface `GWT_Client`. To do so, open the GWT_IndexStatus.php file, go to line 53 and enter your login credentials.

```php
<?php
	interface GWT_Client
	{
		const Email = 'username@gmail.com';
		const Passwd = 'secretpassword';
	}
```

#### (Explict) Authentication via `login()`

Alternatively, you can use the `login()` method to authenticate your requests. This is also handy if you want to request data for another account than defined in the interface.

For an example, please see the php file example4.php.

### Example 1 - `getDataByDomain()`

To download index status data for a single domain, the steps are as follows:

 - In the same folder where you added the GWT\_IndexStatus.php, create and run the following PHP script.<br>_Note: You'll need to authenticate your request (see above)! Also, you'll need to replace the example URL with a domain that is registered in your GWT account._

```php
<?php
require_once('GWT_IndexStatus.php');

try {
  $indx = new GWT_IndexStatus();

  $json = $indx->getDataByDomain('http://www.domain.tld/'); # NOTE: Must have a trailing slash!
  print_r( json_decode($json) );
}
catch (Exception $e) {
  die( $e->getMessage() );
}
```

By default, the class will return totals of indexed pages, the cumulative number of pages crawled, the number of pages that Google knows about which are not crawled because they are blocked by robots.txt, and also the number of pages that were not selected for inclusion in Google's search results. However you can adjust the request parameters using the `setParam()` method (as done in the 2nd example).

### Example 2 - `getDataAllDomains()`

To download index status data for all domains that are registered in your Webmaster Tools account, the steps are as follows:

 - In the same folder where you added the GWT\_IndexStatus.php, create and run the following PHP script.<br>_Note: You'll need to authenticate your request (see above)!_

```php
<?php
require_once('GWT_IndexStatus.php');

try {
  $indx = new GWT_IndexStatus();
  
  /**
   * Exclude 'blocked by robots.txt'-data.
   * Valid parameters are 'is-crawl' (Ever crawled), 'is-indx' (Total indexed), 
   * 'is-not-slctd' (Not selected) and 'is-rbt' (Blocked by robots).
   */
  $indx->setParam('is-rbt', 0); 

  $json = $indx->getDataAllDomains();
  print_r( json_decode($json) );
}
catch (Exception $e) {
  die( $e->getMessage() );
}
```

### Example 3 - Save data as CSV

To download index status data for all domains that are registered in your Webmaster Tools account and save the resultsets into CSV files, the steps are as follows:

 - In the same folder where you added the GWT\_IndexStatus.php, create and run the following PHP script.<br>_Note: You'll need to authenticate your request (see above)!_

```php
<?php
require_once('GWT_IndexStatus.php');

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
```

This example code will create a CSV file for each domain that is reqistered in your GWT account in the same folder where you run the script.

## Legal Note

The author of the software is not a partner, affiliate, or licensee of Google Inc. or its employees, nor is the software in any other way formally associated with or legitimized by Google Inc.. Google is a registered trademark of Google Inc.. Use of the trademark is subject to Google Permissions: http://www.google.com/permissions/index.html.

## Copyright / License

URL: https://github.com/eyecatchup/GWT_IndexStatus-php/      
License: http://eyecatchup.mit-license.org/     
(c) 2012, Stephan Schmitz <eyecatchup@gmail.com>  