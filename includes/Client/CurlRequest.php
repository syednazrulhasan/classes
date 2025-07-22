<?php

namespace Client;

class CurlRequest
{
    public $curl;

    public function __construct($url)
    {
        $this->curl = curl_init($url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    
      
    }

    public function setOption($option, $value)
    {
        curl_setopt($this->curl, $option, $value);
    }

    public function setHeaders($headers)
    {
        $head_params=array();
        
        foreach($headers as $key=>$value){
          array_push( $head_params,$key.':'.$value);
        }

        curl_setopt($this->curl,CURLOPT_HTTPHEADER ,$head_params);
        return $head_params;
    }

    public function setMethod($method)
    {
        if (strtoupper($method) == 'POST') {
            $this->post();
        } elseif (strtoupper($method) == 'PUT') {
            $this->put();
        } elseif (strtoupper($method) == 'PATCH') {
            $this->patch();
        } else {
            $this->get();
        }
    }

    //set send request post data
    public function setBody($data,$data_type)
    {
        $postData=strtolower($data_type)=="json" ? json_encode($data):http_build_query($data);
        $this->setOption(CURLOPT_POSTFIELDS,$postData);
    }

    //set request methods GET
    public function get(){
        $this->setOption(CURLOPT_CUSTOMREQUEST,'GET');
    }
    
    //set request methods POST
    protected function post()
    {
        $this->setOption(CURLOPT_ENCODING,'');
        $this->setOption(CURLOPT_MAXREDIRS,10);
        $this->setOption(CURLOPT_TIMEOUT,0);
        $this->setOption(CURLOPT_FOLLOWLOCATION,0);
        $this->setOption(CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
        $this->setOption(CURLOPT_CUSTOMREQUEST,'POST');
       
    }

    //set request methods PUT
    public function put(){
     
        $this->setOption(CURLOPT_ENCODING,'');
        $this->setOption(CURLOPT_MAXREDIRS,10);
        $this->setOption(CURLOPT_TIMEOUT,0);
        $this->setOption(FOLLOWLOCATION,0);
        $this->setOption(CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
        $this->setOption(CURLOPT_CUSTOMREQUEST,'PUT');
    }

    // Set request methods PATCH
    public function patch()
    {
        $this->setOption(CURLOPT_ENCODING, '');
        $this->setOption(CURLOPT_MAXREDIRS, 10);
        $this->setOption(CURLOPT_TIMEOUT, 0);
        $this->setOption(CURLOPT_FOLLOWLOCATION, 0);
        $this->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH');
    }    

    public function execute()
    {
        try {
            $response = curl_exec($this->curl);
            if ($response === false) {
                $error = curl_error($this->curl);
                if (!empty($error)) {
                    throw new \Exception($error);
                } else {
                    throw new \Exception('CURL request failed.');
                }
            } else {
                return $response;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function close()
    {
        curl_close($this->curl);
    }

    public static function sanitizePost() {
        if(isset($_POST)){
            foreach($_POST as $key => $value) {
                $_POST[$key] = filter_var($value, FILTER_SANITIZE_STRING);
                if($key === "email") {
                    $_POST[$key] = filter_var($_POST[$key], FILTER_SANITIZE_EMAIL);
                }
            } 
        }
    }

    public static function validate($request_data,$validations){
        $errors=array();
        if(isset($request_data)){
            if(!empty($validations)){
                foreach($validations as $field_name=>$validates){
                    if(!empty($validates)){
                        $rules = explode("|", $validates);
                        
                        foreach($rules as $rule){
                            if($rule=='required' && empty($request_data[$field_name])){
                                $error_msg="{field} field is required.";
                                $errors[$field_name][]=preg_replace('/\{field\}/', $field_name,  $error_msg);
                            }

                            if($rule=='numeric' && !is_numeric($request_data[$field_name])){
                                $error_msg="Please enter a numeric value for the {field} field.";
                                $errors[$field_name][]=preg_replace('/\{field\}/', $field_name,  $error_msg);
                            }

                            if($rule=='email' && !filter_var($request_data[$field_name], FILTER_VALIDATE_EMAIL)){
                                $error_msg="Please enter a valid email address.";
                                $errors[$field_name][]=$error_msg;
                            }

                            
                            if (preg_match('/^min:(\d+)$/', $rule, $matches)) {
                                $min_length = intval($matches[1]);
                                if(strlen($request_data[$field_name]) < $min_length ){
                                    $error_msg="Please enter a value for the {field} greater than or equal to {min}.";
                                    $errors[$field_name][]=preg_replace('/\{min\}/', $min_length,  preg_replace('/\{field\}/',$field_name,$error_msg)); 
                                   
                                }
                            }

                            if (preg_match('/^max:(\d+)$/', $rule, $matches)) {
                                $max_length = intval($matches[1]);
                                if(strlen($request_data[$field_name]) > $max_length ){
                                    $error_msg="Please enter a value for the {field} less than or equal to {max}.";
                                    $errors[$field_name][]=preg_replace('/\{max\}/', $max_length,  preg_replace('/\{field\}/',$field_name,$error_msg)); 
                               
                                }
                            }

                            if($rule=='phone'){
                                $phone_number = preg_replace('/[^0-9]/', '', $request_data[$field_name]);
                                $error_msg="Please enter a valid phone number.";
                                $digits = substr($phone_number, 2);
                                if (strlen($digits) !=10 && !is_numeric($digits)) {
                                    $errors[$field_name][]="Please enter a valid phone number."; 
                                }
                            }
                        }
                    }
                }
            }
        }

        return  $errors;
    }


}