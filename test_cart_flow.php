<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'http://127.0.0.1:8000/api';

echo "1. Attempting login...\n";
// Create a client directly calling the app to avoid external network issues if possible, 
// but asking to run via php command is easier.
// actually let's use CURL or just php stream context to hit the running server.
// Since the server is running on port 8000.

function makeRequest($method, $url, $data = [], $token = null) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n" .
                         "Accept: application/json\r\n" .
                         ($token ? "Authorization: Bearer $token\r\n" : ""),
            'method'  => $method,
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return [$http_response_header, $result];
}

$loginUrl = $baseUrl . '/customer/login';
list($headers, $result) = makeRequest('POST', $loginUrl, [
    'email' => 'test@example.com',
    'password' => 'password'
]);

$response = json_decode($result, true);

if (!isset($response['token'])) {
    echo "Login failed. Registering new user...\n";
    $registerUrl = $baseUrl . '/customer/register';
    $email = 'test' . time() . '@example.com';
    list($headers, $result) = makeRequest('POST', $registerUrl, [
        'name' => 'Tester',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password'
    ]);
    $response = json_decode($result, true);
    if (!isset($response['token'])) {
        echo "Registration failed:\n";
        print_r($response);
        exit(1);
    }
    echo "Registered as $email\n";
} else {
    echo "Logged in as test@example.com\n";
}

$token = $response['token'];
echo "Token obtained.\n\n";

echo "2. Adding item to cart...\n";
$cartUrl = $baseUrl . '/carts';
// Get a product ID first? Assuming product 1 exists.
// Let's check DB first using Eloquent to be safe.
$product = \App\Models\Product::first();
if (!$product) {
    echo "No products found in DB. Creating one via factory...\n";
    // We can't easily use factory here without setting up app context fully which we did technically.
    // Let's just create one manually using Eloquent.
    $product = \App\Models\Product::create([
         'name' => 'Test Product',
         'description' => 'Test Desc',
         'price' => 100,
         'stock' => 50,
         // other fields? 'category_id', 'sku', 'image'
         'sku' => 'SKU'.time(),
         'is_active' => true,
    ]);
    // It might fail if category_id needed.
}
$productId = $product->id;
echo "Using Product ID: $productId\n";

list($headers, $result) = makeRequest('POST', $cartUrl, [
    'product_id' => $productId,
    'quantity' => 1
], $token);

echo "Response headers: \n";
print_r($headers[0]); 
echo "\nResponse body: \n";
echo $result . "\n";
