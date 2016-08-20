(function($){
	$(function(){
		var options = JSON.parse("$options");

		$("#$name_Address").change(function(){
			$("#$name_Latitude").val('');
			$("#$name_Longditude").val('');
		});
		$("#$name_Address").geocomplete(options).bind("geocode:result", function(event, result){
			$("#$name_Latitude").val(result.geometry.location.lat());
			$("#$name_Longditude").val(result.geometry.location.lng());
		});
	});
})(jQuery);
