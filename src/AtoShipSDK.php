<?php

declare(strict_types=1);

namespace atoship\SDK;

use atoship\SDK\Client\HttpClient;
use atoship\SDK\Config\Configuration;
use atoship\SDK\Exception\atoshipException;
use atoship\SDK\Exception\ValidationException;
use atoship\SDK\Model\Address;
use atoship\SDK\Model\Order;
use atoship\SDK\Model\Response\ApiResponse;
use atoship\SDK\Model\Response\PaginatedResponse;
use atoship\SDK\Model\ShippingLabel;
use atoship\SDK\Model\ShippingRate;
use atoship\SDK\Model\TrackingInfo;
use atoship\SDK\Util\ValidationUtils;

/**
 * Main SDK class for interacting with the atoship API.
 * 
 * This class provides a high-level interface for all atoship operations including
 * order management, shipping rates, label purchasing, tracking, and more.
 * 
 * @example
 * ```php
 * $sdk = new atoshipSDK('your-api-key');
 * 
 * $order = $sdk->createOrder([
 *     'orderNumber' => 'ORDER-001',
 *     'recipientName' => 'John Doe',
 *     'recipientStreet1' => '123 Main St',
 *     'recipientCity' => 'San Francisco',
 *     'recipientState' => 'CA',
 *     'recipientPostalCode' => '94105',
 *     'recipientCountry' => 'US',
 *     'items' => [
 *         [
 *             'name' => 'Product',
 *             'sku' => 'SKU-001',
 *             'quantity' => 1,
 *             'unitPrice' => 29.99,
 *             'weight' => 2.0,
 *             'weightUnit' => 'lb'
 *         ]
 *     ]
 * ]);
 * 
 * if ($order->isSuccess()) {
 *     echo 'Order created: ' . $order->getData()->getId();
 * }
 * ```
 * 
 * @package atoship\SDK
 * @author  atoship Team <support@atoship.com>
 * @version 1.0.0
 * @since   1.0.0
 */
class atoshipSDK
{
    private HttpClient $client;
    private Configuration $config;

    /**
     * Creates a new SDK instance.
     *
     * @param string|Configuration $apiKeyOrConfig API key string or Configuration object
     * @param array<string, mixed> $options Additional configuration options (when using string API key)
     * 
     * @throws atoshipException If the configuration is invalid
     */
    public function __construct($apiKeyOrConfig, array $options = [])
    {
        if (is_string($apiKeyOrConfig)) {
            $this->config = new Configuration($apiKeyOrConfig, $options);
        } elseif ($apiKeyOrConfig instanceof Configuration) {
            $this->config = $apiKeyOrConfig;
        } else {
            throw new atoshipException('Invalid configuration. Expected string API key or Configuration object.');
        }

        $this->client = new HttpClient($this->config);
    }

    /**
     * Gets the current configuration.
     *
     * @return Configuration The configuration object (API key is masked)
     */
    public function getConfiguration(): Configuration
    {
        return $this->config->withMaskedApiKey();
    }

    /**
     * Updates the SDK configuration.
     *
     * @param Configuration $config The new configuration
     * 
     * @throws atoshipException If the configuration is invalid
     */
    public function updateConfiguration(Configuration $config): void
    {
        $this->config = $config;
        $this->client->updateConfiguration($config);
    }

    // Order Management

    /**
     * Creates a new order.
     *
     * @param array<string, mixed> $orderData Order information
     * 
     * @return ApiResponse<Order> The API response containing the created order
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the order data is invalid
     */
    public function createOrder(array $orderData): ApiResponse
    {
        ValidationUtils::validateOrderData($orderData);

        return $this->client->post('/api/orders', $orderData, Order::class);
    }

    /**
     * Retrieves an order by ID.
     *
     * @param string $orderId The order ID
     * 
     * @return ApiResponse<Order> The API response containing the order
     * 
     * @throws atoshipException If the request fails
     */
    public function getOrder(string $orderId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($orderId, 'Order ID cannot be empty');

        return $this->client->get("/api/orders/{$orderId}", Order::class);
    }

    /**
     * Lists orders with optional filtering and pagination.
     *
     * @param array<string, mixed> $params Query parameters for filtering and pagination
     * 
     * @return PaginatedResponse<Order> The paginated response containing orders
     * 
     * @throws atoshipException If the request fails
     */
    public function listOrders(array $params = []): PaginatedResponse
    {
        return $this->client->getPaginated('/api/orders', $params, Order::class);
    }

    /**
     * Updates an existing order.
     *
     * @param string $orderId The order ID
     * @param array<string, mixed> $orderData Updated order information
     * 
     * @return ApiResponse<Order> The API response containing the updated order
     * 
     * @throws atoshipException If the request fails
     */
    public function updateOrder(string $orderId, array $orderData): ApiResponse
    {
        ValidationUtils::validateNotEmpty($orderId, 'Order ID cannot be empty');

        return $this->client->put("/api/orders/{$orderId}", $orderData, Order::class);
    }

    /**
     * Deletes an order.
     *
     * @param string $orderId The order ID
     * 
     * @return ApiResponse<null> The API response
     * 
     * @throws atoshipException If the request fails
     */
    public function deleteOrder(string $orderId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($orderId, 'Order ID cannot be empty');

        return $this->client->delete("/api/orders/{$orderId}");
    }

    /**
     * Creates multiple orders in a batch.
     *
     * @param array<array<string, mixed>> $orders Array of order data
     * 
     * @return ApiResponse<array> The API response containing batch results
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the batch data is invalid
     */
    public function createOrdersBatch(array $orders): ApiResponse
    {
        if (empty($orders)) {
            throw new ValidationException('At least one order is required for batch creation');
        }

        if (count($orders) > 100) {
            throw new ValidationException('Maximum 100 orders allowed per batch');
        }

        foreach ($orders as $index => $orderData) {
            try {
                ValidationUtils::validateOrderData($orderData);
            } catch (ValidationException $e) {
                throw new ValidationException("Order {$index} validation failed: {$e->getMessage()}");
            }
        }

        return $this->client->post('/api/orders/batch', ['orders' => $orders]);
    }

    // Shipping Rates

    /**
     * Gets shipping rates for a package.
     *
     * @param array<string, mixed> $rateRequest Rate request with addresses and package details
     * 
     * @return ApiResponse<ShippingRate[]> The API response containing shipping rates
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the rate request is invalid
     */
    public function getRates(array $rateRequest): ApiResponse
    {
        ValidationUtils::validateRateRequest($rateRequest);

        return $this->client->postList('/api/shipping/rates', $rateRequest, ShippingRate::class);
    }

    /**
     * Compares rates from multiple carriers.
     *
     * @param array<string, mixed> $rateRequest Rate request with addresses and package details
     * 
     * @return ApiResponse<ShippingRate[]> The API response containing compared rates
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the rate request is invalid
     */
    public function compareRates(array $rateRequest): ApiResponse
    {
        ValidationUtils::validateRateRequest($rateRequest);

        return $this->client->postList('/api/shipping/rates/compare', $rateRequest, ShippingRate::class);
    }

    // Label Management

    /**
     * Purchases a shipping label.
     *
     * @param array<string, mixed> $labelRequest Label purchase request
     * 
     * @return ApiResponse<ShippingLabel> The API response containing the purchased label
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the label request is invalid
     */
    public function purchaseLabel(array $labelRequest): ApiResponse
    {
        ValidationUtils::validateLabelRequest($labelRequest);

        return $this->client->post('/api/shipping/labels', $labelRequest, ShippingLabel::class);
    }

    /**
     * Retrieves a shipping label by ID.
     *
     * @param string $labelId The label ID
     * 
     * @return ApiResponse<ShippingLabel> The API response containing the label
     * 
     * @throws atoshipException If the request fails
     */
    public function getLabel(string $labelId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($labelId, 'Label ID cannot be empty');

        return $this->client->get("/api/shipping/labels/{$labelId}", ShippingLabel::class);
    }

    /**
     * Lists shipping labels with optional filtering and pagination.
     *
     * @param array<string, mixed> $params Query parameters for filtering and pagination
     * 
     * @return PaginatedResponse<ShippingLabel> The paginated response containing labels
     * 
     * @throws atoshipException If the request fails
     */
    public function listLabels(array $params = []): PaginatedResponse
    {
        return $this->client->getPaginated('/api/shipping/labels', $params, ShippingLabel::class);
    }

    /**
     * Cancels a shipping label.
     *
     * @param string $labelId The label ID
     * 
     * @return ApiResponse<null> The API response
     * 
     * @throws atoshipException If the request fails
     */
    public function cancelLabel(string $labelId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($labelId, 'Label ID cannot be empty');

        return $this->client->post("/api/shipping/labels/{$labelId}/cancel");
    }

    /**
     * Requests a refund for a shipping label.
     *
     * @param string $labelId The label ID
     * 
     * @return ApiResponse<null> The API response
     * 
     * @throws atoshipException If the request fails
     */
    public function refundLabel(string $labelId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($labelId, 'Label ID cannot be empty');

        return $this->client->post("/api/shipping/labels/{$labelId}/refund");
    }

    // Package Tracking

    /**
     * Tracks a package by tracking number.
     *
     * @param string $trackingNumber The tracking number
     * @param string|null $carrier Optional carrier name
     * 
     * @return ApiResponse<TrackingInfo> The API response containing tracking information
     * 
     * @throws atoshipException If the request fails
     */
    public function trackPackage(string $trackingNumber, ?string $carrier = null): ApiResponse
    {
        ValidationUtils::validateNotEmpty($trackingNumber, 'Tracking number cannot be empty');

        $formattedTrackingNumber = strtoupper(trim($trackingNumber));
        $endpoint = "/api/tracking/{$formattedTrackingNumber}";

        if ($carrier !== null && trim($carrier) !== '') {
            $endpoint .= '?' . http_build_query(['carrier' => trim($carrier)]);
        }

        return $this->client->get($endpoint, TrackingInfo::class);
    }

    /**
     * Tracks multiple packages at once.
     *
     * @param array<string> $trackingNumbers Array of tracking numbers
     * 
     * @return ApiResponse<array> The API response containing tracking results
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the tracking numbers are invalid
     */
    public function trackMultiple(array $trackingNumbers): ApiResponse
    {
        if (empty($trackingNumbers)) {
            throw new ValidationException('At least one tracking number is required');
        }

        if (count($trackingNumbers) > 50) {
            throw new ValidationException('Maximum 50 tracking numbers allowed per request');
        }

        $formattedNumbers = array_map(function ($number) {
            return strtoupper(trim($number));
        }, $trackingNumbers);

        return $this->client->post('/api/tracking/batch', [
            'trackingNumbers' => $formattedNumbers
        ]);
    }

    // Address Management

    /**
     * Validates an address.
     *
     * @param array<string, mixed> $addressData Address information
     * 
     * @return ApiResponse<array> The API response containing validation results
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the address data is invalid
     */
    public function validateAddress(array $addressData): ApiResponse
    {
        ValidationUtils::validateAddressData($addressData);

        return $this->client->post('/api/addresses/validate', $addressData);
    }

    /**
     * Gets address suggestions based on a query.
     *
     * @param string $query The search query
     * @param string $country The country code (default: 'US')
     * 
     * @return ApiResponse<array> The API response containing address suggestions
     * 
     * @throws atoshipException If the request fails
     */
    public function suggestAddresses(string $query, string $country = 'US'): ApiResponse
    {
        ValidationUtils::validateNotEmpty($query, 'Query cannot be empty');

        $params = ['q' => $query, 'country' => $country];
        $endpoint = '/api/addresses/suggest?' . http_build_query($params);

        return $this->client->get($endpoint);
    }

    /**
     * Saves an address to the address book.
     *
     * @param array<string, mixed> $addressData Address information
     * 
     * @return ApiResponse<Address> The API response containing the saved address
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the address data is invalid
     */
    public function saveAddress(array $addressData): ApiResponse
    {
        ValidationUtils::validateAddressData($addressData);

        return $this->client->post('/api/addresses', $addressData, Address::class);
    }

    /**
     * Lists saved addresses with pagination.
     *
     * @param array<string, mixed> $params Query parameters for pagination
     * 
     * @return PaginatedResponse<Address> The paginated response containing addresses
     * 
     * @throws atoshipException If the request fails
     */
    public function listAddresses(array $params = []): PaginatedResponse
    {
        return $this->client->getPaginated('/api/addresses', $params, Address::class);
    }

    /**
     * Retrieves an address by ID.
     *
     * @param string $addressId The address ID
     * 
     * @return ApiResponse<Address> The API response containing the address
     * 
     * @throws atoshipException If the request fails
     */
    public function getAddress(string $addressId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($addressId, 'Address ID cannot be empty');

        return $this->client->get("/api/addresses/{$addressId}", Address::class);
    }

    /**
     * Updates an existing address.
     *
     * @param string $addressId The address ID
     * @param array<string, mixed> $addressData Updated address information
     * 
     * @return ApiResponse<Address> The API response containing the updated address
     * 
     * @throws atoshipException If the request fails
     */
    public function updateAddress(string $addressId, array $addressData): ApiResponse
    {
        ValidationUtils::validateNotEmpty($addressId, 'Address ID cannot be empty');

        return $this->client->put("/api/addresses/{$addressId}", $addressData, Address::class);
    }

    /**
     * Deletes an address.
     *
     * @param string $addressId The address ID
     * 
     * @return ApiResponse<null> The API response
     * 
     * @throws atoshipException If the request fails
     */
    public function deleteAddress(string $addressId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($addressId, 'Address ID cannot be empty');

        return $this->client->delete("/api/addresses/{$addressId}");
    }

    // User and Account Management

    /**
     * Gets the user profile.
     *
     * @return ApiResponse<array> The API response containing user information
     * 
     * @throws atoshipException If the request fails
     */
    public function getProfile(): ApiResponse
    {
        return $this->client->get('/api/profile');
    }

    /**
     * Updates the user profile.
     *
     * @param array<string, mixed> $profileData Updated profile information
     * 
     * @return ApiResponse<array> The API response containing the updated user
     * 
     * @throws atoshipException If the request fails
     */
    public function updateProfile(array $profileData): ApiResponse
    {
        return $this->client->put('/api/profile', $profileData);
    }

    /**
     * Gets account usage statistics.
     *
     * @return ApiResponse<array> The API response containing usage information
     * 
     * @throws atoshipException If the request fails
     */
    public function getUsage(): ApiResponse
    {
        return $this->client->get('/api/account/usage');
    }

    /**
     * Gets billing information.
     *
     * @return ApiResponse<array> The API response containing billing information
     * 
     * @throws atoshipException If the request fails
     */
    public function getBilling(): ApiResponse
    {
        return $this->client->get('/api/account/billing');
    }

    // API Key Management

    /**
     * Creates a new API key.
     *
     * @param array<string, mixed> $keyData API key information
     * 
     * @return ApiResponse<array> The API response containing the created API key
     * 
     * @throws atoshipException If the request fails
     */
    public function createApiKey(array $keyData): ApiResponse
    {
        return $this->client->post('/api/keys', $keyData);
    }

    /**
     * Lists API keys.
     *
     * @return ApiResponse<array> The API response containing API keys
     * 
     * @throws atoshipException If the request fails
     */
    public function listApiKeys(): ApiResponse
    {
        return $this->client->get('/api/keys');
    }

    /**
     * Revokes an API key.
     *
     * @param string $keyId The API key ID
     * 
     * @return ApiResponse<null> The API response
     * 
     * @throws atoshipException If the request fails
     */
    public function revokeApiKey(string $keyId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($keyId, 'API key ID cannot be empty');

        return $this->client->delete("/api/keys/{$keyId}");
    }

    // Webhook Management

    /**
     * Creates a new webhook.
     *
     * @param array<string, mixed> $webhookData Webhook information
     * 
     * @return ApiResponse<array> The API response containing the created webhook
     * 
     * @throws atoshipException If the request fails
     * @throws ValidationException If the webhook data is invalid
     */
    public function createWebhook(array $webhookData): ApiResponse
    {
        ValidationUtils::validateWebhookData($webhookData);

        return $this->client->post('/api/webhooks', $webhookData);
    }

    /**
     * Lists webhooks.
     *
     * @return ApiResponse<array> The API response containing webhooks
     * 
     * @throws atoshipException If the request fails
     */
    public function listWebhooks(): ApiResponse
    {
        return $this->client->get('/api/webhooks');
    }

    /**
     * Gets a webhook by ID.
     *
     * @param string $webhookId The webhook ID
     * 
     * @return ApiResponse<array> The API response containing the webhook
     * 
     * @throws atoshipException If the request fails
     */
    public function getWebhook(string $webhookId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($webhookId, 'Webhook ID cannot be empty');

        return $this->client->get("/api/webhooks/{$webhookId}");
    }

    /**
     * Updates a webhook.
     *
     * @param string $webhookId The webhook ID
     * @param array<string, mixed> $webhookData Updated webhook information
     * 
     * @return ApiResponse<array> The API response containing the updated webhook
     * 
     * @throws atoshipException If the request fails
     */
    public function updateWebhook(string $webhookId, array $webhookData): ApiResponse
    {
        ValidationUtils::validateNotEmpty($webhookId, 'Webhook ID cannot be empty');

        return $this->client->put("/api/webhooks/{$webhookId}", $webhookData);
    }

    /**
     * Deletes a webhook.
     *
     * @param string $webhookId The webhook ID
     * 
     * @return ApiResponse<null> The API response
     * 
     * @throws atoshipException If the request fails
     */
    public function deleteWebhook(string $webhookId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($webhookId, 'Webhook ID cannot be empty');

        return $this->client->delete("/api/webhooks/{$webhookId}");
    }

    /**
     * Tests a webhook by sending a test event.
     *
     * @param string $webhookId The webhook ID
     * 
     * @return ApiResponse<array> The API response containing test results
     * 
     * @throws atoshipException If the request fails
     */
    public function testWebhook(string $webhookId): ApiResponse
    {
        ValidationUtils::validateNotEmpty($webhookId, 'Webhook ID cannot be empty');

        return $this->client->post("/api/webhooks/{$webhookId}/test");
    }

    // Carrier Management

    /**
     * Lists available carriers.
     *
     * @return ApiResponse<array> The API response containing carriers
     * 
     * @throws atoshipException If the request fails
     */
    public function listCarriers(): ApiResponse
    {
        return $this->client->get('/api/carriers');
    }

    /**
     * Gets carrier information.
     *
     * @param string $carrierCode The carrier code
     * 
     * @return ApiResponse<array> The API response containing carrier information
     * 
     * @throws atoshipException If the request fails
     */
    public function getCarrier(string $carrierCode): ApiResponse
    {
        ValidationUtils::validateNotEmpty($carrierCode, 'Carrier code cannot be empty');

        return $this->client->get("/api/carriers/{$carrierCode}");
    }

    // Monitoring and Analytics

    /**
     * Gets monitoring metrics.
     *
     * @param array<string, mixed> $params Query parameters for date range
     * 
     * @return ApiResponse<array> The API response containing metrics
     * 
     * @throws atoshipException If the request fails
     */
    public function getMonitoringMetrics(array $params = []): ApiResponse
    {
        $endpoint = '/api/monitoring/metrics';
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $this->client->get($endpoint);
    }

    /**
     * Gets performance metrics.
     *
     * @return ApiResponse<array> The API response containing performance metrics
     * 
     * @throws atoshipException If the request fails
     */
    public function getPerformanceMetrics(): ApiResponse
    {
        return $this->client->get('/api/monitoring/performance');
    }

    /**
     * Gets analytics data.
     *
     * @param array<string, mixed> $params Query parameters for analytics
     * 
     * @return ApiResponse<array> The API response containing analytics data
     * 
     * @throws atoshipException If the request fails
     */
    public function getAnalytics(array $params = []): ApiResponse
    {
        $endpoint = '/api/analytics';
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $this->client->get($endpoint);
    }

    // Utility Methods

    /**
     * Performs a health check on the API.
     *
     * @return ApiResponse<array> The API response containing health status
     * 
     * @throws atoshipException If the request fails
     */
    public function healthCheck(): ApiResponse
    {
        return $this->client->get('/api/health');
    }

    /**
     * Gets system status information.
     *
     * @return ApiResponse<array> The API response containing system status
     * 
     * @throws atoshipException If the request fails
     */
    public function getSystemStatus(): ApiResponse
    {
        return $this->client->get('/api/status');
    }
}