<?php
#namespace Client;
class AnthillCRM {
    private $soapUrl  = "https://dummy.anthillcrm.com/api/v1.asmx";
    private $username = "dummy@userlogin.com";
    private $password = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; 
    private $datesubmission;

    public function __construct() {
        $timezone = new DateTimeZone('Europe/London');
        $date = new DateTime('now', $timezone);
        $this->datesubmission = $date->format('d-m-Y H:i:s');
    }

    private function sanitizeArray(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeArray($value); // recursive
            } else {
                $clean[$key] = htmlspecialchars(trim($value));
            }
        }
        return $clean;
    }

    /*Create a new Enquiry*/
    public function createEnquiry(array $postData): array {
        $postData = $this->sanitizeArray($postData);


        $type = $postData['function'] ?? '';
        $xml = ($type === 'brochure-form')
            ? $this->buildBrochureXML($postData)
            : $this->buildConsultationXML($postData);

        $response = $this->postXML($xml);

        if (isset($response->CreateCustomerEnquiryResponse)) {
            $ids = $response->CreateCustomerEnquiryResponse->CreateCustomerEnquiryResult->int;
            $customerId = (string) $ids[0];
            $enquiryId  = (string) $ids[1];

            // ✅ Update address immediately after getting customer ID
            $postData['customer_id'] = $customerId;
            $this->updateCustomerAddress($postData);

            // ✅ Update customer details thereafter to update Name and Phone
            $this->updateCustomerDetails($postData);

            // ✅ Call MessageBird webhook
            $this->postToMessageBird($postData, $enquiryId, $customerId);

            return [
                'status' => true,
                'customer_id' => (string) $ids[0],
                'enquiry_id'  => (string) $ids[1]
            ];
        }

        return ['status' => false, 'data' => 'Enquiry creation failed'];
    }

    //*Future Scope to add fields dynamically by mapping post fields to anthill key*/
    public function updateEnquiryDetails(array $postData): array {
            $postData = $this->sanitizeArray($postData);

            $xml = <<<XML
                <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                 xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                                 xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                  <soap12:Header>
                    <AuthHeader xmlns="http://www.anthill.co.uk/">
                      <Username>{$this->username}</Username>
                      <Password>{$this->password}</Password>
                    </AuthHeader>
                  </soap12:Header>
                  <soap12:Body>
                    <EditEnquiryDetails xmlns="http://www.anthill.co.uk/">
                      <enquiryId>{$postData['enquiry_id']}</enquiryId>
                      <customFields>
                        <CustomField>
                          <Key>How did you find us?</Key>
                          <Value>{$postData['howdidyoufindus']}</Value>
                        </CustomField>
                        <CustomField>
                          <Key>What is your price range?</Key>
                          <Value>{$postData['whatisyourpricerange']}</Value>
                        </CustomField>
                        <CustomField>
                          <Key>What type of project is this?</Key>
                          <Value>{$postData['whattypeofprojectisthis']}</Value>
                        </CustomField>
                        <CustomField>
                          <Key>What you’re looking for?</Key>
                          <Value>{$postData['whatareyoulookingfor']}</Value>
                        </CustomField>
                        <CustomField>
                          <Key>Preferred communication?</Key>
                          <Value>{$postData['preferredcommunication']}</Value>
                        </CustomField>
                        <CustomField>
                          <Key>When is best for you?</Key>
                          <Value>{$postData['whenisbestoforyou']}</Value>
                        </CustomField>
                      </customFields>
                    </EditEnquiryDetails>
                  </soap12:Body>
                </soap12:Envelope>
                XML;

        $response = $this->postXML($xml);

        if (isset($response->EditEnquiryDetailsResponse)) {
            return ['status' => true];
        }

        return ['status' => false, 'data' => 'Enquiry update failed'];
    }

    /*Future Scope to add fields dynamically by mapping post fields to anthill key*/
    private function updateCustomerAddress(array $data): bool {
        if (empty($data['customer_id'])) {
            return false;
        }

        $customerId = (int) $data['customer_id'];
        $address1   = $data['streetname'] ?? '';
        $city       = $data['city'] ?? '';
        $county     = $data['countystate'] ?? '';
        $country    = $data['countryname'] ?? '';
        $postcode   = $data['postcode'] ?? '';

        $xml = <<<XML
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                             xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Header>
                <AuthHeader xmlns="http://www.anthill.co.uk/">
                  <Username>{$this->username}</Username>
                  <Password>{$this->password}</Password>
                </AuthHeader>
              </soap12:Header>
              <soap12:Body>
                <EditCustomerAddress xmlns="http://www.anthill.co.uk/">
                  <customerId>{$customerId}</customerId>
                  <addressModel>
                    <Address1>{$address1}</Address1>
                    <City>{$city}</City>
                    <County>{$county}</County>
                    <Country>{$country}</Country>
                    <Postcode>{$postcode}</Postcode>
                  </addressModel>
                </EditCustomerAddress>
              </soap12:Body>
            </soap12:Envelope>
            XML;

        $response = $this->postXML($xml);

        return isset($response->EditCustomerAddressResponse);
    }

    /*Future Scope to add fields dynamically by mapping post fields to anthill key*/
    private function updateCustomerDetails(array $data): bool {
        if (empty($data['customer_id'])) {
            return false;
        }

        $customerId = (int) $data['customer_id'];
        $nametitle  = htmlspecialchars($data['nametitle'] ?? '');
        $firstname  = htmlspecialchars($data['firstname'] ?? '');
        $surname    = htmlspecialchars($data['surname'] ?? '');
        $full_phone = htmlspecialchars($data['full_phone'] ?? '');

        $xml = <<<XML
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                             xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Header>
                <AuthHeader xmlns="http://www.anthill.co.uk/">
                  <Username>{$this->username}</Username>
                  <Password>{$this->password}</Password>
                </AuthHeader>
              </soap12:Header>
              <soap12:Body>
                <EditCustomerDetails xmlns="http://www.anthill.co.uk/">
                  <customerId>{$customerId}</customerId>
                  <customFields>
                    <CustomField>
                        <Key>Title</Key>
                        <Value>{$nametitle}</Value>
                    </CustomField>
                    <CustomField>
                        <Key>First Name</Key>
                        <Value>{$firstname}</Value>
                    </CustomField>
                    <CustomField>
                        <Key>Last Name</Key>
                        <Value>{$surname}</Value>
                    </CustomField>
                    <CustomField>
                        <Key>Telephone</Key>
                        <Value>{$full_phone}</Value>
                    </CustomField>
                    <CustomField>
                        <Key>Mobile</Key>
                        <Value>{$full_phone}</Value>
                    </CustomField>
                  </customFields>
                </EditCustomerDetails>
              </soap12:Body>
            </soap12:Envelope>
            XML;

        $response = $this->postXML($xml);
        return isset($response->EditCustomerDetailsResponse);
    }

    private function buildBrochureXML($data): string {

        $address     = htmlspecialchars(trim($data['streetname'] ?? ''));
        $city        = htmlspecialchars(trim($data['city'] ?? ''));
        $postcode    = htmlspecialchars(trim($data['postcode'] ?? ''));
        $county      = htmlspecialchars(trim($data['countystate'] ?? ''));
        $country     = htmlspecialchars(trim($data['countryname'] ?? ''));
        $consent     = isset($data['agreed']) && $data['agreed'] === 'on' ? 'Opt in' : 'Opt out';
        $marketingConsent = $consent === 'Opt in' ? 'true' : 'false';

        $utm_source  = $data['utm_source'] ?? '';
        $utm_medium  = $data['utm_medium'] ?? '';
        $utm_campaign= $data['utm_campaign'] ?? '';
        $utm_content = $data['utm_content'] ?? '';
        $utm_term    = $data['utm_term'] ?? '';
        $gclid       = $data['gclid'] ?? '';

        return <<<XML
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                             xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Header>
                <AuthHeader xmlns="http://www.anthill.co.uk/">
                  <Username>{$this->username}</Username>
                  <Password>{$this->password}</Password>
                </AuthHeader>
              </soap12:Header>
              <soap12:Body>
                <CreateCustomerEnquiry xmlns="http://www.anthill.co.uk/">
                  <locationId>3</locationId>
                  <source>Online</source>
                  <customer>
                    <TypeId>1</TypeId>
                    <MarketingConsentGiven>{$marketingConsent}</MarketingConsentGiven>
                    <Address>
                      <Address1>{$address}</Address1>
                      <City>{$city}</City>
                      <County>{$county}</County>
                      <Country>{$country}</Country>
                      <Postcode>{$postcode}</Postcode>
                    </Address>
                    <CustomFields>
                      <CustomField><Key>Title</Key><Value>{$data['nametitle']}</Value></CustomField>
                      <CustomField><Key>First Name</Key><Value>{$data['firstname']}</Value></CustomField>
                      <CustomField><Key>Last Name</Key><Value>{$data['surname']}</Value></CustomField>
                      <CustomField><Key>Email</Key><Value>{$data['email']}</Value></CustomField>
                      <CustomField><Key>Telephone</Key><Value>{$data['full_phone']}</Value></CustomField>
                      <CustomField><Key>Marketing Consent</Key><Value>{$consent}</Value></CustomField>
                    </CustomFields>
                  </customer>
                  <enquiry>
                    <TypeId>3</TypeId>
                    <CustomFields>
                        <CustomField><Key>utm_source</Key><Value>{$utm_source}</Value></CustomField>
                        <CustomField><Key>utm_medium</Key><Value>{$utm_medium}</Value></CustomField>
                        <CustomField><Key>utm_campaign</Key><Value>{$utm_campaign}</Value></CustomField>
                        <CustomField><Key>utm_content</Key><Value>{$utm_content}</Value></CustomField>
                        <CustomField><Key>utm_term</Key><Value>{$utm_term}</Value></CustomField>
                        <CustomField><Key>gclid</Key><Value>{$gclid}</Value></CustomField>
                        <CustomField><Key>Source</Key><Value>Online</Value></CustomField>
                        <CustomField><Key>How did you find us?</Key><Value>LUUX</Value></CustomField>
                        <CustomField><Key>Domain</Key><Value>https://previews.luux-media.com/smallbone2025v2</Value></CustomField>
                    </CustomFields>
                  </enquiry>
                </CreateCustomerEnquiry>
              </soap12:Body>
            </soap12:Envelope>
            XML;
    }

    private function buildConsultationXML($data): string {
        $address    = htmlspecialchars(trim($data['streetname02'] ?? ''));
        $city       = htmlspecialchars(trim($data['city02'] ?? ''));
        $postcode   = htmlspecialchars(trim($data['cpostcode'] ?? ''));
        $county     = htmlspecialchars(trim($data['countystate02'] ?? ''));
        $country    = htmlspecialchars(trim($data['countryname'] ?? ''));
        $consent    = isset($data['cagreed']) && $data['cagreed'] === 'on' ? 'Opt in' : 'Opt out';
        $marketingConsent = $consent === 'Opt in' ? 'true' : 'false';

        $utm_source  = $data['utm_source'] ?? '';
        $utm_medium  = $data['utm_medium'] ?? '';
        $utm_campaign= $data['utm_campaign'] ?? '';
        $utm_content = $data['utm_content'] ?? '';
        $utm_term    = $data['utm_term'] ?? '';
        $gclid       = $data['gclid'] ?? '';

        return <<<XML
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                             xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
              <soap12:Header>
                <AuthHeader xmlns="http://www.anthill.co.uk/">
                  <Username>{$this->username}</Username>
                  <Password>{$this->password}</Password>
                </AuthHeader>
              </soap12:Header>
              <soap12:Body>
                <CreateCustomerEnquiry xmlns="http://www.anthill.co.uk/">
                  <locationId>3</locationId>
                  <source>Online</source>
                  <customer>
                    <TypeId>1</TypeId>
                    <MarketingConsentGiven>{$marketingConsent}</MarketingConsentGiven>
                    <Address>
                      <Address1>{$address}</Address1>
                      <City>{$city}</City>
                      <County>{$county}</County>
                      <Country>{$country}</Country>
                      <Postcode>{$postcode}</Postcode>
                    </Address>
                    <CustomFields>
                      <CustomField><Key>Title</Key><Value>{$data['cnametitle']}</Value></CustomField>
                      <CustomField><Key>First Name</Key><Value>{$data['cfirstname']}</Value></CustomField>
                      <CustomField><Key>Last Name</Key><Value>{$data['csurname']}</Value></CustomField>
                      <CustomField><Key>Email</Key><Value>{$data['email']}</Value></CustomField>
                      <CustomField><Key>Telephone</Key><Value>{$data['telephone']}</Value></CustomField>
                      <CustomField><Key>Marketing Consent</Key><Value>{$consent}</Value></CustomField>
                    </CustomFields>
                  </customer>
                  <enquiry>
                    <TypeId>4</TypeId>
                    <CustomFields>
                      <CustomField><Key>utm_source</Key><Value>{$utm_source}</Value></CustomField>
                      <CustomField><Key>utm_medium</Key><Value>{$utm_medium}</Value></CustomField>
                      <CustomField><Key>utm_campaign</Key><Value>{$utm_campaign}</Value></CustomField>
                      <CustomField><Key>utm_content</Key><Value>{$utm_content}</Value></CustomField>
                      <CustomField><Key>utm_term</Key><Value>{$utm_term}</Value></CustomField>
                      <CustomField><Key>gclid</Key><Value>{$gclid}</Value></CustomField>
                      <CustomField><Key>Source</Key><Value>Online</Value></CustomField>
                      <CustomField><Key>How did you find us?</Key><Value>LUUX</Value></CustomField>
                    </CustomFields>
                  </enquiry>
                </CreateCustomerEnquiry>
              </soap12:Body>
            </soap12:Envelope>
            XML;
    }

    private function postXML(string $xml) {
        $ch = curl_init($this->soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xml)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $body = str_replace(["<soap:Body>", "</soap:Body>"], "", $response);
            $xml = simplexml_load_string($body);
            $xml->registerXPathNamespace('ns', 'http://www.anthill.co.uk/');
            return $xml;
        }
        return false;
    }

    private function postToMessageBird(array $postData, string $enquiryId, string $customerId): void {
        $url = "https://capture.region.messagebird.com/webhooks/your-webhook-id/your-token-id";
        
        $payload = [
            'anthill_enquiryid'  => $enquiryId,
            'anthill_customerid' => $customerId,
            'firstname'          => $postData['firstname'] ?? '',
            'lastname'           => $postData['surname'] ?? '',
            'email'              => $postData['email'] ?? '',
            'phone'              => $postData['full_phone'] ?? '',
            'form_type'          => $postData['function'] ?? '',
            'address'            => $postData['streetname'] ?? '',
            'city'               => $postData['city'] ?? '',
            'county'             => $postData['countystate'] ?? '',
            'country'            => $postData['countryname'] ?? '',
            'zipcode'            => $postData['postcode'] ?? '',
            'datesubmission'     => $this->datesubmission
        ];

        $json = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /*Get Enquiry Details by Customer ID*/
    public function getEnquiryById(int $enquiryId): string {
        // SOAP Request XML (exactly as your working code)
        $xmlRequest = <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                         xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                         xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
          <soap12:Header>
            <AuthHeader xmlns="http://www.anthill.co.uk/">
              <Username>{$this->username}</Username>
              <Password>{$this->password}</Password>
            </AuthHeader>
          </soap12:Header>
          <soap12:Body>
            <GetEnquiryDetails xmlns="http://www.anthill.co.uk/">
              <enquiryId>{$enquiryId}</enquiryId>
            </GetEnquiryDetails>
          </soap12:Body>
        </soap12:Envelope>
        XML;
    
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($xmlRequest)
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
    
        // Execute cURL request
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
    
        // Load the XML response exactly like your procedural code
        $xml = simplexml_load_string($response);
        $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
        $xml->registerXPathNamespace('ns', 'http://www.anthill.co.uk/');
    

        // Extract CustomFields and map to key-value pairs
        $customFields = $xml->xpath('//ns:CustomField');
        $result = [];
    
        if ($customFields) {
            foreach ($customFields as $field) {
                $key = (string)$field->Key;
                $value = isset($field->Value) && strlen(trim((string)$field->Value)) > 0 
                         ? (string)$field->Value 
                         : null;
                $result[$key] = $value;
            }
        }
    
        // Return as JSON string
        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /*Get Customer Details by Customer ID*/
    public function getCustomerById(int $customerId, bool $includeActivity = false): string
    {
        $includeActivityValue = $includeActivity ? "true" : "false";

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
          <soap12:Header>
            <AuthHeader xmlns="http://www.anthill.co.uk/">
              <Username>{$this->username}</Username>
              <Password>{$this->password}</Password>
            </AuthHeader>
          </soap12:Header>
          <soap12:Body>
            <GetCustomerDetails xmlns="http://www.anthill.co.uk/">
              <customerId>{$customerId}</customerId>
              <includeActivity>{$includeActivityValue}</includeActivity>
            </GetCustomerDetails>
          </soap12:Body>
        </soap12:Envelope>
        XML;

        $headers = [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($soapRequest)
        ];

        $ch = curl_init($this->soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        // Load XML response with namespaces
        $xml = new \SimpleXMLElement($response);
        $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
        $xml->registerXPathNamespace('ns', 'http://www.anthill.co.uk/');

        // Extract Address
        $addressNode = $xml->xpath('//ns:GetCustomerDetailsResult/ns:Address')[0] ?? null;

        if ($addressNode) {
            $address = [
                'Address1' => (string) ($addressNode->Address1 ?? ''),
                'Address2' => (string) ($addressNode->Address2 ?? ''),
                'City'     => (string) ($addressNode->City ?? ''),
                'County'   => (string) ($addressNode->County ?? ''),
                'Country'  => (string) ($addressNode->Country ?? ''),
                'Postcode' => (string) ($addressNode->Postcode ?? '')
            ];
        } else {
            $address = ["error" => "Address not found"];
        }

        // Extract CustomFields
        $customFields = $xml->xpath('//ns:CustomField');
        $customFieldData = [];

        if ($customFields) {
            foreach ($customFields as $field) {
                $key   = (string)$field->Key;
                $value = isset($field->Value) && strlen(trim((string)$field->Value)) > 0 ? (string)$field->Value : null;
                $customFieldData[$key] = $value;
            }
        }

        // Combine results
        $result = [
            "Address" => $address,
            "CustomFields" => $customFieldData
        ];

        return json_encode($result, JSON_PRETTY_PRINT);
    }


    public function clearEnquiryDetails(int $enquiryId, array $fieldsToClear): string
    {
        if (empty($fieldsToClear)) {
            throw new \Exception("No fields specified to clear.");
        }
    
        // Build <CustomField> XML elements with empty values
        $customFieldsXml = "";
        foreach ($fieldsToClear as $key) {
            $customFieldsXml .= <<<XML
            <CustomField>
                <Key>{$key}</Key>
                <Value></Value>
            </CustomField>
    
        XML;
        }
    
        $soapRequest = <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                         xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                         xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
          <soap12:Header>
            <AuthHeader xmlns="http://www.anthill.co.uk/">
              <Username>{$this->username}</Username>
              <Password>{$this->password}</Password>
            </AuthHeader>
          </soap12:Header>
          <soap12:Body>
            <EditEnquiryDetails xmlns="http://www.anthill.co.uk/">
              <enquiryId>{$enquiryId}</enquiryId>
              <customFields>
        {$customFieldsXml}      </customFields>
            </EditEnquiryDetails>
          </soap12:Body>
        </soap12:Envelope>
        XML;
    
        // Send SOAP request via cURL
        $ch = curl_init($this->soapUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soapRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/soap+xml; charset=utf-8",
            "Content-Length: " . strlen($soapRequest)
        ]);
    
        $response = curl_exec($ch);
    
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
    
        curl_close($ch);
    
        return $response;
    }

}