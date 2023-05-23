<?php


namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Locale\Resolver;
use Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface;

/**
 * Class Warehouse
 * Returns list of warehouse for admin side
 */
class Warehouse implements \Magento\Framework\App\ActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Customer\Model\Address
     */
    private $modelAddress;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;
    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $resolver;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface
     */
    private $warehouseRepository;
    /**
     * @var bool|false|string
     */
    public $serializedJsonString;

    /**
     * City constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Customer\Model\Address $modelAddress
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     * @param \Magento\Framework\Locale\Resolver $resolver
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaCatalog\Api\WarehouseRepositoryInterface $warehouseRepository
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Address $modelAddress,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository,
        Resolver $resolver,
        \Magento\Framework\App\RequestInterface $request,
        WarehouseRepositoryInterface $warehouseRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->modelAddress = $modelAddress;
        $this->customerSession = $customerSession;
        $this->jsonSerializer = $jsonSerializer;
        $this->cityRepository = $cityRepository;
        $this->resolver = $resolver;
        $this->request = $request;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** structure in $resultJson
         *
         *   $resultJson = [
         * 'cityList' => [
         * ['label' => 'labelVal', 'value' => 'testVal'],
         * ['label' => 'labelVal', 'value' => 'testVal'],
         * ['label' => 'labelVal', 'value' => 'testVal'],
         * ['label' => 'labelVal', 'value' => 'testVal'],
         * ['label' => 'labelVal', 'value' => 'testVal'],
         * ['label' => 'labelVal', 'value' => 'testVal']
         * ]];
         */
        $resultJson = [];
        $cityId = 1;
        if (is_array($this->request->getParam('cityId'))) {
            $cityId = (int)($this->request->getParam('cityId')[0]);
        } else {
            $cityId = $this->request->getParam('cityId');
        }
        /** @var \Perspective\NovaposhtaCatalog\Model\City\City $cityObject */
        $cityObject = $this->cityRepository->getCityByCityId((int)$cityId);
        if (!$cityObject->getRef()){
            $cityObject = $this->cityRepository->getCityByCityRef($cityId);
        }
        $warehouseCollection = $this->warehouseRepository->getListOfWarehousesByCityRef(
            $cityObject->getRef(),
            $this->resolver->getLocale()
        );
        $resultJson['warehouseList'] = $warehouseCollection;
        $billingID = $this->customerSession->getCustomer()->getDefaultBilling();
        $address = $this->modelAddress->load($billingID);
        $data = $address->getData();
        $resultJson = array_merge($resultJson, ['customerData' => $data]);
        $this->serializedJsonString = $this->jsonSerializer->serialize($resultJson);
        return $this->resultJsonFactory->create()->setData($this->serializedJsonString);
    }
}
