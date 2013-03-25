IqEnginesAPI
============

Dead Simple wrapper for iqengines API's in php.

    <?php
    require_once 'IqEnginesAPI.php';

    $api = new IqEnginesAPI(array(
        'api_key' => IQ_API_KEY,
        'api_secret' => IQ_API_SECRET
    ));
    echo $api->match(realpath("sample.jpg"));

Note:
Each request is treated with a unique device id preventing "results stealing". If you don't like this approach you should be able to modify this simple class to obtain device id as constructor dependency.

Enjoy!
