<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Test\Unit\Api\Fixture {
    class Curl
    {
        public static ?string $responseFile = null;
        public static ?string $requestedUrl = null;
        public static ?\WeakReference $handle = null;
    }
}

namespace Perspective\NovaposhtaShipping\Api {
    use Perspective\NovaposhtaShipping\Test\Unit\Api\Fixture\Curl;

    // Redirect only the endpoint; execution, options and handle lifetime use real cURL.
    function curl_init(?string $url = null): \CurlHandle|false
    {
        if (Curl::$responseFile === null) {
            return \curl_init($url);
        }

        Curl::$requestedUrl = $url;
        $handle = \curl_init('file://' . Curl::$responseFile);
        if ($handle !== false) {
            Curl::$handle = \WeakReference::create($handle);
        }
        return $handle;
    }
}
