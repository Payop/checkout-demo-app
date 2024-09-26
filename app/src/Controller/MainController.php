<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\{OrderRepository, ProductRepository};
use App\Service\PayopClient;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class MainController
 * @package App\Controller
 */
class MainController extends AbstractController
{
    #[Route(path: "/", name: "homepage")]
    public function homepage(ProductRepository $pr) : Response
    {
        $products = $pr->findAll();

        return $this->render('homepage.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route(path: "/order/create/{productId}", name: "create-order")]
    public function createOrder(
        string $productId,
        ProductRepository $pr,
        EntityManagerInterface $em
    ) : Response {
        $order = new Order();
        $order->setProduct($pr->find($productId));
        $em->persist($order);
        $em->flush();

        return $this->redirectToRoute('order-chpm', ['orderId' => $order->getId()]);
    }

    #[Route(path: "/order/{orderId}/chpm/", name: "order-chpm")]
    public function choosePaymentMethod(
        Request $request,
        string $orderId,
        OrderRepository $or,
        PayopClient $client,
        EntityManagerInterface $em
    ) : Response {
        $order = $or->find($orderId);
        $paymentMethods = $client->getPaymentMethods();

        if ($request->isMethod(Request::METHOD_POST) && $request->request->has('pm')) {
            $pmId = $request->request->get('pm');
            $fields = $request->request->all()['fields'] ?? [];

            $resultUrl = $this->generateUrl('order-result', [
                'id' => $orderId,
                'state' => 'succeeded',
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $resultUrl = "{$resultUrl}?invoiceId={{invoiceId}}&txid={{txid}}";
            $failUrl = $this->generateUrl('order-result', [
                'id' => $orderId,
                'state' => 'failed',
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $failUrl = "{$failUrl}?invoiceId={{invoiceId}}&txid={{txid}}";

            try {
                $invoiceId = $client->createInvoice(
                    $order,
                    $fields,
                    $resultUrl,
                    $failUrl,
                    $pmId
                );
                $order->setPayopInvoiceId($invoiceId);
                $em->persist($order);
                $em->flush();
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('order-chpm', ['orderId' => $orderId]);
            }

            return $this->redirectToRoute('before-payment-transaction', ['orderId' => $orderId]);
        }

        return $this->render('order-chpm.html.twig', [
            'order' => $order,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    #[Route(path: "/order/{orderId}/before-payment-transaction", name: "before-payment-transaction")]
    public function beforeCreatePaymentTransaction(
        Request $request,
        string $orderId,
        OrderRepository $or,
        EntityManagerInterface $em,
        PayopClient $client
    ) : Response {
        $order = $or->find($orderId);
        // while we have created invoice with selected payment method, we can decide what to do next.
        // There available a two cases, depends on selected payment method.
        // 1. if paymentMethod.formType is "cards" - create transaction request require Card Token. So next step is - card form
        // 2. if paymentMethod.formType is not "cards" - we can make request to create transaction.
        $invoice = $client->getInvoice($order->getPayopInvoiceId());

        if ($invoice['paymentMethod']['formType'] === 'cards') {
            return $this->redirectToRoute('bank-card', ['orderId' => $orderId]);
        }

        // create transaction
        $this->createTransaction($request, $or, $em, $client, $orderId, null);

        return $this->redirectToRoute('transaction-status', ['orderId' => $orderId]);
    }

    #[Route(path: "/order/{orderId}/bank-card", name: "bank-card")]
    public function cardForm(
        Request $request,
        OrderRepository $or,
        PayopClient $client,
        EntityManagerInterface $em,
        string $orderId
    ) {
        $order = $or->find($orderId);

        if ($request->isMethod(Request::METHOD_POST)) {
            $tokenData = $client->createCardToken(
                $order->getPayopInvoiceId(),
                $request->request->all()
            );
            $this->createTransaction($request, $or, $em, $client, $orderId, $tokenData['token']);

            return $this->redirectToRoute('transaction-status', ['orderId' => $orderId]);
        }

        return $this->render('card-form.html.twig', [
            // Test card - it's not real
            'pan' => '5555555555554444',
            'expirationDate' => '12/20',
            'cvv' => '123',
            'holderName' => 'Makar Makarenko',
        ]);
    }

    #[Route(path: "/order/{orderId}/tx-status", name: "transaction-status")]
    public function checkTransactionStatus(
        string $orderId,
        OrderRepository $or,
        PayopClient $client
    ) : Response {
        $order = $or->find($orderId);
        $statusResponse = $client->checkTransactionStatus($order->getPayopTxid());
        if (!empty($statusResponse['form'])) {
            if ($statusResponse['form']['method'] === Request::METHOD_GET) {
                return $this->redirect($statusResponse['form']['url']);
            }

            return $this->render('status-post.html.twig', ['form' => $statusResponse['form']]);
        }

        if ($statusResponse['status'] === 'pending' && empty($statusResponse['url'])) {
            sleep(10);
            return $this->redirectToRoute('transaction-status', ['orderId' => $orderId]);
        }

        if (in_array($statusResponse['status'], ['success', 'fail'])) {
            return $this->redirect($statusResponse['url']);
        }

        throw new \RuntimeException('Ooops ...');

        // Based on above response we can choose several ways what to do next:
        // !!! Important: The list is sorted by importance of checks.
        // 1. $statusResponse['form'] is not empty - redirect user (GET/POST) to $statusResponse['form']['url'].
        //    Usually POST request - this is payer bank 3DS page.
        //    So you have to send form with enctype='application/x-www-form-urlencoded' attribute
        //    and this request should be InBrowser (Normal POST request: https://stackoverflow.com/a/15262442/2090853)
        //    $statusResponse['form'] can has next structure:
        //    ['url' => url where make request, 'method' => 'http method GET|POST', 'fields' => [array with formFieldName => formFieldValue]]
        //      Example for GET: ['url' => 'https://pay.skrill.com/app/?sid=9345093478', 'method' => 'GET', 'fields' => []]
        //      Example for POST:
        //          [
        //              'url' => 'https://acs.anybank.com/',
        //              'method' => 'POST',
        //             'fields' => ['PaReq' => 'fmn3o8usfjlils', 'MD' => '8ec777d6-685d-4e06-b356-d7673acb47ba', 'TermUrl' => 'https://payop.com/v1/url']
        //          ]
        // 2. $statusResponse['status'] is "pending" - repeat transaction status request after 5-10 seconds.
        // 3. $statusResponse['status'] is "success" - redirect to $statusResponse['url']
        // 4. $statusResponse['status'] is "fail" - redirect to $statusResponse['url']
        // 5. Exceptional case. Something went wrong on the Payop side. Contact our support.
    }

    #[Route(path: "/order/{id}/result/{state}", name: "order-result")]
    public function resultPage(
        string $id,
        string $state,
        OrderRepository $or
    ) : Response {
        // We don't have to change anything here,
        // because everyone can send this request
        $order = $or->find($id);

        return $this->render('order-result.html.twig', [
            'order' => $order,
            'state' => $state,
        ]);
    }

    #[Route(path: "/ipn", name: "ipn")]
    public function ipn(
        Request $request,
        PayopClient $client,
        OrderRepository $or,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) : Response {
        $data = json_decode($request->getContent(), true);
        $logger->info('IPN Request', [
            'request' => $data,
            'method' => $request->getMethod(),
        ]);

        $tx = $client->getTransaction($data['invoice']['txid']);
        $order = $or->find($tx['orderId']);

        if ($tx['state'] === 2) {
            $order->setStatus(Order::STATUS_ACCEPTED);
            $em->persist($order);
            $em->flush();
            return new Response('', Response::HTTP_OK);
        }

        if (in_array($tx['state'], [3, 5], false)) {
            $order->setStatus(Order::STATUS_FAILED);
            $em->persist($order);
            $em->flush();
            return new Response('', Response::HTTP_OK);
        }

        return new Response('', Response::HTTP_PRECONDITION_FAILED);
    }

    /**
     * @param Request $request
     * @param OrderRepository $or
     * @param EntityManagerInterface $em
     * @param PayopClient $client
     * @param string $orderId
     * @param string|null $cardToken
     *
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    private function createTransaction(
        Request $request,
        OrderRepository $or,
        EntityManagerInterface $em,
        PayopClient $client,
        string $orderId,
        ?string $cardToken
    ) : void {
        $order = $or->find($orderId);
        // While we have invoice we can get data for checkout from this invoice
        // or we can make page where payer can provide all this data.
        // Here we using first case.
        $invoice = $client->getInvoice($order->getPayopInvoiceId());
        $customer = $invoice['payer']['extraFields'];
        $customer['email'] = $invoice['payer']['email'];
        // IP is required field for payer.
        // It's detected automatically on the Payop side, or we can pass it manually
        // But in case of using integration Server-To-Server you have to pass it.
        $customer['ip'] = $request->getClientIp();
        $checkStatusUrl = $this->generateUrl(
            'transaction-status',
            ['orderId' => $orderId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $transaction = $client->createTransaction(
            $invoice['identifier'],
            $checkStatusUrl,
            $customer,
            $cardToken
        );

        $order->setPayopTxid($transaction['txid']);
        $em->persist($order);
        $em->flush();
    }
}
