<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SendcloudService
{
    private string $apiKey;
    private string $apiSecret;
    private HttpClientInterface $client;

    public function __construct(string $apiKey, string $apiSecret, HttpClientInterface $client)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->client = $client;
    }

    public function fetchShippingMethods(): array
    {
        $url = 'https://panel.sendcloud.sc/api/v2/shipping_methods';
        $authString = base64_encode($this->apiKey . ':' . $this->apiSecret);
        
        $response = $this->client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $authString,
                'Content-Type' => 'application/json',
            ],
        ]);
        
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(sprintf(
                "Sendcloud error (%d): %s",
                $statusCode,
                $response->getContent(false)
            ));
        }
        
        return $response->toArray();
    }

    public function createParcel(array $parcelData): array
    {
        if (!isset($parcelData['shipping_method'])) {
            throw new \InvalidArgumentException("Le paramètre 'shipping_method' est obligatoire");
        }

        if (!isset($parcelData['weight']) || !is_array($parcelData['weight'])) {
            throw new \InvalidArgumentException("Le paramètre 'weight' doit être un tableau avec 'value' et 'unit'");
        }

        $url = 'https://panel.sendcloud.sc/api/v2/parcels';
        $authString = base64_encode($this->apiKey . ':' . $this->apiSecret);
        
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $authString,
                'Content-Type' => 'application/json',
            ],
            'json' => ['parcel' => $parcelData],
        ]);
        
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(sprintf(
                "Sendcloud error (%d): %s",
                $statusCode,
                $response->getContent(false)
            ));
        }
        
        return $response->toArray();
    }

    public function fetchShippingOptionsForRecipient(array $recipientParams, array $additionalParams = []): array
    {
        // Validation des paramètres requis
        if (!isset($recipientParams['to_country_code'])) {
            throw new \InvalidArgumentException("Le paramètre 'to_country_code' est obligatoire");
        }

        if (!isset($recipientParams['weight']) || !is_array($recipientParams['weight'])) {
            throw new \InvalidArgumentException("Le paramètre 'weight' doit être un tableau avec 'value' et 'unit'");
        }
        

        $url = 'https://panel.sendcloud.sc/api/v3/fetch-shipping-options';
        $authString = base64_encode($this->apiKey . ':' . $this->apiSecret);
        
        $senderParams = [
            'from_country_code' => 'FR',
            'from_postal_code' => '75001',
        ];
        
        $params = array_merge($senderParams, $recipientParams, $additionalParams);
        
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $authString,
                'Content-Type' => 'application/json',
            ],
            'json' => $params,
        ]);
        
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(sprintf(
                "Sendcloud error (%d): %s",
                $statusCode,
                $response->getContent(false)
            ));
        }
        
        return $response->toArray();
    }
}