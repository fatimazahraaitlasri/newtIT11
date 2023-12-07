<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use  DateTimeImmutable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("post/{postId}/comment")
 */
class CommentController extends AbstractController
{
    /**
     * @Route("/", name="app_comment_index", methods={"GET"})
     */
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_comment_new", methods={"GET", "POST"})
     * @ParamConverter("post", class="App\Entity\Post", options={"id" = "postId"})
     */
    public function new(Request $request, Post $post , CommentRepository $commentRepository): Response
    {
        $comment = new Comment();
        $comment -> setCreatedAt(new DateTimeImmutable()); 
        $comment->setUser($this->getUser());
        $comment->setPost($post);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->add($comment, true);

            return $this->redirectToRoute('app_post_show', [
                'id' => $post->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_comment_show", methods={"GET"})
     */
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }


    







    /**
     * @Route("/{id}/edit", name="app_comment_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Comment $comment, CommentRepository $commentRepository): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->add($comment, true);

            return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_comment_delete", methods={"POST"})
     */
    public function delete(Request $request, Comment $comment, CommentRepository $commentRepository): Response
    {   
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $commentRepository->remove($comment, true);
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    } 

}
