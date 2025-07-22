<?php
namespace Client;

class MailgunMailer
{
    private string $api_key = "your_api_key_here";
    private string $domain  = "example.com";
    private string $to      = "recipient@example.com"; // Default recipient address
    private string $from    = "Sender Name <no-reply@example.com>"; // Sender address

    /**
     * Optionally override the recipient email
     */
    public function setRecipient(string $email): void
    {
        $this->to = $email;
    }

    /**
     * Send an email via Mailgun
     */
    public function sendEmail(string $subject, string $html): array
    {
        $url = "https://api.mailgun.net/v3/{$this->domain}/messages";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "api:{$this->api_key}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'to'      => $this->to,
                'from'    => $this->from,
                'subject' => $subject,
                'html'    => $html
            ]);

            $result = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($result, true);

            if ($http_status === 200 && isset($response['message']) && strpos($response['message'], 'Queued') !== false) {
                return ['status' => true, 'data' => 'Message has been sent'];
            } else {
                throw new \Exception("Failed to send email. Mailgun response: " . $result);
            }

        } catch (\Exception $e) {
            return ['status' => false, 'data' => "Message could not be sent. Error: " . $e->getMessage()];
        }
    }
}