<?php
//{{{PHP_ENCODE}}}

namespace Client;

class MD365
{
	private $tenantId        = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
	private $clientId        = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';
	private $clientSecret    = 'your_client_secret_here';
	private $scope           = 'your_scope_here';
	private $subscriptionKey = 'your_subscription_key_here';
	private $apiurl_stage    = 'https://api.example.com/api/comms/stg/v1/enquiry';
	private $apiurl_prod     = 'https://api.example.com/api/comms/v1/enquiry';
    private $channelId       = 1;
    private $accessToken;

    public function __construct()
    {
        // Constructor logic (if needed)
    }

    public function getAccessToken()
    {
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        $data = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => "{$this->scope}/.default"
        ];

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $json = json_decode($response, true);
            $this->accessToken = $json['access_token'];
            return $this->accessToken;
        } else {
            throw new \Exception("Failed to retrieve access token: $response");
        }
    }

    public function createEnquiry(array $leadData)
    {

        $accessToken = $this->getAccessToken();
        #echo $accessToken.'<br/>' ; 

        $url = $this->apiurl_prod;
        
        #echo $url.'<br/>'; 
        
        $postdata = json_encode($leadData, JSON_UNESCAPED_UNICODE);
        
        #echo $postdata.'<br/>'; 

        $headers = [
            'Channel-ID: '.$this->channelId,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postdata),
            'Ocp-Apim-Subscription-Key: ' . $this->subscriptionKey,
            'Authorization: ' . $accessToken,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        #curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        #curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        #echo 'HTTP CODE='.$httpCode.'<br/>';

        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
        }

        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return json_encode(['status' => true]);
        } else {
            throw new \Exception("Failed to create lead. HTTP $httpCode: $response");
        }
    }
}
//{{{/PHP_ENCODE}}}