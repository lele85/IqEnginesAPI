<?php
require_once 'IqEnginesAPI.php';

$api = new IqEnginesAPI(array(
	'api_key' => IQ_API_KEY,
	'api_secret' => IQ_API_SECRET
));
echo $api->match(realpath("sample.jpg"));