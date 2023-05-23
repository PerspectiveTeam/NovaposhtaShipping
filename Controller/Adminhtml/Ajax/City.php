<?php


namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Locale\Resolver;

/**
 * Class City
 * Returns list of city for admin side
 */
class City implements \Magento\Framework\App\ActionInterface
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
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Address $modelAddress,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository,
        Resolver $resolver
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->modelAddress = $modelAddress;
        $this->customerSession = $customerSession;
        $this->jsonSerializer = $jsonSerializer;
        $this->cityRepository = $cityRepository;
        $this->resolver = $resolver;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** structure in $resultJson
         *
         *   $resultJson = [
              'cityList' => [
                  ['label' => 'labelVal', 'value' => 'testVal'],
                  ['label' => 'labelVal', 'value' => 'testVal'],
                  ['label' => 'labelVal', 'value' => 'testVal'],
                  ['label' => 'labelVal', 'value' => 'testVal'],
                  ['label' => 'labelVal', 'value' => 'testVal'],
                  ['label' => 'labelVal', 'value' => 'testVal']
              ]];
         */
        $resultJson = [];
        $resultJson['cityList'] = $this->cityRepository->getAllCityReturnCityId($this->resolver->getLocale());
        $billingID = $this->customerSession->getCustomer()->getDefaultBilling();
        $address = $this->modelAddress->load($billingID);
        $data = $address->getData();
        $resultJson = array_merge($resultJson, ['customerData' => $data]);
        $this->serializedJsonString = $this->jsonSerializer->serialize($resultJson);
        return $this->resultJsonFactory->create()->setData($this->serializedJsonString);
    }
}
