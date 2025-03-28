<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\Carrier;
use App\Entity\OrderDetail;
use App\Form\OrderType;
use App\Service\SendcloudService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/commande/livraison', name: 'app_order')]
public function index(Cart $cart, SendcloudService $sendcloudService, EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $addresses = $user->getAddresses();
    if (count($addresses) === 0) {
        return $this->redirectToRoute('app_account_address_form');
    }

    // Calcul du poids total du panier
    $totalWeightKg = 0.0;
    foreach ($cart->getCart() as $item) {
        $p = $item['object'];
        $qty = $item['qty'];
        $totalWeightKg += ($p->getWeight() ?? 0.0) * $qty;
    }
    $totalWeightKg = max(0.1, $totalWeightKg);

    // Récupération des méthodes d'expédition
    $carriers = [];
    $addressObj = $addresses[0];
    try {
        $recipientParams = [
            'to_country_code' => $addressObj->getCountry(),
            'to_postal_code'  => $addressObj->getPostal(),
            'weight' => [
                'value' => (string)number_format($totalWeightKg, 3, '.', ''),
                'unit' => 'kg'
            ],
            'from_country_code' => 'FR',
            'from_postal_code' => '75001',
        ];
        
        $shippingOptions = $sendcloudService->fetchShippingOptionsForRecipient($recipientParams);
        
        foreach ($shippingOptions['data'] ?? [] as $option) {
            foreach ($option['quotes'] ?? [] as $quote) {
                // Vérifie si le transporteur existe déjà
                $existingCarrier = $em->getRepository(Carrier::class)->findOneBy([
                    'codeTransporter' => $option['code']
                ]);
                
                if (!$existingCarrier) {
                    $c = new Carrier();
                    $c->setCodeTransporter((string)$option['code']);
                    $c->setName($option['product']['name'] ?? $option['code']);
                    $c->setPrice((float)($quote['price']['total']['value'] ?? 0));
                    $c->setDescription($option['product']['description'] ?? 'Méthode d\'expédition');
                    $em->persist($c);
                    $carriers[] = $c;
                } else {
                    $carriers[] = $existingCarrier;
                }
            }
        }
        $em->flush();
    } catch (\Exception $e) {
        $this->addFlash('danger', "Erreur transporteurs: ".$e->getMessage());
    }

    $form = $this->createForm(OrderType::class, null, [
        'addresses' => $addresses,
        'carriers' => $carriers,
        'action'    => $this->generateUrl('app_order_process'),
            'method'    => 'POST'
    ]);

    return $this->render('order/index.html.twig', [
        'deliverForm' => $form->createView(),
    ]);
}

#[Route('/commande/recapitulatif', name: 'app_order_process', methods: ['POST'])]
public function process(
    Request $request,
    Cart $cart,
    EntityManagerInterface $em,
    SendcloudService $sendcloudService
): Response {
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $products = $cart->getCart();
        if (!$products || count($products) === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

    $addresses = $user->getAddresses();
    if (count($addresses) === 0) {
        return $this->redirectToRoute('app_account_address_form');
    }

    // Récupérer les transporteurs persistés
    $carriers = $em->getRepository(Carrier::class)->findAll();
    
    $form = $this->createForm(OrderType::class, null, [
        'addresses' => $addresses,
        'carriers' => $carriers,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if (!$form->isValid()) {
            // Logger les erreurs détaillées
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('danger', 'Erreur de validation : ' . implode(', ', $errors));
            return $this->redirectToRoute('app_order');
        }

        // Récupération des données
        $chosenAddress = $form->get('addresses')->getData();
        $chosenCarrier = $form->get('carriers')->getData();

        // Création de la commande
        $order = new Order();
        $order->setUser($user);
        $order->setCreatedAt(new \DateTime());
        $order->setState(1);

        // Formatage de l'adresse
        $addressString = sprintf(
            "%s %s<br/>%s<br/>%s %s<br/>%s<br/>%s",
            $chosenAddress->getFirstname(),
            $chosenAddress->getLastname(),
            $chosenAddress->getAddress(),
            $chosenAddress->getPostal(),
            $chosenAddress->getCity(),
            $chosenAddress->getCountry(),
            $chosenAddress->getPhone()
        );
        $order->setDelivery($addressString);
        $order->setCarrierName($chosenCarrier->getName());
        $order->setCarrierPrice($chosenCarrier->getPrice());

        // Ajout des produits
        $totalWeightKg = 0.0;
        foreach ($cart->getCart() as $item) {
            $product = $item['object'];
            $quantity = $item['qty'];
            
            $orderDetail = new OrderDetail();
            $orderDetail->setProductName($product->getName());
            $orderDetail->setProductIllustration($product->getIllustration());
            $orderDetail->setProductPrice($product->getPrice() / (1 + $product->getTva()/100));
            $orderDetail->setProductTva($product->getTva());
            $orderDetail->setProductQuantity($quantity);
            
            $order->addOrderDetail($orderDetail);
            $totalWeightKg += $product->getWeight() * $quantity;
        }

        // Persistance
        $em->persist($order);
        $em->flush();

        // Création du colis Sendcloud
        try {
            $totalWeightKg = 0.0;
            foreach ($cart->getCart() as $item) {
                $totalWeightKg += ($item['object']->getWeight() ?? 0.0) * $item['qty'];
            }
            $parcelData = [
                "name" => $chosenAddress->getFullName(),
                "address" => $chosenAddress->getAddress(),
                "postal_code" => $chosenAddress->getPostal(),
                "city" => $chosenAddress->getCity(),
                "country" => $chosenAddress->getCountry(),
                "telephone" => $chosenAddress->getPhone(),
                "email" => $user->getEmail(),
                "weight" => [
                    'value' => number_format($totalWeightKg, 3, '.', ''),

                    'unit' => 'kg'
                ],
                "request_label" => true,
                "shipping_method" => [
                    "id" => (int)$chosenCarrier->getCodeTransporter(),
                    "name" => $chosenCarrier->getName(),
                ]
            ];

            $result = $sendcloudService->createParcel($parcelData);
            if ($parcel = $result['parcel'] ?? null) {
                $order->setShippingReference((string)$parcel['id']);
                if (!empty($parcel['label_url'])) {
                    $order->setShippingLabelUrl($parcel['label_url']);
                }
                $em->flush();
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', "Erreur d'expédition : " . $e->getMessage());
        }

        return $this->redirectToRoute('app_order_summary', ['id' => $order->getId()]);
    }

    return $this->redirectToRoute('app_order');
}
    #[Route('/commande/recapitulatif/{id}', name: 'app_order_summary')]
    public function summary(int $id, EntityManagerInterface $entityManager, Cart $cart): Response
    {
        $order = $entityManager->getRepository(Order::class)->find($id);
        if (!$order) {
            $this->addFlash('warning', "Commande introuvable.");
            return $this->redirectToRoute('app_cart');
        }
        if ($order->getUser() !== $this->getUser()) {
            $this->addFlash('danger', "Vous n'avez pas accès à cette commande.");
            return $this->redirectToRoute('app_cart');
        }

        $totalProducts = 0.0;
        foreach ($order->getOrderDetails() as $detail) {
            $ht = $detail->getProductPrice();
            $tva = $detail->getProductTva();
            $qty = $detail->getProductQuantity();
            $priceTtc = $ht * (1 + $tva / 100);
            $totalProducts += $priceTtc * $qty;
        }
        $totalTtc = $totalProducts + $order->getCarrierPrice();

        return $this->render('order/summary.html.twig', [
            'order' => $order,
            'totalWt' => $totalTtc,
        ]);
    }
}