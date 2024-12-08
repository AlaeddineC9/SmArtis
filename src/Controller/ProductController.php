<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SousCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{   
    #[Route('/produit/{category_slug}/{souscategory_slug}/{slug}', name: 'app_product')]
    public function index(
        $category_slug,
        $souscategory_slug,
        $slug,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SousCategoryRepository $sousCategoryRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Récupérer la catégorie
        $category = $categoryRepository->findOneBySlug($category_slug);
        if (!$category) {
            return $this->redirectToRoute('app_home');
        }

        // Vérifier si c'est un produit sans sous-catégorie
        if ($souscategory_slug === 'aucune') {
            $product = $productRepository->findOneBy([
                'slug' => $slug,
                'category' => $category,
                'sousCategory' => null
            ]);
        } else {
            // Produit avec sous-catégorie
            $sousCategory = $sousCategoryRepository->findOneBySlug($souscategory_slug);
            if (!$sousCategory || $sousCategory->getCategory() !== $category) {
                return $this->redirectToRoute('app_home');
            }

            $product = $productRepository->findOneBy([
                'slug' => $slug,
                'category' => $category,
                'sousCategory' => $sousCategory
            ]);
        }

        if (!$product) {
            return $this->redirectToRoute('app_home');
        }

        // Gestion du formulaire d'avis
        $review = new Review();
        $reviewForm = $this->createForm(ReviewType::class, $review);
        $reviewForm->handleRequest($request);

        if ($reviewForm->isSubmitted() && $reviewForm->isValid()) {
            $review->setProduct($product);
            if ($this->getUser()) {
                $review->setUser($this->getUser());
            }

            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
            return $this->redirectToRoute('app_product', [
                'category_slug' => $category_slug,
                'souscategory_slug' => $souscategory_slug,
                'slug' => $slug
            ]);
        }

        return $this->render('product/index.html.twig', [
            'product' => $product,
            'reviewForm' => $reviewForm->createView()
        ]);
    }
}
