<?php

// Replace with your Instrumentation Key
$instrumentationKey = 'b6ea18cc-cc84-4137-aca8-e21944199f67'; // Your Azure Instrumentation Key

// Function to send telemetry to Application Insights
function sendTelemetry($type, $data) {
    global $instrumentationKey;

    // Create the JSON payload
    $payload = json_encode([
        'name' => $type,
        'time' => date('c'),
        'iKey' => $instrumentationKey,
        'data' => $data,
    ]);

    // Send the data via cURL
    $ch = curl_init('https://dc.services.visualstudio.com/v2/track');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    echo "Telemetry sent: $response\n";
}

// Log CPU and memory usage
function logCpuAndMemoryUsage($pid) {
    if (file_exists("/proc/$pid/status")) {
        $status = file_get_contents("/proc/$pid/status");

        // Extract memory usage
        preg_match('/VmRSS:\s+(\d+)\s+kB/', $status, $memoryMatches);
        $memoryUsage = $memoryMatches[1] ?? 'Unknown';

        // Extract CPU usage
        $cpuUsage = shell_exec("ps -p $pid -o %cpu --no-headers");
        $cpuUsage = trim($cpuUsage) ?: 'Unknown';

        // Prepare data
        $data = [
            'baseType' => 'MetricData',
            'baseData' => [
                'metrics' => [
                    ['name' => 'CPU Usage', 'value' => (float)$cpuUsage],
                    ['name' => 'Memory Usage', 'value' => (int)$memoryUsage],
                ],
                'properties' => [
                    'Process ID' => $pid,
                ],
            ],
        ];

        sendTelemetry('Microsoft.ApplicationInsights.Metric', $data);
    } else {
        echo "Process with PID $pid not found.\n";
    }
}

// Log network statistics
function logNetworkUsage() {
    $networkStats = shell_exec("ifconfig eth0 | grep 'RX packets\\|TX packets'");
    preg_match('/RX packets (\d+)/', $networkStats, $rxMatches);
    preg_match('/TX packets (\d+)/', $networkStats, $txMatches);

    $rxPackets = $rxMatches[1] ?? 0;
    $txPackets = $txMatches[1] ?? 0;

    $data = [
        'baseType' => 'MetricData',
        'baseData' => [
            'metrics' => [
                ['name' => 'Received Packets', 'value' => (int)$rxPackets],
                ['name' => 'Transmitted Packets', 'value' => (int)$txPackets],
            ],
        ],
    ];

    sendTelemetry('Microsoft.ApplicationInsights.Metric', $data);
}

// Log response time
function logResponseTime($url) {
    $startTime = microtime(true);

    // Make a request to the URL
    file_get_contents($url);

    $endTime = microtime(true);
    $responseTime = $endTime - $startTime;

    $data = [
        'baseType' => 'RequestData',
        'baseData' => [
            'id' => uniqid(),
            'name' => 'Response Time Test',
            'duration' => sprintf('00:00:%06.3f', $responseTime),
            'responseCode' => 200,
            'success' => true,
            'url' => $url,
        ],
    ];

    sendTelemetry('Microsoft.ApplicationInsights.Request', $data);
}

// Log availability
function logAvailability($url) {
    $startTime = microtime(true);

    $headers = @get_headers($url);
    $responseCode = $headers ? intval(substr($headers[0], 9, 3)) : 0;
    $endTime = microtime(true);

    $responseTime = $endTime - $startTime;
    $isAvailable = ($responseCode >= 200 && $responseCode < 300);

    $data = [
        'baseType' => 'AvailabilityData',
        'baseData' => [
            'id' => uniqid(),
            'name' => 'Availability Test',
            'duration' => sprintf('00:00:%06.3f', $responseTime),
            'success' => $isAvailable,
            'runLocation' => 'Server',
            'message' => $isAvailable ? 'Available' : 'Unavailable',
            'url' => $url,
        ],
    ];

    sendTelemetry('Microsoft.ApplicationInsights.Availability', $data);

    echo $isAvailable ? "URL is available.\n" : "URL is unavailable.\n";
}

// Main telemetry execution
$pid = 1388; // Replace with your process PID
$testUrl = 'http://example.com'; // Replace with your application URL

echo "Starting telemetry...\n";

// Capture various metrics
logCpuAndMemoryUsage($pid);
logNetworkUsage();
logResponseTime($testUrl);
logAvailability($testUrl);

echo "Telemetry completed.\n";

?>

