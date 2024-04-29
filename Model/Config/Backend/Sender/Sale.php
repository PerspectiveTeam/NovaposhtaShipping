<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Backend\Sender;

use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyIndexInterface;
use Perspective\NovaposhtaShipping\Api\Data\CounterpartyOrgThirdpartyInterface;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\CounterpartyIndexFactory;
use Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyDoorsAddressesFactory;
use Perspective\NovaposhtaShipping\Model\CounterpartyorgthirdpartyWarehouseAddressesFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyDoorsAddressFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyFactory;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdpartyWarehouseAddressFactory;

/**
 * Class Sale
 */
class Sale extends Value
{
    /**
     * @var string[]
     */
    public array $counterpartyDoorsAddressMap =
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
    public array $counterpartyWarehouseAddressMap =
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
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;

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
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        NovaposhtaHelper $novaposhtaHelper,
        ManagerInterface $messageManager,
        \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyIndexFactory $counterpartyIndexResourceModelFactory,
        CounterpartyIndexFactory $counterpartyIndexModelFactory,
        \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdpartyFactory $counterpartyOrgThirdpartyModelFactory,
        CounterpartyOrgThirdpartyFactory $counterpartyOrgThirdpartyResourceModelFactory,
        CounterpartyorgthirdpartyDoorsAddressesFactory $counterpartyorgthirdpartyDoorsAddressesFactory,
        CounterpartyorgthirdpartyWarehouseAddressesFactory $counterpartyorgthirdpartyWarehouseAddressesFactory,
        CounterpartyOrgThirdpartyDoorsAddressFactory $counterpartyOrgThirdpartyDoorsAddressResourceFactory,
        CounterpartyOrgThirdpartyWarehouseAddressFactory $counterpartyOrgThirdpartyWarehouseAddressResourceFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
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
            $counterpartyIndexModelResource->load($counterpartyIndexModel, $this->getValue(), CounterpartyIndexInterface::COUNTERPARTY_REF);
            if (!$counterpartyIndexModel->getId()) {
                try {
                    /**
                     * перший - реф контрагента, другий - місто
                     * іноді реф міста може бути 00000000-0000-0000-0000-000000000000
                     * тоді дивимося в таблиці perspective_novaposhta_counterparty_c_prsn_addr_wh
                     * або perspective_novaposhta_counterparty_c_prsn_addr_doors
                     */
                    $data = explode(',', $this->getValue());
                    $counterpartyIndexModel = $this->counterpartyIndexModelFactory->create();
                    $counterpartyIndexModel->setCounterpartyRef($data[0]);
                    $counterpartyIndexModel->setContactProperty('Sender');
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
                                    $this->processExistedCounterParty($counterpartiesModel, $existModel, $singleContact, $counterpartiesResourceModel);
                                } else {
                                    $this->processNewCounterparty($counterpartiesModel, $singleContact, $counterpartiesResourceModel);
                                }
                            }
                        }
                    }
                    $this->messageManager->addSuccessMessage('The counterparty saved in the system');
                } catch (Exception $e) {
                    $this->messageManager->addExceptionMessage($e, $e->getMessage());
                }
            }
        }
        return parent::afterSave();
    }

    /**
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $counterpartiesModel
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $existModel
     * @param mixed $singleContact
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty $counterpartiesResourceModel
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function processExistedCounterParty(\Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $counterpartiesModel, \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $existModel, mixed $singleContact, CounterpartyOrgThirdparty $counterpartiesResourceModel)
    {
        $counterpartiesModel->setId($existModel->getId());
        $this->processNewCounterparty($counterpartiesModel, $singleContact, $counterpartiesResourceModel);
    }

    /**
     * @param \Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $counterpartiesModel
     * @param mixed $singleContact
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty $counterpartiesResourceModel
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function processNewCounterparty(\Perspective\NovaposhtaShipping\Model\CounterpartyOrgThirdparty $counterpartiesModel, mixed $singleContact, CounterpartyOrgThirdparty $counterpartiesResourceModel): void
    {
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
        $counterpartiesModel->setContactPersonNote($singleContact['ContactPersonNote'] ?: '');
        $counterpartiesResourceModel->save($counterpartiesModel);
    }
}
