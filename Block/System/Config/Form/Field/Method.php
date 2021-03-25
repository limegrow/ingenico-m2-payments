<?php

namespace Ingenico\Payment\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;

class Method extends AbstractFieldArray
{
    /**
     * Method constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->addColumn('title', [
            'label' => __('Title'),
            'style' => 'width:80px',
            'class' => 'required-entry'
        ]);
        $this->addColumn('pm', [
            'label' => 'PM',
            'style' => 'width:80px',
            'class' => 'required-entry'
        ]);
        $this->addColumn('brand', [
            'label' => 'BRAND',
            'style' => 'width:80px',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Method');
    }
}
