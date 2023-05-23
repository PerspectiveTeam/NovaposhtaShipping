<?php

namespace Perspective\NovaposhtaShipping\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\DataPersistorInterface;
use Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface;
use Perspective\NovaposhtaShipping\Model\Quote\Info\Session\QuoteObject;

/**
 * Класс добавляет список городов в jsLayout
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    const CITY_NOVAPOSHTA_FIELD = 'city_novaposhta_field';
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
     */
    public function __construct(
        DataPersistorInterface $dataPersistor,
        CityRepositoryInterface $cityRepository
    ) {
        $this->cityRepository = $cityRepository;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process($jsLayout)
    {
        $cities = array();
        if ($fieldValue = $this->dataPersistor->get(self::CITY_NOVAPOSHTA_FIELD)) {
            $city = $this->cityRepository->getCityByCityRef($fieldValue);

            if (!empty($city->getRef())) {
                $cities[] = [
                    'value' => $city->getRef(),
                    'label' => $city->getDescriptionUa(),
                ];
            }
        }

        if (!isset($jsLayout['components']['checkoutProvider']['dictionaries']['city'])) {
            $jsLayout['components']['checkoutProvider']['dictionaries']['city'] = $cities;
        }
        return $jsLayout;
    }

}
