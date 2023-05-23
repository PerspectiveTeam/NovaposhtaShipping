<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Order\Create\Form;

use Magento\Framework\Data\Form;
use Magento\Sales\Block\Adminhtml\Order\Create\Form\AbstractForm;

/**
 * Не получается добавить вид name как у остальных полей
 */
class AbstractFormPlugin
{

    /**
     * @var array|mixed
     */
    protected $data;

    public function __construct(
        $data = []
    ) {
        $this->data = $data;
    }

    /**
     * @param AbstractForm $subject
     * @param Form $result
     * @return Form
     */
    public function afterGetForm(AbstractForm $subject, Form $result): Form
    {
        if ($result->getElement('street')->getLineCount() < 3) {
            $result->getElement('street')->setLineCount(3);
        }
        foreach ($this->data as $element) {
            if (!$result->getElement($element::NOVAPOSHTA_SHIPPING_VISIBLE_SELECT_ID)) {
                $result = $element->createVisibleSelect($result);
            }
        }
        return $result;
    }
}
