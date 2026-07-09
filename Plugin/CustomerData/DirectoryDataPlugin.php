<?php

declare(strict_types=1);

namespace Perspective\NovaposhtaShipping\Plugin\CustomerData;

use Magento\Checkout\CustomerData\DirectoryData;
use Perspective\NovaposhtaCatalog\Helper\Config;

class DirectoryDataPlugin
{
    /**
     * @var Config
     */
    private Config $catalogConfig;

    public function __construct(
        Config $catalogConfig
    ) {
        $this->catalogConfig = $catalogConfig;
    }

    public function afterGetSectionData(DirectoryData $subject, array $result): array
    {
        if (empty($result['UA']['regions'])) {
            return $result;
        }

        $directoryMap = $this->catalogConfig->getDirectoryMap();
        if (empty($directoryMap)) {
            return $result;
        }

        foreach ($result['UA']['regions'] as &$regionData) {
            if (!isset($directoryMap[$regionData['code']])) {
                continue;
            }
            $regionData['area_ref'] = $directoryMap[$regionData['code']];
        }
        unset($regionData);

        return $result;
    }
}
