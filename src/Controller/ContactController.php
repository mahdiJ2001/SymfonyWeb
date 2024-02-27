<?php

namespace App\Controller;

use App\Entity\ContactVisitor;
use App\Form\ContactVisitorType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact_form")
     */
    public function contactForm(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contactVisitor = new ContactVisitor();
        $form = $this->createForm(ContactVisitorType::class, $contactVisitor);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contactVisitor);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été envoyé avec succès.');

            return $this->redirectToRoute('home');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
