<?php
require_once 'config.php';

header('Content-Type: application/json');

$news_api_key = getenv('NEWS_API_KEY');
if (!$news_api_key) {
    echo json_encode(['status' => 'error', 'message' => 'API Key Missing']);
    exit;
}

$logs_dir = ROOT_PATH . 'logs';
$cache_file = $logs_dir . '/news_cache.json';
$cache_time = 3600;

if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

$cache_file = ROOT_PATH . 'logs/news_cache.json';
$cache_time = 3600;

// 1. Check Cache First
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    echo file_get_contents($cache_file);
    exit;
}

// 2. If no cache or expired, Fetch from NewsAPI
$query = urlencode('enterprise risk management OR financial crime compliance');
$url = "https://newsapi.org/v2/everything?q=$query&sortBy=publishedAt&pageSize=4&language=en&apiKey=$news_api_key";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'ERM-Institute-App/1.0');
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    file_put_contents($cache_file, $response);
    echo $response;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Fetch Failed']);
}