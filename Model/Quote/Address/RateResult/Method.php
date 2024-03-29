<?php

namespace Perspective\NovaposhtaShipping\Model\Quote\Address\RateResult;

/**
 * Цей класс мав виправляти помилку округлення ціни
 * є ситуація, наприклад, з base currency = USD
 * і валюта магазину = UAH, тоді
 * при ціні з АПІ НП в 175 грн, при курсі 40 грн за долар
 * виходить 4.375 долара, але в магазині відображається 4.38
 * і коли М2 перетворює 4.38 долара в гривні, то виходить 175.2 грн, а має бути 175 грн.
 * Цей клас не впливає на виправлення округлення і він потрібен лише для інформаційних цілей
 *
 * Деталі:
 * base ціна для шиппінгу ломаєтся... в податках
 * vendor/magento/module-tax/Model/Calculation/AbstractAggregateCalculator.php:107 - для безподаткового розрахунку
 * vendor/magento/module-tax/Model/Calculation/AbstractAggregateCalculator.php:46 - для податкового
 *
 */
class Method extends \Magento\Quote\Model\Quote\Address\RateResult\Method
{
}
