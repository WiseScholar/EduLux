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
$cache_time = 900; 

if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// 1. FORCE REFRESH
$force_refresh = isset($_GET['cache_bust']);

if (!$force_refresh && file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    echo file_get_contents($cache_file);
    exit;
}

// 2. SMART QUERY: Focus on Business/Finance for fresh daily volatility
$query = urlencode('finance risk OR banking compliance OR global economy');
$url = "https://newsapi.org/v2/everything?q=$query&sortBy=publishedAt&pageSize=10&language=en&apiKey=$news_api_key";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'ERM-Institute-App/1.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && $response) {
    $data = json_decode($response, true);

    if (isset($data['articles'])) {
        $filtered = array_filter($data['articles'], function($art) {
            return !empty($art['urlToImage']) && !empty($art['title']);
        });
        $data['articles'] = array_values($filtered);
        $response = json_encode($data);
    }

    file_put_contents($cache_file, $response);
    echo $response;
} else {

    if (file_exists($cache_file)) {
        echo file_get_contents($cache_file);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Intelligence Feed Offline']);
    }
}