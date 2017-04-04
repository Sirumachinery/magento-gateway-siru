<?php

class Siru_Mobile_Block_Form_Desc extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sirumobile/desc.phtml');
    }
}