<?php
/**
 * @author Andre Lohmann
 * 
 * @package geoform
 * @subpackage fields-formattedinput
 */
class GeoLocationField extends FormField {
	
	/**
	 * @var string $_locale
	 */
	protected $_locale;
	
	/**
	 * @var FormField
	 */
	protected $fieldAddress = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldLatitude = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldLongditude = null;
	
	function __construct($name, $title = null, $value = "", $form = null) {
		// naming with underscores to prevent values from actually being saved somewhere
		$this->fieldLatitude = new HiddenField("{$name}[Latitude]", null);
		$this->fieldLongditude = new HiddenField("{$name}[Longditude]", null);
		$this->fieldAddress = $this->FieldAddress($name);
		
		parent::__construct($name, $title, $value, $form);
	}

	/**
	 * Override addExtraClass
	 * 
	 * @param string $class
	 */
	public function addExtraClass($class) {
		$this->fieldAddress->addExtraClass($class);
                
		return $this;
	}

	/**
	 * Override removeExtraClass
	 * 
	 * @param string $class
	 */
	public function removeExtraClass($class) {
		$this->fieldAddress->removeExtraClass($class);
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	function Field($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.min.js');

		Requirements::javascript('geoform/javascript/jquery.geocomplete.js');
		
		if(GoogleMaps::getApiKey()) Requirements::javascript('//maps.googleapis.com/maps/api/js?sensor=false&libraries=places&language='.i18n::get_tinymce_lang().'&key='.GoogleMaps::getApiKey());  // don't use Sensor on this Field
		else  Requirements::javascript('//maps.googleapis.com/maps/api/js?sensor=false&libraries=places&language='.i18n::get_tinymce_lang());

		$name = $this->name;
		$js = <<<JS
(function($){
    $(function(){
        $("#{$name}_Address").geocomplete().bind("geocode:result", function(event, result){
            $("#{$name}_Latitude").val(result.geometry.location.lat());
            $("#{$name}_Longditude").val(result.geometry.location.lng());
        });
    });
})(jQuery);
JS;
		Requirements::customScript($js, 'GeoLocationField_Js_'.$this->ID());

		$css = <<<CSS
/* make the location suggest dropdown appear above dialog */
.pac-container {
    z-index: 2000 !important;
}
CSS;
		Requirements::customCSS($css, 'GeoLocationField_Css_'.$this->ID());
		
	
		return "<div class=\"fieldgroup\">" .
			$this->fieldLatitude->Field() . //SmallFieldHolder() .
			$this->fieldLongditude->Field() . //SmallFieldHolder() .
			"<div class=\"fieldgroupField\">" . $this->fieldAddress->Field() . "</div>" . 
		"</div>";
	}
	
	/**
	 * @param string $name - Name of field
	 * @return FormField
	 */
	protected function FieldAddress($name) {
		
		$field = new TextField(
			"{$name}[Address]", 
			_t('GeoLocationFiels.ADDRESSPLACEHOLDER', 'Address')
		);
		
		return $field;
	}
	
	function setValue($val) {
		$this->value = $val;

		if(is_array($val)) {
			$this->fieldAddress->setValue($val['Address']);
			$this->fieldLatitude->setValue($val['Latitude']);
			$this->fieldLongditude->setValue($val['Longditude']);
		} elseif($val instanceof GeoLocation) {
			$this->fieldAddress->setValue($val->getAddress());
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
	function saveInto(DataObjectInterface $dataObject) {
		$fieldName = $this->name;
		if($dataObject->hasMethod("set$fieldName")) {
			$dataObject->$fieldName = DBField::create('GeoLocation', array(
				"Address" => $this->fieldAddress->Value(),
				"Latitude" => $this->fieldLatitude->Value(),
				"Longditude" => $this->fieldLongditude->Value()
			));
		} else {
			$dataObject->$fieldName->setAddress($this->fieldAddress->Value()); 
			$dataObject->$fieldName->setLatitude($this->fieldLatitude->Value());
			$dataObject->$fieldName->setLongditude($this->fieldLongditude->Value());
		}
	}

	/**
	 * Returns a readonly version of this field.
	 */
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
	
	/**
	 * @todo Implement removal of readonly state with $bool=false
	 * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
	 */
	function setReadonly($bool) {
		parent::setReadonly($bool);
		
		if($bool) {
			$this->fieldAddress = $this->fieldAddress->performReadonlyTransformation();
			$this->fieldLatitude = $this->fieldLatitude->performReadonlyTransformation();
			$this->fieldLongditude = $this->fieldLongditude->performReadonlyTransformation();
		}
	}

	public function setDisabled($bool) {
		parent::setDisabled($bool);
		
		$this->fieldAddress->setDisabled($bool);
		$this->fieldLatitude->setDisabled($bool);
		$this->fieldLongditude->setDisabled($bool);

		return $this;
	}
	
	function setLocale($locale) {
		$this->_locale = $locale;
	}
	
	function getLocale() {
		return $this->_locale;
	}
	
	/**
	 * Validates PostCodeLocation against GoogleMaps Serverside
	 * 
	 * @return String
	 */
	public function validate($validator){
		$name = $this->name;
		
		$addressField = $this->fieldAddress;
		$latitudeField = $this->fieldLatitude;
		$longditudeField = $this->fieldLongditude;
		$addressField->setValue($_POST[$name]['Address']);
		$latitudeField->setValue($_POST[$name]['Latitude']);
		$longditudeField->setValue($_POST[$name]['Longditude']);
                
		// Result was unique
		if($latitudeField->Value() != '' && is_numeric($latitudeField->Value()) && $longditudeField->Value() != '' && is_numeric($longditudeField->Value())){
			return true;
		}
		
		// postcode and country are still placeholders
                
		if(stristr(trim(_t('GeoLocationField.ADDRESSPLACEHOLDER', 'Address')), trim($addressField->Value()))){
			$validator->validationError($name, _t('GeoLocationField.VALIDATION', 'Please enter an accurate address!'), "validation");
			return false;
		}
                
		if(trim($addressField->Value()) == ''){
			$validator->validationError($name, _t('GeoLocationField.VALIDATION', 'Please enter an accurate address!'), "validation");
			return false;
		}

		// fetch result from google (serverside)
		$myAddress = (stristr(trim(_t('GeoLocationField.ADDRESSPLACEHOLDER', 'Address')), trim($addressField->Value()))) ? '' : trim($addressField->Value());

		// Update to v3 API
		$googleUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($myAddress).'&language='.i18n::get_tinymce_lang();
		if(GoogleMaps::getApiKey()) $googleUrl.= '&key='.GoogleMaps::getApiKey();

		$result = json_decode(file_get_contents($googleUrl), true);

		// if result unique
		if($result['status'] == 'OK' && count($result['results']) == 1){
			$latitudeField->setValue($result['results'][0]['geometry']['location']['lat']);
			$longditudeField->setValue($result['results'][0]['geometry']['location']['lng']);
			return true;
		}else{
			$tmpCounter = 0;
			$tmpLocality = null;
			for($i=0; $i<count($result['results']); $i++){
				// check if type is locality political
				if($result['results'][$i]['types'][0] == 'locality' && $result['results'][$i]['types'][1] == 'political'){
					$tmpLocality = $i;
					$tmpCounter++;
				}
			}

			if($tmpCounter == 1){
				$latitudeField->setValue($result['results'][$tmpLocality]['geometry']['location']['lat']);
				$longditudeField->setValue($result['results'][$tmpLocality]['geometry']['location']['lng']);
				return true;
			}else{
				// result not unique
				$validator->validationError($name, _t('GeoLocationField.VALIDATIONUNIQUE', 'The address is not unique, please specify.'), "validation");
				return false;
			}
		}
	}
}