<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Api;

use Magento\Framework\App\ErrorHandler;
use Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\Carrier\AreaAndRegion;
use Perspective\NovaposhtaShipping\Model\Carrier\NovaposhtaApi;
use Perspective\NovaposhtaShipping\Model\Config\Source\Sale;
use Perspective\NovaposhtaShipping\Service\Cache\OperationsCache;
use Perspective\NovaposhtaShipping\Test\Unit\Api\Fixture\Curl;
use PHPUnit\Framework\TestCase;

class NovaPoshtaApi2Test extends TestCase
{
    private string $responseFile;
    private int $previousErrorReporting;

    protected function setUp(): void
    {
        require_once __DIR__ . '/Fixture/Curl.php';
        $this->responseFile = tempnam(sys_get_temp_dir(), 'np-api-test-');
        Curl::$responseFile = $this->responseFile;
        Curl::$requestedUrl = null;
        Curl::$handle = null;
        $this->previousErrorReporting = error_reporting(E_ALL);
        set_error_handler([new ErrorHandler(), 'handler']);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        error_reporting($this->previousErrorReporting);
        Curl::$responseFile = null;
        Curl::$requestedUrl = null;
        Curl::$handle = null;
        unlink($this->responseFile);
    }

    public function testArrayResponseAndHandleLifetimeWithMagentoErrorHandler(): void
    {
        $response = ['success' => true, 'data' => [['Ref' => 'city-ref']], 'errors' => []];
        file_put_contents($this->responseFile, json_encode($response));

        $api = new NovaPoshtaApi2('test-key');
        self::assertSame($response, $api->request('Address', 'searchSettlements', ['CityName' => 'Kyiv']));
        self::assertSame('https://api.novaposhta.ua/v2.0/json/', Curl::$requestedUrl);
        self::assertNotNull(Curl::$handle);
        self::assertNull(Curl::$handle->get());
    }

    public function testJsonResponseRemainsUnmodified(): void
    {
        $response = '{"success":true,"data":[],"errors":[]}';
        file_put_contents($this->responseFile, $response);

        $api = new NovaPoshtaApi2('test-key');
        $api->setFormat('json');
        self::assertSame($response, $api->request('Address', 'getCities'));
    }

    public function testXmlResponseRemainsUnmodified(): void
    {
        $response = '<root><success>true</success><data/></root>';
        file_put_contents($this->responseFile, $response);

        $api = new NovaPoshtaApi2('test-key');
        $api->setFormat('xml');
        self::assertSame($response, $api->request('Address', 'getCities', ['Page' => 1]));
        self::assertSame('https://api.novaposhta.ua/v2.0/xml/', Curl::$requestedUrl);
    }

    public function testApiErrorsAreReturnedWhenExceptionsAreDisabled(): void
    {
        $response = ['success' => false, 'data' => [], 'errors' => ['First error', 'Second error']];
        file_put_contents($this->responseFile, json_encode($response));

        $api = new NovaPoshtaApi2('test-key');
        self::assertSame($response, $api->request('Address', 'getCities'));
    }

    public function testApiErrorsStillThrowWhenExceptionsAreEnabled(): void
    {
        file_put_contents($this->responseFile, json_encode([
            'success' => false, 'data' => [], 'errors' => ['First error', 'Second error'],
        ]));

        $api = new NovaPoshtaApi2('test-key', 'ua', true);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("First error\nSecond error");
        $api->request('Address', 'getCities');
    }

    public function testShippingRateAreaLookupUsesTheRealApiClient(): void
    {
        $response = ['success' => true, 'data' => [['Area' => 'area-ref']], 'errors' => []];
        file_put_contents($this->responseFile, json_encode($response));
        $provider = $this->createStub(NovaposhtaApi::class);
        $provider->method('getApi')->willReturn(new NovaPoshtaApi2('test-key'));

        self::assertSame($response, (new AreaAndRegion($provider))->getAreaAndRegionData('Kyiv'));
    }

    public function testAdminSenderOptionsUseTheRealApiClientOnCacheMiss(): void
    {
        file_put_contents($this->responseFile, json_encode([
            'success' => true,
            'data' => [['Ref' => 'sender-ref', 'Description' => 'Sender', 'CityDescription' => 'Kyiv']],
            'errors' => [],
        ]));
        $helper = $this->createStub(NovaposhtaHelper::class);
        $helper->method('getStoreConfigByCode')->willReturn('test-key');
        $helper->method('getApi')->willReturn(new NovaPoshtaApi2('test-key'));
        $cache = $this->createStub(OperationsCache::class);
        $cache->method('load')->willReturn(false);
        $cache->method('save')->willReturn(true);

        $options = (new Sale($helper, $cache))->toOptionArray();
        self::assertSame('sender-ref', $options[1]['value']);
        self::assertSame('Sender Kyiv', $options[1]['label']);
    }
}
