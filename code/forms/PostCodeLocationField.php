<?php
/**
 * @author Andre Lohmann
 * 
 * @package geoform
 * @subpackage fields-formattedinput
 */
class PostCodeLocationField extends FormField {
	
	/**
	 * @var string $_locale
	 */
	protected $_locale;
        
	protected $wrapFieldgroup = true;
	
	/**
	 * @var FormField
	 */
	protected $fieldPostcode = null;
	
	/**
	 * @var FormField
	 */
	protected $fieldCountry = null;
	
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
		$this->fieldLatitude = new HiddenField("{$name}[Latitude]", null);
		$this->fieldLongditude = new HiddenField("{$name}[Longditude]", null);
		$this->fieldPostcode = $this->FieldPostcode($name);
		$this->fieldCountry = $this->FieldCountry($name);
		
		parent::__construct($name, $title, null, $form);
		$this->setValue($value);
	}

	/**
	 * Override addExtraClass
	 * 
	 * @param string $class
	 */
	public function addExtraClass($class) {
		$this->fieldPostcode->addExtraClass($class);
		$this->fieldCountry->addExtraClass($class);
                
		return $this;
	}

	/**
	 * Override removeExtraClass
	 * 
	 * @param string $class
	 */
	public function removeExtraClass($class) {
		$this->fieldPostcode->removeExtraClass($class);
		$this->fieldCountry->removeExtraClass($class);
		
		return $this;
	}
        
	public function setPostcodeAttribute($name, $value){
		$this->fieldPostcode->setAttribute($name, $value);
		return $this;
	}

	public function setCountryAttribute($name, $value){
		$this->fieldCountry->setAttribute($name, $value);
		return $this;
	}

	public function setWrapFieldgroup($bool = true){
		$this->wrapFieldgroup = $bool;
		return $this;
	}
	
	public function Field($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.min.js');

		if(GoogleMaps::getApiKey()) Requirements::javascript('//maps.googleapis.com/maps/api/js?libraries=places&language='.i18n::get_tinymce_lang().'&key='.GoogleMaps::getApiKey());  // don't use Sensor on this Field
		else  Requirements::javascript('//maps.googleapis.com/maps/api/js?libraries=places&language='.i18n::get_tinymce_lang());

		$name = $this->getName();
		$postcode = _t('PostCodeLocationField.ZIPCODEPLACEHOLDER', 'ZIP/Postcode');
		$country = _t('PostCodeLocationField.CITYCOUNTRYPLACEHOLDER', 'City/Country');
		
		// set caption if required
		$js = <<<JS
jQuery(document).ready(function() {
    // bind PostCodeLocationChanged to Postcode and Country Fields
    jQuery('#{$name}_Postcode').keyup({$name}PostCodeLocationChanged).focus({$name}PostCodeLocationEmptyPostcode);
    jQuery('#{$name}_Country').keyup({$name}PostCodeLocationChanged).focus({$name}PostCodeLocationEmptyCountry);
    // alternatively there exists a jquery Plugin JQUERY-TYPING
});
    
function {$name}PostCodeLocationEmptyPostcode(){
    if(jQuery('#{$name}_Postcode').val().indexOf('{$postcode}') > -1){
        jQuery('#{$name}_Postcode').val('');
    }
}
    
function {$name}PostCodeLocationEmptyCountry(){
    if(jQuery('#{$name}_Country').val().indexOf('{$country}') > -1){
        jQuery('#{$name}_Country').val('');
    }
}

var {$name}PostcodeTypeTimer = null;

// react on typing
function {$name}PostCodeLocationChanged(){
    // check typeTimer and delete
    if({$name}PostcodeTypeTimer){
        clearTimeout({$name}PostcodeTypeTimer);
    }
                        
    // trim Postcode value
    var postcode = jQuery('#{$name}_Postcode').val().replace(/\s+$/,"").replace(/^\s+/,"");
    // trim Country value
    var country = jQuery('#{$name}_Country').val().replace(/\s+$/,"").replace(/^\s+/,"");
    
    // Postcode or Country at least more than 2 digits and not placeholster is stristr of value
    if(((postcode.length >= 2 && !("{$postcode}".indexOf(postcode) > -1)) || country.length >= 2 && !("{$country}".indexOf(country) > -1)) && !("{$postcode}".indexOf(postcode) > -1 && "{$country}".indexOf(country) > -1)){
        {$name}PostcodeTypeTimer = setTimeout('{$name}PostCodeLocationFetch()', 500); // execute googlemaps request after 1/2 second of not typing
    }
}

var {$name}PostcodeGeocoder = null;

// fetch google data and update lat, lng
function {$name}PostCodeLocationFetch(){
    // clear Lat + Lng
    jQuery('#{$name}_Latitude').val('');
    jQuery('#{$name}_Longditude').val('');
    
    // trim Postcode value
    var postcode = jQuery('#{$name}_Postcode').val().replace(/\s+$/,"").replace(/^\s+/,"");
    // trim Country value
    var country = jQuery('#{$name}_Country').val().replace(/\s+$/,"").replace(/^\s+/,"");
    
    postcode = ("{$postcode}".indexOf(postcode) == -1) ? postcode : '';
    country = ("{$country}".indexOf(country) == -1) ? country : '';
    
    // create request
    var Request = {
        address: postcode+', '+country
    };
    
    // create geocoder
    {$name}PostcodeGeocoder = new google.maps.Geocoder();
    {$name}PostcodeGeocoder.geocode(Request, {$name}PostcodeGeocoderCallback);
}

function {$name}PostcodeGeocoderCallback(Response, Status){
    // Status OK
    if(Status == 'OK'){
        if(Response.length == 1){
            jQuery('#{$name}_Latitude').val(Response[0]['geometry']['location'].lat());
            jQuery('#{$name}_Longditude').val(Response[0]['geometry']['location'].lng());
            //alert($('#{$name}_Latitude').val()+','+$('#{$name}_Longditude').val());
        }else{
            // check if there is only one locality, while all others are places of interest
            var id = PostcodeIsSingleLocality(Response);
            if(id != null){
                jQuery('#{$name}_Latitude').val(Response[id]['geometry']['location'].lat());
                jQuery('#{$name}_Longditude').val(Response[id]['geometry']['location'].lng());
            }
            
            // else result not unique
            //alert(Response.length);
        }
    }
}

function PostcodeIsSingleLocality(Response){
    // check if Response has only one locality->Political
    var counter = 0;
    var locality = null;
    for(var i=0; i<Response.length; i++){
        // check if type is locality political
        if(Response[i]['types'][0] == 'locality' && Response[i]['types'][1] == 'political'){
            locality = i;
            counter++;
        }
    }
    
    return (counter == 1) ? locality : null;
}
JS;
		Requirements::customScript($js, 'PostCodeLocationField_Js_'.$this->ID());
                
		if($this->wrapFieldgroup){
			$field = "<div class=\"fieldgroup\">" .
					 $this->fieldLatitude->Field() . //SmallFieldHolder() .
					 $this->fieldLongditude->Field() . //SmallFieldHolder() .
					 "<div class=\"fieldgroup-field\">" . 
					 $this->fieldPostcode->Field() . " " . $this->fieldCountry->Field() .
					 "</div>" .
					 "</div>";
		}else{
			$field = $this->fieldLatitude->Field() . //SmallFieldHolder() .
					 $this->fieldLongditude->Field() . //SmallFieldHolder() .
					 $this->fieldPostcode->Field() .
					 " " .
					 $this->fieldCountry->Field();
		}

		return $field;
	}
	
	/**
	 * @param string $name - Name of field
	 * @return FormField
	 */
	protected function FieldPostcode($name) {
		
		$field = new TextField(
			"{$name}[Postcode]",
                        null,
			_t('PostCodeLocationField.ZIPCODEPLACEHOLDER', 'ZIP/Postcode')
		);
		
		return $field;
	}
	
	/**
	 * @param string $name - Name of field
	 * @return FormField
	 */
	protected function FieldCountry($name) {
		
		$field = new TextField(
			"{$name}[Country]",
			null,
			_t('PostCodeLocationField.CITYCOUNTRYPLACEHOLDER', 'City/Country')
		);
		
		return $field;
	}
	
	public function setValue($val) {
		$this->value = $val;

		if(is_array($val)) {
			$this->fieldPostcode->setValue($val['Postcode']);
			$this->fieldCountry->setValue($val['Country']);
			$this->fieldLatitude->setValue($val['Latitude']);
			$this->fieldLongditude->setValue($val['Longditude']);
		} elseif($val instanceof PostCodeLocation) {
			$this->fieldPostcode->setValue($val->getPostcode());
			$this->fieldCountry->setValue($val->getCountry());
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
			$dataObject->$fieldName = DBField::create_field('PostCodeLocation', array(
				"Postcode" => $this->fieldPostcode->Value(),
				"Country" => $this->fieldCountry->Value(),
				"Latitude" => $this->fieldLatitude->Value(),
				"Longditude" => $this->fieldLongditude->Value()
			));
		} else {
			$dataObject->$fieldName->setPostcode($this->fieldPostcode->Value()); 
			$dataObject->$fieldName->setCountry($this->fieldCountry->Value()); 
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
		
		$this->fieldPostcode->setReadonly($bool);
		$this->fieldCountry->setReadonly($bool);
		$this->fieldLatitude->setReadonly($bool);
		$this->fieldLongditude->setReadonly($bool);
	}

	public function setDisabled($bool) {
		parent::setDisabled($bool);
		
		$this->fieldPostcode->setDisabled($bool);
		$this->fieldCountry->setDisabled($bool);
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
		
		$postcodeField = $this->fieldPostcode;
		$countryField = $this->fieldCountry;
		$latitudeField = $this->fieldLatitude;
		$longditudeField = $this->fieldLongditude;
		$postcodeField->setValue($_POST[$name]['Postcode']);
		$countryField->setValue($_POST[$name]['Country']);
		$latitudeField->setValue($_POST[$name]['Latitude']);
		$longditudeField->setValue($_POST[$name]['Longditude']);
                
		// Result was unique
		if($latitudeField->Value() != '' && is_numeric($latitudeField->Value()) && $longditudeField->Value() != '' && is_numeric($longditudeField->Value())){
			return true;
		}
		
		// postcode and country are still placeholders
                
		if(trim($postcodeField->Value()) == '' || trim($countryField->Value()) == ''){
			$validator->validationError($name, _t('PostCodeLocationField.VALIDATIONJS', 'Please enter an accurate ZIP and City/Country.'), "validation");
			return false;
		}
                
		if(stristr(trim(_t('PostCodeLocationField.ZIPCODEPLACEHOLDER', 'ZIP/Postcode')), trim($postcodeField->Value())) && stristr(trim(_t('PostCodeLocationField.CITYCOUNTRYPLACEHOLDER', 'City/Country')), trim($countryField->Value()))){
			$validator->validationError($name, _t('PostCodeLocationField.VALIDATIONJS', 'Please enter an accurate ZIP and City/Country.'), "validation");
			return false;
		}

		// fetch result from google (serverside)
		$myPostcode = (stristr(trim(_t('PostCodeLocationField.ZIPCODEPLACEHOLDER', 'ZIP/Postcode')), trim($postcodeField->Value()))) ? '' : trim($postcodeField->Value());
		$myCountry = (stristr(trim(_t('PostCodeLocationField.CITYCOUNTRYPLACEHOLDER', 'City/Country')), trim($countryField->Value()))) ? '' : trim($countryField->Value());

		// Update to v3 API
		$googleUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($myPostcode.', '.$myCountry).'&language='.i18n::get_tinymce_lang();
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
				$validator->validationError($name, _t('PostCodeLocationField.VALIDATIONUNIQUEJS', 'ZIP and City/Country are not unique, please specify.'), "validation");
				return false;
			}
		}
	}
}