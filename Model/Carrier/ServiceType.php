<?php

namespace Perspective\NovaposhtaShipping\Model\Carrier;

use Magento\Framework\DataObject;

class ServiceType
{

    /**
     * @param \Magento\Framework\DataObject $object
     * @return string
     */
    public function getServiceType(DataObject $object): string
    {
        $serviceType = 'DoorsDoors';
        if ($object->getCurrentMethod() === 'w2w') {
            $serviceType = 'WarehouseWarehouse';
        }
        if ($object->getCurrentMethod() === 'w2c') {
            $serviceType = 'WarehouseDoors';
        }
        if ($object->getCurrentMethod() === 'c2c') {
            $serviceType = 'DoorsDoors';
        }
        if ($object->getCurrentMethod() === 'c2w') {
            $serviceType = 'DoorsWarehouse';
        }
        if ($object->getCurrentMethod() === 'w2c') {
            $serviceType = 'WarehouseDoors';
        }
        if ($object->getCurrentMethod() === 'c2c') {
            $serviceType = 'DoorsDoors';
        }
        if ($object->getCurrentMethod() === 'c2w') {
            $serviceType = 'DoorsWarehouse';
        }
        return $serviceType;
    }
}
