<?php


namespace Perspective\NovaposhtaShipping\Api\Data;

/**
 * Interface ShippingCheckoutAddressInterface
 */
interface CounterpartyIndexInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_counterparty_index';
    const ID = "id";
    const COUNTERPARTY_REF = "counterpartyRef";
    const CONTACT_PROPERTY = "contactProperty";
    const CITY_REF = "city_ref";

    public function getCounterpartyRef();

    public function getContactProperty();

    public function getCityRef();

    public function setCounterpartyRef($data);

    public function setContactProperty($data);

    public function setCityRef($data);


}
