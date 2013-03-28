<?php

/**
 * Wrapper for IqEnginesAPI
 *
 * @author Emanuele Rampichini emanuele.rampichini@gmail.com
 */

class IqEnginesAPI {

    static $QUERY_END_POINT = "http://api.iqengines.com/v1.2/query/";
    static $RESULT_END_POINT = "http://api.iqengines.com/v1.2/result/";
    static $UPDATE_END_POINT = "http://api.iqengines.com/v1.2/update/";

    private $api_key;
    private $api_secret;
    private $use_json = 1;

    public function __construct($params) {
        $this->api_key = $params['api_key'];
        $this->api_secret = $params['api_secret'];
    }
    
    private function verifyQueryResponse($response){
        $response = json_decode($response,true);
        $success = (
                isset($response["data"]) &&
                isset($response["data"]["error"]) &&
                ($response["data"]["error"] == 0)
                );
        return $success;
    }

    private function getUniqueDeviceId(){
        $id = (string)sha1(uniqid(mt_rand(), true));
        return $id;
    }

    private function initCurl($fields, $endpoint){
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // RETURN THE CONTENTS OF THE CALL
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HEADER, false);  // DO NOT RETURN HTTP HEADERS 
        if ($endpoint == self::$QUERY_END_POINT){
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }
        return $ch;
    }
    
    private function getComposedField($key,$value){
        if ($key == 'img'){
            $exploded = explode('/', $value);
            $filename = $exploded[count($exploded) - 1];
            return $key.$filename;
        }
        return $key.$value;
    }

    /**
    * Fields should be concatenated with key alphabetical sorting
    */
    public function signFields($fields){
        $standard_fields = array(
            'api_key' => $this->api_key,
            'json' => $this->use_json,
            'time_stamp' => date('YmdHis')
        );
        $complete_array = array_merge($fields, $standard_fields);
        ksort($complete_array);
        $flat_signature = "";
        foreach ($complete_array as $key => $value) {
            $flat_signature .= $this->getComposedField($key,$value);
        }
        $encoded_signature = hash_hmac("sha1", $flat_signature, $this->api_secret, false);
        $complete_array['api_sig'] = $encoded_signature;
        return $complete_array;
    }

    
    public function query($abs_path, $deviceId) {
        
        // Preparing the data we will be sending
        $fields = $this->signFields(array(
            "img" => '@'.$abs_path,
            "device_id" => $deviceId,
        ));
        $ch = $this->initCurl($fields, self::$QUERY_END_POINT);
        $response = curl_exec($ch);
        if($this->verifyQueryResponse($response)){
            return $response;
        }
        return false;
    }
    
    public function match($abs_path){
        $deviceId = $this->getUniqueDeviceId();
        $qid = $this->query($abs_path, $deviceId);
        return $this->update($deviceId);
    }

    public function update($deviceId){
        $fields = $this->signFields(array("device_id"=>$deviceId));
        $ch = $this->initCurl($fields,self::$UPDATE_END_POINT);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
      
    public function result($quid){
        $fields = $this->signFields(array('qid' => $quid));
        $ch = $this->initCurl($fields,self::$RESULT_END_POINT);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
