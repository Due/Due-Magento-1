<?php

class Due_Payments_Block_Dashboard extends Mage_Core_Block_Template
{
    /**
     * Duecards collection
     *
     * @var Due_Payments_Model_Resource_Duecard_Collection
     */
    protected $_cards = null;

    protected static $_cardTypes = array(
        'VI' => 'VISA',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'JCB' => 'JCB',
        'DN' => 'DinersClub',
        'AE' => 'American Express'
    );

    /**
     * Set Duecard instance
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $pager = $this->getLayout()->createBlock('page/html_pager')
            ->setCollection($this->getDueCards())->setIsOutputRequired(false);
        $this->setChild('pager', $pager)
            ->setBackUrl($this->getUrl('customer/account/'));
        $this->getDueCards()->load();
        return $this;
    }

    /**
     * Retrieve Duecards collection
     *
     * @return Due_Payments_Model_Resource_Duecard_Collection
     */
    public function getDueCards()
    {
        if (is_null($this->_cards)) {
            $this->_cards = Mage::getResourceModel('due/duecard_collection')
                ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomerId())
                ->setOrder('created_at', 'desc');
        }
        return $this->_cards;
    }

    public function getCustomer()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer->getId()) {
            return $customer;
        }

        return false;
    }

    /**
     * Retrieve item value by key
     *
     * @param Varien_Object $item
     * @param string $key
     * @return mixed
     */
    public function getItemValue(Due_Payments_Model_Duecard $item, $key)
    {
        switch ($key) {
            case 'created_at':
            case 'updated_at':
                $value = ($item->getData($key))
                    ? $this->helper('core')->formatDate($item->getData($key), 'short', true) : $this->__('N/A');
                break;
            case 'delete_url':
                $value = $this->getUrl('*/dashboard/delete', array('card_id' => $item->getCardId()));
                break;
            case 'expiration':
                $exp_month = str_pad((int) $item->getExpMonth(),2,'0',STR_PAD_LEFT);
                $exp_year = $item->getExpYear() > 2000 ? $item->getExpYear() - 2000 : $item->getExpYear();
                $value = $exp_month . '/' . $exp_year;
                break;
            case 'number':
                $value = 'xxxx-' . $item->getLast4();
                break;
            case 'type':
                $value = isset(self::$_cardTypes[$item->getType()]) ? self::$_cardTypes[$item->getType()] : $item->getType();
                break;
            default:
                $value = ($item->getData($key)) ? $item->getData($key) : $this->__('N/A');
        }
        return $this->escapeHtml($value);
    }
}
