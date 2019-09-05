<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\PayopClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     *
     * @param \App\Repository\ProductRepository $pr
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function homepage(ProductRepository $pr) : Response
    {
        $products = $pr->findAll();

        return $this->render('homepage.html.twig', [
            'products' => $products,
        ]);
    }

    /**
     * @Route("/order/create/{productId}", name="create-order")
     *
     * @param string $productId
     * @param \App\Repository\ProductRepository $pr
     * @param \Doctrine\ORM\EntityManagerInterface $em
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
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

    /**
     * @Route("/order/{orderId}/chpm/", name="order-chpm")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $orderId
     * @param \App\Repository\OrderRepository $or
     * @param \App\Service\PayopClient $client
     * @param \Symfony\Contracts\Cache\CacheInterface $pool
     * @param \Doctrine\ORM\EntityManagerInterface $em
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function choosePaymentMethod(
        Request $request,
        string $orderId,
        OrderRepository $or,
        PayopClient $client,
        CacheInterface $pool,
        EntityManagerInterface $em
    ) : Response {
        $order = $or->find($orderId);

        $paymentMethods = $pool->get('paymentMethods', function(ItemInterface $item) use ($client) {
            $item->expiresAt((new \DateTimeImmutable())->modify('+5 min'));

            return $client->getPaymentMethods();
        });

        if ($request->isMethod(Request::METHOD_POST) && $request->request->has('pm')) {
            $pmId = $request->request->get('pm');
            $fields = $request->request->get('fields', []);
            // IP it's required field for payer.
            // It's detected automatically on the Payop side, or we can pass it manually
            $fields['ip'] = $request->getClientIp();

            $resultUrl = $this->generateUrl('order-result', [
                'id' => $orderId,
                'state' => 'succeeded',
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $failUrl = $this->generateUrl('order-result', [
                'id' => $orderId,
                'state' => 'failed',
            ], UrlGeneratorInterface::ABSOLUTE_URL);

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

    /**
     * @Route("/order/{orderId}/before-payment-transaction", name="before-payment-transaction")
     */
    public function beforeCreatePaymentTransaction(string $orderId) : Response
    {
        $t = 1;
    }

    /**
     * Just show result page
     *
     * @Route("/order/{id}/result/{state}", name="order-result")
     *
     * @param string $id
     * @param string $state
     * @param \App\Repository\OrderRepository $or
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
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
}
