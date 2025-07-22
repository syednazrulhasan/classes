<?php

namespace Client;

use Exception;

class HubSpotContactManager
{
    private string $accessToken;
    private string $listId;

    private string $lookupUrl = "https://api.hubapi.com/crm/v3/objects/contacts/search";
    private string $createUrl = "https://api.hubapi.com/crm/v3/objects/contacts";

    public function __construct(string $accessToken, string $listId)
    {
        $this->accessToken = $accessToken;
        $this->listId = $listId;
    }

    public function syncContact(array $data): string
    {
        $email = $data['email'] ?? null;
        if (!$email) {
            throw new Exception("Email is required for syncing a contact.");
        }

        $contactId = $this->lookupContact($email);

        if ($contactId) {
            $this->updateContact($contactId, $data);
        } else {
            $contactId = $this->createContact($data);
        }

        $this->addToList($contactId);
        return $contactId;
    }

    public function lookupContact(string $email): ?string
    {
        $payload = json_encode([
            "filterGroups" => [[
                "filters" => [[
                    "propertyName" => "email",
                    "operator" => "EQ",
                    "value" => $email
                ]]
            ]]
        ]);

        $response = $this->request($this->lookupUrl, "POST", $payload);
        return $response['results'][0]['id'] ?? null;
    }

    public function updateContact(string $id, array $data): void
    {
        $url = "https://api.hubapi.com/crm/v3/objects/contacts/$id";
        $payload = json_encode(['properties' => $data]);

        $this->request($url, "PATCH", $payload);
    }

    public function createContact(array $data): string
    {
        $payload = json_encode(['properties' => $data]);
        $response = $this->request($this->createUrl, "POST", $payload);

        return $response["id"] ?? throw new Exception("Failed to create contact.");
    }

    public function addToList(string $contactId): void
    {
        $url = "https://api.hubapi.com/contacts/v1/lists/{$this->listId}/add";
        $payload = json_encode(["vids" => [$contactId]]);

        $this->request($url, "POST", $payload);
    }

    private function request(string $url, string $method, string $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->accessToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$result) {
            throw new Exception("HubSpot API Error: $error");
        }

        return json_decode($result, true);
    }
}