<?php

namespace App\Controller;

use App\Entity\ContactInfo;
use App\Entity\ContactMessage;
use App\Form\ContactMessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // 1. Récupération de l'entité ContactInfo (id=1 par ex.)
        $contact = $entityManager->getRepository(ContactInfo::class)->find(1);

        // 2. Création du formulaire de message
        $contactMessage = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $contactMessage);
        $form->handleRequest($request);

        // 3. Traitement du formulaire si soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $timezone = $this->getParameter('app_timezone');
            $contactMessage->setCreatedAt(new \DateTime('now', new \DateTimeZone($timezone)));

            $entityManager->persist($contactMessage);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été envoyé avec succès !');

            return $this->redirectToRoute('app_contact');
        }

        // 4. Rendu du template
        return $this->render('contact/index.html.twig', [
            'contact' => $contact,
            'form'    => $form->createView(),
        ]);
    }
}
