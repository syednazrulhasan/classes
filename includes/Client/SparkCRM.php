<?php
namespace Client;

class SparkCRM
{
    private string $apiToken;
    private string $apiBaseUrl;

    public function __construct()
    {
        // Define API token here so no need to pass it during initialization
        $this->apiToken = "YOUR_API_TOKEN_HERE";
        $this->apiBaseUrl = "https://api.spark.re/v2";
    }

    /**
     * Create a contact in Spark CRM
     */
    public function createContact(array $data): array
    {
        $contactUrl = $this->apiBaseUrl . '/contacts';
        $jsonData = json_encode($data);

        return $this->sendRequestAndDecode($contactUrl, $jsonData);
    }

    /**
     * Add a note to a contact in Spark CRM
     */
    public function addNote(int $contactId, string $noteText, ?int $teamMemberId = null): array
    {
        $noteUrl = $this->apiBaseUrl . '/notes';
        $noteData = [
            "contact_id" => $contactId,
            "text"       => $noteText
        ];

        if ($teamMemberId !== null) {
            $noteData["team_member_id"] = $teamMemberId;
        }

        $jsonData = json_encode($noteData);

        return $this->sendRequestAndDecode($noteUrl, $jsonData);
    }

    /**
     * Internal helper to send cURL requests and decode JSON
     */
    private function sendRequestAndDecode(string $url, string $jsonData): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->apiToken}"
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg());
        }

        return $decoded;
    }
}