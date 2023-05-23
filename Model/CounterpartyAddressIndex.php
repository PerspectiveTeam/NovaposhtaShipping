<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\AbstractExtensibleModel;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyAddressIndexInterface;

/**
 * Class CounterpartyAddressIndex
 * @package Perspective\NovaposhtaShipping\Model
 */
class CounterpartyAddressIndex extends AbstractExtensibleModel implements CounterpartyAddressIndexInterface
{
    /**
     * @var \Magento\Framework\Model\Context
     */
    private $context;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndexFactory
     */
    private $counterpartyAddressIndexFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var \Magento\Framework\Model\ResourceModel\AbstractResource|null
     */
    private $resource;
    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|null
     */
    private $resourceCollection;
    /**
     * @var array
     */
    private $data;

    /**
     * CounterpartyAddressIndex constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndexFactory $counterpartyAddressIndexFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndexFactory $counterpartyAddressIndexFactory,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory $collectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->context = $context;
        $this->registry = $registry;
        $this->extensionFactory = $extensionFactory;
        $this->customAttributeFactory = $customAttributeFactory;
        $this->counterpartyAddressIndexFactory = $counterpartyAddressIndexFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;
        $this->data = $data;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init(\Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex::class);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setCounterpartyRef($data)
    {
        return $this->setData(self::COUNTERPARTY_REF, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setCityRef($data)
    {
        return $this->setData(self::CITY_REF, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setRef($data)
    {
        return $this->setData(self::REF, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setDescription($data)
    {
        return $this->setData(self::DESCRIPTION, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setCityDescription($data)
    {
        return $this->setData(self::CITY_DESCRIPTION, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setStreetRef($data)
    {
        return $this->setData(self::STREET_REF, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setStreetDescription($data)
    {
        return $this->setData(self::STREET_DESCRIPTION, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setBuildingRef($data)
    {
        return $this->setData(self::BUILDING_REF, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setBuildingDescription($data)
    {
        return $this->setData(self::BUILDING_DESCRIPTION, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setNote($data)
    {
        return $this->setData(self::NOTE, $data);
    }

    /**
     * @param $data
     * @return \Perspective\NovaposhtaShipping\Model\CounterpartyAddressIndex
     */
    public function setAddressName($data)
    {
        return $this->setData(self::ADDRESS_NAME, $data);
    }

    /**
     * @return mixed|null
     */
    public function getCounterpartyRef()
    {
        return $this->getData(self::COUNTERPARTY_REF);
    }

    /**
     * @return mixed|null
     */
    public function getCityRef()
    {
        return $this->getData(self::CITY_REF);
    }

    /**
     * @return mixed|null
     */
    public function getRef()
    {
        return $this->getData(self::REF);
    }

    /**
     * @return mixed|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @return mixed|null
     */
    public function getCityDescription()
    {
        return $this->getData(self::CITY_DESCRIPTION);
    }

    /**
     * @return mixed|null
     */
    public function getStreetRef()
    {
        return $this->getData(self::STREET_REF);
    }

    /**
     * @return mixed|null
     */
    public function getStreetDescription()
    {
        return $this->getData(self::STREET_DESCRIPTION);
    }

    /**
     * @return mixed|null
     */
    public function getBuildingRef()
    {
        return $this->getData(self::BUILDING_REF);
    }

    /**
     * @return mixed|null
     */
    public function getBuildingDescription()
    {
        return $this->getData(self::BUILDING_DESCRIPTION);
    }

    /**
     * @return mixed|null
     */
    public function getNote()
    {
        return $this->getData(self::NOTE);
    }

    /**
     * @return mixed|null
     */
    public function getAddressName()
    {
        return $this->getData(self::ADDRESS_NAME);
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndexFactory
     */
    public function getThisResourceModel()
    {
        return $this->counterpartyAddressIndexFactory->create();
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyAddressIndex\CollectionFactory
     */
    public function getThisResourceCollectionModel()
    {
        return $this->collectionFactory->create();
    }
}
