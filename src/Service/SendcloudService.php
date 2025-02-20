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
        $this->apiKey    = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->client    = $client;
    }

    public function createParcel(array $parcelData): array
    {
        // shipping_method sera défini en amont dans OrderController
        if (!isset($parcelData['shipping_method'])) {
            $parcelData['shipping_method'] = 'colissimo:home/fr'; // fallback
        }

        $url = 'https://panel.sendcloud.sc/api/v2/parcels';
        $authString = base64_encode($this->apiKey . ':' . $this->apiSecret);

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $authString,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'parcel' => $parcelData
            ],
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException(
                "Sendcloud error (" . $response->getStatusCode() . "): " . $response->getContent(false)
            );
        }

        return $response->toArray();
    }

    public function fetchShippingOptionsForRecipient(array $recipientParams, array $additionalParams = []): array
    {
        $url = 'https://panel.sendcloud.sc/api/v3/fetch-shipping-options';
        $authString = base64_encode($this->apiKey . ':' . $this->apiSecret);

        $senderParams = [
            'from_country_code' => 'FR',
            'from_postal_code'  => '95630'
        ];

        $params = array_merge($senderParams, $recipientParams, $additionalParams);

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $authString,
                'Content-Type'  => 'application/json',
            ],
            'json' => $params,
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException(
                "Sendcloud error (" . $response->getStatusCode() . "): " . $response->getContent(false)
            );
        }

        return $response->toArray();
    }
}
