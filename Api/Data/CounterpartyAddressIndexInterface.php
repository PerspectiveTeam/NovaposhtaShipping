<?php


namespace Perspective\NovaposhtaShipping\Api\Data;


interface CounterpartyAddressIndexInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_counterparty_address_index';
    const ID = "id";
    const COUNTERPARTY_REF = 'CounterpartyRef';
    const CITY_REF = 'CityRef';
    const REF = 'Ref';
    const DESCRIPTION = 'Description';
    const CITY_DESCRIPTION = 'CityDescription';
    const STREET_REF = 'StreetRef';
    const STREET_DESCRIPTION = 'StreetDescription';
    const BUILDING_REF = 'BuildingRef';
    const BUILDING_DESCRIPTION = 'BuildingDescription';
    const NOTE = 'Note';
    const ADDRESS_NAME = 'AddressName';
    public function getThisResourceModel();
    public function getThisResourceCollectionModel();
    public function setCounterpartyRef($data);
    public function setCityRef($data);
    public function setRef($data);
    public function setDescription($data);
    public function setCityDescription($data);
    public function setStreetRef($data);
    public function setStreetDescription($data);
    public function setBuildingRef($data);
    public function setBuildingDescription($data);
    public function setNote($data);
    public function setAddressName($data);
    public function getCounterpartyRef();
    public function getCityRef();
    public function getRef();
    public function getDescription();
    public function getCityDescription();
    public function getStreetRef();
    public function getStreetDescription();
    public function getBuildingRef();
    public function getBuildingDescription();
    public function getNote();
    public function getAddressName();
}
