<?php

namespace Payavel\Orchestration\Tests\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Payavel\Orchestration\Contracts\Merchantable;
use Payavel\Orchestration\Contracts\Providable;
use Payavel\Orchestration\Contracts\Serviceable;
use Payavel\Orchestration\DataTransferObjects\Merchant as MerchantDto;
use Payavel\Orchestration\DataTransferObjects\Provider as ProviderDto;
use Payavel\Orchestration\DataTransferObjects\Service as ServiceDto;
use Payavel\Orchestration\Models\Merchant as MerchantModel;
use Payavel\Orchestration\Models\Provider as ProviderModel;
use Payavel\Orchestration\Models\Service as ServiceModel;

trait CreatesServiceables
{
    protected function createService($data = [])
    {
        $createService = 'createService' . Str::studly(Config::get('orchestration.defaults.driver'));

        return $this->$createService($data);
    }

    protected function createServiceConfig($data)
    {
        $data['id'] = $data['id'] ?? Str::lower($this->faker->unique()->word());

        Config::set('orchestration.services.' . $data['id'], [
            'config' => Str::slug($data['id']),
        ]);

        return new ServiceDto($data);;
    }

    protected function createServiceDatabase($data)
    {
        return ServiceModel::factory()->create($data);
    }

    protected function createProvider($service = null, $data = [])
    {
        if (is_null($service)) {
            $service = $this->createService();
        }

        $createProvider = 'createProvider' . Str::studly(Config::get('orchestration.defaults.driver'));

        return $this->$createProvider($service, $data);
    }

    protected function createProviderConfig($service, $data)
    {
        $data['id'] = $data['id'] ?? preg_replace('/[^a-z0-9]+/i', '_', strtolower(Str::remove(['\'', ','], $this->faker->unique()->company())));
        $data['gateway'] = $data['gateway'] ?? 'App\\Services\\' . Str::studly($service->getId()) . '\\' . Str::studly($data['id']) . Str::studly($service->getId()) . 'Request';

        Config::set(Str::slug($service->getId()) . '.providers.' . $data['id'], [
            'gateway' => $data['gateway'],
        ]);

        return new ProviderDto($service, $data);
    }

    protected function createProviderDatabase($service, $data)
    {
        $data['service_id'] = $service->getId();

        return ProviderModel::factory()->create($data);
    }

    protected function createMerchant($service = null, $data = [])
    {
        if (is_null($service)) {
            $service = $this->createService();
        }

        $createMerchant = 'createMerchant' . Str::studly(Config::get('orchestration.defaults.driver'));

        return $this->$createMerchant($service, $data);
    }

    protected function createMerchantConfig($service, $data)
    {
        $data['id'] = $data['id'] ?? preg_replace('/[^a-z0-9]+/i', '_', strtolower(Str::remove(['\'', ','], $this->faker->unique()->company())));

        Config::set(Str::slug($service->getId()) . '.merchants.' . $data['id'], []);

        return new MerchantDto($service, $data);
    }

    protected function createMerchantDatabase($service, $data)
    {
        $data['service_id'] = $service->getId();

        return MerchantModel::factory()->create($data);
    }

    protected function linkMerchantToProvider(Merchantable $merchant, Providable $provider, $data = [])
    {
        $linkMerchantToProvider = 'linkMerchantToProvider' . Str::studly(Config::get('orchestration.defaults.driver'));

        $this->$linkMerchantToProvider($merchant, $provider, $data);
    }

    protected function linkMerchantToProviderConfig(Merchantable $merchant, Providable $provider, $data)
    {
        Config::set(
            $providers = Str::slug($merchant->getService()->getId()) . '.merchants.' . $merchant->getId() . '.providers',
            array_merge(
                Config::get($providers, []),
                [$provider->getId() => $data]
            )
        );
    }

    protected function linkMerchantToProviderDatabase(Merchantable $merchant, Providable $provider, $data)
    {
        $merchant->providers()->sync([$provider->getId() => $data], false);
    }

    protected function setDefaultsForService(Serviceable $service, Merchantable $merchant = null, Providable $provider = null)
    {
        $setDefaultsForService = 'setDefaultsForService' . Str::studly(Config::get('orchestration.defaults.driver'));

        $this->$setDefaultsForService($service, $merchant, $provider);
    }

    protected function setDefaultsForServiceConfig(Serviceable $service, Merchantable $merchant = null, Providable $provider = null)
    {
        Config::set(
            Str::slug($service->getId()) . '.defaults.merchant',
            $merchant instanceof Merchantable ? $merchant->getId() : $merchant
        );

        if (is_null($provider) && ! is_null($merchant)) {
            $provider = Collection::make(
                Config::get(Str::slug($service->getId()) . '.merchants.' . $merchant->getId() . '.providers')
            )
                ->keys()
                ->first();
        }

        Config::set(
            Str::slug($service->getId()) . '.defaults.provider',
            $provider instanceof Providable ? $provider->getId() : $provider
        );
    }

    protected function setDefaultsForServiceDatabase(Serviceable $service, Merchantable $merchant = null, Providable $provider = null)
    {
        if (is_null($provider) && ! is_null($merchant)) {
            $provider = $merchant->default_provider_id;
        }

        $service->update([
            'default_merchant_id' => $merchant instanceof Merchantable ? $merchant->getId() : $merchant,
            'default_provider_id' => $provider instanceof  Providable ? $provider->getId() : $provider,
        ]);
    }
}
