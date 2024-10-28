<?php

namespace Perspective\NovaposhtaShipping\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation;

class VisualisatorRepository
{

    /**
     * @var \Perspective\NovaposhtaShipping\Model\BoxShippingVisualisationFactory
     */
    private BoxShippingVisualisationFactory $boxShippingVisualisationFactory;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation
     */
    private BoxShippingVisualisation $boxShippingVisualisationResourceModel;

    /**
     * @var \Perspective\NovaposhtaShipping\Model\ResourceModel\BoxShippingVisualisation\CollectionFactory
     */
    private BoxShippingVisualisation\CollectionFactory $boxShippingVisualisationCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private TimezoneInterface $timezone;

    public function __construct(
        BoxShippingVisualisationFactory $boxShippingVisualisationFactory,
        BoxShippingVisualisation $boxShippingVisualisationResourceModel,
        BoxShippingVisualisation\CollectionFactory $boxShippingVisualisationCollectionFactory,
        TimezoneInterface $timezone,
    ) {
        $this->boxShippingVisualisationFactory = $boxShippingVisualisationFactory;
        $this->boxShippingVisualisationResourceModel = $boxShippingVisualisationResourceModel;
        $this->boxShippingVisualisationCollectionFactory = $boxShippingVisualisationCollectionFactory;
        $this->timezone = $timezone;
    }

    /**
     * @param $cartId
     * @param $visualisatorData
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function process($cartId, $visualisatorData)
    {
        $boxShippingVisualisationCollection = $this->boxShippingVisualisationCollectionFactory->create();
        $boxShippingVisualisationCollection->addFieldToFilter('cart_id', $cartId);
        foreach ($boxShippingVisualisationCollection->getIterator() as $item) {
            $this->boxShippingVisualisationResourceModel->delete($item);
        }
        foreach ($visualisatorData as $urlOfBoxVisualisation) {
            $urlOfBoxVisualisationModel = $this->boxShippingVisualisationFactory->create()->setData(
                [
                    'cart_id' => $cartId,
                    'box_url' => base64_encode($urlOfBoxVisualisation),
                    'created_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
                ]
            );
            $this->boxShippingVisualisationResourceModel->save($urlOfBoxVisualisationModel);
        }
    }
}
