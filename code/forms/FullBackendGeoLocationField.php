<?php

/**
 * @author Ondrej Kaspar
 * @author Andre Lohmann
 *
 * @package geoform
 * @subpackage fields-formattedinput
 */
class FullBackendGeoLocationField extends BackendGeoLocationField
{

	/**
	 * @var HiddenField
	 */
	protected $fieldCountry = null;

	/**
	 * @var HiddenField
	 */
	protected $fieldRegion = null;

	/**
	 * @var HiddenField
	 */
	protected $fieldCity = null;

	/**
	 * @var HiddenField
	 */
	protected $fieldStreet = null;

	/**
	 * @var HiddenField
	 */
	protected $fieldAreaCode = null;

	function __construct($name, $title = null, $value = "", $form = null)
	{
		$this->fieldCountry = new HiddenField("{$name}[Country]", null);
		$this->fieldRegion = new HiddenField("{$name}[Region]", null);
		$this->fieldCity = new HiddenField("{$name}[City]", null);
		$this->fieldStreet = new HiddenField("{$name}[Street]", null);
		$this->fieldAreaCode = new HiddenField("{$name}[AreaCode]", null);

		$this->fieldCountry->addExtraClass('backend-geo-location-country-field');
		$this->fieldRegion->addExtraClass('backend-geo-location-region-field');
		$this->fieldCity->addExtraClass('backend-geo-location-city-field');
		$this->fieldStreet->addExtraClass('backend-geo-location-street-field');
		$this->fieldAreaCode->addExtraClass('backend-geo-location-area-code-field');

		parent::__construct($name, $title, $value, $form);
	}

	/**
	 * @return string
	 */
	function Field($properties = array())
	{
		Requirements::javascript('geoform/javascript/fullbackendgeolocationfield.js');

		if (GoogleMaps::getApiKey()) {
			Requirements::javascript('//maps.googleapis.com/maps/api/js?v=3.26&callback=initializeGoogleMaps&signed_in=true&libraries=places&language=' . i18n::get_tinymce_lang() . '&key=' . GoogleMaps::getApiKey());
		} else {
			Requirements::javascript('//maps.googleapis.com/maps/api/js?v=3.26&callback=initializeGoogleMaps&signed_in=true&libraries=places&language=' . i18n::get_tinymce_lang());
		}

		Requirements::css('geoform/css/backendgeolocationfield.css');

		return "<div class=\"fieldgroup\">" .
		"<div class=\"backend-geo-location-field\">" .
		$this->fieldLatitude->Field() .
		$this->fieldLongditude->Field() .
		$this->fieldCountry->Field() .
		$this->fieldRegion->Field() .
		$this->fieldCity->Field() .
		$this->fieldStreet->Field() .
		$this->fieldAreaCode->Field() .
		"<div class=\"fieldgroupField\">" . $this->fieldAddress->Field() . "</div>" .
		"</div>" .
		"</div>";
	}

	function setValue($val)
	{
		parent::setValue($val);

		if (is_array($val)) {
			$this->fieldCountry->setValue($val['Country']);
			$this->fieldRegion->setValue($val['Region']);
			$this->fieldCity->setValue($val['City']);
			$this->fieldStreet->setValue($val['Street']);
			$this->fieldAreaCode->setValue($val['AreaCode']);
		} elseif ($val instanceof GeoLocation) {
			$this->fieldCountry->setValue($val->getCountry());
			$this->fieldRegion->setValue($val->getRegion());
			$this->fieldCity->setValue($val->getCity());
			$this->fieldStreet->setValue($val->getStreet());
			$this->fieldAreaCode->setValue($val->getAreaCode());
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
	function saveInto(DataObjectInterface $dataObject)
	{
		$fieldName = $this->name;

		if ($dataObject->hasMethod("set$fieldName")) {
			$dataObject->$fieldName = DBField::create('FullGeoLocation', array(
				"Address" => $this->fieldAddress->Value(),
				"Latitude" => $this->fieldLatitude->Value(),
				"Longditude" => $this->fieldLongditude->Value(),
				"Country" => $this->fieldCountry->Value(),
				"Region" => $this->fieldRegion->Value(),
				"City" => $this->fieldCity->Value(),
				"Street" => $this->fieldStreet->Value(),
				"AreaCode" => $this->fieldAreaCode->Value(),
			));
		} else {
			$dataObject->$fieldName->setAddress($this->fieldAddress->Value());
			$dataObject->$fieldName->setLatitude($this->fieldLatitude->Value());
			$dataObject->$fieldName->setLongditude($this->fieldLongditude->Value());
			$dataObject->$fieldName->setCountry($this->fieldCountry->Value());
			$dataObject->$fieldName->setRegion($this->fieldRegion->Value());
			$dataObject->$fieldName->setCity($this->fieldCity->Value());
			$dataObject->$fieldName->setStreet($this->fieldStreet->Value());
			$dataObject->$fieldName->setAreaCode($this->fieldAreaCode->Value());
		}
	}

	/**
	 * Returns a readonly version of this field.
	 */
	function performReadonlyTransformation()
	{
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}

	/**
	 * @todo Implement removal of readonly state with $bool=false
	 * @todo Set readonly state whenever field is recreated, e.g. in setAllowedCurrencies()
	 */
	function setReadonly($bool)
	{
		parent::setReadonly($bool);

		if ($bool) {
			$this->fieldCountry = $this->fieldCountry->performReadonlyTransformation();
			$this->fieldRegion = $this->fieldRegion->performReadonlyTransformation();
			$this->fieldCity = $this->fieldCity->performReadonlyTransformation();
			$this->fieldStreet = $this->fieldStreet->performReadonlyTransformation();
			$this->fieldAreaCode = $this->fieldAreaCode->performReadonlyTransformation();
		}
	}

	public function setDisabled($bool)
	{
		parent::setDisabled($bool);

		$this->fieldCountry->setDisabled($bool);
		$this->fieldRegion->setDisabled($bool);
		$this->fieldCity->setDisabled($bool);
		$this->fieldStreet->setDisabled($bool);
		$this->fieldAreaCode->setDisabled($bool);

		return $this;
	}

	/**
	 * Override parent and handle the setting of address fields with setGoogleResult
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	protected function fillUniqueAddressWithGoogle($validator)
	{
		$response = parent::fillUniqueAddressWithGoogle($validator);

		if (!$response || !is_array($response) || !is_array($response['results'])) {
			return false;
		}

		// one result -> use it
		if (count($response['results']) === 1) {
			return $this->setGoogleResult($response['results'][0]['address_components']);
		}

		// more results -> parse them
		$tmpCounter = 0;
		$tmpLocality = null;

		foreach ($response['results'] as $place) {
			if ($place['types'][0] == 'locality' && $place['types'][1] == 'political') {
				$tmpLocality = $place;
				$tmpCounter++;
			}
		}

		if ($tmpCounter === 1 && !is_null($tmpLocality)) {
			return $this->setGoogleResult($tmpLocality['address_components']);
		}

		return false;
	}

	private function setGoogleResult($result)
	{
		$no = '';
		$premise = '';

		//TODO @kaspiCZ extend error handling and return false
		foreach ($result as $addrPart) {
			if ($addrPart['types'][0] === 'country') {
				$this->fieldCountry->setValue($addrPart['long_name']);
			} else if ($addrPart['types'][0] === 'administrative_area_level_1') {
				$this->fieldRegion->setValue($addrPart['long_name']);
			} else if ($addrPart['types'][0] === 'locality') {
				$this->fieldCity->setValue($addrPart['long_name']);
			} else if ($addrPart['types'][0] === 'route') {
				$this->fieldStreet->setValue($addrPart['long_name']);
			} else if ($addrPart['types'][0] === 'postal_code') {
				$this->fieldAreaCode->setValue($addrPart['long_name']);
			} else if ($addrPart['types'][0] === 'premise') {
				$premise = $addrPart['short_name'];
			} else if ($addrPart['types'][0] === 'street_number') {
				$no = $addrPart['short_name'];
			}
		}

		if ($no !== '' || $premise !== '') {
			$value = $no . '/' . $premise;

			$value = preg_replace('/^\/.*/', '', $value);
			$value = preg_replace('/.*\/$/', '', $value);

			$this->fieldStreet->setValue($this->fieldStreet->value . ' ' . $value);
		}

		return true;
	}
}
