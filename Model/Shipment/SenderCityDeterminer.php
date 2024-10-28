<?php

namespace Perspective\NovaposhtaShipping\Model\Shipment;

use Magento\Framework\App\ResourceConnection;

class SenderCityDeterminer
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private ResourceConnection $resource;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    public function getCityByDeliveryTechnologyAndContactPersonAddress($deliveryTechnology, $contactPersonAddress)
    {
        $city = '';
        if ($deliveryTechnology == 'w2w' || $deliveryTechnology == 'w2c') {
            $city = $this->getCityInContactPersonWarehouseTable($contactPersonAddress);
        } elseif ($deliveryTechnology == 'c2w' || $deliveryTechnology == 'c2c') {
            $city = $this->getCityInContactPersonAddressTable($contactPersonAddress);
        }
        return $city;
    }

    private function getCityInContactPersonAddressTable($contactPersonAddress)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('perspective_novaposhta_counterparty_c_prsn_addr_doors');
        $select = $connection->select();
        $select->from($tableName, ['CityRef']);
        $select->where('`Ref` LIKE "'. $contactPersonAddress.'"');
        $city = $connection->fetchOne($select);
        return $city;
    }
    private function getCityInContactPersonWarehouseTable($contactPersonAddress)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('perspective_novaposhta_counterparty_c_prsn_addr_wh');
        $select = $connection->select();
        $select->from($tableName, ['CityRef']);
        $select->where('`Ref` LIKE "'. $contactPersonAddress.'"');
        $city = $connection->fetchOne($select);
        return $city;
    }
}
