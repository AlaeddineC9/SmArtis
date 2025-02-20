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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    /**
     * Étape 1 : Affiche le formulaire d'adresse et de transporteur (2 moins chers)
     */
    #[Route('/commande/livraison', name: 'app_order')]
    public function index(
        Request $request,
        SendcloudService $sendcloudService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $addresses = $user->getAddresses();
        if (count($addresses) === 0) {
            return $this->redirectToRoute('app_account_address_form');
        }
        
        // On prend la première adresse pour paramétrer l'API Sendcloud
        $addressObj = $addresses[0];
        $recipientParams = [
            'to_country_code' => $addressObj->getCountry(),
            'to_postal_code'  => $addressObj->getPostal(),
        ];
        $additionalParams = [
            'functionalities' => ['b2c' => true],
            'weight' => [
                'value' => '1.5', // par exemple 1.5 kg
                'unit'  => 'kg'
            ]
        ];
        
        $carriers = [];
        try {
            // Récupération des options d'expédition depuis Sendcloud
            $shippingOptions = $sendcloudService->fetchShippingOptionsForRecipient(
                $recipientParams,
                $additionalParams
            );
            if (!empty($shippingOptions['data'])) {
                $tempList = [];
                foreach ($shippingOptions['data'] as $option) {
                    $code = $option['code'] ?? '???';
                    $productName = $option['product']['name'] ?? $code;
                    if (!empty($option['quotes'])) {
                        foreach ($option['quotes'] as $quote) {
                            $val = $quote['price']['total']['value'] ?? null;
                            if ($val && (float)$val > 0) {
                                $tempList[] = [
                                    'code'  => $code,
                                    'name'  => $productName,
                                    'price' => (float)$val
                                ];
                            }
                        }
                    }
                }
                usort($tempList, fn($a, $b) => $a['price'] <=> $b['price']);
                $tempList = array_slice($tempList, 0, 2);
                
                foreach ($tempList as $item) {
                    $c = new Carrier();
                    // On s'assure que le code n'est pas "???"
                    if ($item['code'] === '???') {
                        continue;
                    }
                    $c->setCodeTransporter($item['code']);
                    $c->setName($item['name']);
                    $c->setPrice($item['price']);
                    $c->setDescription('Option depuis Sendcloud');
                    $carriers[] = $c;
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', "Impossible de récupérer les transporteurs : " . $e->getMessage());
        }
        
        // Création du formulaire OrderType avec adresses et carriers
        $form = $this->createForm(OrderType::class, null, [
            'addresses' => $addresses,
            'carriers'  => $carriers,
            'action'    => $this->generateUrl('app_order_process'),
            'method'    => 'POST'
        ]);
        
        return $this->render('order/index.html.twig', [
            'deliverForm' => $form->createView(),
        ]);
    }
    
    /**
     * Étape 2 : Traitement du formulaire, création de la commande et génération de l'étiquette.
     * Route accessible en POST uniquement.
     */
    #[Route('/commande/recapitulatif', name: 'app_order_process', methods: ['POST'])]
    public function process(
        Request $request,
        Cart $cart,
        EntityManagerInterface $entityManager,
        SendcloudService $sendcloudService,
        MailerInterface $mailer
    ): Response {
        if ($request->getMethod() !== 'POST') {
            $this->addFlash('warning', 'Vous avez été redirigé vers le panier.');
            return $this->redirectToRoute('app_cart');
        }
        
        $products = $cart->getCart();
        if (!$products || count($products) === 0) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }
        
        // On recrée le formulaire pour obtenir les données POSTées
        $form = $this->createForm(OrderType::class, null, [
            'addresses' => $this->getUser()->getAddresses(),
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Création de l'objet Order
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setCreatedAt(new \DateTime());
            $order->setState(1);
            
            // Récupération des données du formulaire
            $chosenCarrier = $form->get('carriers')->getData();
            $addressObj = $form->get('addresses')->getData();
            
            // Constitution de la chaîne d'adresse
            $addressString = sprintf(
                "%s %s<br/>%s<br/>%s %s<br/>%s<br/>%s",
                $addressObj->getFirstname(),
                $addressObj->getLastname(),
                $addressObj->getAddress(),
                $addressObj->getPostal(),
                $addressObj->getCity(),
                $addressObj->getCountry(),
                $addressObj->getPhone()
            );
            $order->setDelivery($addressString);
            
            // Affectation du transporteur
            if ($chosenCarrier instanceof Carrier) {
                $order->setCarrierName($chosenCarrier->getName());
                $order->setCarrierPrice($chosenCarrier->getPrice());
            } else {
                $order->setCarrierName("Transporteur inconnu");
                $order->setCarrierPrice(0.0);
            }
            
            // Création des OrderDetail en tenant compte de la promotion
            $totalWeightKg = 0.0;
            foreach ($products as $item) {
                $product = $item['object'];
                $qty = $item['qty'];
                $totalWeightKg += ($product->getWeight() ?? 0.0) * $qty;
                
                // Utiliser le prix TTC promotionné (getPriceWithPromotion) si applicable
                $priceTtcRemise = $product->getPriceWithPromotion();
                $tvaRate = $product->getTva() ?? 0.0;
                // Calcul du prix HT : prix TTC / (1 + tva/100)
                $priceHtRemise = $priceTtcRemise / (1 + $tvaRate / 100);
                
                $orderDetail = new OrderDetail();
                $orderDetail->setProductName($product->getName());
                $orderDetail->setProductIllustration($product->getIllustration());
                $orderDetail->setProductPrice($priceHtRemise);
                $orderDetail->setProductTva($tvaRate);
                $orderDetail->setProductQuantity($qty);
                
                $order->addOrderDetail($orderDetail);
            }
            
            $entityManager->persist($order);
            $entityManager->flush(); // commande créée
            
            // Préparation des paramètres pour l'appel Sendcloud
            $recipientParams = [
                'to_country_code' => $addressObj->getCountry(),
                'to_postal_code'  => $addressObj->getPostal()
            ];
            $additionalParams = [
                'functionalities' => ['b2c' => true, 'b2b' => true],
                'weight' => [
                    'value' => (string)$totalWeightKg,
                    'unit'  => 'kg'
                ]
            ];
            
            try {
                $shippingOptions = $sendcloudService->fetchShippingOptionsForRecipient(
                    $recipientParams,
                    $additionalParams
                );
            } catch (\Exception $e) {
                $this->addFlash('danger', "Erreur lors de la récupération des options d'expédition : " . $e->getMessage());
                $shippingOptions = null;
            }
            
            // Sélection automatique du shipping_method le moins cher
            $chosenMethod = null;
            if (!empty($shippingOptions['data'])) {
                $validOptions = [];
                foreach ($shippingOptions['data'] as $option) {
                    if (!empty($option['quotes'])) {
                        foreach ($option['quotes'] as $quote) {
                            $val = $quote['price']['total']['value'] ?? null;
                            if ($val !== null) {
                                $validOptions[] = [
                                    'code'  => $option['code'],
                                    'price' => (float)$val
                                ];
                            }
                        }
                    }
                }
                if (!empty($validOptions)) {
                    usort($validOptions, fn($a, $b) => $a['price'] <=> $b['price']);
                    $chosenMethod = $validOptions[0]['code'];
                }
            }
            
            // Appel à Sendcloud pour générer l'étiquette.
            if ($chosenCarrier instanceof Carrier) {
                $parcelData = [
                    'name'           => $addressObj->getFirstname().' '.$addressObj->getLastname(),
                    'company_name'   => '',
                    'address'        => $addressObj->getAddress(),
                    'city'           => $addressObj->getCity(),
                    'postal_code'    => $addressObj->getPostal(),
                    'country'        => $addressObj->getCountry(),
                    'telephone'      => $addressObj->getPhone(),
                    'email'          => $this->getUser()->getEmail(),
                    'request_label'  => true,
                    // On utilise le shipping_method le moins cher s'il a été trouvé, sinon le code du carrier sélectionné
                    'shipping_method'=> $chosenMethod ?? $chosenCarrier->getCodeTransporter(),
                ];
                
                try {
                    $result = $sendcloudService->createParcel($parcelData);
                    $parcel = $result['parcel'] ?? null;
                    if ($parcel) {
                        $order->setShippingReference((string)($parcel['id'] ?? ''));
                        if (!empty($parcel['label_url'])) {
                            $order->setShippingLabelUrl($parcel['label_url']);
                            $this->addFlash('info', "Étiquette Sendcloud générée : " . $parcel['label_url']);
                            
                            // Optionnel : envoi par email de l'étiquette
                            $pdfContent = @file_get_contents($parcel['label_url']);
                            if ($pdfContent !== false) {
                                $email = (new Email())
                                    ->from('no-reply@tonsite.com')
                                    ->to('admin@tonsite.com')
                                    ->subject('Nouvelle étiquette d\'expédition - Commande #' . $order->getId())
                                    ->text('Veuillez trouver ci-joint l\'étiquette d\'expédition pour la commande #' . $order->getId())
                                    ->attach($pdfContent, 'etiquette_expedition.pdf', 'application/pdf');
                                $mailer->send($email);
                            } else {
                                $this->addFlash('warning', "L'étiquette n'a pas pu être téléchargée pour envoi par email.");
                            }
                        }
                        $entityManager->flush();
                    } else {
                        $this->addFlash('danger', "Réponse Sendcloud invalide : 'parcel' non trouvé.");
                    }
                } catch (\Exception $e) {
                    $this->addFlash('danger', "Erreur Sendcloud : " . $e->getMessage());
                }
            }
            
            return $this->redirectToRoute('app_order_summary', [
                'id' => $order->getId()
            ]);
        }
        
        return $this->redirectToRoute('app_cart');
    }
    
    #[Route('/commande/recapitulatif/{id}', name: 'app_order_summary')]
    public function summary(
        int $id,
        EntityManagerInterface $entityManager,
        Cart $cart
    ): Response {
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
            'order'   => $order,
            'totalWt' => $totalTtc,
        ]);
    }
}
