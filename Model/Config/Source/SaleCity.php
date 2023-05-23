<?php

namespace Perspective\NovaposhtaShipping\Model\Config\Source;
class SaleCity
{
    /**
     * @var \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper
     */
    private $novaposhtaHelper;
    /**
     * @var \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface
     */
    private $cityRepository;

    public function __construct(
        \Perspective\NovaposhtaShipping\Helper\NovaposhtaHelper $novaposhtaHelper,
        \Perspective\NovaposhtaCatalog\Api\CityRepositoryInterface $cityRepository
    ) {
        $this->novaposhtaHelper = $novaposhtaHelper;
        $this->cityRepository = $cityRepository;
    }

    public function toOptionArray($isMultiselect = false)
    {
        $colllection = $this->cityRepository->getAllCityReturnCityId('uk_UA');
        if (isset($colllection)) {
            return $colllection;
        } else {
            return [['label' => __('Firstly you need to specify API key and make cities list update'), 'value' => -1]];
        }
    }
}
