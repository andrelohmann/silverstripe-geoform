(function ($) {

	var gmapsAPILoaded = false;

	function init(wrapper) {

		var autocomplete;
		var address = wrapper.find('.backend-geo-location-address-field');
		var lat = wrapper.find('.backend-geo-location-latitude-field');
		var lng = wrapper.find('.backend-geo-location-longditude-field');

		var country = wrapper.find('.backend-geo-location-country-field');
		var region = wrapper.find('.backend-geo-location-region-field');
		var city = wrapper.find('.backend-geo-location-city-field');
		var street = wrapper.find('.backend-geo-location-street-field');
		var areaCode = wrapper.find('.backend-geo-location-area-code-field');

		autocomplete = new google.maps.places.Autocomplete(
			address[0],
			{types: ['geocode']}
		);

		// TODO @kaspiCZ update also for the base addon scripts
		address.on('change', function () {
			if ($(this).val().length <= 0) {
				address.val('');
				lat.val('');
				lng.val('');

				country.val('');
				region.val('');
				city.val('');
				street.val('');
				areaCode.val('');
			}
		});

		google.maps.event.addListener(autocomplete, 'place_changed', function () {
			var place = autocomplete.getPlace();

			country.val('');
			region.val('');
			city.val('');
			street.val('');
			areaCode.val('');

			if (place.geometry === undefined) {
				address.val('');
				lat.val('');
				lng.val('');

				return;
			}

			lat.val(place.geometry.location.lat());
			lng.val(place.geometry.location.lng());

			var no = '';
			var premise = '';
			var cityVal = '';

			$(place.address_components).each(function () {
				if (this.types[0] === 'country') {
					country.val(this.long_name);
				} else if (this.types[0] === 'administrative_area_level_1') {
					region.val(this.long_name);
				} else if (this.types[0] === 'locality') {
					cityVal = this.long_name;
				} else if (this.types[0] === 'sublocality_level_1') {
					cityVal = this.long_name;
				} else if (this.types[0] === 'route') {
					street.val(this.long_name);
				} else if (this.types[0] === 'postal_code') {
					areaCode.val(this.long_name);
				} else if (this.types[0] === 'premise') {
					premise = this.short_name;
				} else if (this.types[0] === 'street_number') {
					no = this.short_name
				}
			});

			if (no !== '' || premise !== '') {
				var value = no + '/' + premise;

				value = value.replace('/^\/.*/', '');
				value = value.replace('/.*\/$/', '');

				street.val(street.val() + ' ' + value);
			}

			if (cityVal !== '') {
				city.val(cityVal);
			}
		});
	}

	$.entwine(function ($) {
		$('.backend-geo-location-field').entwine({
			onmatch: function () {
				if (gmapsAPILoaded) init($(this));
				this._super();
			}
		});
	});

	// Export the init function
	window.initializeGoogleMaps = function () {
		gmapsAPILoaded = true;
		$('.backend-geo-location-field').each(function () {
			init($(this));
		});
	}
})(jQuery);
