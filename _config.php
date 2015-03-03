<?php

if(defined('GOOGLE_MAPS_API_KEY')) GoogleMaps::setApiKey(GOOGLE_MAPS_API_KEY);

// Add Geofuntions UDF to mysql
DataObject::add_extension('AddGeodistanceFunction');