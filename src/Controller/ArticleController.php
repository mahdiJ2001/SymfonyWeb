<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Form\CommentaireType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class ArticleController extends AbstractController
{
    /**
     * @Route("/article", name="article")
     */
    public function index(ArticleRepository $repo): Response
    {
        $articles = $repo->findAll();

        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'articles' => $articles,
        ]);
    }

    /**
     * @Route("/article/create", name="create_article")
     * @Route("/article/{id}/edit", name="edit_article")
     */
    public function form(Article $article = null, Request $request, EntityManagerInterface $manager): Response
    {
        // Check if the user is authenticated
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('Vous devez être connecté pour créer un article.');
        }

        if (!$article && $request->get('id')) {
            $article = $manager->getRepository(Article::class)->find($request->get('id'));
        }

        if (!$article) {
            $article = new Article();
        }


        $comments = $article->getCommentaires();

        $commentaire = new Commentaire();
        $commentForm = $this->createForm(CommentaireType::class, $commentaire);

        $commentForm->handleRequest($request);
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $commentaire->setArticle($article);
            $manager->persist($commentaire);
            $manager->flush();


            $commentForm = $this->createForm(CommentaireType::class, new Commentaire());
        }


        $form = $this->createFormBuilder($article)
            ->add('title')
            ->add('content')
            ->add('image')
            ->add('category')
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if (!$article->getId()) {
                $article->setCreatedAt(new \DateTime());
            }

            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/create.html.twig', [
            'formArticle' => $form->createView(),
            'editMode' => $article->getId() !== null,
            'article' => $article,
            'comments' => $comments,
            'commentForm' => $commentForm->createView(),
        ]);
    }

    /**
     * @Route("/article/{id}", name="article_show")
     */
    public function show(ArticleRepository $repo, $id, Request $request, EntityManagerInterface $manager, Security $security): Response
    {
        $article = $repo->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        if (!$security->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('Vous devez être connecté pour ajouter un commentaire.');
        }


        $comments = $article->getCommentaires();


        $commentaire = new Commentaire();
        $commentForm = $this->createForm(CommentaireType::class, $commentaire);


        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $commentaire->setArticle($article);
            $manager->persist($commentaire);
            $manager->flush();


            $commentForm = $this->createForm(CommentaireType::class, new Commentaire());
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'comments' => $comments,
            'commentForm' => $commentForm->createView(),
        ]);
    }
}
