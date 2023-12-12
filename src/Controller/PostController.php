<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use  DateTimeImmutable;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use entityManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Category;

/**
 * @Route("/post")
 */
class PostController extends AbstractController

{
    private $entityManager; 
   
    

    public function __construct(EntityManagerInterface $entityManager) // Ajoutez cette méthode
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/", name="app_post_index", methods={"GET"}) 
     */
    public function index(PostRepository $postRepository ,Request $request): Response
    {

        $categoryId = $request->query->get('category');
        $posts = $this->entityManager->getRepository(Post::class)->findByCategory($categoryId);
        $categories = $this->entityManager->getRepository(Category::class)->findAll(); 
        return $this->render('post/index.html.twig', [ 
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }

    /**
     * @Route("/new", name="app_post_new", methods={"GET", "POST"})
     */
    public function new(Request $request, PostRepository $postRepository): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postRepository->add($post, true);

            return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin_post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

/**
 * @Route("/{id}", name="app_post_show", methods={"GET", "POST"})
 */
public function show(Request $request, Post $post, CommentRepository $commentRepository, int $id): Response
{
    $comment = new Comment();
    $comment->setCreatedAt(new DateTimeImmutable());
    $comment->setUser($this->getUser());
    $comment->setPost($post);

    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $commentRepository->add($comment, true);

        $this->addFlash('success', 'Le commentaire a été ajouté avec succès.');
        return new RedirectResponse($this->generateUrl('app_post_show', ['id' => $post->getId()]));
        // Notez que la redirection a été supprimée ici.
    }

    $comments = $commentRepository->findCommentsByPostId($id);

    return $this->render('post/show.html.twig', [
        'post' => $post,
        'comments' => $comments,
        'form' => $form->createView(),
    ]);
}


  


}
