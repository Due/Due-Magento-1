<?php

class Due_Payments_DashboardController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (!$this->_getSession()->isLoggedIn()) {
            $this->_redirect('customer/account/login');
        }

        $this->loadLayout();
        $this->renderLayout();
        //Zend_Debug::dump($this->getLayout()->getUpdate()->getHandles());
    }

    public function deleteAction()
    {
        $card = $this->_initCard();
        if (!$card) {
            $this->_redirect('*/*/index');
            return;
        }

        if (!$this->_getSession()->isLoggedIn()) {
            $this->_redirect('customer/account/login');
        }

        if ($card->getCustomerId() != $this->_getSession()->getCustomerId()) {
            $this->_getSession()->addError($this->__('Access denied.'));
            $this->_redirect('*/*/index');
            return;
        }

        $card->delete();

        $this->_redirect('*/*/index');
    }

    /**
     * Init billing agreement model from request
     *
     * @return Due_Payments_Model_Duecard
     */
    protected function _initCard()
    {
        $card_id = $this->getRequest()->getParam('card_id');
        if ($card_id) {
            /** @var Due_Payments_Model_Duecard $card */
            $card = Mage::getModel('due/duecard')->load($card_id);
            if (!$card->getCardId()) {
                $this->_getSession()->addError($this->__('Wrong Card ID specified.'));
                $this->_redirect('*/*/');
                return false;
            }
        }

        Mage::register('current_card_id', $card);
        return $card;
    }

    /**
     * Retrieve customer session model
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
}
