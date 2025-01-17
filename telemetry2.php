<?php

require_once 'vendor/autoload.php';

use ApplicationInsights\Telemetry_Client;
use ApplicationInsights\Channel\Contracts\Message_Severity_Level;

class LocalTelemetry {
    private $telemetryClient;
    private $logFile;

    public function __construct($logFilePath) {
        // Initialize Application Insights TelemetryClient
        $this->telemetryClient = new Telemetry_Client();
        $this->logFile = $logFilePath;

        // Ensure log directory exists
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    public function trackEvent($eventName, $properties = []) {
        // Create log entry
        $logData = sprintf(
            "Event: %s | Properties: %s | Timestamp: %s\n",
            $eventName,
            json_encode($properties),
            date('Y-m-d H:i:s')
        );

        // Write to local log file
        file_put_contents($this->logFile, $logData, FILE_APPEND);

        // Optionally, log using Application Insights (no real transmission)
        $this->telemetryClient->trackEvent($eventName, $properties);
    }

    public function trackMetric($metricName, $metricValue) {
        // Create log entry
        $logData = sprintf(
            "Metric: %s | Value: %s | Timestamp: %s\n",
            $metricName,
            $metricValue,
            date('Y-m-d H:i:s')
        );

        // Write to local log file
        file_put_contents($this->logFile, $logData, FILE_APPEND);

        // Optionally, log using Application Insights (no real transmission)
        $this->telemetryClient->trackMetric($metricName, $metricValue);
    }

    public function trackMessage($message, $severity = Message_Severity_Level::INFORMATION) {
        // Create log entry
        $logData = sprintf(
            "Message: %s | Severity: %s | Timestamp: %s\n",
            $message,
            $severity,
            date('Y-m-d H:i:s')
        );

        // Write to local log file
        file_put_contents($this->logFile, $logData, FILE_APPEND);

        // Optionally, log using Application Insights (no real transmission)
        $this->telemetryClient->trackMessage($message, $severity);
    }
}

// Example Usage
$logFile = __DIR__ . '/logs/telemetry.log';
$telemetry = new LocalTelemetry($logFile);

// Track an event
$telemetry->trackEvent('UserLoggedIn', ['username' => 'muneeb']);

// Track a metric
$telemetry->trackMetric('PageLoadTime', 1.23);

// Track a custom message
$telemetry->trackMessage('Application started successfully.', Message_Severity_Level::INFORMATION);
?>

