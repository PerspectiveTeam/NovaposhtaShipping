<?php

namespace Perspective\NovaposhtaShipping\Controller\Adminhtml\Order\Shipment;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Perspective\NovaposhtaShipping\Model\Shipment\ShipmentCreator;

class ProduceTtnWarehouseAction implements ActionInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var bool|string
     */
    private $serializedJsonString;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\Shipment\ShipmentCreator
     */
    private ShipmentCreator $shipmentCreator;

    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        Json $jsonSerializer,
        ShipmentCreator $shipmentCreator
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->shipmentCreator = $shipmentCreator;
    }

    public function execute()
    {
        $object = new DataObject();
        $object->setData('order_id', $this->request->getParam('order_id'));
        $object->setData('sender', $this->request->getParam('sender'));
        $object->setData('contact_person', $this->request->getParam('contactPerson'));
        $object->setData('contact_person_address', $this->request->getParam('contactPersonAddress'));
        $object->setData('recipient_city', $this->request->getParam('recipientCity'));
        $object->setData('recipient_warehouse', $this->request->getParam('warehouse'));
        $response = $this->shipmentCreator->process($object);

        $this->serializedJsonString = $this->jsonSerializer->serialize($response);
        return $this->resultJsonFactory->create()->setData($this->serializedJsonString);
    }
}
