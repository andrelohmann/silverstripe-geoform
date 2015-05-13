(function($) {

	var gmapsAPILoaded = false;
        
        function init(wrapper){
        
            var autocomplete;
            var address = wrapper.find('.backend-geo-location-address-field');
            var lat = wrapper.find('.backend-geo-location-latitude-field');
            var lng = wrapper.find('.backend-geo-location-longditude-field');
            
            autocomplete = new google.maps.places.Autocomplete(
                address[0],
                { types: ['geocode'] }
            );
    
            google.maps.event.addListener(autocomplete, 'place_changed', function(){
                var place = autocomplete.getPlace();
                lat.val(place.geometry.location.lat());
                lng.val(place.geometry.location.lng());
            });
        }
        
        $.entwine(function($){
            $('.backend-geo-location-field').entwine({
                onmatch: function() {
                    if(gmapsAPILoaded) init($(this));
                    this._super();
                }
            });
        });

	// Export the init function
	window.initializeGoogleMaps = function() {
		gmapsAPILoaded = true;
		$('.backend-geo-location-field').each(function(){
                    init($(this));
                });
	}
})(jQuery);