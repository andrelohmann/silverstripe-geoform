<?php
/**
 * @author Andre Lohmann
 * 
 * @package geoform
 * @subpackage fields-formattedinput
 */
class HiddenLocationField extends HiddenField {
	
	/**
	 * @var string $_locale
	 */
	protected $_locale;
	
	/**
	 * @var FormField
	 */
	protected $fieldLatitude = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldLongditude = null;
	
	/**
	 * @var FormField
	 */
	protected $isRequired = false;
	
	public function __construct($name, $title = null, $value = "", $form = null) {
            
		// naming with underscores to prevent values from actually being saved somewhere
		$this->fieldLatitude = new HiddenField("{$name}[Latitude]", null);
		$this->fieldLongditude = new HiddenField("{$name}[Longditude]", null);
		
		parent::__construct($name, $title, null, $form);
		$this->setValue($value);
	}

	/**
	 * Returns a "field holder" for this field - used by templates.
	 * 
	 * Forms are constructed by concatenating a number of these field holders.
	 * The default field holder is a label and a form field inside a div.
	 * @see FieldHolder.ss
	 * 
	 * @param array $properties key value pairs of template variables
	 * @return string
	 */
	public function FieldHolder($properties = array()) {
		$obj = ($properties) ? $this->customise($properties) : $this;

		return $obj->renderWith($this->getFieldHolderTemplates());
	}
	
	public function Field($properties = array()) {
                Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.min.js');
            
            $name = $this->getName();
            
            // set caption if required
            $js = <<<JS
!function($){
    $(function(){
        // Try HTML5 geolocation
        if(navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position){
                    jQuery('#{$name}-Latitude').val(position.coords.latitude);
                    jQuery('#{$name}-Longditude').val(position.coords.longitude);
            });
        }
    });
}(window.jQuery);
JS;
                    
            Requirements::customScript($js, 'HiddenLocationField_Js_'.$this->ID());
            
            $field = $this->fieldLatitude->Field() . //SmallFieldHolder() .
            $this->fieldLongditude->Field(); //SmallFieldHolder()
            
            return $field;
	}
	
	public function setValue($val) {
		$this->value = $val;

		if(is_array($val)) {
			$this->fieldLatitude->setValue($val['Latitude']);
			$this->fieldLongditude->setValue($val['Longditude']);
		} elseif($val instanceof Location) {
			$this->fieldLatitude->setValue($val->getLatitude());
			$this->fieldLongditude->setValue($val->getLongditude());
		}
	}
	
	/**
	 * 30/06/2009 - Enhancement: 
	 * SaveInto checks if set-methods are available and use them 
	 * instead of setting the values in the money class directly. saveInto
	 * initiates a new Money class object to pass through the values to the setter
	 * method.
	 *
	 * (see @link MoneyFieldTest_CustomSetter_Object for more information)
	 */
	public function saveInto(DataObjectInterface $dataObject) {
		$fieldName = $this->name;
		if($dataObject->hasMethod("set$fieldName")) {
			$dataObject->$fieldName = DBField::create_field('Location', array(
				"Latitude" => $this->fieldLatitude->Value(),
				"Longditude" => $this->fieldLongditude->Value()
			));
		} else {
			$dataObject->$fieldName->setLatitude($this->fieldLatitude->Value());
			$dataObject->$fieldName->setLongditude($this->fieldLongditude->Value());
		}
	}
	
	public function setLocale($locale) {
		$this->_locale = $locale;
		return $this;
	}
	
	public function getLocale() {
		return $this->_locale;
	}
	
	public function setIsRequired($required = true) {
		$this->isRequired = (bool)$required;

		return $this;
	}
        
	public function getIsRequired(){
            return $this->isRequired;
	}
	
	/**
	 * Validates PostCodeLocation against GoogleMaps Serverside
	 * 
	 * @return String
	 */
	public function validate($validator){
		$name = $this->name;
		
		$latitudeField = $this->fieldLatitude;
		$longditudeField = $this->fieldLongditude;
		$latitudeField->setValue($_POST[$name]['Latitude']);
		$longditudeField->setValue($_POST[$name]['Longditude']);
                
                // Result was unique
                if($latitudeField->Value() != '' && is_numeric($latitudeField->Value()) && $longditudeField->Value() != '' && is_numeric($longditudeField->Value())){
                    return true;
                }
                
                if($this->isRequired){
                    //$validator->validationError($name, _t('HiddenLocationField.LOCATIONREQUIRED', 'Please allow access to your location'), "validation");
					$this->form->sessionMessage(_t('HiddenLocationField.LOCATIONREQUIRED', 'Please allow access to your location'), 'bad');
                    return false;
                }
	}
}