<?php

class Due_Payments_Block_Form_CC extends Mage_Payment_Block_Form_Cc
{
    protected static $_cardTypes = array(
        'VI' => 'VISA',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'JCB' => 'JCB',
        'DN' => 'DinersClub',
        'AE' => 'American Express'
    );

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('due/cc/form.phtml');
    }

    public function getDueEnv()
    {
        $method = $this->getMethod();
        $sandbox_mode = $method->getConfigData('sandbox_mode');
        return $sandbox_mode ? 'stage' : 'prod';
    }

    public function getDueAppId()
    {
        $method = $this->getMethod();
        $sandbox_mode = $method->getConfigData('sandbox_mode');
        return $method->getConfigData($sandbox_mode ? 'app_id_sandbox' : 'app_id');
    }

    public function getDueRailType()
    {
    	return $this->getMethod()->getConfigData('rail_type');
    }

    public function isStoreCardsEnabled()
    {
        $method = $this->getMethod();
        return (bool) $method->getConfigData('store_cards');
    }

    public function isCustomerLoggedIn()
    {
        return Mage::helper('due')->isCustomerLoggedIn();
    }

    public function getStoredCards()
    {
        return Mage::helper('due')->getStoredCards();
    }

    /**
     * Render Card
     * @param $card
     *
     * @return string
     */
    public function formatCard($card) {
        $type = isset(self::$_cardTypes[$card['type']]) ? self::$_cardTypes[$card['type']] : $card['type'];
        $last4 = $card['last4'];
        $exp_month = str_pad((int) $card['exp_month'],2,'0',STR_PAD_LEFT);
        $exp_year = $card['exp_year'] > 2000 ? $card['exp_year'] - 2000 : $card['exp_year'];

        return $this->__('%s ending in %s %s/%s', $type, $last4, $exp_month, $exp_year);
    }
}
