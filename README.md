# Classes for Anthill, MySQL PDO, Hubspot, Mailgun, Microsoft Dynamics 365, SalesForce, Spark RE, Zapier 

===================================

### Intro
This repository contains classes for various CRM connections, designed to help us get started quickly with any form-to-CRM integration.The classes belong to `Client` namespace and can be used as follows and included with `require 'includes/index.php';`

```
use Client\CurlRequest;
use Client\ZapierWebHook;
use Client\MD365;
use Client\SalesForce;
use Client\HubSpotContactManager;
use Client\MailgunMailer;
use Client\Response;
```

### Validate Form
```
$validate=CurlRequest::validate($_POST,[
       "firstname"         =>"required",
       "lastname"          =>"required",
       "email"             =>"required|email",
       "number-input"      =>"phone"
  ]);

  if(!empty($validate)){
      foreach($validate as $error){
          echo json_encode(['status' => false, "data" =>  $error[0] ]);
      }
      exit;
  }
```


### Sending Mail by Mailgun API
```
$subject = "Subject - Enquiry";
$mailer = new MailgunMailer();
$mailer->setRecipient("abc@pqr.com"); // Optional override

$response = $mailer->sendEmail(
    $subject,
    $htmlmail
);

echo json_encode($response);
```

### Sending Leads to Anthill
#### Creating an Enquiry
```
$crm = new AnthillCRM();
$response = $crm->createEnquiry($_POST);

if ($response['status']) {
  echo json_encode([
      'status'      => true,
      'customer_id' => $response['customer_id'],
      'enquiry_id'  => $response['enquiry_id']
  ]);
} else {
  echo json_encode(['status' => false]);
}
```
#### Updating an Enquiry
```
$crm = new AnthillCRM();
$fieldsToUpdate = [
  'enquiry_id' => '000000',
  'howdidyoufindus'  => 'Google',
  'whatisyourpricerange' => 'Not a Concern',
  'whattypeofprojectisthis'   => 'Playground',
  'whatareyoulookingfor' => 'Construction',
  'preferredcommunication' => 'Video Call',
  'whenisbestoforyou'    => 'Afternoons'
  ];

$response =  $crm->updateEnquiryDetails($fieldsToUpdate);
```
#### Reset Enquiry Details
```
$fieldsToClear = [
    "How did you find us?",
    "What is your price range?",
    "What is your timeframe for completion?",
    "What type of project is this?",
    "What you’re looking for?",
    "Preferred communication?",
    "When is best for you?"
];
$crm = new AnthillCRM();
$response = $crm->clearEnquiryDetails(000000, $fieldsToClear);
```
#### Get Enquiry by Enquiry ID
```
$crm = new AnthillCRM();
$enquiry = $crm->getEnquiryById(000000);

echo '<pre>';
print_r($enquiry);
```
#### Get Customer by Customer ID
```
$crm = new AnthillCRM();
$customer = $crm->getCustomerById(111111);

echo '<pre>';
print_r($customer);
```

### Sending Leads to Hubspot
```
$accessToken = "pat-xyz-xxxxxxxx-xxxx-xxxx-xxxxxxxxxxxx";
$listId      = "123456"; // HubSpot list ID
$hubspot     = new HubSpotContactManager($accessToken, $listId);
$data        = array(
        'firstname'  => $_POST['firstname'],
        'lastname'   => $_POST['lastname'],
        'email'      => $_POST['email'],
        'phone'      => $_POST['number-input'],
        'utm_source'   => $_POST['utm_source'],
        'utm_medium'   => $_POST['utm_medium'],
        'utm_campaign' => $_POST['utm_campaign'],
        'utm_term'     => $_POST['utm_term'],
        'utm_content'  => $_POST['utm_content'],
        'gclid'        => $_POST['gclid'],
        'feedback'     => $_POST['message']
    );

try {
    $email = $data['email'];
    $contactId = $hubspot->lookupContact($email);

    if ($contactId) {
        $hubspot->updateContact($contactId, $data);
    } else {
        $contactId = $hubspot->createContact($data);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => false, "data" => $e->getMessage() ]);
}
```

### Sending Leads to Salesforce
```
$leadsData = [
    'FirstName'   => $_POST['firstname'],
    'LastName'    => $_POST['lastname'],
    'Email'       => $_POST['email'],
    'MobilePhone' => ltrim($_POST['number-input'], '+'),
    'Message__c'      => $_POST['message'],
    'utm_source__c'   => $_POST['utm_source'],
    'utm_medium__c'   => $_POST['utm_medium'],
    'utm_campaign__c' => $_POST['utm_campaign'],
    'utm_term__c'     => $_POST['utm_term'],
    'utm_content__c'  => $_POST['utm_content'],
    'utm_gclid__c'    => $_POST['gclid']
    
];

$leadsid = ''; // Salesforce ID placeholder

// Step 1: Try fetching existing lead by email
$lookup = $salesforce->getLeadIdByEmail($_POST['email']);

if (isset($lookup['status']) && $lookup['status'] === true && !empty($lookup['leadId'])) {
    // Lead exists – perform update
    $leadsid = $lookup['leadId'];

    // Optionally update the existing lead
    $updateResult = $salesforce->updateLead($leadsid, $leadsData);

    // If update fails, log but continue
    if (!isset($updateResult['status']) || $updateResult['status'] !== true) {
        $leadsid = 'SF ERROR: Failed to update lead';
    }

} else {
    // Lead not found – create a new one
    $createResult = $salesforce->createLead($leadsData);

    if (isset($createResult['status']) && $createResult['status'] == 1 && !empty($createResult['id'])) {
        $leadsid = $createResult['id'];
    } else {
        $leadsid = isset($createResult['error']) ? 'SF ERROR: ' . $createResult['error'] : 'SF ERROR: Unknown';
    }
}
```

### Sending Leads to Microsoft Dynamics
```
try {

    $formData = array(
        "formName"           => "LUUX_Residence_Enquiry",
        "prefix"             => $_POST['title'],
        "firstName"          => $_POST['firstname'] ,
        "lastName"           => $_POST['lastname'] ,
        "emailAddress"       => $_POST['email'] ,
        "phoneNumber"        => $_POST['number-input'],
        "additionalAttributes" => [
                    ["key" => "Country", "value" => $_POST['country'] ],
                    ["key" => "Property Interest", "value" => $_POST['property'] ],
                    ["key" => "Preferred Home Size", "value" => $_POST['homesize'] ],
                    ["key" => "Residency Type", "value" => $_POST['residence'] ],
                    ["key" => "Budget", "value" => $_POST['budget'] ],
                    ["key" => "Are You a Broker?", "value" => $_POST['broker'] ],
                    ["key" => "Privacy Policy Consent?", "value" => $_POST['consent'] ],
                    ["key" => "Comments", "value" => strip_tags($_POST['comment']) ],
                    ["key" => "utm_source", "value" => $_POST['utm_source'] ],
                    ["key" => "utm_medium", "value" => $_POST['utm_medium'] ],
                    ["key" => "utm_campaign", "value" => $_POST['utm_campaign'] ],
                    ["key" => "utm_term", "value" => $_POST['utm_term'] ],
                    ["key" => "utm_content", "value" => $_POST['utm_content'] ],
                    ["key" => "gclid", "value" => $_POST['gclid'] ]
                    
                ]
    );

    $md = new MD365();         
    $response = $md->createEnquiry($formData);
    echo $response; 
    
} catch (Exception $e) {
    
}
```

### Sending Leads to Zapier
```
try {
    $formData = [
        "firstname"     => $_POST['firstname'],
        "lastname"      => $_POST['lastname'],
        "email"         => $_POST['email'],
        "telephone"     => $_POST['number-input'],
        "message"       => $_POST['message'],
        "salesforceid"  => $leadsid,

        "utm_source"    => $_POST['utm_source'] ?? '',
        "utm_campaign"  => $_POST['utm_campaign'] ?? '',
        "utm_medium"    => $_POST['utm_medium'] ?? '',
        "utm_term"      => $_POST['utm_term'] ?? '',
        "utm_content"   => $_POST['utm_content'] ?? '',
        "gclid"         => $_POST['gclid'] ?? '',
    ];

    $zapier = new ZapierWebhook();
    $zapier->setWebHook('ENQUIRY_SHEET');
    $response = $zapier->googleSheetPost($formData);

} catch (Exception $e) {
    // Optional: log or fallback
}
```

### Sending Leads to Database
#### Insert
```
$db = new Database();

// Map form data to database columns
$formData = [
    "firstName"       => $_POST['firstname'] ?? NULL,
    "lastName"        => $_POST['lastname'] ?? NULL,
    "email"           => $_POST['emailaddress'] ?? NULL,
    "phoneNumber"     => $_POST['telephone-number-input'] ?? NULL,
];

try {
    $insertId = $db->insert("general_user_info", $formData);
    if ($insertId) {
        echo json_encode(['status' => true, "data" => 'Message has been sent','taskid'=>$insertId ]);
    } else {
        echo "Failed to insert data.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```
#### Update
```
$db = new Database();
$taskId = $_POST['taskid']; // ID to update

$updateData = [
    "budget"         => $_POST['budget'] ?? NULL,
    "prospect"       => $_POST['identity'] ?? NULL,
    "purchaceIn"     => $_POST['purchase_time'] ?? NULL,
];

try {
    $updatedRows = $db->update("general_user_info", $updateData, "id = :id", ["id" => $taskId]);
    if ($updatedRows) {

        echo json_encode(['status' => true, "data" => 'Message has been sent', 'taskid' => $taskId  ]);
    } else {
        echo json_encode(['status' => true, "data" => 'Record not updated', 'taskid' => $taskId  ]);
    }
} catch (Exception $e) {

    echo json_encode(['status' => false, "data" => $e->getMessage(), 'taskid' => $taskId  ]);
} 
```

### Sending Leads to Spark RE CRM 
```
$crm = new SparkCRM();

// Prepare contact data
$contactData = [
    "first_name" => $_POST['firstname'],
    "last_name"  => $_POST['lastname'],
    "email"      => $_POST['emailaddress'],
    "phone"      => $_POST['telephone-number-input'],
    "website"    => 'https://residences.brilandclub.com',
    "contact_preference" => $_POST['contact_method'],
    "agent"      => ($_POST['broker'] === "Yes"),
    "rating_id"  => ($_POST['broker'] === "Yes") ? 51470 : 51469,
    "custom_field_values" => [
        ["custom_field_id" => 20692, "value" => $_POST['utm_source']],
        ["custom_field_id" => 20771, "value" => $_POST['utm_medium']],
        ["custom_field_id" => 20693, "value" => $_POST['utm_campaign']],
        ["custom_field_id" => 20773, "value" => $_POST['utm_content']],
        ["custom_field_id" => 20772, "value" => $_POST['utm_term']],
        ["custom_field_id" => 20774, "value" => $_POST['gclid']],
    ],
    "question_answers" => [
        ["question" => "Are you willing to discuss available properties?", "answers" => [$_POST['discuss']]],
        ["question" => "Preferred Home Size:", "answers" => [$_POST['home_size']]],
        ["question" => "I'm interested in:", "answers" => [$_POST['residency_type']]],
        ["question" => "Have you previously visited Harbour Island?", "answers" => [$_POST['visited_harbour_island']]]
    ]
];

// Create contact
$contactResponse = $crm->createContact($contactData);

if (isset($contactResponse['id'])) {
    $contactId = $contactResponse['id'];

    // Add note
    $noteResponse = $crm->addNote($contactId, $_POST['question'], 7261);

    echo "Contact and note created successfully.";
} else {
    echo "Failed to create contact: " . print_r($contactResponse, true);
}
```