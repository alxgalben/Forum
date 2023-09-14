<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Form\ReplyType;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    /**
     * @Route("/homepage", name="homepage")
     */
    public function homepage(Request $request, PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAll();
        $form = $this->createForm(ReplyType::class);
        $form->handleRequest($request);

        return $this->render('homepage/homepage.html.twig', [
            'posts' => $posts,
            'form' => $form->createView()
        ]);
    }
}
