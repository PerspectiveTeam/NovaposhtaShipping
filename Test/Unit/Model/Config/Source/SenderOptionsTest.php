<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Model\Config\Source;

use Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Config\Source\Sale;
use Perspective\NovaposhtaShipping\Model\Config\Source\SaleContact;
use Perspective\NovaposhtaShipping\Model\Config\Source\SaleContactAddress;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;
use PHPUnit\Framework\TestCase;

class SenderOptionsTest extends TestCase
{
    public function testContactsAreIsolatedByApiKeyAndSenderAndFailuresCanBeRetried(): void
    {
        $config = ['api_key' => 'first-key', 'sale_sender' => 'private-person'];
        $api = new SenderOptionsApi();
        $api->responses = [
            ['success' => true, 'data' => [['Ref' => 'first-contact', 'Description' => 'First']]],
            ['success' => true, 'data' => [['Ref' => 'second-contact', 'Description' => 'Second']]],
            ['success' => false, 'data' => []],
            ['success' => true, 'data' => [['Ref' => 'third-contact', 'Description' => 'Third']]]
        ];
        $source = new SaleContact($this->helper($config, $api), $this->cache());
        self::assertSame('first-contact', $source->toOptionArray()[1]['value']);
        self::assertSame('first-contact', $source->toOptionArray()[1]['value']);
        $config['api_key'] = 'second-key';
        self::assertSame('second-contact', $source->toOptionArray()[1]['value']);
        $config['sale_sender'] = 'other-sender';
        self::assertCount(1, $source->toOptionArray());
        self::assertSame('third-contact', $source->toOptionArray()[1]['value']);
        self::assertCount(4, $api->calls);
    }

    public function testSenderListDoesNotReuseAnotherAccountsCache(): void
    {
        $config = ['api_key' => 'first-key'];
        $api = new SenderOptionsApi();
        $api->responses = [
            ['success' => true, 'data' => [['Ref' => 'sender-one', 'Description' => 'One', 'CityDescription' => '']]],
            ['success' => true, 'data' => [['Ref' => 'sender-two', 'Description' => 'Two', 'CityDescription' => '']]]
        ];
        $source = new Sale($this->helper($config, $api), $this->cache());
        self::assertSame('sender-one', $source->toOptionArray()[1]['value']);
        $config['api_key'] = 'second-key';
        self::assertSame('sender-two', $source->toOptionArray()[1]['value']);
    }

    public function testAddressesBelongToSelectedContactAndAccount(): void
    {
        $config = ['api_key' => 'first-key', 'sale_sender' => 'sender,legacy-city', 'sale_sender_contact' => 'person-one'];
        $api = new SenderOptionsApi();
        $response = ['success' => true, 'data' => [
            ['Ref' => 'person-one', 'Addresses' => [
                'DoorsAddresses' => [['Ref' => 'door', 'SettlementDescription' => 'City', 'StreetDescription' => 'Street', 'BuildingNumber' => '17', 'Flat' => '66']],
                'WarehouseAddresses' => [['Ref' => 'warehouse', 'CityDescription' => 'City', 'AddressDescription' => 'Warehouse 1'], ['Ref' => 'warehouse', 'AddressDescription' => 'Duplicate']]
            ]],
            ['Ref' => 'person-two', 'Addresses' => ['WarehouseAddresses' => [['Ref' => 'other-warehouse', 'AddressDescription' => 'Other']]]]
        ]];
        $api->responses = [$response, ['success' => true, 'data' => []]];
        $source = new SaleContactAddress($this->helper($config, $api), $this->cache());
        $options = $source->toOptionArray();
        self::assertSame(['', 'door', 'warehouse'], array_column($options, 'value'));
        self::assertStringContainsString('66', $options[1]['label']);
        $config['sale_sender_contact'] = 'person-two';
        self::assertSame(['', 'other-warehouse'], array_column($source->toOptionArray(), 'value'));
        $config['api_key'] = 'second-key';
        $options = $source->toOptionArray();
        self::assertSame('', $options[0]['value']);
        self::assertStringContainsString('no saved addresses', (string)$options[0]['label']);
        self::assertCount(2, $api->calls);
        self::assertSame('sender', $api->calls[0][2]['CounterpartyRef']);
        self::assertSame(1, $api->calls[0][2]['getContactPersonAddress']);
    }

    public function testUnconfiguredSourcesReturnValidOptionArraysWithoutApiCalls(): void
    {
        $config = [];
        $api = new SenderOptionsApi();
        $helper = $this->helper($config, $api);
        $cache = $this->cache();
        foreach ([new Sale($helper, $cache), new SaleContact($helper, $cache), new SaleContactAddress($helper, $cache)] as $source) {
            $options = $source->toOptionArray();
            self::assertCount(1, $options);
            self::assertSame('', $options[0]['value']);
        }
        self::assertSame([], $api->calls);
    }

    private function helper(array &$config, NovaPoshtaApi2 $api): NovaposhtaHelper
    {
        $helper = $this->createStub(NovaposhtaHelper::class);
        $helper->method('getStoreConfigByCode')->willReturnCallback(function ($carrier, $field) use (&$config) {
            return $config[$field] ?? null;
        });
        $helper->method('getApi')->willReturn($api);
        return $helper;
    }

    private function cache(): OperationsCache
    {
        $data = [];
        $cache = $this->createStub(OperationsCache::class);
        $cache->method('load')->willReturnCallback(function ($key) use (&$data) { return $data[$key] ?? false; });
        $cache->method('save')->willReturnCallback(function ($value, $key) use (&$data) { $data[$key] = $value; return true; });
        return $cache;
    }
}

class SenderOptionsApi extends NovaPoshtaApi2
{
    public array $responses = [];
    public array $calls = [];

    public function __construct()
    {
    }

    public function request($model, $method, $params = null)
    {
        $this->calls[] = [$model, $method, $params];
        if ($this->responses === []) {
            throw new \LogicException('Unexpected API request');
        }
        return array_shift($this->responses);
    }
}
