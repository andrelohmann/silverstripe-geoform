<?php
/**
 * @author Andre Lohmann
 * 
 * @package geoform
 * @subpackage fields-formattedinput
 */
class LocationField extends FormField {
	
	/**
	 * @var string $_locale
	 */
	protected $_locale;
        
        protected $wrapFieldgroup = true;
	
	/**
	 * @var FormField
	 */
	protected $fieldLatitude = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldLongditude = null;
	
	public function __construct($name, $title = null, $value = "", $form = null) {
            
		// naming with underscores to prevent values from actually being saved somewhere
		$this->fieldLatitude = $this->FieldLatitude($name);
		$this->fieldLongditude = $this->FieldLongditude($name);
		
		parent::__construct($name, $title, null, $form);
		$this->setValue($value);
	}
        
        public function setWrapFieldgroup($bool = true){
            $this->wrapFieldgroup = $bool;
            return $this;
        }
	
	public function Field($properties = array()) {
		
            $name = $this->getName();
                
                if($this->wrapFieldgroup){
                    $field = "<div class=\"fieldgroup\">" .
                             "<div class=\"fieldgroup-field\">" . 
                             $this->fieldLatitude->Field() . //SmallFieldHolder() .
                             $this->fieldLongditude->Field() . //SmallFieldHolder() .
                             "</div>" .
                             "</div>";
                }else{
                    $field = $this->fieldLatitude->Field() . //SmallFieldHolder() .
                             $this->fieldLongditude->Field(); //SmallFieldHolder()
                }
                
                return $field;
	}
	
	/**
	 * @param string $name - Name of field
	 * @return FormField
	 */
	protected function FieldLatitude($name) {
		
		$field = new TextField(
			"{$name}[Latitude]",
                        null,
			_t('GeoForm.FIELDLABELLATITUDE', 'Latitude')
		);
		
		return $field;
	}
	
	/**
	 * @param string $name - Name of field
	 * @return FormField
	 */
	protected function FieldLongditude($name) {
		
		$field = new TextField(
			"{$name}[Longditude]",
			null,
                        _t('GeoForm.FIELDLABELLONGDITUDE', 'Longditude')
		);
		
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

	/**
	 * Returns a readonly version of this field.
	 */
	public function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
	
	/**
	 * @todo Implement removal of readonly state with $bool=false
	 * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
	 */
	public function setReadonly($bool) {
		parent::setReadonly($bool);
		
		$this->fieldLatitude->setReadonly($bool);
		$this->fieldLongditude->setReadonly($bool);
	}

	public function setDisabled($bool) {
		parent::setDisabled($bool);
		
		$this->fieldLatitude->setDisabled($bool);
		$this->fieldLongditude->setDisabled($bool);

		return $this;
	}
	
	public function setLocale($locale) {
		$this->_locale = $locale;
		return $this;
	}
	
	public function getLocale() {
		return $this->_locale;
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
	}
}