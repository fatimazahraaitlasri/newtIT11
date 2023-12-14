<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

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
 * @Security("is_granted('ROLE_USER')")
 */
public function new(Request $request, PostRepository $postRepository): Response
{
    $post = new Post();
    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Assurez-vous que le post est lié à l'utilisateur connecté
        $post->setUser($this->getUser());

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


  /**
 * @Route("/{id}/edit", name="app_post_edit", methods={"GET", "POST"})
 * @Security("is_granted('POST_EDIT', post)")
 */
public function edit(Request $request, Post $post, PostRepository $postRepository): Response
{
    $this->denyAccessUnlessGranted('edit', $post);

    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Assurez-vous que l'édition est effectuée par le propriétaire du post
        $this->denyAccessUnlessGranted('edit', $post);

        $postRepository->add($post, true);

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->renderForm('post/show.html.twig', [
        'post' => $post,
        'form' => $form, 
    ]);
}

/**
 * @Route("/{id}", name="app_post_delete", methods={"POST"})
 * @Security("is_granted('POST_DELETE', post)")
 */
public function delete( Request $request, Post $post, PostRepository $postRepository, FlashBagInterface $flashBag ): Response
{
    $this->denyAccessUnlessGranted('delete', $post);


    if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
        try {
    $this->entityManager->remove($post);
    $this->entityManager->flush();

    $flashBag->add('success', 'Your post has been deleted successfully.');
    } catch (\Exception $e) {
    dd($e->getMessage()); // ou dd($e->getMessage());
    $flashBag->add('error', 'An error occurred while deleting the post.');
    }

    }

    return $this->redirectToRoute('app_user_profile', [], Response::HTTP_SEE_OTHER);
    
}



}
