<?php


namespace Perspective\NovaposhtaShipping\Api\Data;


interface CounterpartyOrgThirdpartyWarehouseInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_counterparty_addresses_warehouses';
    const ID = "id";
    const CONTACTPERSONREF = 'ContactPersonRef';
    const REF = 'Ref';
    const CITYREF = 'CityRef';
    const CITYDESCRIPTION = 'CityDescription';
    const ADDRESSDESCRIPTION = 'AddressDescription';
    const WAREHOUSENUMBER = 'WarehouseNumber';
    const TYPEOFWAREHOUSE = 'TypeOfWarehouse';
    const GENERAL = 'General';
    const TOTALMAXWEIGHTALLOWED = 'TotalMaxWeightAllowed';
    const PLACEMAXWEIGHTALLOWED = 'PlaceMaxWeightAllowed';


    public function setContactPersonRef($data);
    public function setRef($data);
    public function setCityRef($data);
    public function setCityDescription($data);
    public function setAddressDescription($data);
    public function setWarehouseNumber($data);
    public function setTypeOfWarehouse($data);
    public function setGeneral($data);
    public function setTotalMaxWeightAllowed($data);
    public function setPlaceMaxWeightAllowed($data);
    public function getContactPersonRef();
    public function getRef();
    public function getCityRef();
    public function getCityDescription();
    public function getAddressDescription();
    public function getWarehouseNumber();
    public function getTypeOfWarehouse();
    public function getGeneral();
    public function getTotalMaxWeightAllowed();
    public function getPlaceMaxWeightAllowed();

}
