<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\SousCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/categorie/{slug}', name: 'app_category')]
    public function index(
        $slug,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        SousCategoryRepository $sousCategoryRepository
    ): Response {
        $category = $categoryRepository->findOneBySlug($slug);
        if (!$category) {
            return $this->redirectToRoute('app_home');
        }

        // Produits sans sous-catégorie
        $products = $productRepository->findBy([
            'category' => $category,
            'sousCategory' => null,
        ]);

        $sousCategories = $category->getSousCategories();

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'products' => $products,
            'sousCategories' => $sousCategories,
        ]);
    }

    #[Route('/souscategorie/{slug}', name: 'app_souscategory')]
    public function souscategory(
        $slug,
        SousCategoryRepository $sousCategoryRepository,
        ProductRepository $productRepository
    ): Response {
        $sousCategory = $sousCategoryRepository->findOneBySlug($slug);
        if (!$sousCategory) {
            return $this->redirectToRoute('app_home');
        }

        $products = $productRepository->findBy(['sousCategory' => $sousCategory]);

        return $this->render('souscategory/index.html.twig', [
            'sousCategory' => $sousCategory,
            'products' => $products,
        ]);
    }

    public function navbar(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('partials/_navbar.html.twig', [
            'categories' => $categories,
        ]);
    }
}
