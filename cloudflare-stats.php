<?php
//ini_set('display_errors', 1);


//ini_set('memory_limit', '1024M');

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
$header = "<meta name=\"keywords\" content=\"Vonwallace.com Real-time Website Cloudflare Statistics\">
    <meta name=\"description\" content=\"Vonwallace.com Real-time Website Cloudflare Statistics\">

<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/apple-touch-icon.png\"/>
<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/favicon-32x32.png\"/>
<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/favicon-16x16.png\"/>
<link rel=\"canonical\" href=\"/stats/cloudflare-stats.php\"/>






<style>.hr1 {height: 0;width:40%;margin: auto;border-bottom: 1px solid white;}table, th, td {
                                                                                  border: 1px solid white;
                                                                                  border-collapse: collapse;
</style>


";

echo "<!DOCTYPE html >
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">
<head>
    <meta charset=\"UTF-8\"/>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />
    
    <link rel=\"stylesheet\" href=\"/css/vwimprove.css\"/>
    <link rel=\"stylesheet\" href=\"/css/pagestyling.css\"/>
    <link rel=\"stylesheet\" href=\"/css/phonelookup.css\"/>
    <meta property=\"og:image\" content=\"https://vonwallace.com/mstile-150x150.png\" />
    
    <style>.hr1 {height: 0;width:40%;margin: auto;border-bottom: 1px solid white;}table, th, td {
                                                                                      border: 1px solid white;
                                                                                      border-collapse: collapse;}table {
                                                                                                                     width: 100%;
                                                                                                                 }
    </style>
   

    <link href=\"/bootstrap/css/bootstrap.min.css\" rel=\"stylesheet\" crossorigin=\"anonymous\">
    <script src=\"/bootstrap/js/bootstrap.bundle.min.js\"  crossorigin=\"anonymous\"></script>
    <style>body {background-color: #000;
            color:#fff;}</style>

    <title> Real-time Website Cloudflare Statistics | Vonwallace</title>", $header,
" </head>
<body>";

$filename = "../menu/menu5.txt";
$handle = fopen($filename, "r");
$contents = fread($handle, filesize($filename));
fclose($handle);
echo $contents;

echo "<div class=\"wrapper\">
    <div class=\"hr1\"></div>
    <div class=\"htitle\">REAL-TIME WEBSITE CLOUDFLARE STATISTICS</div>
    <div class=\"hr1\"></div>
    <div class=\"htime\">", date("l jS \of F Y h:i:s A"), "<br /></div><br /><br /><hr/>";


require 'vendor/autoload.php'; // Assuming Composer installation

use GuzzleHttp\Client;

$apiToken = '';
$zoneId = '';

// Define the time interval (e.g., 30 days)
$timeInterval = 30; // Adjust this based on your requirements

try {
    $client = new Client([
        'headers' => [
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type' => 'application/json',
        ],
    ]);

    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$timeInterval days"));

    // Store data in an array
    $tableData = [];
    $processedDates = [];
    $totals = ['date' => 'Total', 'requests' => 0, 'pageViews' => 0, 'uniqueVisitors' => 0];

    // Make multiple requests for smaller time intervals
    while (strtotime($endDate) > strtotime($startDate)) {
        $query = 'query {
            viewer {
                zones(filter: { zoneTag: "' . $zoneId . '" }) {
                    httpRequests1dGroups(
                        filter: {
                            date_gt: "' . $startDate . '"
                        }
                        orderBy: [date_ASC]
                        limit: 10000
                    ) {
                        dimensions { date }
                        uniq {
                            uniques
                        }
                        sum {
                            requests,
                            pageViews
                        }
                    }
                }
            }
        }';

        $response = $client->post('https://api.cloudflare.com/client/v4/graphql', [
            'json' => ['query' => $query],
        ]);

        // Assuming $response is an instance of Psr\Http\Message\ResponseInterface
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            if ($data !== null) {
            // Access and process data for each day
            foreach ($data['data']['viewer']['zones'][0]['httpRequests1dGroups'] as $group) {
                $date = $group['dimensions']['date'];

                // Check if date has already been processed
                if (!in_array($date, $processedDates)) {
                    $requests = $group['sum']['requests'];
                    $pageViews = $group['sum']['pageViews'];
                    $uniqueVisitors = $group['uniq']['uniques']; // Adjust this line

                    // Calculate ratios
                    $requestsPerVisitor = ($uniqueVisitors > 0) ? $requests / $uniqueVisitors : 0;
                    $pageViewsPerVisitor = ($uniqueVisitors > 0) ? $pageViews / $uniqueVisitors : 0;

                    // Store data in the array
                    $tableData[] = [
                        'date' => $date,
                        'requests' => $requests,
                        'pageViews' => $pageViews,
                        'uniqueVisitors' => $uniqueVisitors,
                        'requestsPerVisitor' => $requestsPerVisitor,
                        'pageViewsPerVisitor' => $pageViewsPerVisitor,
                    ];

                    // Update totals
                    $totals['requests'] += $requests;
                    $totals['pageViews'] += $pageViews;
                    $totals['uniqueVisitors'] += $uniqueVisitors;

                    // Mark date as processed
                    $processedDates[] = $date;
                }
            }

            } else {
                echo "Error: Unable to decode JSON response.";
                break; // Break out of the loop on failure
            }
        } else {
            echo "API request failed with status code: " . $response->getStatusCode();
            break; // Break out of the loop on failure
        }

        // Update the start date for the next iteration
        $startDate = date('Y-m-d', strtotime("$startDate +1 day"));
    }

    echo '<div style="text-align: center;"><div class="b_chart"><div id="your_div_id" style="width: 900px;height: 600px;"></div></div></div><br/><br/><hr/>';

echo '<h3>🌟 Comprehensive Site Performance Overview - Last 30 Days</h3>';
    // Generate HTML table

    echo '<div class="table_style1"><table border="1">';
    echo '<tr><th>Date</th><th>Requests</th><th>Page Views</th><th>Unique Visitors</th><th>Requests per Visitor</th><th>Page Views per Visitor</th></tr>';

    foreach ($tableData as $rowData) {
        echo '<tr>';
        echo '<td>' . $rowData['date'] . '</td>';
        echo '<td>' . $rowData['requests'] . '</td>';
        echo '<td>' . $rowData['pageViews'] . '</td>';
        echo '<td>' . $rowData['uniqueVisitors'] . '</td>';
        echo '<td>' . number_format($rowData['requestsPerVisitor'], 2) . '</td>'; // Format to 2 decimal places
        echo '<td>' . number_format($rowData['pageViewsPerVisitor'], 2) . '</td>'; // Format to 2 decimal places
        echo '</tr>';
    }

    // Display totals row
    echo '<tr>';
    echo '<td><strong>Totals</strong></td>';
    echo '<td><strong>' . $totals['requests'] . '</strong></td>';
    echo '<td><strong>' . $totals['pageViews'] . '</strong></td>';
    echo '<td><strong>' . $totals['uniqueVisitors'] . '</strong></td>';
    echo '<td><strong>' . number_format(($totals['uniqueVisitors'] > 0) ? $totals['requests'] / $totals['uniqueVisitors'] : 0, 2) . '</strong></td>'; // Format to 2 decimal places
    echo '<td><strong>' . number_format(($totals['uniqueVisitors'] > 0) ? $totals['pageViews'] / $totals['uniqueVisitors'] : 0, 2) . '</strong></td>'; // Format to 2 decimal places
    echo '</tr>';

    echo '</table></div>';

    $timeInterval = 8; // Adjust this based on your requirements
    $startDate = date('Y-m-d', strtotime("-$timeInterval days"));



    $query = 'query GetZoneTopPaths {
    viewer {
        zones(filter: {zoneTag: "' . $zoneId . '" }) {
            topPaths: httpRequestsAdaptiveGroups(
                filter: {
                    date_gt: "' . $startDate . '"
                }
                orderBy: [date_DESC]
                limit: 10000
            ) {
                dimensions {
                    path: clientRequestPath,
                    date
                }
                sum {
                    pageViews: visits
                }
            }
        }
    }
}';

// Use the provided HTTP client to make the request
    $response = $client->post('https://api.cloudflare.com/client/v4/graphql', [
        'json' => ['query' => $query],
    ]);

// Assuming $response is an instance of Psr\Http\Message\ResponseInterface
    if ($response->getStatusCode() === 200) {
        // Parse the JSON response
        $responseArray = json_decode($response->getBody()->getContents(), true);

        // Check for errors
        if (isset($responseArray['errors'])) {
            // Output the errors for debugging
            var_dump($responseArray['errors']);
            echo 'API request returned errors.';
        } else {
            // Extract the relevant data
            $zones = $responseArray['data']['viewer']['zones'];

            // Check if there are any zones
            if (empty($zones)) {
                echo 'No zones available.';
            } else {
                // Get the first zone
                $zone = $zones[0];

             if (isset($zone['topPaths'])) {
    $topPaths = $zone['topPaths'];

    // Combine entries with the same path and sum up the pageViews
    $pathsWithSum = [];
    foreach ($topPaths as $pathData) {
        $path = $pathData['dimensions']['path'];
        $pageViews = $pathData['sum']['pageViews'];

        if (isset($pathsWithSum[$path])) {
            // If path exists, add pageViews
            $pathsWithSum[$path] += $pageViews;
        } else {
            // If path doesn't exist, create a new entry
            $pathsWithSum[$path] = $pageViews;
        }
    }

    // Remove paths with zero page views
    $pathsWithSum = array_filter($pathsWithSum, function ($pageViews) {
        return $pageViews > 0;
    });

    // Sort the results by the sum of pageViews
    arsort($pathsWithSum);

    // Take the top 100 paths
    $pathsWithSum = array_slice($pathsWithSum, 0, 100);

                echo '<hr/><h3>🌟 Highlighting Top 100 Page Views - Last 8 Days</h3>';
    // Start building the HTML table
    echo '<div class="table_style1"><table border="1">';
    echo '<tr><th>Path</th><th>Page Views</th></tr>';

    foreach ($pathsWithSum as $path => $pageViews) {
       if($path)
           if (strpos($path, 'import') === false && strpos($path, 'insert') === false)
           {
               echo '<tr>';
               echo '<td><a href="' . $path . '" target="_blank">' . $path . '</a></td>';
               echo '<td>' . $pageViews . '</td>';
               echo '</tr>';
           }
    }

    // Add a row at the bottom for totals
    echo '<tr><td><strong>Total</strong></td><td><strong>' . array_sum($pathsWithSum) . '</strong></td></tr>';

    // Close the table
    echo '</table></div>';


                } else {
                    echo 'No topPaths data available.';
                }
            }
        }
    } else {
        echo 'API request failed with status code: ' . $response->getStatusCode();
    }

    $timeInterval = 8; // Adjust this based on your requirements
    $startDate = date('Y-m-d', strtotime("-$timeInterval days"));


// GraphQL query
$query = 'query GetZoneTopNs {
  viewer {
    zones(filter: {zoneTag: "' . $zoneId . '"}) {
      countries: httpRequestsAdaptiveGroups(
                filter: {
                    date_gt: "' . $startDate . '"
                }
                orderBy: [date_DESC]
                limit: 10000
            ) {
        count
        avg {
          sampleInterval
          
        }
        sum {
          edgeResponseBytes
          visits
          
        }
        dimensions {
        date
          metric: clientCountryName
          
        }
       
      }
      
    }
    
  }
}';

// Use the provided HTTP client to make the request
    $response = $client->post('https://api.cloudflare.com/client/v4/graphql', [
        'json' => ['query' => $query],
    ]);

// Assuming $response is an instance of Psr\Http\Message\ResponseInterface
    if ($response->getStatusCode() === 200) {
        // Parse the JSON response
        $responseArray = json_decode($response->getBody()->getContents(), true);

        // Check for errors
        if (isset($responseArray['errors'])) {
            // Output the errors for debugging
            var_dump($responseArray['errors']);
            echo 'API request returned errors.';
        } else {
            // Extract the relevant data
            $zones = $responseArray['data']['viewer']['zones'];

            // Check if there are any zones
            if (empty($zones)) {
                echo 'No zones available.';
            } else {
                // Get the first zone
                $zone = $zones[0];

                // Check if countries data is available
                if (isset($zone['countries'])) {
                    $countries = $zone['countries'];

                    // Combine entries with the same country and sum up the counts
                    $countriesWithSum = [];
                    foreach ($countries as $countryData) {
                        $country = $countryData['dimensions']['metric'];
                        $count = $countryData['count'];

                        if (isset($countriesWithSum[$country])) {
                            // If country exists, add count
                            $countriesWithSum[$country] += $count;
                        } else {
                            // If country doesn't exist, create a new entry
                            $countriesWithSum[$country] = $count;
                        }
                    }

                    // Sort the results by the count of each country in descending order
                    arsort($countriesWithSum);
                    echo '<hr/><h3>🌟 Top Countries by Count - Last 8 Days</h3>';
                    // Start building the HTML table
                    echo '<div class="table_style1"><table border="1">';
                    echo '<tr><th>Country</th><th>Count</th></tr>';

                    foreach ($countriesWithSum as $country => $count) {
                        echo '<tr>';
                        echo '<td>' . $country . '</td>';
                        echo '<td>' . $count . '</td>';
                        echo '</tr>';
                    }

                    // Add a row at the bottom for totals
                    echo '<tr><td><strong>Total</strong></td><td><strong>' . array_sum($countriesWithSum) . '</strong></td></tr>';

                    // Close the table
                    echo '</table></div>';
                } else {
                    echo 'No countries data available.';
                }
            }
        }
    } else {
        echo 'API request failed with status code: ' . $response->getStatusCode();
    }


    // Google Chart

    echo "<hr/><div class=\"center\"><b><i><a href=\"https://www.biblegateway.com/passage/?search=1+John+4%3A7-21&amp;version=NIV\" target=\"_blank\">God Is Love - 1 John 4:7-21</a></i></b><br /></div></div><br />";


// JavaScript for Google Chart
    echo '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
    echo '<script type="text/javascript">';
    echo 'google.charts.load("current", {"packages":["corechart"]});';
    echo 'google.charts.setOnLoadCallback(drawChart);';

    echo 'function drawChart() {';
    echo 'var data = new google.visualization.DataTable();';
    echo 'data.addColumn("string", "Date");';
    echo 'data.addColumn("number", "Requests");';
    echo 'data.addColumn("number", "Page Views");';
    echo 'data.addColumn("number", "Unique Visitors");';

    foreach ($tableData as $rowData) {
        echo 'data.addRow(["' . $rowData['date'] . '", ' . $rowData['requests'] . ', ' . $rowData['pageViews'] . ', ' . $rowData['uniqueVisitors'] . ']);';
    }

    echo 'var options = {';
    echo 'title: "Requests, Page Views, and Unique Visitors - Last 30 Days",';
    echo 'curveType: "function",';
    echo 'legend: { position: "bottom" },';
    echo 'hAxis: {';
    echo '  slantedText: true,';
    echo '  slantedTextAngle: 45';
    echo '},';
    echo 'chartArea: {';
    echo '  width: "80%",';  // Adjust the width of the chart area
    echo '  height: "60%",';  // Adjust the height of the chart area
    echo '  bottom: 150,';  // Increase the bottom padding
    echo ' top: 40';
    echo '},';
    echo '};';

    echo 'var chart = new google.visualization.LineChart(document.getElementById("your_div_id"));';
    echo 'chart.draw(data, options);';
    echo '}';
    echo '</script>';



} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
