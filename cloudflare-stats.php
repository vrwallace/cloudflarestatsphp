<?php
//ini_set('display_errors', 1);

ini_set('max_execution_time', 300);

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

<style>
.table_style1 {
  margin-bottom: 40px;
}

h3 {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 20px;
}

.b_chart {
margin-top: 40px;
  margin-bottom: 40px;
}

</style>




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

$apiToken = '';
$zoneId = '';

// Define the time interval (e.g., 30 days)
$timeInterval = 30; // Adjust this based on your requirements

try {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$timeInterval days"));

    // Store data in an array
    $tableData = [];
    $totals = ['requests' => 0, 'pageViews' => 0, 'uniqueVisitors' => 0, 'requestsPerVisitor' => 0, 'pageViewsPerVisitor' => 0];

// Prepare the query for the entire date range
    $query = 'query {
    viewer {
        zones(filter: { zoneTag: "' . $zoneId . '" }) {
            httpRequests1dGroups(
                filter: {
                    date_gt: "' . $startDate . '",
                    date_leq: "' . $endDate . '"
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

// Initialize cURL handle
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/graphql');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Execute the cURL request
    $response = curl_exec($ch);
    curl_close($ch);

// Process the response
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data !== null) {
            // Access and process data for each day
            foreach ($data['data']['viewer']['zones'][0]['httpRequests1dGroups'] as $group) {
                $date = $group['dimensions']['date'];

                $requests = $group['sum']['requests'];
                $pageViews = $group['sum']['pageViews'];
                $uniqueVisitors = $group['uniq']['uniques'];

                // Aggregate data for the same date
                if (isset($tableData[$date])) {
                    $tableData[$date]['requests'] += $requests;
                    $tableData[$date]['pageViews'] += $pageViews;
                    $tableData[$date]['uniqueVisitors'] += $uniqueVisitors;
                } else {
                    $tableData[$date] = [
                        'date' => $date,
                        'requests' => $requests,
                        'pageViews' => $pageViews,
                        'uniqueVisitors' => $uniqueVisitors,
                        'requestsPerVisitor' => 0,
                        'pageViewsPerVisitor' => 0,
                    ];
                }

                // Update totals
                $totals['requests'] += $requests;
                $totals['pageViews'] += $pageViews;
                $totals['uniqueVisitors'] += $uniqueVisitors;
            }

            // Calculate requests per visitor and page views per visitor for each date
            foreach ($tableData as $date => &$data) {
                if ($data['uniqueVisitors'] > 0) {
                    $data['requestsPerVisitor'] = $data['requests'] / $data['uniqueVisitors'];
                    $data['pageViewsPerVisitor'] = $data['pageViews'] / $data['uniqueVisitors'];
                } else {
                    $data['requestsPerVisitor'] = 0;
                    $data['pageViewsPerVisitor'] = 0;
                }
            }




        } else {
            echo "Error: Unable to decode JSON response.";
        }
    } else {
        echo "API request failed.";
    }





    echo '<div style="text-align: center;"><div class="b_chart"><div id="your_div_id" style="width: 900px;height: 600px;"></div></div></div><hr/>';

    echo '<h3>Comprehensive Site Performance Overview - Last 30 Days</h3>';
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

    // Initialize cURL handle
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/graphql');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for errors
    if ($response !== false) {
        // Parse the JSON response
        $responseArray = json_decode($response, true);

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

                    echo '<hr/><h3>Highlighting Top 100 Page Views - Last 8 Days (Filtered)</h3>';
                    // Start building the HTML table
                    echo '<div class="table_style1"><table border="1">';
                    echo '<tr><th>Path</th><th>Page Views</th></tr>';

                    foreach ($pathsWithSum as $path => $pageViews) {
                        if ($path)
                            if (strpos($path, 'import') === false && strpos($path, 'insert') === false && strpos($path, '%') === false && strpos($path, '//') === false && strpos($path, '404.php') === false && strpos($path, '&') === false && strpos($path, '.php/') === false) {
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
    echo 'API request failed with status code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE);
}

// Close the cURL handle
curl_close($ch);

$timeInterval = 8; // Adjust this based on your requirements
$startDate = date('Y-m-d', strtotime("-$timeInterval days"));

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

// Initialize cURL handle
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/graphql');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Execute the cURL request
$response = curl_exec($ch);

// Prepare JavaScript array for tracking processed countries
echo '<script>var processedCountriesJS = [];</script>';

// Check for errors
if ($response !== false) {
    // Parse the JSON response
    $responseArray = json_decode($response, true);

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

                    // Add country to JavaScript array and check for duplicates
                    echo '<script>
                            if (processedCountriesJS.includes("' . $country . '")) {
                                console.log("Duplicate entry found for country: ' . $country . '");
                            } else {
                                processedCountriesJS.push("' . $country . '");
                            }
                          </script>';

                    // Aggregate data for the same country
                    if (isset($countriesWithSum[$country])) {
                        $countriesWithSum[$country] += $count;
                    } else {
                        $countriesWithSum[$country] = $count;
                    }
                }

                // Sort the results by the count of each country in descending order
                arsort($countriesWithSum);

                echo '<hr/><h3>Top Countries by Count - Last 8 Days</h3>';
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
    echo 'API request failed with status code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE);
}

// Close the cURL handle
curl_close($ch);


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
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Fetch and display monthly views and requests
        fetch('/network/fetch_hits.php')
            .then(response => response.json())
            .then(data => {
                console.log(data);  // Add this line for debugging
                if (data.pageViews !== undefined && data.requests !== undefined) {
                    changeSpeechBubbleText('Hello! ' + data.requests.toLocaleString() + ' requests, ' + data.pageViews.toLocaleString() + ' page views for 30 days! Advertise with us!');

                } else {
                    changeSpeechBubbleText('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                changeSpeechBubbleText('Error: ' + error);
            });
    });


</script>
</body>
</html>
