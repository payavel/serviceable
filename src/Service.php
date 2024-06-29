<?php

namespace Payavel\Orchestration;

use Exception;
use Payavel\Orchestration\ServiceConfig;
use Payavel\Orchestration\Traits\SimulatesAttributes;

class Service
{
    use SimulatesAttributes;

    /**
     * The service config.
     *
     * @var \Payavel\Orchestration\ServiceConfig
     */
    protected $config;

    /**
     * The service driver that will handle provider/account gateway resolutions.
     *
     * @var \Payavel\Orchestration\ServiceDriver
     */
    protected $driver;

    /**
     * The configured provider.
     *
     * @var \Payavel\Orchestration\Contracts\Providable
     */
    protected $provider;

    /**
     * The configured account.
     *
     * @var \Payavel\Orchestration\Contracts\Accountable
     */
    protected $account;

    /**
     * The gateway class where requests will be executed.
     *
     * @var \Payavel\Orchestration\ServiceRequest
     */
    protected $gateway;

    /**
     * Sets the service config and the driver for it.
     *
     * @param \Payavel\Orchestration\ServiceConfig|string|int $serviceConfig
     * @return void
     *
     * @throws Exception
     */
    public function __construct($serviceConfig)
    {
        if (! $serviceConfig instanceof ServiceConfig && is_null($serviceConfig = ServiceConfig::find($serviceConfig))) {
            throw new Exception("Service config with id '{$serviceConfig}' was not found.");
        }

        $this->config = $serviceConfig;

        if (! class_exists($driver = $this->config->get('drivers.'.$this->config->get('defaults.driver')))) {
            throw new Exception('Invalid driver provided.');
        }

        $this->driver = new $driver($this->config);
    }

    /**
     * Fluent provider setter.
     *
     * @param \Payavel\Orchestration\Contracts\Providable|string|int $provider
     * @return \Payavel\Orchestration\Service
     */
    public function provider($provider)
    {
        $this->setProvider($provider);

        return $this;
    }

    /**
     * Get the current provider.
     *
     * @return \Payavel\Orchestration\Contracts\Providable
     */
    public function getProvider()
    {
        if (! isset($this->provider)) {
            $this->setProvider($this->getDefaultProvider());
        }

        return $this->provider;
    }

    /**
     * Set the provider.
     *
     * @param \Payavel\Orchestration\Contracts\Providable|string|int $provider
     * @return void
     *
     * @throws Exception
     */
    public function setProvider($provider)
    {
        if (is_null($provider = $this->driver->resolveProvider($provider))) {
            throw new Exception('Invalid provider.');
        }

        $this->provider = $provider;

        $this->gateway = null;
    }

    /**
     * Get the default service provider.
     *
     * @return string|int|\Payavel\Orchestration\Contracts\Providable
     */
    public function getDefaultProvider()
    {
        return $this->driver->getDefaultProvider($this->account);
    }

    /**
     * Fluent account setter.
     *
     * @param \Payavel\Orchestration\Contracts\Accountable|string|int $account
     * @return \Payavel\Orchestration\Service
     */
    public function account($account)
    {
        $this->setAccount($account);

        return $this;
    }

    /**
     * Get the current account.
     *
     * @return \Payavel\Orchestration\Contracts\Accountable
     */
    public function getAccount()
    {
        if (! isset($this->account)) {
            $this->setAccount($this->getDefaultAccount());
        }

        return $this->account;
    }

    /**
     * Set the specified account.
     *
     * @param \Payavel\Orchestration\Contracts\Accountable|string|int $account
     * @return void
     *
     * @throws Exception
     */
    public function setAccount($account)
    {
        if (is_null($account = $this->driver->resolveAccount($account))) {
            throw new Exception('Invalid account.');
        }

        $this->account = $account;

        $this->gateway = null;
    }

    /**
     * Get the default account.
     *
     * @return string|int|\Payavel\Orchestration\Contracts\Accountable
     */
    public function getDefaultAccount()
    {
        return $this->driver->getDefaultAccount($this->provider);
    }

    /**
     * Get the service's gateway.
     *
     * @return \Payavel\Orchestration\ServiceRequest
     */
    protected function getGateway()
    {
        if (! isset($this->gateway)) {
            $this->setGateway();
        }

        return $this->gateway;
    }

    /**
     * Resolve a new instance of the service's gateway.
     *
     * @return void
     */
    protected function setGateway()
    {
        $provider = $this->getProvider();
        $account = $this->getAccount();

        $this->gateway = $this->driver->resolveGateway($provider, $account);
    }

    /**
     * Reset the service to its defaults.
     *
     * @return void
     */
    public function reset()
    {
        $this->driver->refresh();

        $this->gateway = $this->account = $this->provider = null;
    }

    /**
     * @param string $method
     * @param array $params
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $params)
    {
        return $this->getGateway()->request($method, $params);
    }
}
