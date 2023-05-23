<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Shipping;

use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Perspective\NovaposhtaShipping\Helper\Config;

class OrganisationDeterminer extends Template
{
    private Config $config;

    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    )
    {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isOrganisation()
    {
        return (bool)$this->getConfig()->getShippingConfigByCode('novaposhtashipping', 'is_organization');
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Helper\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
