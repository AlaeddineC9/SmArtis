<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PresentationController extends AbstractController
{
     /**
     * Étape 1 : Choix de l’adresse et du transporteur (2 moins chers) et création de la commande
     */
    #[Route('/a-propos', name: 'app_about_us')]    
    public function index(): Response
    {
        return $this->render('presentation/index.html.twig', [
            // Vous pouvez passer des variables au template ici, par exemple :
            // 'variable' => $value,
        ]);
    }
}
