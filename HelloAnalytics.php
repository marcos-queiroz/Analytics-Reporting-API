<?php

// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

$analytics = initializeAnalytics();

$startData = date("Y-m-d", strtotime('-30 days', strtotime(date("Y-m-d"))));
$endData = date("Y-m-d", strtotime('-1 days', strtotime(date("Y-m-d"))));

echo "<h1>Periodo: $startData a $endData</h1>";

$totalUsuarios = getResult(getTotal($analytics, "users", $startData, $endData));
echo "<h3>Usuários</h3>";
pre($totalUsuarios); 

$totalNovosUsuarios = getResult(getTotal($analytics, "newUsers", $startData, $endData));
echo "<h3>Novos Usuários</h3>";
pre($totalNovosUsuarios); 

$getTotalSessoes = getResult(getTotal($analytics, "sessions", $startData, $endData));
echo "<h3>Sessões</h3>";
pre($getTotalSessoes); 

$getTotalSessoesUsuarios = getResult(getTotal($analytics, "sessionsPerUser", $startData, $endData));
echo "<h3>Número de sessões por usuário</h3>";
pre($getTotalSessoesUsuarios); 

$getTotalPageViews = getResult(getTotal($analytics, "pageviews", $startData, $endData));
echo "<h3>Visualizações de página</h3>";
pre($getTotalPageViews); 


// print
function pre($array = null)
{
  echo "<pre>";
  print_r($array);
  echo "</pre>";
}


/**
* Initializes an Analytics Reporting API V4 service object.
*
* @return An authorized Analytics Reporting API V4 service object.
*/
function initializeAnalytics()
{
  
  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = __DIR__ . '/email-gerado-267920-936d85d5c2c2.json';
  
  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_AnalyticsReporting($client);
  
  return $analytics;
}


/**
* Queries the Analytics Reporting API V4.
*
* @param service An authorized Analytics Reporting API V4 service object.
* @param metric
* @param startDate
* @param endDate
* @return The Analytics Reporting API V4 response.
*/
function getTotal($analytics, $metric = null, $startDate = null, $endDate = null) {
  
  // Replace with your view ID, for example XXXX.
  $VIEW_ID = "XXXXXXXXXXXXXXX";
  
  // Create the DateRange object.
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate($startDate);
  $dateRange->setEndDate($endDate);
  
  //Create the browser dimension.
  $browser = new Google_Service_AnalyticsReporting_Dimension();
  $browser->setName("ga:browser");
  
  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:".$metric);
  $sessions->setAlias($metric);
  
  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($VIEW_ID);
  $request->setDateRanges($dateRange);
  $request->setMetrics(array($sessions));
  
  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet( $body );
}

/**
* Parses and prints the Analytics Reporting API V4 response.
*
* @param An Analytics Reporting API V4 response.
*/
function getResult($reports) {
  $response = array();
  
  for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
    $report = $reports[ $reportIndex ];
    $header = $report->getColumnHeader();
    $dimensionHeaders = $header->getDimensions();
    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
    $rows = $report->getData()->getRows();
    
    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
      $row = $rows[ $rowIndex ];
      $dimensions = $row->getDimensions();
      $metrics = $row->getMetrics();
      
      for ($j = 0; $j < count($metrics); $j++) {
        $values = $metrics[$j]->getValues();
        for ($k = 0; $k < count($values); $k++) {
          $entry = $metricHeaders[$k];
          $n = array($entry->getName() => $values[$k]);
        }
      }
      
      if($dimensionHeaders){
        $dimension = array();
        
        for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
          $d = array(str_replace(':','_',$dimensionHeaders[$i]) => str_replace('\'\'','',$dimensions[$i]));
          
          array_push($dimension, $d);
        }
        $t = array_merge($dimension, $n);
      }else{
        $t = $n;
      }
      
      array_push($response, $t);
      
    }
  }
  
  return $response;
}
