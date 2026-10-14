<?php

declare(strict_types=1);

use Perspective\NovaposhtaShipping\Api\NovaPoshtaApi2;
use Perspective\NovaposhtaShipping\Test\Unit\Api\Fixture\Curl;

require dirname(__DIR__, 2) . '/Api/NovaPoshtaApi2.php';
require dirname(__DIR__) . '/Unit/Api/Fixture/Curl.php';

error_reporting(E_ALL);
set_error_handler(static function (int $level, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $level, $file, $line);
});

$responseFile = tempnam(sys_get_temp_dir(), 'np-api-smoke-');
Curl::$responseFile = $responseFile;
$checks = 0;

try {
    $success = ['success' => true, 'data' => [['Ref' => 'city-ref']], 'errors' => []];
    $cases = [
        ['array', json_encode($success), $success, false],
        ['array', json_encode($success), $success, true],
        ['json', json_encode($success), json_encode($success), false],
        ['xml', '<root><success>true</success></root>', '<root><success>true</success></root>', false],
        ['array', '{"success":false,"data":[],"errors":["API error"]}',
            ['success' => false, 'data' => [], 'errors' => ['API error']], false],
    ];

    foreach ($cases as [$format, $body, $expected, $throwErrors]) {
        file_put_contents($responseFile, $body);
        $api = new NovaPoshtaApi2('test-key', 'ua', $throwErrors);
        $api->setFormat($format);
        $actual = $api->request('Address', 'getCities', ['Page' => 1]);
        if ($actual !== $expected || Curl::$handle === null || Curl::$handle->get() !== null) {
            throw new RuntimeException('Unexpected response or retained cURL handle for ' . $format);
        }
        $checks++;
    }

    file_put_contents($responseFile, '{"success":false,"data":[],"errors":["API error"]}');
    try {
        (new NovaPoshtaApi2('test-key', 'ua', true))->request('Address', 'getCities');
        throw new RuntimeException('Expected API error was not thrown');
    } catch (Exception $exception) {
        if ($exception->getMessage() !== 'API error') {
            throw $exception;
        }
        $checks++;
    }

    echo 'PHP ', PHP_VERSION, ': PASS (', $checks, ' API checks, E_ALL)', PHP_EOL;
} finally {
    Curl::$responseFile = null;
    Curl::$handle = null;
    unlink($responseFile);
    restore_error_handler();
}
