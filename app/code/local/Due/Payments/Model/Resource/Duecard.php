<?php

class Due_Payments_Model_Resource_Duecard extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        // Note that "card_id" refers to the key field in your database table.
        $this->_init('due/duecard', 'card_id');
    }
}
