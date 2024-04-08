<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Backend\Sender;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyInterface;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;

/**
 * Class Sale
 */
class Sale extends Value
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;
    /**
     * @var \Magento\Framework\Model\Context
     */
    private $context;
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $config;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndexFactory
     */
    private $counterpartyIndexFactory;
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
     * @var \Perspective\NovaposhtaShipping\Model\CounterpartyIndex
     */
    private $counterpartyIndexModel;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\CounterpartyIndexFactory
     */
    private $counterpartyIndexModelFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndexFactory
     */
    private $counterpartyIndexResourceModelFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdpartyFactory
     */
    private $counterpartyOrgThirdpartyModelFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyFactory
     */
    private $counterpartyOrgThirdpartyResourceModelFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddressesFactory
     */
    private $counterpartyorgthirdpartyDoorsAddressesFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyWarehouseAddressesFactory
     */
    private $counterpartyorgthirdpartyWarehouseAddressesFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddressFactory
     */
    private $counterpartyOrgThirdpartyDoorsAddressResourceFactory;
    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddressFactory
     */
    private $counterpartyOrgThirdpartyWarehouseAddressResourceFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Sale constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndexFactory $counterpartyIndexResourceModelFactory
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyIndexFactory $counterpartyIndexModelFactory
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdpartyFactory $counterpartyOrgThirdpartyModelFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyFactory $counterpartyOrgThirdpartyResourceModelFactory
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddressesFactory $counterpartyorgthirdpartyDoorsAddressesFactory
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyWarehouseAddressesFactory $counterpartyorgthirdpartyWarehouseAddressesFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddressFactory $counterpartyOrgThirdpartyDoorsAddressResourceFactory
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddressFactory $counterpartyOrgThirdpartyWarehouseAddressResourceFactory
     * @param \Perspective\NovaposhtaShipping\Api\Data\CounterpartyAddressIndexInterfaceFactory $counterpartyAddressIndexRepositoryFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        NovaposhtaHelper $novaposhtaHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndexFactory $counterpartyIndexResourceModelFactory,
        \Perspective\NovaposhtaShipping\Model\CounterpartyIndexFactory $counterpartyIndexModelFactory,
        \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdpartyFactory $counterpartyOrgThirdpartyModelFactory,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyFactory $counterpartyOrgThirdpartyResourceModelFactory,
        \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddressesFactory $counterpartyorgthirdpartyDoorsAddressesFactory,
        \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyWarehouseAddressesFactory $counterpartyorgthirdpartyWarehouseAddressesFactory,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddressFactory $counterpartyOrgThirdpartyDoorsAddressResourceFactory,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddressFactory $counterpartyOrgThirdpartyWarehouseAddressResourceFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->context = $context;
        $this->registry = $registry;
        $this->config = $config;
        $this->cacheTypeList = $cacheTypeList;
        $this->resource = $resource;
        $this->resourceCollection = $resourceCollection;
        $this->data = $data;
        $this->counterpartyIndexModelFactory = $counterpartyIndexModelFactory;
        $this->counterpartyIndexResourceModelFactory = $counterpartyIndexResourceModelFactory;
        $this->counterpartyOrgThirdpartyModelFactory = $counterpartyOrgThirdpartyModelFactory;
        $this->counterpartyOrgThirdpartyResourceModelFactory = $counterpartyOrgThirdpartyResourceModelFactory;
        $this->counterpartyorgthirdpartyDoorsAddressesFactory = $counterpartyorgthirdpartyDoorsAddressesFactory;
        $this->counterpartyorgthirdpartyWarehouseAddressesFactory = $counterpartyorgthirdpartyWarehouseAddressesFactory;
        $this->counterpartyOrgThirdpartyDoorsAddressResourceFactory = $counterpartyOrgThirdpartyDoorsAddressResourceFactory;
        $this->counterpartyOrgThirdpartyWarehouseAddressResourceFactory = $counterpartyOrgThirdpartyWarehouseAddressResourceFactory;
        $this->messageManager = $messageManager;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return \Perspective\NovaposhtaShipping\Model\Config\Backend\Sender\Sale
     */
    public function afterSave()
    {
        if ($this->isValueChanged()) {
            /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyIndex $counterpartyIndexModel */
            $counterpartyIndexModel = $this->counterpartyIndexModelFactory->create();
            /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndex $counterpartyIndexModelResource */
            $counterpartyIndexModelResource = $this->counterpartyIndexResourceModelFactory->create();
            $counterpartyIndexModelResource->load($counterpartyIndexModel, $this->getValue(), \Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface::COUNTERPARTY_REF);
            // $counterpartyIndexModel->load($this->getValue(), 'counterpartyRef');
            if (!$counterpartyIndexModel->getId()) {
                //сделано так из-за того что грид не расчитан под такое обращение с ним
                try {
                    //первый реф контрагента, второй- город
                    $data = explode(',', $this->getValue());
                    $counterpartyIndexModel = $this->counterpartyIndexModelFactory->create();
                    $counterpartyIndexModel->setCityRef($data[1]);
                    $counterpartyIndexModel->setContactProperty('Sender');
                    $counterpartyIndexModel->setCounterpartyRef($data[0]);
                    $counterpartyIndexModelResource->save($counterpartyIndexModel);
                    /**
                     * Где-то, в этом классе, происходит дублирование @see $counterpartyIndexModel
                     * Сохраняет первую запись корректно в perspective_novaposhta_counterparty_index,
                     * а потом фигачит еще 83 шт
                     * Если кто увидит и поймет в чем причина - дайте знать: faqreg@gmail.com
                     */
                    $parentCounterpartyContacts = $this->novaposhtaHelper->getApi()->request(
                        'ContactPersonGeneral',
                        'getContactPersonsList',
                        [
                            'ContactProperty' => $counterpartyIndexModel->getContactProperty(),
                            'CounterpartyRef' => $counterpartyIndexModel->getCounterpartyRef(),
                            'getContactPersonAddress' => 1,
                            'FindByString' => '',
                            'Limit' => 200,
                            'Page' => 1
                        ]
                    );
                    if (array_key_exists('success', $parentCounterpartyContacts)) {
                        if ($parentCounterpartyContacts['success'] === true) {
                            $counterpartyContactsDataArr = $parentCounterpartyContacts['data'];  //контактные лица контрагента
                            foreach ($counterpartyContactsDataArr as $singleContact) {
                                /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $counterpartiesModel */
                                $counterpartiesModel = $this->counterpartyOrgThirdpartyModelFactory->create();
                                $counterpartiesResourceModel = $this->counterpartyOrgThirdpartyResourceModelFactory->create();
                                $counterpartiesResourceModel->load($counterpartiesModel, $singleContact['Ref'], CounterpartyOrgThirdpartyInterface::REF);
                                $existModel = $counterpartiesModel;
                                $existRef = $existModel->getCounterpartyRef();
                                if ($existRef) {
                                    $counterpartiesModel->setId($existModel->getId());
                                    $counterpartiesModel->setDescription($singleContact['Description']);
                                    $counterpartiesModel->setPhone($singleContact['Phones']);
                                    $counterpartiesModel->setEmail($singleContact['Email']);
                                    $counterpartiesModel->setRef($singleContact['Ref']);
                                    $counterpartiesModel->setLastname($singleContact['LastName']);
                                    $counterpartiesModel->setFirstname($singleContact['FirstName']);
                                    $counterpartiesModel->setMiddlename($singleContact['MiddleName']);
                                    $counterpartiesModel->setAdditionalPhone($singleContact['AdditionalPhone']);
                                    $counterpartiesModel->setInfo($singleContact['Info']);
                                    $counterpartiesModel->setCounterpartyRef($singleContact['CounterpartyRef']);
                                    $counterpartiesModel->setAddresses($singleContact['Addresses']);
                                    /*Разрешенные адреса существующего конкретного контрагента */
                                    foreach ($singleContact['Addresses'] as $addressType => $addressArray) {
                                        if ($addressType === 'DoorsAddresses') {
                                            foreach ($addressArray as $address) {
                                                /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddresses $addressDoorsModel */
                                                $addressDoorsModel = $this->counterpartyorgthirdpartyDoorsAddressesFactory->create();
                                                foreach ($this->counterpartyDoorsAddressMap as $arrIndex => $method) {
                                                    $addressDoorsModel->{$method}($address[$arrIndex]);
                                                }
                                                $addressDoorsModel->setContactPersonRef($singleContact['Ref']); //parent counterparty of this contact person for this address
                                                /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddress $addressDoorsModelResource */
                                                $addressDoorsModelResource = $this->counterpartyOrgThirdpartyDoorsAddressResourceFactory->create();
                                                $addressDoorsModelResource->save($addressDoorsModel);
                                            }
                                        }
                                        if ($addressType === 'WarehouseAddresses') {
                                            foreach ($addressArray as $address) {
                                                /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddresses $addressDoorsModel */
                                                $addressWarehouseModel = $this->counterpartyorgthirdpartyWarehouseAddressesFactory->create();
                                                $addressWarehouseModel->setContactPersonRef($singleContact['Ref']);
                                                foreach ($this->counterpartyWarehouseAddressMap as $arrIndex => $method) {
                                                    $addressWarehouseModel->{$method}($address[$arrIndex]);
                                                }
                                                /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddress $addressWarehouseModelResource */
                                                $addressWarehouseModelResource = $this->counterpartyOrgThirdpartyWarehouseAddressResourceFactory->create();
                                                $addressWarehouseModelResource->save($addressWarehouseModel);
                                            }
                                        }
                                    }
                                    /* конец разборки адрессов существующего агента */
                                    $counterpartiesModel->setContactPersonNote($singleContact['ContactPersonNote']);
                                    $counterpartiesResourceModel->save($counterpartiesModel);
                                } else {
                                    $counterpartiesModel->setDescription($singleContact['Description']);
                                    $counterpartiesModel->setPhone($singleContact['Phones']);
                                    $counterpartiesModel->setEmail($singleContact['Email']);
                                    $counterpartiesModel->setRef($singleContact['Ref']);
                                    $counterpartiesModel->setLastname($singleContact['LastName']);
                                    $counterpartiesModel->setFirstname($singleContact['FirstName']);
                                    $counterpartiesModel->setMiddlename($singleContact['MiddleName']);
                                    $counterpartiesModel->setAdditionalPhone($singleContact['AdditionalPhone']);
                                    $counterpartiesModel->setInfo($singleContact['Info']);
                                    $counterpartiesModel->setCounterpartyRef($singleContact['CounterpartyRef']);
                                    $counterpartiesModel->setAddresses($singleContact['Addresses']);
                                    /*Разрешенные адреса нового конкретного контрагента */
                                    foreach ($singleContact['Addresses'] as $addressType => $addressArray) {
                                        if ($addressType === 'DoorsAddresses') {
                                            foreach ($addressArray as $address) {
                                                /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddresses $addressDoorsModel */
                                                $addressDoorsModel = $this->counterpartyorgthirdpartyDoorsAddressesFactory->create();
                                                foreach ($this->counterpartyDoorsAddressMap as $arrIndex => $method) {
                                                    $addressDoorsModel->{$method}($address[$arrIndex]);
                                                }
                                                $addressDoorsModel->setContactPersonRef($singleContact['Ref']); //parent counterparty of this contact person for this address
                                                /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddress $addressDoorsModelResource */
                                                $addressDoorsModelResource = $this->counterpartyOrgThirdpartyDoorsAddressResourceFactory->create();
                                                $addressDoorsModelResource->save($addressDoorsModel);
                                            }
                                        }
                                        if ($addressType === 'WarehouseAddresses') {
                                            foreach ($addressArray as $address) {
                                                /** @var \Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddresses $addressDoorsModel */
                                                $addressWarehouseModel = $this->counterpartyorgthirdpartyWarehouseAddressesFactory->create();
                                                $addressWarehouseModel->setContactPersonRef($singleContact['Ref']);
                                                foreach ($this->counterpartyWarehouseAddressMap as $arrIndex => $method) {
                                                    $addressWarehouseModel->{$method}($address[$arrIndex]);
                                                }
                                                /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddress $addressWarehouseModelResource */
                                                $addressWarehouseModelResource = $this->counterpartyOrgThirdpartyWarehouseAddressResourceFactory->create();
                                                $addressWarehouseModelResource->save($addressWarehouseModel);
                                            }
                                        }
                                    }
                                    /* конец разборки адрессов нового агента */
                                    $counterpartiesModel->setContactPersonNote($singleContact['ContactPersonNote']);
                                    $counterpartiesResourceModel->save($counterpartiesModel);
                                }
                            }
                        }
                    }
                    $this->messageManager->addSuccessMessage('The counterparty saved in system');
                } catch (\Exception $e) {
                    $this->messageManager->addExceptionMessage($e, $e->getMessage());
                }
            }
        }
        return parent::afterSave();
    }

    /**
     * @var string[]
     */
    public $counterpartyDoorsAddressMap =
        [
            'Ref' => 'setRef',
            'CityRef' => 'setCityRef',
            'SettlementRef' => 'setSettlementRef',
            'SettlementDescription' => 'setSettlementDescription',
            'Type' => 'setType',
            'RegionDescription' => 'setRegionDescription',
            'AreaDescription' => 'setAreaDescription',
            'StreetRef' => 'setStreetRef',
            'StreetDescription' => 'setStreetDescription',
            'Description' => 'setDescription',
            'BuildingNumber' => 'setBuildingNumber',
            'Flat' => 'setFlat',
            'Floor' => 'setFloor',
            'Note' => 'setNote',
            'AddressName' => 'setAddressName',
            'General' => 'setGeneral',
            'StreetsTypeRef' => 'setStreetsTypeRef',
            'StreetsType' => 'setStreetsType',
        ];
    /**
     * @var string[]
     */
    public $counterpartyWarehouseAddressMap =
        [
            'Ref' => 'setRef',
            'CityRef' => 'setCityRef',
            'CityDescription' => 'setCityDescription',
            'AddressDescription' => 'setAddressDescription',
            'WarehouseNumber' => 'setWarehouseNumber',
            'TypeOfWarehouse' => 'setTypeOfWarehouse',
            'General' => 'setGeneral',
            'TotalMaxWeightAllowed' => 'setTotalMaxWeightAllowed',
            'PlaceMaxWeightAllowed' => 'setPlaceMaxWeightAllowed',

        ];
    public $counterpartyAddressIndexMap =
        [
            'Ref' => 'setRef',
            'CityRef' => 'setCityRef',
            'CityDescription' => 'setCityDescription',
            'Description' => 'setDescription',
            'StreetRef' => 'setStreetRef',
            'StreetDescription' => 'setStreetDescription',
            'BuildingRef' => 'setBuildingRef',
            'BuildingDescription' => 'setBuildingDescription',
            'Note' => 'setNote',
            'AddressName' => 'setAddressName',

        ];
}
