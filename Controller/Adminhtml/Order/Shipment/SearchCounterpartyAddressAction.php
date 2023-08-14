<?php

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Order\Shipment;

class SearchCounterpartyAddressAction implements \Magento\Framework\App\ActionInterface
{
    private $optionsSeat;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var bool|string
     */
    private $serializedJsonString;
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Carrier\Sender
     */
    private $sender;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Perspective\NovaposhtaShipping\Model\Carrier\Sender $sender

    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->sender = $sender;
    }

    public function searchCounterpartyAddressAction()
    {
        //город в 0, контрагент в 1
        $cityData = explode(',', $this->request->getParam('citySender'));
        if (isset ($cityData[0]) && isset ($cityData[1])) {
            $citySender = $cityData[0];
            $counterpartyRef = $cityData[1];
        } else {
            return $this->resultJsonFactory->create()->setData([]);
        }
        $result = $this->sender->searchCounterpartyAddress($counterpartyRef, $citySender);
        $this->serializedJsonString = $this->jsonSerializer->serialize($result);
        return $this->resultJsonFactory->create()->setData($this->serializedJsonString);
    }

    public function execute()
    {
        return $this->searchCounterpartyAddressAction();
    }
}
