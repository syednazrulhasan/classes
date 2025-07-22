<?php

namespace Client;

use Client\CurlRequest;

class ZapierWebhook{
    private $webhookUrl;

    public function __construct($webhookUrl='') {
        $this->webhookUrl = $webhookUrl;
        if(!empty($webhookUrl)){
            $this->curlRequest=new CurlRequest($this->webhookUrl);
        }
        $this->baseUrl="https://hooks.zapier.com/hooks/catch/";
    }

    public function googleSheetPost($data) {
        
        // Set cURL options
        $formData=$this->postQuery($data);

        $this->curlRequest->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curlRequest->setOption(CURLOPT_POST, true);
        $this->curlRequest->setOption( CURLOPT_POSTFIELDS, json_encode($formData));
        $this->curlRequest->setOption(CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        // Execute cURL request
        $response =  $this->curlRequest->execute();
        // Close cURL session
        $this->curlRequest->close();

        if($response){
            $result=json_decode($response,true);
            if($result['status']!='success'){
                return ['status'=>false,'result'=>'request send failed'];
            }
            return ['status'=>true,'result'=>$response];
        }else{
            return ['status'=>false,'result'=>'request send failed'];
        }
        
    }
    
    public function setWebHook($hook){
        $hooks = [
            "BROCHURE_SHEET"             => $this->baseUrl . "dummy-id-1/xxxx",
            "BROCHURE_SHEET_SECONDARY"   => $this->baseUrl . "dummy-id-2/xxxx",
            "ENQUIRY_SHEET"              => $this->baseUrl . "dummy-id-3/xxxx",
            "ENQUIRY_SHEET_SECONDARY"    => $this->baseUrl . "dummy-id-4/xxxx"
        ];

        if (isset($hooks[$hook])) {
            $this->webhookUrl =$hooks[$hook];
            $this->curlRequest=new CurlRequest($this->webhookUrl);
        }
    }

    public function postQuery($data){
        $result=[];
        foreach($data as $key=>$value){
            $postname="querystring__".$key;
            $result[$postname]=$value;
        }
        return $result;
    }
}