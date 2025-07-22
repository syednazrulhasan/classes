<?php

namespace Client;

use Client\CurlRequest;

class SalesForce
{
    private $authUrl;
    private $apiUrl;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;

    public function __construct()
    {
        $this->authUrl       = "https://login.salesforce.com/services/oauth2/token";
        $this->apiUrl        = "https://yourinstance.my.salesforce.com";
        $this->clientId      = "your_client_id_here";
        $this->clientSecret  = "your_client_secret_here";
        $this->username      = "user@example.com";
        $this->password      = "your_password_here";
    }

    public function getAccessToken()
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $data = [
            'grant_type'    => 'password',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username'      => $this->username,
            'password'      => $this->password,
        ];

        $curlRequest = new CurlRequest($this->authUrl);
        $curlRequest->setMethod('POST');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'form');

        $response = $curlRequest->execute();
        if ($response) {
            $response = json_decode($response, true);
            if (isset($response['access_token'])) {
                return $response['access_token'];
            }
        }
        return false;
    }

    public function createLead($data)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Lead');
        $curlRequest->setMethod('POST');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'JSON');

        $response = $curlRequest->execute();
        if ($response) {
            $response = json_decode($response, true);
            if (isset($response['id'])) {
                return ['status' => true, "id" => $response['id']];
            }
            return ['status' => false, "error" => $response['errorCode'] ?? "Create Lead Failed"];
        }

        return ['status' => false, "error" => "Create Lead Failed"];
    }

    public function updateLead($leadId, $data)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['status' => false, "error" => "Failed to obtain access token."];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Lead/' . $leadId);
        $curlRequest->setMethod('PATCH');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'JSON');

        $response = $curlRequest->execute();
        if ($response === '') {
            return ['status' => true, 'id' => $leadId];
        }

        $decoded = json_decode($response, true);
        return ['status' => false, 'error' => $decoded['message'] ?? 'Update Lead Failed'];
    }

    public function getLead($leadId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['status' => false, "error" => "Failed to obtain access token."];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Lead/' . $leadId);
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        return $response ? ['status' => true, "lead" => json_decode($response, true)] : ['status' => false, "error" => "Failed to retrieve lead"];
    }

    public function getLeadIdByEmail($email)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, 'error' => 'Failed to obtain access token.'];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $email = addslashes($email);
        $query = urlencode("SELECT Id FROM Lead WHERE Email = '{$email}'");

        $curlRequest = new CurlRequest($this->apiUrl . "/services/data/v61.0/query/?q={$query}");
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        if ($response) {
            $response = json_decode($response, true);
            if (!empty($response['records'])) {
                return ['status' => true, 'leadId' => $response['records'][0]['Id']];
            } else {
                return ['status' => false, 'leadId' => null];
            }
        }

        return ['status' => false, 'error' => 'Failed to retrieve lead by email.'];
    }

    public function createContact($data)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Contact');
        $curlRequest->setMethod('POST');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'JSON');

        $response = $curlRequest->execute();
        return json_decode($response, true);
    }

    public function updateContact($contactId, $data)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, "error" => "Failed to obtain access token."];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Contact/' . $contactId);
        $curlRequest->setMethod('PATCH');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'JSON');

        $response = $curlRequest->execute();
        return json_decode($response, true);
    }

    public function getContact($contactId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, "error" => "Failed to obtain access token."];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Contact/' . $contactId);
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        return json_decode($response, true);
    }

    public function getContactIdByEmail($email)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, 'error' => 'Failed to obtain access token.'];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $email = addslashes($email);
        $query = urlencode("SELECT Id FROM Contact WHERE Email='{$email}'");

        $curlRequest = new CurlRequest($this->apiUrl . "/services/data/v61.0/query/?q=$query");
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        if ($response) {
            $response = json_decode($response, true);
            if (!empty($response['records'])) {
                return ['status' => true, 'contactId' => $response['records'][0]['Id']];
            } else {
                return ['status' => false, 'contactId' => null];
            }
        }

        return ['status' => false, 'error' => 'Failed to retrieve contact by email.'];
    }

    public function getContactIdByMobilePhone($mobile)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, 'error' => 'Failed to obtain access token.'];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $mobile = addslashes($mobile);
        $query = urlencode("SELECT Id FROM Contact WHERE MobilePhone='{$mobile}'");

        $curlRequest = new CurlRequest($this->apiUrl . "/services/data/v61.0/query/?q=$query");
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        if ($response) {
            $response = json_decode($response, true);
            if (!empty($response['records'])) {
                return ['status' => true, 'contactId' => $response['records'][0]['Id']];
            } else {
                return ['status' => false, 'contactId' => null];
            }
        }

        return ['status' => false, 'error' => 'Failed to retrieve contact by email.'];
    }

    public function getContactIdByMobilePhoneLike($mobile)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, 'error' => 'Failed to obtain access token.'];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $mobile = addslashes($mobile);
        $query = urlencode("SELECT Id FROM Contact WHERE MobilePhone LIKE '%{$mobile}'");

        $curlRequest = new CurlRequest($this->apiUrl . "/services/data/v61.0/query/?q=$query");
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        if ($response) {
            $response = json_decode($response, true);
            if (!empty($response['records'])) {
                return ['status' => true, 'contactId' => $response['records'][0]['Id']];
            } else {
                return ['status' => false, 'contactId' => null];
            }
        }

        return ['status' => false, 'error' => 'Failed to retrieve contact by email.'];
    }

    public function createTask($data)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, "error" => "Failed to obtain access token."];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . '/services/data/v61.0/sobjects/Task');
        $curlRequest->setMethod('POST');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'JSON');

        $response = $curlRequest->execute();
        return json_decode($response, true);
    }

    public function updateTask($taskId, $data)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, "error" => "Failed to obtain access token."];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . "/services/data/v61.0/sobjects/Task/{$taskId}");
        $curlRequest->setMethod('PATCH');
        $curlRequest->setHeaders($headers);
        $curlRequest->setBody($data, 'JSON');

        $curlRequest->execute();
    }

    public function getTask($taskId)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return ['status' => false, "error" => "Failed to obtain access token."];

        $headers = [
            'Authorization' => "Bearer " . $accessToken,
        ];

        $curlRequest = new CurlRequest($this->apiUrl . "/services/data/v61.0/sobjects/Task/{$taskId}");
        $curlRequest->setMethod('GET');
        $curlRequest->setHeaders($headers);

        $response = $curlRequest->execute();
        return json_decode($response, true);
    }
}

?>