<?php

$routesJson = file_get_contents('routes.json');

// Strip UTF-8 BOM if present
$routesJson = preg_replace('/^\xEF\xBB\xBF/', '', $routesJson);

// Check for UTF-16LE BOM (approximate check, or just convert encoding)
if (substr($routesJson, 0, 2) === "\xFF\xFE") {
    $routesJson = mb_convert_encoding($routesJson, 'UTF-8', 'UTF-16LE');
}

$routes = json_decode($routesJson, true);

if (!$routes) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "First 100 chars: " . substr($routesJson, 0, 100) . "\n";
    die("Failed to decode routes.json");
}

$collection = [
    'info' => [
        'name' => 'Biji Coffee API',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'item' => [],
    'variable' => [
        [
            'key' => 'base_url',
            'value' => 'http://localhost:8001',
            'type' => 'string'
        ]
    ]
];

$items = [];

foreach ($routes as $route) {
    // Skip ignition, sanctum/csrf-cookie (unless relevant), and non-api routes if desired.
    // User asked for "all endpoints", but let's prioritize "api" or key routes.
    // For now, let's include anything that doesn't start with '_' and isn't a closure if possible (though closure routes are fine).

    if (str_contains($route['uri'], '_ignition')) {
        continue;
    }

    $method = explode('|', $route['method'])[0]; // Take first method (e.g. GET from GET|HEAD)
    if ($method === 'HEAD')
        continue;

    $uri = $route['uri'];
    // Postman URL format
    $urlObj = [
        'raw' => '{{base_url}}/' . ltrim($uri, '/'),
        'host' => ['{{base_url}}'],
        'path' => explode('/', $uri)
    ];

    $name = $route['uri'];
    if (isset($route['name'])) {
        $name = $route['name'];
    }

    $item = [
        'name' => $name,
        'request' => [
            'method' => $method,
            'header' => [
                [
                    'key' => 'Accept',
                    'value' => 'application/json'
                ]
            ],
            'url' => $urlObj
        ]
    ];

    // Add body for POST/PUT/PATCH
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $item['request']['body'] = [
            'mode' => 'raw',
            'raw' => "{\n    \n}",
            'options' => [
                'raw' => [
                    'language' => 'json'
                ]
            ]
        ];
    }

    // Grouping logic: First segment
    $parts = explode('/', $uri);
    $group = $parts[0];
    if ($group === 'api' && isset($parts[1])) {
        $group = $parts[1];
    }

    // Clean up group name (remove parameters like {user})
    $group = preg_replace('/\{.*\}/', 'Parameters', $group);

    if (!isset($items[$group])) {
        $items[$group] = [
            'name' => ucfirst($group),
            'item' => []
        ];
    }

    $items[$group]['item'][] = $item;
}

// Flatten items into collection
foreach ($items as $groupItem) {
    $collection['item'][] = $groupItem;
}

file_put_contents('postman_collection.json', json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Postman collection generated with " . count($routes) . " routes processed.\n";
