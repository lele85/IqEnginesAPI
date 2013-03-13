<?php

/**
 * Wrapper for IqEnginesAPI
 *
 * @author Emanuele Rampichini emanuele.rampichini@gmail.com
 */

class IqEnginesAPI {

    static $QUERY_END_POINT = "http://api.iqengines.com/v1.2/query/";
    static $RESULT_END_POINT = "http://api.iqengines.com/v1.2/result/";

    private $api_key;
    private $api_secret;
    private $max_result_call = 1;
    private $use_json = 1;

    public function __construct($params) {
        $this->api_key = $params['api_key'];
        $this->api_secret = $params['api_secret'];
        if (isset($params['max_result_call'])) {
            $this->max_result_call = $params['max_result_call'];
        }
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
            $flat_signature .= $key.$value;
        }
        $encoded_signature = hash_hmac("sha1", $flat_signature, $this->api_secret, false);
        
        if (isset($complete_array['img'])){
            $complete_array['img'] = '@' . realpath($complete_array['img']);
        }
        $complete_array['api_sig'] = $encoded_signature;
        return $complete_array;
    }

    /**
     * 
     * @return string quid or false
     */
    public function query($filename) {
        // Preparing the data we will be sending
        $fields = $this->signFields(array("img" => $filename));

        $ch = $this->initCurl($fields, self::$QUERY_END_POINT);
        $response = curl_exec($ch);
        if($this->verifyQueryResponse($response)){
            return $fields['api_sig'];
        }
        return false;
    }
    
    private function verifyResponseReady($response){
        $o_response = json_decode($response, true);
        $ready = !(isset($o_response['data']) && isset($o_response['data']['comment']));
        if ($ready){
            return $response;
        }
        return false;   
    }

    public function match($filename){
        return $this->result($this->query($filename));
    }
      
    public function result($quid, $try_count = 0){
        if ($try_count > $this->max_result_call){
            return false;
        }
        $fields = $this->signFields(array('qid' => $quid));
        
        $ch = $this->initCurl($fields,self::$RESULT_END_POINT);
        $response = curl_exec($ch);

        if ($this->verifyResponseReady($response)){
            return $response;
        }
        $try_count +=1;
        return $this->result($quid, $try_count);
    }
}