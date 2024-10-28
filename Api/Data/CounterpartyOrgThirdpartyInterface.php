<?php


namespace Perspective\NovaposhtaShipping\Api\Data;

/**
 * Interface ShippingCheckoutAddressInterface
 */
interface CounterpartyOrgThirdpartyInterface
{
    const DATABASE_TABLE_NAME = 'perspective_novaposhta_counterparty_contact_person';
    const ID = "id";
    const DESCRIPTION = "description";
    const REF = "ref";
    const FIRSTNAME = "firstname";
    const MIDDLENAME = "middlename";
    const LASTNAME = "lastname";
    const COUNTERPARTY_REF = "counterpartyRef";
    const PHONE = "phone";
    const INFO = "info";
    const EMAIL = "email";
    const CONTACT_PERSON_NOTE = "contact_person_note";
    const ADDRESSES = "addresses";
    const ADDITIONAL_PHONE = "additional_phone";

    public function getDescription();

    public function getRef();

    public function getFirstname();

    public function getMiddlename();

    public function getLastname();

    public function getCounterpartyRef();

    public function getPhone();

    public function getInfo();

    public function getEmail();

    public function getContactPersonNote();

    public function getAddresses();

    public function getAdditionalPhone();

    public function setDescription($data);

    public function setRef($data);

    public function setFirstname($data);

    public function setMiddlename($data);

    public function setLastname($data);

    public function setCounterpartyRef($data);

    public function setPhone($data);

    public function setInfo($data);

    public function setEmail($data);

    public function setContactPersonNote($data);

    public function setAddresses($data);

    public function setAdditionalPhone($data);
}
