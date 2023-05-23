<?php

namespace Perspective\NovaposhtaShipping\Plugin\Model;

use Perspective\NovaposhtaCatalog\Model\StreetRepository;

class StreetRepositoryPlugin
{
    /**
     * @param StreetRepository $subject
     * @param array $result
     * @param string $cityRef
     * @return array
     */
    public function afterGetFormattedByCityRef(StreetRepository $subject, array $result, string $cityRef): array
    {
        $unshiftValue = [
            'value' => 'none',
            'label' => __('Enter street')
        ];
        array_unshift($result,$unshiftValue);
        return $result;
    }
}
