<?php
$endpoints = [
    "http://localhost:8000/webpinjamft/public/",
    "http://localhost:8000/webpinjamft/public/rooms",
    "http://localhost:8000/webpinjamft/public/room/1",
    "http://localhost:8000/webpinjamft/public/booking/create",
    "http://localhost:8000/webpinjamft/public/free",
    "http://localhost:8000/webpinjamft/public/search?q=Lab",
    "http://localhost:8000/webpinjamft/public/api/rooms"
];

foreach ($endpoints as $url) {
    $ctx = stream_context_create(["http" => ["ignore_errors" => true, "timeout" => 5]]);
    $resp = @file_get_contents($url, false, $ctx);
    $status = $http_response_header[0] ?? "NO RESPONSE";
    echo "$status | $url\n";
    if (strpos($url, "api/search") !== false) {
        echo "   Body: " . substr($resp, 0, 150) . "...\n";
    }
}
