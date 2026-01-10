<?php

declare(strict_types=1);

namespace atoship\SDK\Config;

use atoship\SDK\Exception\ConfigurationException;

/**
 * Configuration class for the atoship SDK.
 * 
 * This class contains all configurable options for the SDK including
 * API credentials, network settings, and behavior options.
 * 
 * @package atoship\SDK\Config
 * @author  atoship Team <support@atoship.com>
 * @version 1.0.0
 * @since   1.0.0
 */
class Configuration
{
    private const DEFAULT_BASE_URL = 'https://api.atoship.com';
    private const DEFAULT_TIMEOUT = 30.0;
    private const DEFAULT_MAX_RETRIES = 3;
    private const DEFAULT_USER_AGENT = 'atoship-PHP-SDK/1.0.0';

    private string $apiKey;
    private string $baseUrl;
    private float $timeout;
    private int $maxRetries;
    private bool $debug;
    private string $userAgent;
    private bool $verifySSL;

    /**
     * Creates a new configuration instance.
     *
     * @param string $apiKey The API key for authentication
     * @param array<string, mixed> $options Additional configuration options
     * 
     * @throws ConfigurationException If the configuration is invalid
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty(trim($apiKey))) {
            throw new ConfigurationException('API key cannot be empty');
        }

        $this->apiKey = trim($apiKey);
        $this->baseUrl = $options['baseUrl'] ?? self::DEFAULT_BASE_URL;
        $this->timeout = (float) ($options['timeout'] ?? self::DEFAULT_TIMEOUT);
        $this->maxRetries = (int) ($options['maxRetries'] ?? self::DEFAULT_MAX_RETRIES);
        $this->debug = (bool) ($options['debug'] ?? false);
        $this->userAgent = $options['userAgent'] ?? self::DEFAULT_USER_AGENT;
        $this->verifySSL = (bool) ($options['verifySSL'] ?? true);

        $this->validate();
    }

    /**
     * Creates a configuration builder.
     *
     * @return ConfigurationBuilder A new builder instance
     */
    public static function builder(): ConfigurationBuilder
    {
        return new ConfigurationBuilder();
    }

    /**
     * Creates a configuration with default settings.
     *
     * @param string $apiKey The API key
     * 
     * @return self A new configuration instance
     */
    public static function withApiKey(string $apiKey): self
    {
        return new self($apiKey);
    }

    /**
     * Validates the configuration.
     *
     * @throws ConfigurationException If the configuration is invalid
     */
    private function validate(): void
    {
        if ($this->timeout <= 0) {
            throw new ConfigurationException('Timeout must be positive');
        }

        if ($this->maxRetries < 0) {
            throw new ConfigurationException('Max retries cannot be negative');
        }

        if (!filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new ConfigurationException('Base URL must be a valid URL');
        }
    }

    /**
     * Gets the API key.
     *
     * @return string The API key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Gets the base URL for the API.
     *
     * @return string The base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Gets the request timeout in seconds.
     *
     * @return float The timeout in seconds
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * Gets the maximum number of retries for failed requests.
     *
     * @return int The maximum retry count
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool True if debug mode is enabled
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Gets the user agent string.
     *
     * @return string The user agent
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Checks if SSL verification is enabled.
     *
     * @return bool True if SSL verification is enabled
     */
    public function isVerifySSL(): bool
    {
        return $this->verifySSL;
    }

    /**
     * Creates a copy of this configuration with the API key masked for logging.
     *
     * @return self A configuration with masked API key
     */
    public function withMaskedApiKey(): self
    {
        $maskedKey = strlen($this->apiKey) > 8 
            ? substr($this->apiKey, 0, 4) . '****' . substr($this->apiKey, -4)
            : '****';

        $config = clone $this;
        $config->apiKey = $maskedKey;

        return $config;
    }

    /**
     * Converts the configuration to an array.
     *
     * @param bool $maskApiKey Whether to mask the API key
     * 
     * @return array<string, mixed> The configuration as an array
     */
    public function toArray(bool $maskApiKey = false): array
    {
        $config = $maskApiKey ? $this->withMaskedApiKey() : $this;

        return [
            'apiKey' => $config->apiKey,
            'baseUrl' => $config->baseUrl,
            'timeout' => $config->timeout,
            'maxRetries' => $config->maxRetries,
            'debug' => $config->debug,
            'userAgent' => $config->userAgent,
            'verifySSL' => $config->verifySSL,
        ];
    }

    /**
     * Returns a string representation of the configuration.
     *
     * @return string The configuration as a string (API key is masked)
     */
    public function __toString(): string
    {
        $config = $this->withMaskedApiKey();
        
        return sprintf(
            'Configuration{baseUrl=%s, timeout=%s, maxRetries=%d, debug=%s, verifySSL=%s}',
            $config->baseUrl,
            $config->timeout,
            $config->maxRetries,
            $config->debug ? 'true' : 'false',
            $config->verifySSL ? 'true' : 'false'
        );
    }
}

/**
 * Builder class for creating Configuration instances.
 */
class ConfigurationBuilder
{
    private ?string $apiKey = null;
    private string $baseUrl = Configuration::DEFAULT_BASE_URL;
    private float $timeout = Configuration::DEFAULT_TIMEOUT;
    private int $maxRetries = Configuration::DEFAULT_MAX_RETRIES;
    private bool $debug = false;
    private string $userAgent = Configuration::DEFAULT_USER_AGENT;
    private bool $verifySSL = true;

    /**
     * Sets the API key.
     *
     * @param string $apiKey The API key
     * 
     * @return self This builder
     */
    public function apiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Sets the base URL for the API.
     *
     * @param string $baseUrl The base URL
     * 
     * @return self This builder
     */
    public function baseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Sets the request timeout in seconds.
     *
     * @param float $timeout The timeout in seconds
     * 
     * @return self This builder
     */
    public function timeout(float $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Sets the maximum number of retries for failed requests.
     *
     * @param int $maxRetries The maximum retry count
     * 
     * @return self This builder
     */
    public function maxRetries(int $maxRetries): self
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    /**
     * Enables or disables debug mode.
     *
     * @param bool $debug True to enable debug mode
     * 
     * @return self This builder
     */
    public function debug(bool $debug = true): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Sets the user agent string.
     *
     * @param string $userAgent The user agent
     * 
     * @return self This builder
     */
    public function userAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Enables or disables SSL verification.
     *
     * @param bool $verifySSL True to enable SSL verification
     * 
     * @return self This builder
     */
    public function verifySSL(bool $verifySSL = true): self
    {
        $this->verifySSL = $verifySSL;
        return $this;
    }

    /**
     * Builds the configuration.
     *
     * @return Configuration A new Configuration instance
     * 
     * @throws ConfigurationException If required fields are missing
     */
    public function build(): Configuration
    {
        if ($this->apiKey === null) {
            throw new ConfigurationException('API key is required');
        }

        return new Configuration($this->apiKey, [
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'maxRetries' => $this->maxRetries,
            'debug' => $this->debug,
            'userAgent' => $this->userAgent,
            'verifySSL' => $this->verifySSL,
        ]);
    }
}