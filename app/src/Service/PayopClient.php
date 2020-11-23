<?php

namespace App\Service;

use App\Entity\Order;
use GuzzleHttp\{ClientInterface, RequestOptions};
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\{CacheInterface, ItemInterface};

/**
 * Class PayopClient
 * @package App\Service
 */
class PayopClient
{
    /**
     * Client.
     *
     * @var ClientInterface $client
     */
    private $client;

    /**
     * Project identifier.
     *
     * @var string $projectIdentifier
     */
    private $projectIdentifier;

    /**
     * Payop app public key.
     *
     * @var string $publicKey
     */
    private $publicKey;

    /**
     * Payop app secret key.
     *
     * @var string $secretKey
     */
    private $secretKey;

    /**
     * Authentication access token.
     *
     * @var string $accessToken
     */
    private $accessToken;

    /**
     * Cache pool.
     *
     * @var CacheInterface $cachePool
     */
    private $cachePool;

    /**
     * @param ClientInterface $client
     * @param string $projectIdentifier
     * @param string $publicKey
     * @param string $secretKey
     * @param string $accessToken
     * @param CacheInterface $cachePool
     */
    public function __construct(
        ClientInterface $client,
        string $projectIdentifier,
        string $publicKey,
        string $secretKey,
        string $accessToken,
        CacheInterface $cachePool
    ) {
        $this->client = $client;
        $this->projectIdentifier = $projectIdentifier;
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
        $this->accessToken = $accessToken;
        $this->cachePool = $cachePool;
    }

    /**
     * Get merchant payment methods.
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getPaymentMethods() : array
    {
        return $this->cachePool->get('paymentMethods', function(ItemInterface $item) {
            $item->expiresAt((new \DateTimeImmutable())->modify('+15 min'));
            $response = $this->client->request(
                Request::METHOD_GET,
                sprintf(
                    'instrument-settings/payment-methods/available-for-application/%s',
                    $this->projectIdentifier
                ),
                [RequestOptions::HEADERS => ['token' => $this->accessToken]]
            );
            $data = json_decode($response->getBody()->getContents(), true)['data'];
            $methods = [];

            foreach ($data as $value) {
                $methods[] = $value['paymentMethod'];
            }

            return $methods;
        });
    }

    /**
     * @param Order $order
     * @param array $payer
     * @param $resultUrl
     * @param $failUrl
     * @param string|null $pmId
     *
     * @return string
     * @throws GuzzleException
     */
    public function createInvoice(
        Order $order,
        array $payer,
        $resultUrl,
        $failUrl,
        ?string $pmId
    ) : string {
        // "payer" is a structure with a specific set of fields
        // such as: email, name, phone, extraFields.
        // "email" always required. Other fields depends on selected payment method.
        // To avoid rigid binding to the structure,
        // which does not give the entire possible list of fields to save all possible data
        // we can use "extraFields" field to save payer extra fields.
        $payer['extraFields'] = $payer;

        $params = [
            'publicKey' => $this->publicKey,
            'order' => [
                'id' => $order->getId(),
                // just example. You can pass decimals: 2|3|4
                'amount' => number_format($order->getProduct()->getPrice(), 3, '.', ''),
                'currency' => $order->getProduct()->getCurrency(),
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
     *
     * @throws InvalidArgumentException
     * @throws GuzzleException
     */
    public function getInvoice(string $invoiceId) : array
    {
        $invoice = $this->cachePool->get("invoice_{$invoiceId}", function(ItemInterface $item) use ($invoiceId) {
            $item->expiresAt((new \DateTimeImmutable())->modify('+5 min'));

            $response = $this->client->request(
                Request::METHOD_GET,
                "invoices/{$invoiceId}",
                [RequestOptions::HEADERS => ['token' => $this->accessToken]]
            );

            return json_decode($response->getBody()->getContents(), true)['data'];
        });

        return $invoice;
    }

    /**
     * @param string $invoiceId
     * @param string $checkStatusUrl
     * @param array $customer
     * @param string|null $cardToken
     *
     * @return array
     * @throws GuzzleException
     */
    public function createTransaction(
        string $invoiceId,
        string $checkStatusUrl,
        array $customer,
        ?string $cardToken
    ) : array {
        $params = [
            'invoiceIdentifier' => $invoiceId,
            'customer' => $customer,
            'cardToken' => $cardToken,
            'checkStatusUrl' => $checkStatusUrl,
        ];

        $response = $this->client->request(
            Request::METHOD_POST,
            'checkout/create',
            [RequestOptions::JSON => $params]
        );
        $data = json_decode($response->getBody()->getContents(), true);

        // Here you have to handle possible errors from response

        return $data['data'];
    }

    /**
     * @param string $invoiceId
     * @param array $card
     *
     * @return array
     * @throws GuzzleException
     */
    public function createCardToken(string $invoiceId, array $card) : array
    {
        $params = [
            'invoiceIdentifier' => $invoiceId,
            'pan' => $card['pan'],
            'expirationDate' => $card['expirationDate'],
            'cvv' => $card['cvv'],
            'holderName' => $card['holderName'],
        ];

        $response = $this->client->request(
            Request::METHOD_POST,
            'payment-tools/card-token/create',
            [RequestOptions::JSON => $params]
        );
        $data = json_decode($response->getBody()->getContents(), true);

        // Here you have to handle possible errors from response

        return $data['data'];
    }

    /**
     * @param string $txid
     *
     * @return array
     * @throws GuzzleException
     */
    public function checkTransactionStatus(string $txid) : array
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            "checkout/check-transaction-status/{$txid}"
        );

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    /**
     * @param string $txid
     *
     * @return array
     * @throws GuzzleException
     */
    public function getTransaction(string $txid) : array
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            "transactions/{$txid}",
            [RequestOptions::HEADERS => ['token' => $this->accessToken]]
        );

        return json_decode($response->getBody()->getContents(), true)['data'];
    }

    /**
     * @param Order $order
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
