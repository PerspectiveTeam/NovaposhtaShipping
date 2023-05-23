<?php


namespace Perspective\NovaposhtaShipping\Api\Data;


interface CounterpartyOrgThirdpartyDoorsInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_counterparty_addresses_doors';
    const ID = "id";
    const CONTACTPERSONREF = 'ContactPersonRef';
    const REF = 'Ref';
    const CITYREF = 'CityRef';
    const SETTLEMENTREF = 'SettlementRef';
    const SETTLEMENTDESCRIPTION = 'SettlementDescription';
    const REGIONDESCRIPTION = 'RegionDescription';
    const AREADESCRIPTION = 'AreaDescription';
    const STREETREF = 'StreetRef';
    const STREETDESCRIPTION = 'StreetDescription';
    const DESCRIPTION = 'Description';
    const BUILDINGNUMBER = 'BuildingNumber';
    const FLAT = 'Flat';
    const FLOOR = 'Floor';
    const NOTE = 'Note';
    const ADDRESSNAME = 'AddressName';
    const GENERAL = 'General';
    const STREETSTYPEREF = 'StreetsTypeRef';
    const STREETSTYPE = 'StreetsType';

    public function setContactPersonRef($data);
    public function setRef($data);
    public function setCityRef($data);
    public function setSettlementRef($data);
    public function setSettlementDescription($data);
    public function setRegionDescription($data);
    public function setAreaDescription($data);
    public function setStreetRef($data);
    public function setStreetDescription($data);
    public function setDescription($data);
    public function setBuildingNumber($data);
    public function setFlat($data);
    public function setFloor($data);
    public function setNote($data);
    public function setAddressName($data);
    public function setGeneral($data);
    public function setStreetsTypeRef($data);
    public function setStreetsType($data);
    public function getContactPersonRef();
    public function getRef();
    public function getCityRef();
    public function getSettlementRef();
    public function getSettlementDescription();
    public function getRegionDescription();
    public function getAreaDescription();
    public function getStreetRef();
    public function getStreetDescription();
    public function getDescription();
    public function getBuildingNumber();
    public function getFlat();
    public function getFloor();
    public function getNote();
    public function getAddressName();
    public function getGeneral();
    public function getStreetsTypeRef();
    public function getStreetsType();
}
