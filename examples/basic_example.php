<?php

declare(strict_types=1);

/**
 * atoship PHP SDK - Basic Example
 * 
 * This example demonstrates basic usage of the atoship PHP SDK including:
 * - SDK initialization and configuration
 * - Creating orders with detailed item information
 * - Getting shipping rates from multiple carriers
 * - Purchasing shipping labels
 * - Package tracking and status monitoring
 * - Address validation
 * - Comprehensive error handling
 * - PSR-compliant code structure
 */

require_once __DIR__ . '/../vendor/autoload.php';

use atoship\SDK\atoshipSDK;
use atoship\SDK\Config\Configuration;
use atoship\SDK\Exception\{
    atoshipException,
    ValidationException,
    AuthenticationException,
    RateLimitException,
    ServerException
};

/**
 * Basic Shipping Example using atoship SDK
 */
class BasicExample
{
    private atoshipSDK $sdk;
    private array $stats;
    private float $startTime;

    public function __construct()
    {
        // Initialize SDK with configuration
        $config = Configuration::builder()
            ->apiKey($this->getConfigValue('ATOSHIP_API_KEY', 'your-api-key-here'))
            ->baseUrl($this->getConfigValue('ATOSHIP_BASE_URL', 'https://api.atoship.com'))
            ->timeout(30.0)
            ->maxRetries(3)
            ->debug($this->getConfigValue('ENVIRONMENT') === 'development')
            ->build();

        $this->sdk = new atoshipSDK($config);
        
        // Initialize statistics
        $this->stats = [
            'ordersCreated' => 0,
            'labelsPurchased' => 0,
            'totalCost' => 0.0,
        ];
        $this->startTime = microtime(true);

        echo "✅ BasicExample initialized with PHP SDK\n";
    }

    /**
     * Run the complete basic example workflow
     */
    public function runExample(): void
    {
        echo "🚀 atoship PHP SDK Basic Example\n\n";

        try {
            // Step 1: Create an order
            $order = $this->createSampleOrder();
            if ($order === null) {
                return;
            }

            // Step 2: Get shipping rates
            $rates = $this->getShippingRates($order);
            if (empty($rates)) {
                return;
            }

            // Step 3: Purchase shipping label
            $label = $this->purchaseShippingLabel($order, $rates[0]);
            if ($label === null) {
                return;
            }

            // Step 4: Track the package
            $this->trackPackage($label->getTrackingNumber());

            // Step 5: Demonstrate additional features
            $this->demonstrateAdditionalFeatures($order->getId());

            // Step 6: Show summary
            $this->showSummary();

            echo "🎉 Basic example completed successfully!\n";

        } catch (atoshipException $e) {
            $this->logError("atoship SDK error: " . $e->getMessage());
            echo "❌ SDK Error: " . $e->getMessage() . "\n";
            
        } catch (Throwable $e) {
            $this->logError("Unexpected error: " . $e->getMessage());
            echo "💥 Unexpected error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Create a sample order with detailed information
     */
    private function createSampleOrder(): ?object
    {
        echo "📦 Step 1: Creating an order...\n";

        try {
            $orderData = [
                'orderNumber' => 'PHP-ORDER-' . time(),
                'recipientName' => 'Bob Smith',
                'recipientEmail' => 'bob@example.com',
                'recipientPhone' => '555-987-6543',
                'recipientStreet1' => '456 PHP Street',
                'recipientStreet2' => 'Suite 300',
                'recipientCity' => 'Seattle',
                'recipientState' => 'WA',
                'recipientPostalCode' => '98101',
                'recipientCountry' => 'US',
                'senderName' => 'PHP Store',
                'senderStreet1' => '789 Developer Boulevard',
                'senderCity' => 'Portland',
                'senderState' => 'OR',
                'senderPostalCode' => '97201',
                'senderCountry' => 'US',
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
                    ],
                    [
                        'name' => 'PHP Developer Stickers',
                        'sku' => 'STICKERS-PHP-001',
                        'quantity' => 5,
                        'unitPrice' => 3.99,
                        'weight' => 0.1,
                        'weightUnit' => 'lb',
                        'dimensions' => [
                            'length' => 4.0,
                            'width' => 4.0,
                            'height' => 0.1,
                            'unit' => 'in'
                        ]
                    ]
                ],
                'notes' => 'Educational materials - web development resources',
                'tags' => ['books', 'education', 'php', 'web-development'],
                'customFields' => [
                    'customerType' => 'developer',
                    'orderSource' => 'php-sdk-example',
                    'priority' => 'standard',
                    'language' => 'php',
                    'framework' => 'laravel'
                ]
            ];

            $response = $this->sdk->createOrder($orderData);

            if ($response->isSuccess()) {
                $order = $response->getData();
                $this->stats['ordersCreated']++;

                echo "✅ Order created successfully!\n";
                echo "   Order ID: {$order->getId()}\n";
                echo "   Order Number: {$order->getOrderNumber()}\n";
                echo "   Status: {$order->getStatus()}\n";
                echo "   Total Value: $" . number_format($order->getTotalValue(), 2) . "\n";
                echo "   Items Count: " . count($order->getItems()) . "\n\n";

                return $order;
            } else {
                echo "❌ Failed to create order: {$response->getError()}\n";
                return null;
            }

        } catch (ValidationException $e) {
            echo "❌ Validation error: {$e->getMessage()}\n";
            if ($e->hasDetails()) {
                echo "   Validation details:\n";
                foreach ($e->getDetails() as $field => $messages) {
                    echo "     {$field}: " . implode(', ', $messages) . "\n";
                }
            }
            return null;

        } catch (Exception $e) {
            $this->logError("Error creating order: " . $e->getMessage());
            echo "❌ Error creating order: {$e->getMessage()}\n";
            return null;
        }
    }

    /**
     * Get shipping rates for the order
     */
    private function getShippingRates(object $order): array
    {
        echo "💰 Step 2: Getting shipping rates...\n";

        try {
            // Calculate total weight from order items
            $totalWeight = 0.0;
            foreach ($order->getItems() as $item) {
                $totalWeight += $item->getWeight() * $item->getQuantity();
            }

            $rateRequest = [
                'fromAddress' => [
                    'street1' => $order->getSenderStreet1(),
                    'city' => $order->getSenderCity(),
                    'state' => $order->getSenderState(),
                    'postalCode' => $order->getSenderPostalCode(),
                    'country' => $order->getSenderCountry()
                ],
                'toAddress' => [
                    'street1' => $order->getRecipientStreet1(),
                    'street2' => $order->getRecipientStreet2(),
                    'city' => $order->getRecipientCity(),
                    'state' => $order->getRecipientState(),
                    'postalCode' => $order->getRecipientPostalCode(),
                    'country' => $order->getRecipientCountry()
                ],
                'package' => [
                    'weight' => max($totalWeight, 1.0), // Minimum 1 lb
                    'length' => 12.0,
                    'width' => 9.0,
                    'height' => 6.0,
                    'weightUnit' => 'lb',
                    'dimensionUnit' => 'in'
                ],
                'options' => [
                    'signature' => false,
                    'insurance' => true,
                    'insuranceValue' => $order->getTotalValue(),
                    'saturdayDelivery' => false,
                    'residential' => true
                ]
            ];

            $response = $this->sdk->getRates($rateRequest);

            if ($response->isSuccess()) {
                $rates = $response->getData();
                echo "✅ Shipping rates retrieved successfully!\n";

                foreach ($rates as $index => $rate) {
                    $num = $index + 1;
                    echo "   {$num}. {$rate->getCarrier()} {$rate->getServiceName()}\n";
                    echo "      Price: $" . number_format($rate->getAmount(), 2) . "\n";
                    echo "      Estimated Days: {$rate->getEstimatedDays()}\n";
                    echo "      Delivery Date: " . ($rate->getEstimatedDeliveryDate() ?: 'N/A') . "\n";
                    if ($rate->getZone()) {
                        echo "      Zone: {$rate->getZone()}\n";
                    }
                }

                echo "\n";
                return $rates;
            } else {
                echo "❌ Failed to get rates: {$response->getError()}\n";
                return [];
            }

        } catch (Exception $e) {
            $this->logError("Error getting rates: " . $e->getMessage());
            echo "❌ Error getting rates: {$e->getMessage()}\n";
            return [];
        }
    }

    /**
     * Purchase a shipping label for the order
     */
    private function purchaseShippingLabel(object $order, object $selectedRate): ?object
    {
        echo "🏷️  Step 3: Purchasing shipping label...\n";
        echo "📋 Selected Rate: {$selectedRate->getCarrier()} {$selectedRate->getServiceName()} - $" . 
             number_format($selectedRate->getAmount(), 2) . "\n\n";

        try {
            $labelRequest = [
                'orderId' => $order->getId(),
                'rateId' => $selectedRate->getId(),
                'fromAddress' => [
                    'name' => $order->getSenderName(),
                    'street1' => $order->getSenderStreet1(),
                    'city' => $order->getSenderCity(),
                    'state' => $order->getSenderState(),
                    'postalCode' => $order->getSenderPostalCode(),
                    'country' => $order->getSenderCountry()
                ],
                'toAddress' => [
                    'name' => $order->getRecipientName(),
                    'email' => $order->getRecipientEmail(),
                    'phone' => $order->getRecipientPhone(),
                    'street1' => $order->getRecipientStreet1(),
                    'street2' => $order->getRecipientStreet2(),
                    'city' => $order->getRecipientCity(),
                    'state' => $order->getRecipientState(),
                    'postalCode' => $order->getRecipientPostalCode(),
                    'country' => $order->getRecipientCountry()
                ],
                'package' => [
                    'weight' => array_sum(array_map(
                        fn($item) => $item->getWeight() * $item->getQuantity(),
                        $order->getItems()
                    )),
                    'length' => 12.0,
                    'width' => 9.0,
                    'height' => 6.0,
                    'weightUnit' => 'lb',
                    'dimensionUnit' => 'in'
                ],
                'options' => [
                    'labelFormat' => 'PDF',
                    'labelSize' => '4x6',
                    'signature' => false,
                    'insurance' => true,
                    'insuranceValue' => $order->getTotalValue(),
                    'packaging' => 'package',
                    'references' => [
                        'reference1' => $order->getOrderNumber(),
                        'reference2' => 'PHP-' . time()
                    ]
                ]
            ];

            $response = $this->sdk->purchaseLabel($labelRequest);

            if ($response->isSuccess()) {
                $label = $response->getData();
                $this->stats['labelsPurchased']++;
                $this->stats['totalCost'] += $label->getCost();

                echo "✅ Shipping label purchased successfully!\n";
                echo "   Label ID: {$label->getId()}\n";
                echo "   Tracking Number: {$label->getTrackingNumber()}\n";
                echo "   Carrier: {$label->getCarrier()}\n";
                echo "   Service: {$label->getServiceName()}\n";
                echo "   Cost: $" . number_format($label->getCost(), 2) . "\n";
                echo "   Label URL: {$label->getLabelUrl()}\n";
                if ($label->getVoidUrl()) {
                    echo "   Void URL: {$label->getVoidUrl()}\n";
                }
                echo "\n";

                return $label;
            } else {
                echo "❌ Failed to purchase label: {$response->getError()}\n";
                return null;
            }

        } catch (Exception $e) {
            $this->logError("Error purchasing label: " . $e->getMessage());
            echo "❌ Error purchasing label: {$e->getMessage()}\n";
            return null;
        }
    }

    /**
     * Track a package by tracking number
     */
    private function trackPackage(string $trackingNumber): void
    {
        echo "📍 Step 4: Tracking the package...\n";

        try {
            $response = $this->sdk->trackPackage($trackingNumber);

            if ($response->isSuccess()) {
                $tracking = $response->getData();
                echo "✅ Package tracking information retrieved!\n";
                echo "   Status: {$tracking->getStatus()}\n";
                echo "   Carrier: {$tracking->getCarrier()}\n";
                if ($tracking->getServiceName()) {
                    echo "   Service: {$tracking->getServiceName()}\n";
                }
                echo "   Last Updated: {$tracking->getLastUpdate()}\n";
                if ($tracking->getEstimatedDelivery()) {
                    echo "   Estimated Delivery: {$tracking->getEstimatedDelivery()}\n";
                }
                if ($tracking->getCurrentLocation()) {
                    echo "   Current Location: {$tracking->getCurrentLocation()}\n";
                }

                if ($tracking->getEvents()) {
                    echo "   Tracking Events:\n";
                    foreach ($tracking->getEvents() as $index => $event) {
                        $num = $index + 1;
                        echo "     {$num}. {$event->getTimestamp()}: {$event->getDescription()}\n";
                        if ($event->getLocation()) {
                            echo "        Location: {$event->getLocation()}\n";
                        }
                        if ($event->getStatusCode()) {
                            echo "        Status Code: {$event->getStatusCode()}\n";
                        }
                    }
                }

                echo "\n";
            } else {
                echo "❌ Failed to track package: {$response->getError()}\n";
            }

        } catch (Exception $e) {
            $this->logError("Error tracking package: " . $e->getMessage());
            echo "❌ Error tracking package: {$e->getMessage()}\n";
        }
    }

    /**
     * Demonstrate additional SDK features
     */
    private function demonstrateAdditionalFeatures(string $orderId): void
    {
        echo "🔧 Step 5: Demonstrating additional features...\n";

        // Address validation
        $this->validateAddress();

        // Batch tracking
        $this->batchTrackingDemo();

        // Account metrics
        $this->getAccountMetrics();

        // Order retrieval
        $this->getOrderDetails($orderId);
    }

    /**
     * Demonstrate address validation
     */
    private function validateAddress(): void
    {
        echo "🏠 Address validation example...\n";

        try {
            $addressData = [
                'street1' => '123 Main St',
                'city' => 'Seattle',
                'state' => 'WA',
                'postalCode' => '98101',
                'country' => 'US'
            ];

            $response = $this->sdk->validateAddress($addressData);

            if ($response->isSuccess()) {
                $validation = $response->getData();
                echo "✅ Address validation completed!\n";
                echo "   Valid: " . ($validation['isValid'] ? 'true' : 'false') . "\n";
                if (!empty($validation['suggestions'])) {
                    echo "   Suggestions available for improved accuracy\n";
                }
                echo "\n";
            } else {
                echo "❌ Address validation failed: {$response->getError()}\n";
            }

        } catch (Exception $e) {
            $this->logError("Error validating address: " . $e->getMessage());
            echo "❌ Error validating address: {$e->getMessage()}\n";
        }
    }

    /**
     * Demonstrate batch tracking functionality
     */
    private function batchTrackingDemo(): void
    {
        echo "📦 Batch tracking demonstration...\n";

        try {
            $trackingNumbers = [
                '1Z999AA1234567890',
                '9400111206213123456789',
                '123456789012'
            ];

            $response = $this->sdk->trackMultiple($trackingNumbers);

            if ($response->isSuccess()) {
                echo "✅ Batch tracking completed!\n";
                foreach ($response->getData() as $index => $result) {
                    $num = $index + 1;
                    $status = $result['status'] ?? 'Unknown';
                    $trackingNum = $result['trackingNumber'] ?? 'N/A';
                    echo "   {$num}. {$trackingNum}: {$status}\n";
                }
                echo "\n";
            } else {
                echo "❌ Batch tracking failed: {$response->getError()}\n";
            }

        } catch (Exception $e) {
            $this->logError("Error in batch tracking: " . $e->getMessage());
            echo "❌ Error in batch tracking: {$e->getMessage()}\n";
        }
    }

    /**
     * Get and display account metrics
     */
    private function getAccountMetrics(): void
    {
        echo "📊 Account metrics...\n";

        try {
            $response = $this->sdk->getAccountMetrics();

            if ($response->isSuccess()) {
                $metrics = $response->getData();
                echo "✅ Account metrics retrieved!\n";
                echo "   Orders This Month: " . ($metrics['ordersThisMonth'] ?? 0) . "\n";
                echo "   Labels This Month: " . ($metrics['labelsThisMonth'] ?? 0) . "\n";
                echo "   Total Spent: $" . number_format($metrics['totalSpent'] ?? 0, 2) . "\n";
                echo "   API Calls Today: " . ($metrics['apiCallsToday'] ?? 0) . "\n";
                echo "\n";
            } else {
                echo "❌ Failed to get metrics: {$response->getError()}\n";
            }

        } catch (Exception $e) {
            $this->logError("Error getting metrics: " . $e->getMessage());
            echo "❌ Error getting metrics: {$e->getMessage()}\n";
        }
    }

    /**
     * Retrieve and display order details
     */
    private function getOrderDetails(string $orderId): void
    {
        echo "📋 Retrieving order details...\n";

        try {
            $response = $this->sdk->getOrder($orderId);

            if ($response->isSuccess()) {
                $order = $response->getData();
                echo "✅ Order details retrieved!\n";
                echo "   Order Status: {$order->getStatus()}\n";
                if ($order->getShippingStatus()) {
                    echo "   Shipping Status: {$order->getShippingStatus()}\n";
                }
                if ($order->getLabelsCount() !== null) {
                    echo "   Labels Count: {$order->getLabelsCount()}\n";
                }
                if ($order->getTotalCost() !== null) {
                    echo "   Total Cost: $" . number_format($order->getTotalCost(), 2) . "\n";
                }
                echo "\n";
            } else {
                echo "❌ Failed to get order: {$response->getError()}\n";
            }

        } catch (Exception $e) {
            $this->logError("Error getting order: " . $e->getMessage());
            echo "❌ Error getting order: {$e->getMessage()}\n";
        }
    }

    /**
     * Display example execution summary
     */
    private function showSummary(): void
    {
        $executionTime = microtime(true) - $this->startTime;

        echo "📊 Example Summary:\n";
        echo "   Orders Created: {$this->stats['ordersCreated']}\n";
        echo "   Labels Purchased: {$this->stats['labelsPurchased']}\n";
        echo "   Total Cost: $" . number_format($this->stats['totalCost'], 2) . "\n";
        echo "   Execution Time: " . number_format($executionTime, 1) . " seconds\n";
        echo "\n";
    }

    /**
     * Get configuration value from environment or default
     */
    private function getConfigValue(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Log error message (in production, use a proper logger)
     */
    private function logError(string $message): void
    {
        error_log("[atoship Basic Example] " . $message);
    }
}

/**
 * Print usage information
 */
function printUsage(): void
{
    echo <<<USAGE
atoship PHP SDK Basic Example

Usage:
    php basic_example.php                  # Run the basic example
    php basic_example.php --help           # Show this help

Environment Variables:
    ATOSHIP_API_KEY                        # Required: Your atoship API key
    ATOSHIP_BASE_URL                       # Optional: API base URL (default: https://api.atoship.com)
    ENVIRONMENT                            # Optional: Environment (development/production)

Features Demonstrated:
    - SDK initialization and configuration
    - Creating orders with detailed information
    - Getting shipping rates from multiple carriers
    - Purchasing shipping labels
    - Package tracking and monitoring
    - Address validation
    - Batch operations
    - Account metrics and reporting
    - Comprehensive error handling
    - PSR-compliant code structure

Requirements:
    - PHP 7.4 or higher
    - Composer package manager
    - atoship PHP SDK
    - Valid atoship API key

USAGE;
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Check for help argument
    if (in_array('--help', $argv) || in_array('-h', $argv)) {
        printUsage();
        exit(0);
    }

    // Check for required API key
    $apiKey = $_ENV['ATOSHIP_API_KEY'] ?? getenv('ATOSHIP_API_KEY');
    if (empty($apiKey)) {
        echo "❌ Error: ATOSHIP_API_KEY environment variable is required\n";
        echo "   Please set your API key:\n";
        echo "   export ATOSHIP_API_KEY='your-api-key-here'\n";
        exit(1);
    }

    try {
        $example = new BasicExample();
        $example->runExample();
    } catch (Throwable $e) {
        echo "💥 Fatal error: {$e->getMessage()}\n";
        exit(1);
    }
} else {
    echo "This script must be run from the command line.\n";
}