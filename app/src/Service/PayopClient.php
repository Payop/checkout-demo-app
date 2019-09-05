<?php

namespace App\Service;

use App\Entity\Order;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PayopClient
 * @package App\Service
 */
class PayopClient
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Payop app public key
     *
     * @var string
     */
    private $publicKey;

    /**
     * Payop app secret key
     *
     * @var string
     */
    private $secretKey;

    /**
     * Authentication access token
     *
     * @var string
     */
    private $accessToken;

    /**
     * @param \GuzzleHttp\ClientInterface $client
     * @param string $publicKey
     * @param string $secretKey
     * @param string $accessToken
     */
    public function __construct(
        ClientInterface $client,
        string $publicKey,
        string $secretKey,
        string $accessToken
    ) {
        $this->client = $client;
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->accessToken = $accessToken;
    }

    /**
     * Get merchant payment methods
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPaymentMethods() : array
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            'instrument-settings/payment-methods/available-for-user',
            [RequestOptions::HEADERS => ['token' => $this->accessToken]]
        );

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    /**
     * @param \App\Entity\Order $order
     * @param array $payer
     * @param $resultUrl
     * @param $failUrl
     * @param string|null $pmId
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createInvoice(
        Order $order,
        array $payer,
        $resultUrl,
        $failUrl,
        ?string $pmId
    ) : string {
        $params = [
            'publicKey' => $this->publicKey,
            'order' => [
                'id' => $order->getId(),
                // just example. You can pass decimals: 2|3|4
                'amount' => number_format($order->getProduct()->getPrice(), 3, '.', ''),
                'currency' => $order->getProduct()->getCurrency(),
                // this is not required
                'items' => [
                    'name' => $order->getProduct()->getName(),
                    'price' => $order->getProduct()->getPrice(),
                    'currency' => $order->getProduct()->getCurrency(),
                ],
                'description' => "Payment for demo app order #{$order->getId()}",
            ],
            // email always required.
            // Extra fields can be necessary by payment method requirements.
            'payer' => $payer,
            'language' => 'en',
            'resultUrl' => $resultUrl,
            'failPath' => $failUrl,
            'signature' => $this->signature($order),
        ];

        if ($pmId) {
            $params['paymentMethod'] = $pmId;
        }

        $response = $this->client->request(
            Request::METHOD_POST,
            'invoices/create',
            [RequestOptions::JSON => $params]
        );
        $data = json_decode($response->getBody()->getContents(), true);
        // Here you have to handle possible errors from response

        return $data['data'];
    }

    /**
     * @param string $invoiceId
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getInvoice(string $invoiceId) : array
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            "/v1/invoices/{$invoiceId}",
            [RequestOptions::HEADERS => ['token' => $this->accessToken]]
        );

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    public function createTransaction(
        string $invoiceId,
        string $checkStatusUrl
    )
    {

    }

    /**
     * @param \App\Entity\Order $order
     *
     * @return string
     */
    private function signature(Order $order) : string
    {
        $params = [
            'id' => $order->getId(),
            'amount' => number_format($order->getProduct()->getPrice(), 3, '.', ''),
            'currency' => $order->getProduct()->getCurrency(),
        ];
        ksort($params, SORT_STRING);
        $params = array_values($params);
        $params[] = $this->secretKey;

        return hash('sha256', implode(':', $params));
    }
}
