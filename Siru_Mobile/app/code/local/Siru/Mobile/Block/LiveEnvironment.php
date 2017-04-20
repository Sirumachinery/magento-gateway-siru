<?php


class Siru_Mobile_Block_LiveEnvironment extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = "
           <input type='hidden' name='groups[siru_mobile][fields][live_environment][value]' value='0'>
           <input type='checkbox' name='groups[siru_mobile][fields][live_environment][value]' value='1'>";
        return $html;
    }
}
