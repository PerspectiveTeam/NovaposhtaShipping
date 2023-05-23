<?php

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Order\Shipment;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper;
use Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory;

class SearchContactPersonAction implements ActionInterface
{

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
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory
     */
    private $counterpartyOrgThirdpartyCollectionFactory;

    /**
     * SearchContactPersonAction constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\CollectionFactory $counterpartyOrgThirdpartyCollectionFactory
     */
    public function __construct(
        RequestInterface $request,
        NovaposhtaHelper $novaposhtaHelper,
        JsonFactory $resultJsonFactory,
        Json $jsonSerializer,
        CollectionFactory $counterpartyOrgThirdpartyCollectionFactory

    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->counterpartyOrgThirdpartyCollectionFactory = $counterpartyOrgThirdpartyCollectionFactory;
    }

    private $optionsSeat;

    public function searchContactPersonAction()
    {
        //город в 0, контрагент в 1
        $cityData = explode(',', $this->request->getParam('citySender'));
        if (isset($cityData[0]) && isset ($cityData[1])) {
            $citySender = $cityData[0];
            $counterpartyRef = $cityData[1];
        } else {
            return $this->resultJsonFactory->create()->setData([]);
        }
        /** @var \Perspective\NovaposhtaShipping\Model\ResourceModel\CounterpartyOrgThirdparty\Collection $contactPersonsCollection */
        $contactPersonsCollection = $this->counterpartyOrgThirdpartyCollectionFactory->create();
        $contactPersonsCollection
            ->addFieldToFilter('counterpartyRef', ['like' => $counterpartyRef])
            ->getItems();
        foreach ($contactPersonsCollection as $idx => $value) {
            $result[$idx]['description'] = $value->getDescription();
            $result[$idx]['ref'] = $value->getRef();
        }
        //$this->serializedJsonString = $this->jsonSerializer->serialize($result);
        return $this->resultJsonFactory->create()->setData($result);
    }

    public function execute()
    {
        return $this->searchContactPersonAction();
    }
}
