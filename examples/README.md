# atoship PHP SDK Examples

This directory contains comprehensive examples demonstrating the usage of the atoship PHP SDK for shipping and logistics operations using modern PHP practices and PSR standards.

## Examples Overview

### 1. Basic Example (`basic_example.php`)
Demonstrates fundamental SDK operations:
- ✅ SDK initialization and configuration with builder pattern
- ✅ Creating orders with detailed item information
- ✅ Getting shipping rates from multiple carriers
- ✅ Purchasing shipping labels
- ✅ Package tracking and status monitoring
- ✅ Address validation
- ✅ Comprehensive error handling
- ✅ PSR-compliant code structure

### 2. Advanced Example (`advanced_example.php`)
Showcases enterprise-level features:
- ✅ CSV batch processing with memory optimization
- ✅ Rate optimization strategies
- ✅ Performance monitoring and analytics
- ✅ Retry logic and error recovery
- ✅ Comprehensive reporting
- ✅ Memory-efficient chunk processing
- ✅ PSR-compliant architecture

## Prerequisites

- PHP 7.4 or higher
- Composer package manager
- atoship API key
- Required PHP extensions: `curl`, `json`, `mbstring`

## Installation

1. **Install the SDK and dependencies:**
   ```bash
   composer install
   ```

2. **Set up environment variables:**
   ```bash
   export ATOSHIP_API_KEY='your-api-key-here'
   export ATOSHIP_BASE_URL='https://api.atoship.com'  # Optional
   export ENVIRONMENT='development'  # Optional
   ```

   Or create a `.env` file:
   ```env
   ATOSHIP_API_KEY=your-api-key-here
   ATOSHIP_BASE_URL=https://api.atoship.com
   ENVIRONMENT=development
   ```

## Running the Examples

### Basic Example

```bash
# Run the basic example
php basic_example.php

# Show help
php basic_example.php --help
```

**Example Output:**
```
🚀 atoship PHP SDK Basic Example

📦 Step 1: Creating an order...
✅ Order created successfully!
   Order ID: ord_1234567890
   Order Number: PHP-ORDER-1640995200
   Status: PENDING
   Total Value: $74.94
   Items Count: 2

💰 Step 2: Getting shipping rates...
✅ Shipping rates retrieved successfully!
   1. USPS Priority Mail
      Price: $8.95
      Estimated Days: 2
      Delivery Date: 2024-01-15
      Zone: 4
   2. FedEx Ground
      Price: $12.45
      Estimated Days: 3
      Delivery Date: 2024-01-16

🏷️ Step 3: Purchasing shipping label...
📋 Selected Rate: USPS Priority Mail - $8.95

✅ Shipping label purchased successfully!
   Label ID: lbl_9876543210
   Tracking Number: 9400111206213123456789
   Carrier: USPS
   Service: Priority Mail
   Cost: $8.95
   Label URL: https://labels.atoship.com/...

🎉 Basic example completed successfully!
```

### Advanced Example

```bash
# Process orders from CSV file
php advanced_example.php --csv orders.csv --strategy balanced

# Run demo with sample data
php advanced_example.php --demo --strategy cost

# Show help
php advanced_example.php --help
```

**Advanced Features:**

1. **CSV Processing:**
   ```bash
   php advanced_example.php --csv sample_orders.csv --strategy balanced
   ```

2. **Rate Optimization Strategies:**
   - `cost` - Minimize shipping costs
   - `speed` - Minimize delivery time
   - `balanced` - Balance cost and speed
   - `premium` - Prefer reliable carriers

3. **Demo Mode:**
   ```bash
   php advanced_example.php --demo --strategy premium
   ```

## CSV Format

Create a CSV file with the following columns for batch processing:

```csv
order_number,recipient_name,recipient_email,recipient_phone,recipient_address1,recipient_address2,recipient_city,recipient_state,recipient_postal_code,recipient_country,sender_name,sender_address1,sender_city,sender_state,sender_postal_code,sender_country,item_name,item_sku,item_quantity,item_price,item_weight,item_weight_unit,shipping_strategy,notes,tags,priority
ORDER-001,John Doe,john@example.com,555-123-4567,123 Main St,Apt 4B,San Francisco,CA,94105,US,PHP Store,456 Business Ave,Los Angeles,CA,90001,US,PHP Book,BOOK-001,1,54.99,1.3,lb,balanced,Educational material,books;education,standard
```

**Required Fields:**
- `recipient_name`
- `recipient_address1`
- `recipient_city`
- `recipient_state`
- `recipient_postal_code`

**Optional Fields:**
- `order_number` (auto-generated if not provided)
- `recipient_email`
- `recipient_phone`
- `recipient_address2`
- `recipient_country` (defaults to 'US')
- Sender information (defaults provided)
- Item details (defaults provided)
- `shipping_strategy` (defaults to 'balanced')
- `priority` (defaults to 'standard')
- `notes`
- `tags`

## Key Features Demonstrated

### 1. SDK Initialization with Configuration Builder

```php
use atoship\SDK\atoshipSDK;
use atoship\SDK\Config\Configuration;

$config = Configuration::builder()
    ->apiKey('your-api-key')
    ->baseUrl('https://api.atoship.com')
    ->timeout(30.0)
    ->maxRetries(3)
    ->debug(true)
    ->userAgent('MyApp/1.0.0')
    ->build();

$sdk = new atoshipSDK($config);
```

### 2. Order Creation with Comprehensive Data

```php
$orderData = [
    'orderNumber' => 'PHP-ORDER-' . time(),
    'recipientName' => 'Bob Smith',
    'recipientEmail' => 'bob@example.com',
    'recipientPhone' => '555-987-6543',
    'recipientStreet1' => '456 PHP Street',
    'recipientCity' => 'Seattle',
    'recipientState' => 'WA',
    'recipientPostalCode' => '98101',
    'recipientCountry' => 'US',
    'items' => [
        [
            'name' => 'PHP Programming Guide',
            'sku' => 'BOOK-PHP-001',
            'quantity' => 1,
            'unitPrice' => 54.99,
            'weight' => 1.3,
            'weightUnit' => 'lb',
            'dimensions' => [
                'length' => 9.0,
                'width' => 7.0,
                'height' => 1.8,
                'unit' => 'in'
            ]
        ]
    ],
    'tags' => ['books', 'education', 'php'],
    'customFields' => [
        'customerType' => 'developer',
        'language' => 'php'
    ]
];

$response = $sdk->createOrder($orderData);
```

### 3. Rate Shopping and Optimization

```php
$rateRequest = [
    'fromAddress' => [
        'street1' => '123 Sender St',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postalCode' => '90001',
        'country' => 'US'
    ],
    'toAddress' => [
        'street1' => '456 Recipient Ave',
        'city' => 'Seattle',
        'state' => 'WA',
        'postalCode' => '98101',
        'country' => 'US'
    ],
    'package' => [
        'weight' => 2.0,
        'length' => 12.0,
        'width' => 9.0,
        'height' => 6.0,
        'weightUnit' => 'lb',
        'dimensionUnit' => 'in'
    ],
    'options' => [
        'signature' => false,
        'insurance' => true,
        'insuranceValue' => 100.0
    ]
];

$response = $sdk->getRates($rateRequest);
```

### 4. Label Purchasing

```php
$labelRequest = [
    'orderId' => $order->getId(),
    'rateId' => $selectedRate->getId(),
    'fromAddress' => $fromAddress,
    'toAddress' => $toAddress,
    'package' => $packageInfo,
    'options' => [
        'labelFormat' => 'PDF',
        'labelSize' => '4x6',
        'signature' => false,
        'insurance' => true,
        'packaging' => 'package',
        'references' => [
            'reference1' => $orderNumber,
            'reference2' => 'PHP-' . time()
        ]
    ]
];

$response = $sdk->purchaseLabel($labelRequest);
```

### 5. Package Tracking

```php
// Single package tracking
$response = $sdk->trackPackage($trackingNumber);

// Batch tracking
$trackingNumbers = [
    '1Z999AA1234567890',
    '9400111206213123456789'
];
$batchResponse = $sdk->trackMultiple($trackingNumbers);
```

### 6. Comprehensive Error Handling

```php
use atoship\SDK\Exception\{
    atoshipException,
    ValidationException,
    AuthenticationException,
    RateLimitException,
    ServerException
};

try {
    $response = $sdk->createOrder($orderData);
    
    if ($response->isSuccess()) {
        $order = $response->getData();
        // Process successful response
    } else {
        echo "API Error: " . $response->getError() . "\n";
        if ($response->getRequestId()) {
            echo "Request ID: " . $response->getRequestId() . "\n";
        }
    }
    
} catch (ValidationException $e) {
    echo "Validation Error: " . $e->getMessage() . "\n";
    if ($e->hasDetails()) {
        foreach ($e->getDetails() as $field => $messages) {
            echo "  {$field}: " . implode(', ', $messages) . "\n";
        }
    }
} catch (RateLimitException $e) {
    echo "Rate Limited: " . $e->getMessage() . "\n";
    echo "Retry After: " . $e->getRetryAfter() . " seconds\n";
} catch (AuthenticationException $e) {
    echo "Authentication Error: " . $e->getMessage() . "\n";
} catch (atoshipException $e) {
    echo "SDK Error: " . $e->getMessage() . "\n";
    if ($e->getRequestId()) {
        echo "Request ID: " . $e->getRequestId() . "\n";
    }
} catch (Exception $e) {
    echo "Unexpected Error: " . $e->getMessage() . "\n";
}
```

### 7. Memory-Efficient Batch Processing

```php
class AdvancedShippingProcessor
{
    private const CHUNK_SIZE = 50;
    
    private function processOrdersInChunks(array $ordersData, string $strategy): array
    {
        $chunks = array_chunk($ordersData, self::CHUNK_SIZE);
        $allResults = [];

        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkResults = $this->processOrderChunk($chunk, $strategy);
            $allResults = array_merge($allResults, $chunkResults);

            // Memory cleanup and rate limiting
            if ($chunkIndex < count($chunks) - 1) {
                gc_collect_cycles();
                usleep(500000); // 0.5 second delay
            }
        }

        return $allResults;
    }
}
```

## Configuration Options

### Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `ATOSHIP_API_KEY` | Yes | - | Your atoship API key |
| `ATOSHIP_BASE_URL` | No | `https://api.atoship.com` | API base URL |
| `ENVIRONMENT` | No | `production` | Environment setting |

### Configuration Builder Options

```php
$config = Configuration::builder()
    ->apiKey('your-api-key')           // Required
    ->baseUrl('https://api.atoship.com') // Optional
    ->timeout(30.0)                    // Request timeout in seconds
    ->maxRetries(3)                    // Maximum retry attempts
    ->debug(true)                      // Enable debug logging
    ->userAgent('MyApp/1.0.0')        // Custom user agent
    ->verifySSL(true)                 // SSL verification
    ->build();
```

## Output and Reports

The advanced example generates several types of output:

### 1. Processing Reports
- JSON reports with detailed analytics
- CSV exports with comprehensive data
- Performance metrics and recommendations

### 2. File Structure
```
examples/
├── output/
│   ├── advanced_results_20240115_143022.csv
│   └── advanced_report_20240115_143022.json
├── basic_example.php
├── advanced_example.php
└── README.md
```

### 3. Analytics Reports
```json
{
  "processingStats": {
    "totalOrders": 100,
    "successfulOrders": 95,
    "failedOrders": 5,
    "totalCost": 1247.50,
    "averageCost": 13.13,
    "processingTime": 45.2,
    "carrierBreakdown": {
      "USPS": 45,
      "FedEx": 30,
      "UPS": 20
    }
  },
  "recommendations": [
    "Consider negotiating bulk rates with USPS",
    "Review data quality for failed orders"
  ]
}
```

## Best Practices Demonstrated

### 1. PSR Compliance
```php
declare(strict_types=1);

namespace atoship\Examples;

use atoship\SDK\atoshipSDK;
use atoship\SDK\Config\Configuration;

class BasicExample
{
    private atoshipSDK $sdk;
    
    public function __construct()
    {
        // Type-safe initialization
    }
}
```

### 2. Configuration Management
```php
private function getConfigValue(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}
```

### 3. Error Logging
```php
private function logError(string $message): void
{
    error_log("[atoship Example] " . $message);
}
```

### 4. Memory Management
```php
// Process in chunks to avoid memory issues
$chunks = array_chunk($orders, 50);
foreach ($chunks as $chunk) {
    $this->processChunk($chunk);
    gc_collect_cycles(); // Force garbage collection
}
```

### 5. Retry Logic with Exponential Backoff
```php
for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
    try {
        return $this->sdk->createOrder($orderData);
    } catch (RateLimitException $e) {
        if ($attempt < self::MAX_RETRIES) {
            sleep(pow(2, $attempt)); // Exponential backoff
            continue;
        }
        throw $e;
    }
}
```

## Troubleshooting

### Common Issues

1. **Composer Issues:**
   ```bash
   composer install
   composer update
   ```

2. **Permission Issues:**
   ```bash
   chmod +x basic_example.php
   chmod +x advanced_example.php
   ```

3. **API Key Issues:**
   ```bash
   echo $ATOSHIP_API_KEY  # Verify key is set
   ```

4. **Memory Issues:**
   ```bash
   php -d memory_limit=512M advanced_example.php --csv large_file.csv
   ```

5. **SSL Issues:**
   ```php
   $config = Configuration::builder()
       ->verifySSL(false)  // For development only
       ->build();
   ```

### Debug Mode

Enable debug logging:

```bash
export ENVIRONMENT=development
php basic_example.php
```

Or in code:
```php
$config = Configuration::builder()
    ->debug(true)
    ->build();
```

### Performance Tips

1. **Optimize Batch Size:**
   ```php
   private const CHUNK_SIZE = 25; // Reduce for slower servers
   ```

2. **Memory Management:**
   ```php
   // Force garbage collection between chunks
   gc_collect_cycles();
   ```

3. **Rate Limiting:**
   ```php
   // Add delays between requests
   usleep(500000); // 0.5 second delay
   ```

## Dependencies

### Required Dependencies
- `atoship/php-sdk` - Core SDK functionality
- `guzzlehttp/guzzle` - HTTP client
- `symfony/validator` - Data validation

### Optional Dependencies
- `monolog/monolog` - Advanced logging
- `symfony/console` - CLI interface
- `league/csv` - CSV processing

## Getting Help

- 📧 Email: support@atoship.com
- 📚 Documentation: https://atoship.com/developers
- 🐛 Issues: https://github.com/atoship/sdk-php/issues
- 💬 Community: https://community.atoship.com

## License

These examples are licensed under the MIT License - see the [LICENSE](LICENSE) file for details.