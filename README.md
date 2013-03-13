IqEnginesAPI
============

Dead Simple wrapper for iqengines API's in php.

    <?php
    require_once 'IqEnginesAPI.php';

    $api = new IqEnginesAPI(array(
	'api_key' => "YOUR_API_SECRET",
	'api_secret' => "YOUR_API_SECRET",
	'max_result_call' => 3 //Polling call limits for query results 
	)
    );
    echo $api->match("sample.jpg");

Enjoy!
