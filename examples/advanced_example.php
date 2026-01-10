<?php

declare(strict_types=1);

/**
 * atoship PHP SDK - Advanced Example
 * 
 * This example demonstrates advanced features including:
 * - CSV batch processing with performance optimization
 * - Rate optimization strategies
 * - Webhook server integration
 * - Performance monitoring and analytics
 * - Retry logic and error recovery
 * - Data export and reporting
 * - PSR-compliant architecture
 * - Memory-efficient processing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use atoship\SDK\atoshipSDK;
use atoship\SDK\Config\Configuration;
use atoship\SDK\Exception\{
    atoshipException,
    ValidationException,
    RateLimitException,
    ServerException
};

/**
 * Advanced Shipping Processor with enterprise features
 */
class AdvancedShippingProcessor
{
    private atoshipSDK $sdk;
    private array $statistics;
    private float $startTime;
    private string $outputDir;

    private const CHUNK_SIZE = 50;
    private const MAX_RETRIES = 3;
    private const RATE_LIMIT_DELAY = 60; // seconds

    public function __construct()
    {
        // Initialize SDK with configuration
        $config = Configuration::builder()
            ->apiKey($this->getConfigValue('ATOSHIP_API_KEY', 'your-api-key-here'))
            ->baseUrl($this->getConfigValue('ATOSHIP_BASE_URL', 'https://api.atoship.com'))
            ->timeout(45.0)
            ->maxRetries(3)
            ->debug($this->getConfigValue('ENVIRONMENT') === 'development')
            ->userAgent('atoship-PHP-Advanced/1.0.0')
            ->build();

        $this->sdk = new atoshipSDK($config);
        
        // Initialize statistics
        $this->statistics = [
            'totalOrders' => 0,
            'successfulOrders' => 0,
            'failedOrders' => 0,
            'totalCost' => 0.0,
            'averageCost' => 0.0,
            'processingTime' => 0.0,
            'carrierBreakdown' => [],
            'errorBreakdown' => []
        ];
        
        $this->startTime = microtime(true);
        $this->outputDir = __DIR__ . '/output';
        
        // Create output directory
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        echo "✅ AdvancedShippingProcessor initialized\n";
    }

    /**
     * Process orders from CSV file with advanced optimization
     */
    public function processCsvOrders(string $csvFilePath, string $strategy = 'balanced'): array
    {
        echo "🚀 Starting advanced batch processing from {$csvFilePath}\n";

        if (!file_exists($csvFilePath)) {
            throw new InvalidArgumentException("CSV file not found: {$csvFilePath}");
        }

        try {
            // Read and validate CSV data
            $ordersData = $this->readCsvOrders($csvFilePath);
            echo "📊 Loaded " . count($ordersData) . " orders from CSV\n";

            $this->statistics['totalOrders'] = count($ordersData);

            // Process orders in chunks for memory efficiency
            $results = $this->processOrdersInChunks($ordersData, $strategy);

            // Calculate final statistics
            $this->statistics['processingTime'] = microtime(true) - $this->startTime;
            $this->calculateFinalStatistics($results);

            // Generate comprehensive reports
            $this->generateComprehensiveReport($results);
            $this->exportResultsToCsv($results);

            echo "✅ Advanced batch processing completed successfully!\n";
            return $results;

        } catch (Exception $e) {
            $this->logError("Batch processing failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Read and validate orders from CSV file
     */
    private function readCsvOrders(string $csvFilePath): array
    {
        $orders = [];
        $handle = fopen($csvFilePath, 'r');
        
        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV file: {$csvFilePath}");
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException("Invalid CSV file: no headers found");
        }

        $rowNumber = 2; // Start from row 2 (after headers)
        
        while (($row = fgetcsv($handle)) !== false) {
            try {
                $rowData = array_combine($headers, $row);
                $order = $this->transformCsvRowToOrder($rowData);
                
                if ($order !== null) {
                    $orders[] = $order;
                } else {
                    echo "⚠️  Skipped invalid row {$rowNumber}\n";
                }
            } catch (Exception $e) {
                echo "⚠️  Error processing row {$rowNumber}: {$e->getMessage()}\n";
            }
            
            $rowNumber++;
        }

        fclose($handle);
        return $orders;
    }

    /**
     * Transform CSV row to order object with validation
     */
    private function transformCsvRowToOrder(array $row): ?array
    {
        // Validate required fields
        $requiredFields = ['recipient_name', 'recipient_address1', 'recipient_city', 
                          'recipient_state', 'recipient_postal_code'];

        foreach ($requiredFields as $field) {
            if (empty(trim($row[$field] ?? ''))) {
                $this->logError("Missing required field: {$field}");
                return null;
            }
        }

        try {
            return [
                'orderNumber' => $row['order_number'] ?? 'ADV-PHP-' . time() . '-' . rand(1000, 9999),
                'recipientName' => trim($row['recipient_name']),
                'recipientEmail' => trim($row['recipient_email'] ?? ''),
                'recipientPhone' => trim($row['recipient_phone'] ?? ''),
                'recipientStreet1' => trim($row['recipient_address1']),
                'recipientStreet2' => trim($row['recipient_address2'] ?? ''),
                'recipientCity' => trim($row['recipient_city']),
                'recipientState' => trim($row['recipient_state']),
                'recipientPostalCode' => trim($row['recipient_postal_code']),
                'recipientCountry' => trim($row['recipient_country'] ?? 'US'),
                'senderName' => trim($row['sender_name'] ?? 'Advanced PHP Store'),
                'senderStreet1' => trim($row['sender_address1'] ?? '123 PHP Boulevard'),
                'senderCity' => trim($row['sender_city'] ?? 'San Francisco'),
                'senderState' => trim($row['sender_state'] ?? 'CA'),
                'senderPostalCode' => trim($row['sender_postal_code'] ?? '94105'),
                'senderCountry' => trim($row['sender_country'] ?? 'US'),
                'items' => [
                    [
                        'name' => trim($row['item_name'] ?? 'Default Item'),
                        'sku' => trim($row['item_sku'] ?? 'DEFAULT-SKU'),
                        'quantity' => max(1, (int)($row['item_quantity'] ?? 1)),
                        'unitPrice' => max(0.0, (float)($row['item_price'] ?? 0)),
                        'weight' => max(0.1, (float)($row['item_weight'] ?? 1)),
                        'weightUnit' => trim($row['item_weight_unit'] ?? 'lb')
                    ]
                ],
                'shippingStrategy' => strtolower(trim($row['shipping_strategy'] ?? 'balanced')),
                'notes' => trim($row['notes'] ?? ''),
                'tags' => array_filter(array_map('trim', explode(',', $row['tags'] ?? ''))),
                'priority' => strtolower(trim($row['priority'] ?? 'standard'))
            ];

        } catch (Exception $e) {
            $this->logError("Error transforming CSV row: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process orders in chunks for memory efficiency
     */
    private function processOrdersInChunks(array $ordersData, string $strategy): array
    {
        $chunks = array_chunk($ordersData, self::CHUNK_SIZE);
        $allResults = [];

        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkNumber = $chunkIndex + 1;
            $totalChunks = count($chunks);
            
            echo "🔄 Processing chunk {$chunkNumber}/{$totalChunks} (" . count($chunk) . " orders)\n";

            $chunkResults = $this->processOrderChunk($chunk, $strategy);
            $allResults = array_merge($allResults, $chunkResults);

            // Progress reporting
            $processed = count($allResults);
            $total = count($ordersData);
            $progress = ($processed / $total) * 100;
            echo "📊 Progress: {$processed}/{$total} orders (" . number_format($progress, 1) . "%)\n";

            // Memory cleanup and rate limiting
            if ($chunkIndex < count($chunks) - 1) {
                gc_collect_cycles();
                usleep(500000); // 0.5 second delay between chunks
            }
        }

        return $allResults;
    }

    /**
     * Process a chunk of orders with error handling
     */
    private function processOrderChunk(array $orders, string $strategy): array
    {
        $results = [];

        foreach ($orders as $orderData) {
            $result = $this->processOrderWithRetry($orderData, $strategy);
            $results[] = $result;

            // Update statistics in real-time
            if ($result['success']) {
                $this->statistics['successfulOrders']++;
                if (isset($result['labelInfo']['cost'])) {
                    $this->statistics['totalCost'] += $result['labelInfo']['cost'];
                }
            } else {
                $this->statistics['failedOrders']++;
                $errorType = $result['error'] ?? 'Unknown Error';
                $this->statistics['errorBreakdown'][$errorType] = 
                    ($this->statistics['errorBreakdown'][$errorType] ?? 0) + 1;
            }
        }

        return $results;
    }

    /**
     * Process single order with retry logic
     */
    private function processOrderWithRetry(array $orderData, string $strategy): array
    {
        $orderNumber = $orderData['orderNumber'];
        $result = [
            'orderNumber' => $orderNumber,
            'success' => false,
            'retryCount' => 0,
            'processingTime' => 0.0
        ];

        $startTime = microtime(true);

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result['retryCount'] = $attempt;

                // Step 1: Create order
                $orderResponse = $this->sdk->createOrder($orderData);
                if (!$orderResponse->isSuccess()) {
                    throw new Exception("Order creation failed: " . $orderResponse->getError());
                }

                $result['orderId'] = $orderResponse->getData()->getId();

                // Step 2: Get optimized rates
                $rates = $this->getOptimizedRates($orderData);
                if (empty($rates)) {
                    throw new Exception("No shipping rates available");
                }

                // Step 3: Select optimal rate
                $selectedRate = $this->selectRateByStrategy($rates, $strategy, $orderData);
                $result['selectedRate'] = [
                    'carrier' => $selectedRate->getCarrier(),
                    'service' => $selectedRate->getServiceName(),
                    'cost' => $selectedRate->getAmount(),
                    'estimatedDays' => $selectedRate->getEstimatedDays()
                ];

                // Step 4: Purchase label
                $labelResponse = $this->purchaseLabelWithRetry($result['orderId'], $selectedRate, $orderData);
                
                if ($labelResponse->isSuccess()) {
                    $label = $labelResponse->getData();
                    $result['labelInfo'] = [
                        'labelId' => $label->getId(),
                        'trackingNumber' => $label->getTrackingNumber(),
                        'cost' => $label->getCost(),
                        'labelUrl' => $label->getLabelUrl()
                    ];

                    // Track carrier usage
                    $carrier = $selectedRate->getCarrier();
                    $this->statistics['carrierBreakdown'][$carrier] = 
                        ($this->statistics['carrierBreakdown'][$carrier] ?? 0) + 1;
                }

                $result['success'] = true;
                $result['processingTime'] = microtime(true) - $startTime;
                return $result;

            } catch (RateLimitException $e) {
                if ($attempt < self::MAX_RETRIES) {
                    echo "⏳ Rate limit hit for {$orderNumber}, waiting...\n";
                    sleep(min(self::RATE_LIMIT_DELAY, pow(2, $attempt)));
                    continue;
                } else {
                    $result['error'] = "Rate limit exceeded: " . $e->getMessage();
                }

            } catch (Exception $e) {
                if ($attempt < self::MAX_RETRIES) {
                    $this->logError("Attempt " . ($attempt + 1) . " failed for {$orderNumber}: " . $e->getMessage());
                    sleep(pow(2, $attempt)); // Exponential backoff
                    continue;
                } else {
                    $result['error'] = $e->getMessage();
                }
            }
        }

        $result['processingTime'] = microtime(true) - $startTime;
        return $result;
    }

    /**
     * Get optimized rates with fallback strategies
     */
    private function getOptimizedRates(array $orderData): array
    {
        $rateRequest = $this->buildRateRequest($orderData);

        // Primary: Get all rates
        try {
            $response = $this->sdk->getRates($rateRequest);
            if ($response->isSuccess() && !empty($response->getData())) {
                return $response->getData();
            }
        } catch (Exception $e) {
            $this->logError("Primary rate request failed: " . $e->getMessage());
        }

        // Fallback: Try individual carriers
        $carriers = ['USPS', 'FedEx', 'UPS'];
        $fallbackRates = [];

        foreach ($carriers as $carrier) {
            try {
                $carrierRequest = array_merge($rateRequest, ['carrier' => $carrier]);
                $response = $this->sdk->getRates($carrierRequest);
                
                if ($response->isSuccess() && !empty($response->getData())) {
                    $fallbackRates = array_merge($fallbackRates, $response->getData());
                }
            } catch (Exception $e) {
                $this->logError("Fallback rate request failed for {$carrier}: " . $e->getMessage());
            }
        }

        return $fallbackRates;
    }

    /**
     * Select optimal rate based on strategy
     */
    private function selectRateByStrategy(array $rates, string $strategy, array $orderData): object
    {
        if (empty($rates)) {
            throw new Exception("No rates available for selection");
        }

        // Filter out expensive rates
        $orderValue = array_sum(array_map(
            fn($item) => $item['unitPrice'] * $item['quantity'],
            $orderData['items']
        ));
        $maxShipping = min($orderValue * 0.3, 50.0); // Max 30% of order value or $50

        $affordableRates = array_filter($rates, fn($rate) => $rate->getAmount() <= $maxShipping);
        if (empty($affordableRates)) {
            $affordableRates = $rates; // Fallback to all rates
        }

        switch ($strategy) {
            case 'cost':
                return $this->findCheapestRate($affordableRates);

            case 'speed':
                return $this->findFastestRate($affordableRates);

            case 'balanced':
                return $this->findBalancedRate($affordableRates);

            case 'premium':
                return $this->findPremiumRate($affordableRates);

            default:
                return $affordableRates[0];
        }
    }

    /**
     * Find the cheapest rate
     */
    private function findCheapestRate(array $rates): object
    {
        return array_reduce($rates, function ($cheapest, $current) {
            return $cheapest === null || $current->getAmount() < $cheapest->getAmount() 
                ? $current 
                : $cheapest;
        });
    }

    /**
     * Find the fastest rate
     */
    private function findFastestRate(array $rates): object
    {
        return array_reduce($rates, function ($fastest, $current) {
            return $fastest === null || $current->getEstimatedDays() < $fastest->getEstimatedDays() 
                ? $current 
                : $fastest;
        });
    }

    /**
     * Find balanced rate (cost vs speed)
     */
    private function findBalancedRate(array $rates): object
    {
        $maxCost = max(array_map(fn($rate) => $rate->getAmount(), $rates));
        $maxDays = max(array_map(fn($rate) => $rate->getEstimatedDays(), $rates));

        return array_reduce($rates, function ($best, $current) use ($maxCost, $maxDays) {
            $currentScore = ($current->getAmount() / $maxCost) * 0.6 + 
                           ($current->getEstimatedDays() / $maxDays) * 0.4;
            
            if ($best === null) {
                $best = ['rate' => $current, 'score' => $currentScore];
            } else {
                $bestScore = ($best['rate']->getAmount() / $maxCost) * 0.6 + 
                            ($best['rate']->getEstimatedDays() / $maxDays) * 0.4;
                
                if ($currentScore < $bestScore) {
                    $best = ['rate' => $current, 'score' => $currentScore];
                }
            }
            
            return $best;
        })['rate'];
    }

    /**
     * Find premium rate (reliable carriers)
     */
    private function findPremiumRate(array $rates): object
    {
        $premiumCarriers = ['FedEx', 'UPS'];
        
        $premiumRates = array_filter($rates, function ($rate) use ($premiumCarriers) {
            return in_array($rate->getCarrier(), $premiumCarriers);
        });

        if (!empty($premiumRates)) {
            return $this->findFastestRate($premiumRates);
        }

        return $this->findBalancedRate($rates);
    }

    /**
     * Purchase label with retry logic
     */
    private function purchaseLabelWithRetry(string $orderId, object $selectedRate, array $orderData): object
    {
        $labelRequest = $this->buildLabelRequest($orderId, $selectedRate, $orderData);

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->sdk->purchaseLabel($labelRequest);
                if ($response->isSuccess()) {
                    return $response;
                }

                if ($attempt < self::MAX_RETRIES - 1) {
                    sleep(pow(2, $attempt));
                } else {
                    throw new Exception("Label purchase failed: " . $response->getError());
                }

            } catch (Exception $e) {
                if ($attempt < self::MAX_RETRIES - 1) {
                    sleep(pow(2, $attempt));
                } else {
                    throw $e;
                }
            }
        }

        throw new Exception("Label purchase failed after " . self::MAX_RETRIES . " attempts");
    }

    /**
     * Build rate request with calculated package dimensions
     */
    private function buildRateRequest(array $orderData): array
    {
        $totalWeight = array_sum(array_map(
            fn($item) => $item['weight'] * $item['quantity'],
            $orderData['items']
        ));

        return [
            'fromAddress' => [
                'street1' => $orderData['senderStreet1'],
                'city' => $orderData['senderCity'],
                'state' => $orderData['senderState'],
                'postalCode' => $orderData['senderPostalCode'],
                'country' => $orderData['senderCountry']
            ],
            'toAddress' => [
                'street1' => $orderData['recipientStreet1'],
                'street2' => $orderData['recipientStreet2'],
                'city' => $orderData['recipientCity'],
                'state' => $orderData['recipientState'],
                'postalCode' => $orderData['recipientPostalCode'],
                'country' => $orderData['recipientCountry']
            ],
            'package' => [
                'weight' => max($totalWeight, 0.1),
                'length' => 12.0,
                'width' => 9.0,
                'height' => 6.0,
                'weightUnit' => 'lb',
                'dimensionUnit' => 'in'
            ],
            'options' => [
                'signature' => $orderData['priority'] === 'high',
                'insurance' => $totalWeight > 2.0,
                'insuranceValue' => array_sum(array_map(
                    fn($item) => $item['unitPrice'] * $item['quantity'],
                    $orderData['items']
                )),
                'residential' => true
            ]
        ];
    }

    /**
     * Build comprehensive label request
     */
    private function buildLabelRequest(string $orderId, object $selectedRate, array $orderData): array
    {
        return [
            'orderId' => $orderId,
            'rateId' => $selectedRate->getId(),
            'fromAddress' => [
                'name' => $orderData['senderName'],
                'street1' => $orderData['senderStreet1'],
                'city' => $orderData['senderCity'],
                'state' => $orderData['senderState'],
                'postalCode' => $orderData['senderPostalCode'],
                'country' => $orderData['senderCountry']
            ],
            'toAddress' => [
                'name' => $orderData['recipientName'],
                'email' => $orderData['recipientEmail'],
                'phone' => $orderData['recipientPhone'],
                'street1' => $orderData['recipientStreet1'],
                'street2' => $orderData['recipientStreet2'],
                'city' => $orderData['recipientCity'],
                'state' => $orderData['recipientState'],
                'postalCode' => $orderData['recipientPostalCode'],
                'country' => $orderData['recipientCountry']
            ],
            'package' => $this->buildRateRequest($orderData)['package'],
            'options' => [
                'labelFormat' => 'PDF',
                'labelSize' => '4x6',
                'packaging' => 'package',
                'references' => [
                    'reference1' => $orderData['orderNumber'],
                    'reference2' => 'ADV-PHP-' . time()
                ]
            ]
        ];
    }

    /**
     * Calculate final statistics
     */
    private function calculateFinalStatistics(array $results): void
    {
        if ($this->statistics['successfulOrders'] > 0) {
            $this->statistics['averageCost'] = $this->statistics['totalCost'] / $this->statistics['successfulOrders'];
        }
    }

    /**
     * Generate comprehensive processing report
     */
    private function generateComprehensiveReport(array $results): void
    {
        $report = [
            'processingStats' => $this->statistics,
            'timestamp' => date('Y-m-d H:i:s'),
            'successRate' => $this->statistics['totalOrders'] > 0 
                ? ($this->statistics['successfulOrders'] / $this->statistics['totalOrders'] * 100) 
                : 0,
            'carrierPerformance' => $this->analyzeCarrierPerformance($results),
            'costAnalysis' => $this->analyzeCosts($results),
            'errorAnalysis' => $this->statistics['errorBreakdown'],
            'recommendations' => $this->generateRecommendations()
        ];

        // Save JSON report
        $reportFile = $this->outputDir . '/advanced_report_' . date('Ymd_His') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

        echo "📊 Comprehensive report saved to {$reportFile}\n";

        // Print summary
        echo "\n📊 Processing Summary:\n";
        echo "   Total Orders: {$this->statistics['totalOrders']}\n";
        echo "   Successful: {$this->statistics['successfulOrders']}\n";
        echo "   Failed: {$this->statistics['failedOrders']}\n";
        echo "   Success Rate: " . number_format($report['successRate'], 1) . "%\n";
        echo "   Total Cost: $" . number_format($this->statistics['totalCost'], 2) . "\n";
        echo "   Average Cost: $" . number_format($this->statistics['averageCost'], 2) . "\n";
        echo "   Processing Time: " . number_format($this->statistics['processingTime'], 1) . " seconds\n";
    }

    /**
     * Analyze carrier performance
     */
    private function analyzeCarrierPerformance(array $results): array
    {
        $carrierStats = [];

        foreach ($results as $result) {
            if ($result['success'] && isset($result['selectedRate'])) {
                $carrier = $result['selectedRate']['carrier'];
                if (!isset($carrierStats[$carrier])) {
                    $carrierStats[$carrier] = [
                        'count' => 0,
                        'totalCost' => 0.0,
                        'totalDays' => 0,
                        'avgCost' => 0.0,
                        'avgDays' => 0.0
                    ];
                }

                $stats = &$carrierStats[$carrier];
                $stats['count']++;
                $stats['totalCost'] += $result['selectedRate']['cost'];
                $stats['totalDays'] += $result['selectedRate']['estimatedDays'];
            }
        }

        // Calculate averages
        foreach ($carrierStats as $carrier => &$stats) {
            if ($stats['count'] > 0) {
                $stats['avgCost'] = $stats['totalCost'] / $stats['count'];
                $stats['avgDays'] = $stats['totalDays'] / $stats['count'];
            }
        }

        return $carrierStats;
    }

    /**
     * Analyze cost distribution
     */
    private function analyzeCosts(array $results): array
    {
        $successfulResults = array_filter($results, fn($r) => $r['success'] && isset($r['labelInfo']));

        if (empty($successfulResults)) {
            return [];
        }

        $costs = array_map(fn($r) => $r['labelInfo']['cost'], $successfulResults);

        return [
            'minCost' => min($costs),
            'maxCost' => max($costs),
            'avgCost' => array_sum($costs) / count($costs),
            'totalCost' => array_sum($costs),
            'costDistribution' => [
                'under10' => count(array_filter($costs, fn($c) => $c < 10)),
                '10to20' => count(array_filter($costs, fn($c) => $c >= 10 && $c < 20)),
                '20to30' => count(array_filter($costs, fn($c) => $c >= 20 && $c < 30)),
                'over30' => count(array_filter($costs, fn($c) => $c >= 30))
            ]
        ];
    }

    /**
     * Generate actionable recommendations
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];

        // Carrier recommendations
        if (!empty($this->statistics['carrierBreakdown'])) {
            $mostUsedCarrier = array_keys($this->statistics['carrierBreakdown'], 
                max($this->statistics['carrierBreakdown']))[0];
            $recommendations[] = "Consider negotiating bulk rates with {$mostUsedCarrier}";
        }

        // Error rate recommendations
        $errorRate = $this->statistics['totalOrders'] > 0 
            ? ($this->statistics['failedOrders'] / $this->statistics['totalOrders'] * 100) 
            : 0;

        if ($errorRate > 10) {
            $recommendations[] = "High error rate detected - review data quality and validation rules";
        }

        // Cost optimization
        if ($this->statistics['averageCost'] > 15) {
            $recommendations[] = "Average shipping cost is high - consider bulk discounts or rate negotiation";
        }

        return $recommendations;
    }

    /**
     * Export results to CSV
     */
    private function exportResultsToCsv(array $results): void
    {
        $csvFile = $this->outputDir . '/advanced_results_' . date('Ymd_His') . '.csv';
        $handle = fopen($csvFile, 'w');

        if ($handle === false) {
            throw new RuntimeException("Unable to create CSV file: {$csvFile}");
        }

        // Write headers
        $headers = [
            'order_number', 'success', 'order_id', 'carrier', 'service',
            'cost', 'estimated_days', 'tracking_number', 'processing_time',
            'retry_count', 'error'
        ];
        fputcsv($handle, $headers);

        // Write data
        foreach ($results as $result) {
            $row = [
                $result['orderNumber'],
                $result['success'] ? 'true' : 'false',
                $result['orderId'] ?? '',
                $result['selectedRate']['carrier'] ?? '',
                $result['selectedRate']['service'] ?? '',
                $result['selectedRate']['cost'] ?? '',
                $result['selectedRate']['estimatedDays'] ?? '',
                $result['labelInfo']['trackingNumber'] ?? '',
                number_format($result['processingTime'], 2),
                $result['retryCount'],
                $result['error'] ?? ''
            ];
            fputcsv($handle, $row);
        }

        fclose($handle);
        echo "📄 Results exported to {$csvFile}\n";
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
     * Log error message
     */
    private function logError(string $message): void
    {
        error_log("[atoship Advanced Example] " . $message);
    }
}

/**
 * Print usage information
 */
function printAdvancedUsage(): void
{
    echo <<<USAGE
atoship PHP SDK Advanced Example

Usage:
    php advanced_example.php --csv orders.csv [options]
    php advanced_example.php --demo [options]
    php advanced_example.php --help

Options:
    --strategy STRATEGY     # Shipping strategy: cost, speed, balanced, premium (default: balanced)
    --help                  # Show this help

Environment Variables:
    ATOSHIP_API_KEY         # Required: Your atoship API key
    ATOSHIP_BASE_URL        # Optional: API base URL
    ENVIRONMENT             # Optional: Environment setting

Features:
    - CSV batch processing with performance optimization
    - Rate optimization strategies
    - Comprehensive error handling and retry logic
    - Performance analytics and reporting
    - Memory-efficient processing
    - PSR-compliant architecture
    - Detailed logging and monitoring

USAGE;
}

// Main execution
if (php_sapi_name() === 'cli') {
    // Check for help argument
    if (in_array('--help', $argv) || in_array('-h', $argv)) {
        printAdvancedUsage();
        exit(0);
    }

    // Check for required API key
    $apiKey = $_ENV['ATOSHIP_API_KEY'] ?? getenv('ATOSHIP_API_KEY');
    if (empty($apiKey)) {
        echo "❌ Error: ATOSHIP_API_KEY environment variable is required\n";
        exit(1);
    }

    try {
        $processor = new AdvancedShippingProcessor();

        // Parse command line arguments
        $csvIndex = array_search('--csv', $argv);
        $demoIndex = array_search('--demo', $argv);
        $strategyIndex = array_search('--strategy', $argv);

        $strategy = 'balanced';
        if ($strategyIndex !== false && isset($argv[$strategyIndex + 1])) {
            $strategy = $argv[$strategyIndex + 1];
        }

        if ($csvIndex !== false && isset($argv[$csvIndex + 1])) {
            // Process CSV file
            $csvFile = $argv[$csvIndex + 1];
            $processor->processCsvOrders($csvFile, $strategy);

        } elseif ($demoIndex !== false) {
            // Run demo with sample data
            echo "🚀 Running advanced demo with sample data...\n";

            // Create sample CSV
            $sampleCsv = '/tmp/sample_advanced_orders.csv';
            $sampleData = [
                ['order_number', 'recipient_name', 'recipient_email', 'recipient_address1', 'recipient_city', 'recipient_state', 'recipient_postal_code', 'item_name', 'item_sku', 'item_quantity', 'item_price', 'item_weight', 'shipping_strategy', 'priority'],
                ['DEMO-001', 'Customer 1', 'customer1@example.com', '100 Demo Street', 'Seattle', 'WA', '98101', 'Demo Product 1', 'DEMO-001', '1', '29.99', '1.0', 'cost', 'standard'],
                ['DEMO-002', 'Customer 2', 'customer2@example.com', '200 Demo Avenue', 'Portland', 'OR', '97201', 'Demo Product 2', 'DEMO-002', '1', '34.99', '1.5', 'speed', 'high'],
                ['DEMO-003', 'Customer 3', 'customer3@example.com', '300 Demo Boulevard', 'San Francisco', 'CA', '94105', 'Demo Product 3', 'DEMO-003', '1', '39.99', '2.0', 'balanced', 'standard'],
                ['DEMO-004', 'Customer 4', 'customer4@example.com', '400 Demo Lane', 'Los Angeles', 'CA', '90001', 'Demo Product 4', 'DEMO-004', '1', '44.99', '2.5', 'premium', 'high'],
                ['DEMO-005', 'Customer 5', 'customer5@example.com', '500 Demo Drive', 'San Diego', 'CA', '92101', 'Demo Product 5', 'DEMO-005', '1', '49.99', '3.0', 'balanced', 'standard']
            ];

            $handle = fopen($sampleCsv, 'w');
            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);

            // Process sample orders
            $processor->processCsvOrders($sampleCsv, $strategy);

            // Cleanup
            unlink($sampleCsv);

        } else {
            echo "❌ No valid command provided. Use --help for usage information.\n";
            exit(1);
        }

    } catch (Throwable $e) {
        echo "💥 Fatal error: {$e->getMessage()}\n";
        exit(1);
    }
} else {
    echo "This script must be run from the command line.\n";
}