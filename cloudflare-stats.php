<?php
ini_set('max_execution_time', 300);

$header = "<meta name=\"keywords\" content=\"Vonwallace.com Real-time Website Cloudflare Statistics\">
    <meta name=\"description\" content=\"Vonwallace.com Real-time Website Cloudflare Statistics\">

<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"/apple-touch-icon.png\"/>
<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"/favicon-32x32.png\"/>
<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"/favicon-16x16.png\"/>
<link rel=\"canonical\" href=\"/stats/cloudflare-stats.php\"/>

<style>

.icon-blue { color: #3498db;   }
.icon-green { color: #2ecc71;   }
.icon-orange { color: #e67e22;   }
.icon-purple { color: #9b59b6;  }
.icon-red { color: #e74c3c;  }

.table_style1 {
  margin-bottom: 40px;
}

h3 {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 20px;
}

h3 i { margin-right: 10px; }

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

    <title> Real-time Website Cloudflare Statistics | Vonwallace</title>
" . $header . "
 </head>
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
    <div class=\"htime\">" . date("l jS \of F Y h:i:s A") . "<br /></div><br /><br /><hr/>";

require 'vendor/autoload.php';

$apiToken = '';
$zoneId = '';

$timeInterval30 = 30;
$timeInterval8 = 8;

$endDate = date('Y-m-d');
$startDate30 = date('Y-m-d', strtotime("-$timeInterval30 days"));
$startDate8 = date('Y-m-d', strtotime("-$timeInterval8 days"));



$queries = [
    'httpRequests' => 'query {
        viewer {
            zones(filter: { zoneTag: "' . $zoneId . '" }) {
                httpRequests1dGroups(
                    filter: {
                        date_gt: "' . $startDate30 . '",
                        date_leq: "' . $endDate . '"
                    }
                    orderBy: [date_ASC]
                    limit: 30
                ) {
                    dimensions { date }
                    sum {
                        requests
                        pageViews
                    }
                    uniq {
                        uniques
                    }
                }
            }
        }
    }',
    'topPaths' => 'query GetZoneTopPaths {
        viewer {
            zones(filter: {zoneTag: "' . $zoneId . '" }) {
                topPaths: httpRequestsAdaptiveGroups(
                    filter: {
                        date_gt: "' . $startDate8 . '"
                    }
                    orderBy: [sum_visits_DESC]
                    limit: 100
                ) {
                    dimensions {
                        path: clientRequestPath
                    }
                    sum {
                        pageViews: visits
                    }
                }
            }
        }
    }',
    'topCountries' => 'query GetZoneTopNs {
        viewer {
            zones(filter: {zoneTag: "' . $zoneId . '"}) {
                countries: httpRequestsAdaptiveGroups(
                    filter: {
                        date_gt: "' . $startDate8 . '"
                    }
                    orderBy: [date_DESC]
                    limit: 300
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
    }'
];


$mh = curl_multi_init();
$curlHandles = [];
$start_times = [];
$end_times = [];

foreach ($queries as $key => $query) {
    $curlHandles[$key] = curl_init();
    curl_setopt($curlHandles[$key], CURLOPT_URL, 'https://api.cloudflare.com/client/v4/graphql');
    curl_setopt($curlHandles[$key], CURLOPT_POST, 1);
    curl_setopt($curlHandles[$key], CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
    curl_setopt($curlHandles[$key], CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($curlHandles[$key], CURLOPT_RETURNTRANSFER, 1);

    curl_multi_add_handle($mh, $curlHandles[$key]);
    $start_times[$key] = microtime(true);
}

$running = null;
do {
    curl_multi_exec($mh, $running);
    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        $key = array_search($ch, $curlHandles);
        if ($key !== false) {
            $end_times[$key] = microtime(true);
        }
    }
} while ($running > 0);

$results = [];
foreach ($curlHandles as $key => $ch) {
    $results[$key] = curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

$httpRequestsData = json_decode($results['httpRequests'], true);
$topPathsData = json_decode($results['topPaths'], true);
$topCountriesData = json_decode($results['topCountries'], true);

if ($httpRequestsData !== null) {
    $tableData = [];
    $totals = ['requests' => 0, 'pageViews' => 0, 'uniqueVisitors' => 0, 'requestsPerVisitor' => 0, 'pageViewsPerVisitor' => 0];

    foreach ($httpRequestsData['data']['viewer']['zones'][0]['httpRequests1dGroups'] as $group) {
        $date = $group['dimensions']['date'];
        $requests = $group['sum']['requests'];
        $pageViews = $group['sum']['pageViews'];
        $uniqueVisitors = $group['uniq']['uniques'];

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

        $totals['requests'] += $requests;
        $totals['pageViews'] += $pageViews;
        $totals['uniqueVisitors'] += $uniqueVisitors;
    }

    foreach ($tableData as $date => &$data) {
        if ($data['uniqueVisitors'] > 0) {
            $data['requestsPerVisitor'] = $data['requests'] / $data['uniqueVisitors'];
            $data['pageViewsPerVisitor'] = $data['pageViews'] / $data['uniqueVisitors'];
        } else {
            $data['requestsPerVisitor'] = 0;
            $data['pageViewsPerVisitor'] = 0;
        }
    }

    echo '<div style="text-align: center;"><div class="b_chart"><div id="your_div_id" style="width: 900px;height: 600px;"></div></div></div><hr/>';

    $httpRequestsTime = ($end_times['httpRequests'] - $start_times['httpRequests']) * 1000;
    echo '<h3><i class="fas fa-calendar-alt icon-green"></i> Comprehensive Site Performance Overview - Last 30 Days (API request: ' . number_format($httpRequestsTime, 2) . 'ms)</h3>';

    echo '<div class="table_style1"><table border="1">';
    echo '<tr><th>Date</th><th>Requests</th><th>Page Views</th><th>Unique Visitors</th><th>Requests per Visitor</th><th>Page Views per Visitor</th></tr>';

    foreach ($tableData as $rowData) {
        echo '<tr>';
        echo '<td>' . $rowData['date'] . '</td>';
        echo '<td>' . $rowData['requests'] . '</td>';
        echo '<td>' . $rowData['pageViews'] . '</td>';
        echo '<td>' . $rowData['uniqueVisitors'] . '</td>';
        echo '<td>' . number_format($rowData['requestsPerVisitor'], 2) . '</td>';
        echo '<td>' . number_format($rowData['pageViewsPerVisitor'], 2) . '</td>';
        echo '</tr>';
    }

    echo '<tr>';
    echo '<td><strong>Totals</strong></td>';
    echo '<td><strong>' . $totals['requests'] . '</strong></td>';
    echo '<td><strong>' . $totals['pageViews'] . '</strong></td>';
    echo '<td><strong>' . $totals['uniqueVisitors'] . '</strong></td>';
    echo '<td><strong>' . number_format(($totals['uniqueVisitors'] > 0) ? $totals['requests'] / $totals['uniqueVisitors'] : 0, 2) . '</strong></td>';
    echo '<td><strong>' . number_format(($totals['uniqueVisitors'] > 0) ? $totals['pageViews'] / $totals['uniqueVisitors'] : 0, 2) . '</strong></td>';
    echo '</tr>';

    echo '</table></div>';
}

if ($topPathsData !== null) {
    $zones = $topPathsData['data']['viewer']['zones'];

    if (!empty($zones) && isset($zones[0]['topPaths'])) {
        $topPaths = $zones[0]['topPaths'];
        $pathsWithSum = [];

        foreach ($topPaths as $pathData) {
            $path = $pathData['dimensions']['path'];
            $pageViews = $pathData['sum']['pageViews'];

            if (isset($pathsWithSum[$path])) {
                $pathsWithSum[$path] += $pageViews;
            } else {
                $pathsWithSum[$path] = $pageViews;
            }
        }

        $pathsWithSum = array_filter($pathsWithSum, function ($pageViews) {
            return $pageViews > 0;
        });

        arsort($pathsWithSum);
        $pathsWithSum = array_slice($pathsWithSum, 0, 100);

        $topPathsTime = ($end_times['topPaths'] - $start_times['topPaths']) * 1000;
        echo '<hr/><h3><i class="fas fa-eye icon-orange"></i> Highlighting Top 100 Page Views - Last 8 Days (Filtered) (API request: ' . number_format($topPathsTime, 2) . 'ms)</h3>';
        $filteredTotal = 0;
        echo '<div class="table_style1"><table border="1">';
        echo '<tr><th>Path</th><th>Page Views</th></tr>';

        foreach ($pathsWithSum as $path => $pageViews) {
            if ($path && strpos($path, 'import') === false && strpos($path, 'insert') === false && strpos($path, '%') === false && strpos($path, '//') === false && strpos($path, '404.php') === false && strpos($path, '&') === false && strpos($path, '.php/') === false) {
                echo '<tr>';
                echo '<td><a href="' . $path . '" target="_blank">' . $path . '</a></td>';
                echo '<td>' . $pageViews . '</td>';
                echo '</tr>';
                $filteredTotal += $pageViews;
            }
        }

       // echo '<tr><td><strong>Total</strong></td><td><strong>' . array_sum($pathsWithSum) . '</strong></td></tr>';

        echo '<tr><td><strong>Total (Filtered)</strong></td><td><strong>' . $filteredTotal . '</strong></td></tr>';
        echo '</table></div>';
    } else {
        echo 'No topPaths data available.';
    }
}

if ($topCountriesData !== null) {
    $zones = $topCountriesData['data']['viewer']['zones'];

    if (!empty($zones) && isset($zones[0]['countries'])) {
        $countries = $zones[0]['countries'];

        $countriesWithSum = [];

        echo '<script>var processedCountriesJS = [];</script>';

        foreach ($countries as $countryData) {
            $country = $countryData['dimensions']['metric'];
            $count = $countryData['count'];

            echo '<script>
                if (processedCountriesJS.includes("' . $country . '")) {
                    console.log("Duplicate entry found for country: ' . $country . '");
                } else {
                    processedCountriesJS.push("' . $country . '");
                }
              </script>';

            if (isset($countriesWithSum[$country])) {
                $countriesWithSum[$country] += $count;
            } else {
                $countriesWithSum[$country] = $count;
            }
        }

        arsort($countriesWithSum);

        $topCountriesTime = ($end_times['topCountries'] - $start_times['topCountries']) * 1000;
        echo '<hr/><h3><i class="fas fa-globe icon-purple"></i> Top Countries by Count - Last 8 Days (API request: ' . number_format($topCountriesTime, 2) . 'ms)</h3>';

        echo '<div class="table_style1"><table border="1">';
        echo '<tr><th>Country</th><th>Count</th></tr>';

        foreach ($countriesWithSum as $country => $count) {
            echo '<tr>';
            echo '<td>' . $country . '</td>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        }

        echo '<tr><td><strong>Total</strong></td><td><strong>' . array_sum($countriesWithSum) . '</strong></td></tr>';
        echo '</table></div>';
    } else {
        echo 'No countries data available.';
    }
}

echo "<hr/><div class=\"center\"><b><i><a href=\"https://www.biblegateway.com/passage/?search=1+John+4%3A7-21&amp;version=NIV\" target=\"_blank\">God Is Love - 1 John 4:7-21</a></i></b><br /></div></div><br />";

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
echo '  width: "80%",';
echo '  height: "60%",';
echo '  bottom: 150,';
echo ' top: 40';
echo '},';
echo '};';

echo 'var chart = new google.visualization.LineChart(document.getElementById("your_div_id"));';
echo 'chart.draw(data, options);';
echo '}';
echo '</script>';
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        fetch('/network/fetch_hits.php')
            .then(response => response.json())
            .then(data => {
                console.log(data);
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
