<?php

namespace Perspective\NovaposhtaShipping\Block\Adminhtml\Controls;

use Magento\Framework\Data\Form\Element\Select;

class Select2Small extends Select
{
    /**
     * @var array
     */
    private $dataBind = [];

    /**
     * @var array
     */
    private $dataMageInit = [];

    /**
     * Render HTML for element's label
     *
     * @param string $idSuffix
     * @param string $scopeLabel
     * @return string
     */
    public function getLabelHtml($idSuffix = '', $scopeLabel = '')
    {
        $scopeLabel = $scopeLabel ? ' data-config-scope="' . $scopeLabel . '"' : '';

        if ($this->getLabel() !== null) {
            $html = '<label class="label admin__field-label" for="' .
                 $idSuffix . '"' . $this->_getUiId(
                    'label'
                ) . '><span' . $scopeLabel . '>' . $this->_escape(
                    $this->getLabel()
                ) . '</span></label>' . "\n";
        } else {
            $html = '';
        }
        return $html;
    }
    /**
     * Get the name.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->_escaper->escapeHtml($this->getData('name'));
    }

    /**
     * Get the element Html.
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '<div  data-bind="' . $this->getDataBind() . '" ' . $this->serialize(
            $this->getHtmlAttributes()
        ) . ' >';
        if ($this->getBeforeElementHtml()) {
            $html .= '<label class="addbefore" for="' .
                $this->getHtmlId() .
                '">' .
                $this->getBeforeElementHtml() .
                '</label>';
        }
        $html .= '<!-- ko template: getTemplate() --><!-- /ko -->' . "\n";
        if ($this->getAfterElementHtml()) {
            $html .= '<label class="addafter" for="' .
                $this->getHtmlId() .
                '">' .
                "\n{$this->getAfterElementHtml()}\n" .
                '</label>' .
                "\n";
        }
        $html.='</div>';
        return $html;
    }

    public function getDataBind()
    {
        foreach ($this->dataBind as $key => $value) {
            $htmlArr[] = $key . ': ' . $value;
        }
        $html = implode(",\n", $htmlArr);
        return $html;
    }

    /**
     * @param array $dataBind
     */
    public function setDataBind(array $dataBind): void
    {
        $this->dataBind = $dataBind;
    }

    public function getDataMageInit()
    {
        return str_replace('[]', '{}', json_encode($this->dataMageInit));
    }

    /**
     * @param array $dataMageInit
     */
    public function setDataMageInit(array $dataMageInit): void
    {
        $this->dataMageInit = $dataMageInit;
    }
}
