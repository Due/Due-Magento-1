<?php

class Due_Payments_Block_Info_CC extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('due/cc/info.phtml');

        // Template for Checkout page
        if ($this->getRequest()->getRequestedActionName() === 'progress') {
            $this->setTemplate('due/cc/title.phtml');
        }

    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }
}
