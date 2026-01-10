# atoship PHP SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/github/workflow/status/atoship/sdk-php/CI)](https://github.com/atoship/sdk-php/actions)

Official PHP SDK for the atoship API. Provides a comprehensive, type-safe interface for all atoship shipping and logistics operations.

## Features

- 🚀 **Comprehensive API Coverage** - Support for all atoship API endpoints
- 🔒 **Type Safety** - Full type hints and strict typing throughout
- 🛡️ **Robust Error Handling** - Custom exceptions with detailed error information
- 🔄 **Automatic Retries** - Configurable retry logic for transient failures
- 📝 **Input Validation** - Comprehensive request validation using Symfony Validator
- 🧪 **Well Tested** - Extensive test suite with high code coverage
- 📚 **PSR Compliant** - Follows PSR-4, PSR-7, PSR-12 standards
- 🔧 **Flexible Configuration** - Builder pattern for easy configuration

## Installation

Install the SDK using Composer:

```bash
composer require atoship/php-sdk
```

## Requirements

- PHP 7.4 or higher
- ext-json
- ext-curl
- Guzzle HTTP client

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use atoship\SDK\atoshipSDK;

// Initialize the SDK
$sdk = new atoshipSDK('your-api-key');

// Create an order
$orderData = [
    'orderNumber' => 'ORDER-001',
    'recipientName' => 'John Doe',
    'recipientStreet1' => '123 Main St',
    'recipientCity' => 'San Francisco',
    'recipientState' => 'CA',
    'recipientPostalCode' => '94105',
    'recipientCountry' => 'US',
    'recipientPhone' => '415-555-0123',
    'recipientEmail' => 'john@example.com',
    'items' => [
        [
            'name' => 'Product Name',
            'sku' => 'PROD-001',
            'quantity' => 1,
            'unitPrice' => 29.99,
            'weight' => 2.0,
            'weightUnit' => 'lb'
        ]
    ]
];

try {
    $response = $sdk->createOrder($orderData);
    
    if ($response->isSuccess()) {
        $order = $response->getData();
        echo "Order created: " . $order->getId() . PHP_EOL;
    } else {
        echo "Error: " . $response->getError() . PHP_EOL;
    }
} catch (\atoship\SDK\Exception\atoshipException $e) {
    echo "SDK Error: " . $e->getMessage() . PHP_EOL;
}
```

## Configuration

### Basic Configuration

```php
// Simple configuration with API key
$sdk = new atoshipSDK('your-api-key');
```

### Advanced Configuration

```php
use atoship\SDK\Config\Configuration;

// Using configuration builder
$config = Configuration::builder()
    ->apiKey('your-api-key')
    ->baseUrl('https://api.atoship.com')  // Custom base URL
    ->timeout(30.0)                       // Request timeout in seconds
    ->maxRetries(3)                       // Maximum retry attempts
    ->debug(true)                         // Enable debug logging
    ->userAgent('MyApp/1.0.0')           // Custom user agent
    ->verifySSL(true)                    // SSL verification
    ->build();

$sdk = new atoshipSDK($config);

// Or using array options
$sdk = new atoshipSDK('your-api-key', [
    'baseUrl' => 'https://api.atoship.com',
    'timeout' => 30.0,
    'maxRetries' => 3,
    'debug' => true,
    'userAgent' => 'MyApp/1.0.0',
    'verifySSL' => true
]);
```

## Core Features

### Order Management

```php
// Create a single order
$orderData = [
    'orderNumber' => 'ORDER-001',
    'recipientName' => 'John Doe',
    'recipientStreet1' => '123 Main St',
    'recipientCity' => 'San Francisco',
    'recipientState' => 'CA',
    'recipientPostalCode' => '94105',
    'recipientCountry' => 'US',
    'recipientEmail' => 'john@example.com',
    'items' => [
        [
            'name' => 'Widget',
            'sku' => 'WIDGET-001',
            'quantity' => 1,
            'unitPrice' => 19.99,
            'weight' => 1.5,
            'weightUnit' => 'lb'
        ]
    ],
    'notes' => 'Handle with care',
    'tags' => ['fragile', 'electronics']
];

$response = $sdk->createOrder($orderData);

// List orders with filtering
$orders = $sdk->listOrders([
    'page' => 1,
    'limit' => 50,
    'status' => 'PENDING',
    'dateFrom' => '2024-01-01',
    'dateTo' => '2024-01-31'
]);

// Get specific order
$order = $sdk->getOrder('order-id');

// Update order
$updatedOrder = $sdk->updateOrder('order-id', [
    'notes' => 'Updated notes'
]);

// Delete order
$sdk->deleteOrder('order-id');

// Create multiple orders in batch
$orders = [
    $orderData1,
    $orderData2,
    $orderData3
];

$batchResponse = $sdk->createOrdersBatch($orders);
```

### Shipping Rates

```php
// Get shipping rates
$rateRequest = [
    'fromAddress' => [
        'name' => 'Warehouse',
        'street1' => '123 Warehouse St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postalCode' => '94105',
        'country' => 'US'
    ],
    'toAddress' => [
        'name' => 'Customer',
        'street1' => '456 Customer Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postalCode' => '90001',
        'country' => 'US'
    ],
    'package' => [
        'weight' => 2.5,
        'length' => 12.0,
        'width' => 8.0,
        'height' => 4.0,
        'weightUnit' => 'lb',
        'dimensionUnit' => 'in'
    ],
    'options' => [
        'signature' => false,
        'insurance' => true,
        'insuranceValue' => 100.00
    ]
];

$rates = $sdk->getRates($rateRequest);

// Compare rates from multiple carriers
$comparedRates = $sdk->compareRates($rateRequest);

if ($rates->isSuccess()) {
    foreach ($rates->getData() as $rate) {
        echo sprintf(
            "%s %s: $%.2f (%d days)\n",
            $rate->getCarrier(),
            $rate->getServiceName(),
            $rate->getAmount(),
            $rate->getEstimatedDays()
        );
    }
}
```

### Label Management

```php
// Purchase shipping label
$labelRequest = [
    'orderId' => 'order-id',
    'rateId' => 'rate-id',
    'fromAddress' => $fromAddress,
    'toAddress' => $toAddress,
    'package' => $packageInfo,
    'options' => [
        'signature' => false,
        'insurance' => true,
        'insuranceValue' => 100.00
    ]
];

$label = $sdk->purchaseLabel($labelRequest);

// List labels with filtering
$labels = $sdk->listLabels([
    'page' => 1,
    'limit' => 50,
    'status' => 'PURCHASED',
    'carrier' => 'USPS',
    'dateFrom' => '2024-01-01'
]);

// Get label details
$labelDetails = $sdk->getLabel('label-id');

// Cancel label
$cancelResult = $sdk->cancelLabel('label-id');

// Request refund
$refundResult = $sdk->refundLabel('label-id');

if ($label->isSuccess()) {
    $labelData = $label->getData();
    echo "Label purchased successfully!\n";
    echo "Tracking Number: " . $labelData->getTrackingNumber() . "\n";
    echo "Label URL: " . $labelData->getLabelUrl() . "\n";
    echo "Cost: $" . number_format($labelData->getCost(), 2) . "\n";
}
```

### Package Tracking

```php
// Track single package
$tracking = $sdk->trackPackage('1Z999AA1234567890');

// Track with specific carrier
$trackingWithCarrier = $sdk->trackPackage('1Z999AA1234567890', 'UPS');

// Track multiple packages
$trackingNumbers = [
    '1Z999AA1234567890',
    '9400111206213123456789',
    '1234567890'
];

$batchTracking = $sdk->trackMultiple($trackingNumbers);

if ($tracking->isSuccess()) {
    $trackingData = $tracking->getData();
    echo "Status: " . $trackingData->getStatus() . "\n";
    echo "Carrier: " . $trackingData->getCarrier() . "\n";
    
    foreach ($trackingData->getEvents() as $event) {
        echo sprintf(
            "%s: %s (%s)\n",
            $event->getTimestamp(),
            $event->getDescription(),
            $event->getLocation() ?: 'Unknown'
        );
    }
}
```

### Address Management

```php
// Validate address
$addressData = [
    'name' => 'John Doe',
    'street1' => '123 Main St',
    'city' => 'San Francisco',
    'state' => 'CA',
    'postalCode' => '94105',
    'country' => 'US'
];

$validation = $sdk->validateAddress($addressData);

// Get address suggestions
$suggestions = $sdk->suggestAddresses('123 Main St, San Francisco', 'US');

// Save address to address book
$savedAddress = $sdk->saveAddress([
    'name' => 'John Doe',
    'street1' => '123 Main St',
    'city' => 'San Francisco',
    'state' => 'CA',
    'postalCode' => '94105',
    'country' => 'US',
    'type' => 'SHIPPING',
    'isDefault' => true
]);

// List addresses
$addresses = $sdk->listAddresses(['page' => 1, 'limit' => 20]);

// Get specific address
$address = $sdk->getAddress('address-id');

// Update address
$updatedAddress = $sdk->updateAddress('address-id', [
    'phone' => '415-555-0123'
]);

// Delete address
$sdk->deleteAddress('address-id');
```

### Webhook Management

```php
// Create webhook
$webhookData = [
    'url' => 'https://your-site.com/webhook',
    'events' => ['order.created', 'label.purchased', 'package.delivered'],
    'secret' => 'your-webhook-secret',
    'active' => true
];

$webhook = $sdk->createWebhook($webhookData);

// List webhooks
$webhooks = $sdk->listWebhooks();

// Get webhook
$webhookDetails = $sdk->getWebhook('webhook-id');

// Update webhook
$updatedWebhook = $sdk->updateWebhook('webhook-id', [
    'active' => false
]);

// Delete webhook
$sdk->deleteWebhook('webhook-id');

// Test webhook
$testResult = $sdk->testWebhook('webhook-id');
```

## Error Handling

The SDK provides comprehensive error handling with custom exceptions:

```php
use atoship\SDK\Exception\{
    atoshipException,
    ValidationException,
    AuthenticationException,
    AuthorizationException,
    NotFoundException,
    RateLimitException,
    ServerException,
    NetworkException,
    ConfigurationException
};

try {
    $response = $sdk->createOrder($orderData);
    
    if (!$response->isSuccess()) {
        echo "API Error: " . $response->getError() . "\n";
        echo "Request ID: " . $response->getRequestId() . "\n";
    }
    
} catch (ValidationException $e) {
    echo "Validation failed: " . $e->getMessage() . "\n";
    if ($e->hasDetails()) {
        print_r($e->getDetails());
    }
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage() . "\n";
} catch (AuthorizationException $e) {
    echo "Authorization failed: " . $e->getMessage() . "\n";
} catch (NotFoundException $e) {
    echo "Resource not found: " . $e->getMessage() . "\n";
} catch (RateLimitException $e) {
    echo "Rate limit exceeded: " . $e->getMessage() . "\n";
    echo "Retry after: " . $e->getRetryAfter() . " seconds\n";
} catch (ServerException $e) {
    echo "Server error: " . $e->getMessage() . "\n";
    echo "Status code: " . $e->getStatusCode() . "\n";
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage() . "\n";
} catch (ConfigurationException $e) {
    echo "Configuration error: " . $e->getMessage() . "\n";
} catch (atoshipException $e) {
    echo "General SDK error: " . $e->getMessage() . "\n";
}
```

## Response Handling

All SDK methods return standardized response objects:

```php
// Standard API Response
$response = $sdk->getOrder('order-id');

if ($response->isSuccess()) {
    $order = $response->getData();
    echo "Order ID: " . $order->getId() . "\n";
    echo "Status: " . $order->getStatus() . "\n";
} else {
    echo "Error: " . $response->getError() . "\n";
    echo "Request ID: " . $response->getRequestId() . "\n";
}

// Paginated Response (for list operations)
$ordersResponse = $sdk->listOrders(['page' => 1, 'limit' => 50]);

if ($ordersResponse->isSuccess()) {
    $orders = $ordersResponse->getItems();
    $total = $ordersResponse->getTotal();
    $hasMore = $ordersResponse->hasMore();
    
    echo "Found {$total} orders, showing " . count($orders) . "\n";
    
    foreach ($orders as $order) {
        echo "- " . $order->getOrderNumber() . " (" . $order->getStatus() . ")\n";
    }
    
    if ($hasMore) {
        echo "There are more results available\n";
    }
}
```

## Input Validation

The SDK performs comprehensive validation using Symfony Validator:

```php
// Order validation includes:
// - Required fields checking
// - Email format validation
// - Phone number validation
// - Postal code validation by country
// - Item quantity and pricing validation
// - Weight and dimension validation

try {
    $response = $sdk->createOrder($orderData);
} catch (ValidationException $e) {
    echo "Validation failed: " . $e->getMessage() . "\n";
    
    // Get detailed validation errors
    $errors = $e->getDetails();
    foreach ($errors as $field => $messages) {
        echo "Field '{$field}': " . implode(', ', $messages) . "\n";
    }
}
```

## Advanced Usage

### Environment Configuration

```php
// Load configuration from environment variables
$apiKey = $_ENV['ATOSHIP_API_KEY'] ?? getenv('ATOSHIP_API_KEY');
$baseUrl = $_ENV['ATOSHIP_BASE_URL'] ?? 'https://api.atoship.com';

$config = Configuration::builder()
    ->apiKey($apiKey)
    ->baseUrl($baseUrl)
    ->debug($_ENV['APP_DEBUG'] ?? false)
    ->build();

$sdk = new atoshipSDK($config);
```

### Custom HTTP Client Configuration

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

// Create custom Guzzle client with middleware
$stack = HandlerStack::create();

// Add logging middleware
$stack->push(Middleware::log(
    $logger,
    new \GuzzleHttp\MessageFormatter('{method} {uri} - {code} {phrase}')
));

$httpClient = new Client([
    'handler' => $stack,
    'timeout' => 30,
    'verify' => true
]);

// Note: Custom HTTP client configuration would be passed through configuration
```

### Batch Operations Example

```php
// Process large number of orders efficiently
$orders = []; // Array of order data

// Split into batches of 100 (API limit)
$batches = array_chunk($orders, 100);

foreach ($batches as $batchIndex => $batch) {
    try {
        $response = $sdk->createOrdersBatch($batch);
        
        if ($response->isSuccess()) {
            $results = $response->getData();
            echo "Batch {$batchIndex}: ";
            echo count($results['successful']) . " successful, ";
            echo count($results['failed']) . " failed\n";
            
            // Process failed orders
            foreach ($results['failed'] as $failed) {
                echo "Failed order {$failed['orderNumber']}: {$failed['error']}\n";
            }
        }
    } catch (atoshipException $e) {
        echo "Batch {$batchIndex} failed: " . $e->getMessage() . "\n";
    }
    
    // Rate limiting: wait between batches
    usleep(100000); // 100ms delay
}
```

### Async Processing with ReactPHP

```php
use React\Http\Browser;
use React\Socket\Connector;

// Note: This is a conceptual example
// Actual async implementation would require additional setup

$loop = \React\EventLoop\Factory::create();
$connector = new Connector($loop);
$browser = new Browser($loop, $connector);

// Async order processing
$promises = [];
foreach ($orderDataArray as $orderData) {
    $promises[] = $browser->post(
        'https://api.atoship.com/api/orders',
        [
            'X-API-Key' => 'your-api-key',
            'Content-Type' => 'application/json'
        ],
        json_encode($orderData)
    );
}

\React\Promise\all($promises)->then(function ($responses) {
    foreach ($responses as $response) {
        echo "Response: " . $response->getBody() . "\n";
    }
});

$loop->run();
```

## Best Practices

### Resource Management

```php
// Always handle resources properly
try {
    $sdk = new atoshipSDK('your-api-key');
    
    // Use SDK
    $response = $sdk->createOrder($orderData);
    
} catch (atoshipException $e) {
    // Handle errors
    error_log('atoship SDK Error: ' . $e->getMessage());
} finally {
    // Cleanup if needed
    unset($sdk);
}
```

### Configuration Management

```php
// Use environment-based configuration
class atoshipConfig 
{
    public static function fromEnvironment(): Configuration
    {
        $apiKey = $_ENV['ATOSHIP_API_KEY'] ?? throw new \RuntimeException('API key not set');
        
        return Configuration::builder()
            ->apiKey($apiKey)
            ->baseUrl($_ENV['ATOSHIP_BASE_URL'] ?? 'https://api.atoship.com')
            ->timeout((float)($_ENV['ATOSHIP_TIMEOUT'] ?? 30.0))
            ->maxRetries((int)($_ENV['ATOSHIP_MAX_RETRIES'] ?? 3))
            ->debug(filter_var($_ENV['ATOSHIP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->build();
    }
}

$sdk = new atoshipSDK(atoshipConfig::fromEnvironment());
```

### Error Logging

```php
use Psr\Log\LoggerInterface;

class atoshipService
{
    private atoshipSDK $sdk;
    private LoggerInterface $logger;
    
    public function __construct(atoshipSDK $sdk, LoggerInterface $logger)
    {
        $this->sdk = $sdk;
        $this->logger = $logger;
    }
    
    public function createOrder(array $orderData): ?Order
    {
        try {
            $response = $this->sdk->createOrder($orderData);
            
            if ($response->isSuccess()) {
                $this->logger->info('Order created successfully', [
                    'orderId' => $response->getData()->getId(),
                    'orderNumber' => $orderData['orderNumber']
                ]);
                
                return $response->getData();
            } else {
                $this->logger->error('Order creation failed', [
                    'error' => $response->getError(),
                    'requestId' => $response->getRequestId(),
                    'orderNumber' => $orderData['orderNumber']
                ]);
            }
        } catch (atoshipException $e) {
            $this->logger->error('atoship SDK exception', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'orderNumber' => $orderData['orderNumber']
            ]);
        }
        
        return null;
    }
}
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer phpstan

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run all quality checks
composer quality
```

### Writing Tests

```php
use PHPUnit\Framework\TestCase;
use atoship\SDK\atoshipSDK;
use atoship\SDK\Config\Configuration;

class atoshipSDKTest extends TestCase
{
    private atoshipSDK $sdk;
    
    protected function setUp(): void
    {
        $config = Configuration::builder()
            ->apiKey('test-api-key')
            ->baseUrl('https://api.test.atoship.com')
            ->build();
            
        $this->sdk = new atoshipSDK($config);
    }
    
    public function testCreateOrder(): void
    {
        $orderData = [
            'orderNumber' => 'TEST-001',
            'recipientName' => 'Test User',
            // ... other required fields
        ];
        
        $response = $this->sdk->createOrder($orderData);
        
        $this->assertTrue($response->isSuccess());
        $this->assertNotNull($response->getData());
    }
}
```

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make your changes
4. Run tests: `composer quality`
5. Commit your changes: `git commit -am 'Add new feature'`
6. Push to the branch: `git push origin feature/new-feature`
7. Submit a pull request

## Support

- 📧 Email: support@atoship.com
- 📚 Documentation: https://atoship.com/docs
- 🐛 Issues: https://github.com/atoship/sdk-php/issues
- 💬 Community: https://community.atoship.com

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.