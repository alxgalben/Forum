<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryModerator;
use App\Entity\Like;
use App\Entity\Poll;
use App\Entity\PollAnswer;
use App\Entity\PollChoice;
use App\Entity\Post;
use App\Entity\Reply;
use App\Entity\User;
use App\Form\PollType;
use App\Form\PostType;
use App\Form\ReplyType;
use App\Repository\CategoryRepository;
use App\Repository\LikeRepository;
use App\Repository\PollRepository;
use App\Repository\PostRepository;
use App\Repository\ReplyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Date;

class PostController extends AbstractController
{
    /**
     * @Route("/create-post", name="create-post")
     */
    public function createPost(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file) {
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($filename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $post->setFile($newFilename);
                $file->move($this->getParameter('files_directory'), $newFilename);
            }

            $post->setDateCreated(new \DateTime());
            $post->setPublished(true);
            $post->setUser($user);

            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('profile');
        }

        return $this->render('post/create-post.html.twig', [
            'form' => $form->createView(),
            'post' => $post
        ]);
    }

    /**
     * @Route("/create-poll", name="create-poll")
     */
    public function createPoll(Request $request, EntityManagerInterface $em): Response
    {
        $poll = new Poll();
        $form = $this->createForm(PollType::class, $poll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parameters = $request->request->all();
            $data = $parameters['poll'];

            foreach ($data['choices'] as $choiceContent) {
                $choice = new PollChoice();
                $choice->setContent($choiceContent);
                $choice->setPoll($poll);
                $poll->addChoice($choice);
                $em->persist($choice);
            }

            $em->persist($poll);
            $em->flush();

            return $this->redirectToRoute('profile');
        }

        return $this->render('post/create-poll.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/respond-to-poll/{postId}", name="respond-to-poll", methods={"POST"})
     */
    public function respondToPoll(Request $request, EntityManagerInterface $em, $postId): Response
    {
        $selectedChoiceId = $request->request->get('selected_choice');
        $pollId = $request->request->get('poll_id');

        $selectedChoice = $em->getRepository(PollChoice::class)->find($selectedChoiceId);
        $poll = $em->getRepository(Poll::class)->find($pollId);

        $pollAnswer = new PollAnswer();
        $pollAnswer->setChoice($selectedChoice);
        $pollAnswer->setPoll($poll);
        $pollAnswer->setUser($this->getUser());

        $em->persist($pollAnswer);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/closed/{pollId}", name="closed-poll")
     */
    public function showClosedPoll($pollId, PollRepository $pollRepository): Response
    {
        $poll = $pollRepository->find($pollId); // Replace with your method to retrieve the poll

        $percentages = $pollRepository->calculateChoicePercentages($pollId);

        return $this->render('post/closed_poll.html.twig', [
            'poll' => $poll,
            'percentages' => $percentages,
        ]);
    }


    /**
     * @Route("/close-poll/{pollId}", name="close-poll")
     */
    public function closePoll(EntityManagerInterface $em, $pollId): Response
    {
        $poll = $em->getRepository(Poll::class)->find($pollId);
        //dd($poll);

        if ($poll) {
            $poll->setClosed(true);
            $em->flush();
        }

        return $this->redirectToRoute('poll', ['pollId' => $pollId]);
    }


    /**
     * @Route("/full-post/{id}", name="full-post")
     */
    public function fullPost(Request $request, PostRepository $postRepository, ReplyRepository $replyRepository, $id, SluggerInterface $slugger): Response
    {
        //dd($request->request->all());
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('This post does not exist.');
        }

        //$replies = $replyRepository->findRepliesByPostId($post->getId());

        $template = 'post/full-post.html.twig';
        if($post->isClosed()) {
            $template = 'post/full-post-without-input.html.twig';
        }

        $reply = new Reply();
        $form = $this->createForm(ReplyType::class, $reply);
        $form->handleRequest($request);

        //$template = ($closed === 'closed') ? 'post/full-post-without-input.html.twig' : 'post/full-post.html.twig';

        return $this->render($template, [
            'form' => $form->createView(),
            'post' => $post,
            'reply' => $reply,
            'replies' => $post->getReplies()
        ]);
    }

    /**
     * @Route("/reply/{id}/{action}", name="reply-action", requirements={"action"="edit|delete"})
     */
    public function replyAction(Request $request, EntityManagerInterface $em, $id, $action, SluggerInterface $slugger): Response
    {
        $replyEntity = $em->getRepository(Reply::class)->find($id);

        if (!$replyEntity) {
            throw $this->createNotFoundException('Reply not found');
        }

        if ($action === 'edit') {
            $oldFile = $replyEntity->getFile();
            $form = $this->createForm(ReplyType::class, $replyEntity);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $file = $form->get('file')->getData();

                if ($file) {
                    $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($filename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                    $replyEntity->setFile($newFilename);
                    $file->move($this->getParameter('files_directory'), $newFilename);
                } else {
                    $replyEntity->setFile($oldFile);
                }

                $replyEntity->setLastModified(new \DateTime());
                $em->persist($replyEntity);
                $em->flush();
                return $this->redirectToRoute('homepage');
            }

            return $this->render('post/edit-reply.html.twig', [
                'editForm' => $form->createView(),
            ]);
        } elseif ($action === 'delete') {
            $em->remove($replyEntity);
            $em->flush();
            return $this->redirectToRoute('homepage');
        }

        throw $this->createNotFoundException('Invalid action');
    }


    /**
     * @Route("/post/edit/{id}", name="edit-post")
     */
    public function editPost(Request $request, EntityManagerInterface $em, $id, PostRepository $postRepository, SluggerInterface $slugger): Response
    {
        $postEntity = $em->getRepository(Post::class)->find($id);

        if (!$postEntity) {
            throw $this->createNotFoundException('Post not found');
        }

        $currentUser = $this->getUser();

        if ($currentUser !== $postEntity->getUser()) {
            throw new AccessDeniedException('You are not authorized to edit this post.');
        }

        $oldFile = $postEntity->getFile();
        $form = $this->createForm(PostType::class, $postEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file) {
                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($filename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $postEntity->setFile($newFilename);
                $file->move($this->getParameter('files_directory'), $newFilename);
            } else {
                $postEntity->setFile($oldFile);
            }
            $postEntity->setDateUpdated(new \DateTime());
            $em->persist($postEntity);
            $em->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('post/edit-post.html.twig', [
            'editForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/post/delete/{id}", name="delete-post")
     */
    public function deletePost(EntityManagerInterface $em, $id): Response
    {
        $postEntity = $em->getRepository(Post::class)->find($id);

        if (!$postEntity) {
            throw $this->createNotFoundException('Post not found');
        }

        $currentUser = $this->getUser();

        if ($currentUser !== $postEntity->getUser()) {
            throw new AccessDeniedException('You are not authorized to delete this post.');
        }

        $em->remove($postEntity);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/post/like/{id}", name="like-post")
     */
    public function likePost(EntityManagerInterface $em, $id, PostRepository $postRepository): Response
    {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $currentUser = $this->getUser();

        if (!$post->isLikedByUser($currentUser)) {
            $like = new Like();
            $like->setPost($post);
            $like->setUser($currentUser);

            $em->persist($like);
            $em->flush();
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/post/{id}/{action}", name="post-action", requirements={"action"="close|unclose"})
     */
    public function closeUnclosePost(Request $request, EntityManagerInterface $em, $id, $action, PostRepository $postRepository): Response
    {
        $postEntity = $em->getRepository(Post::class)->find($id);

        if (!$postEntity) {
            throw $this->createNotFoundException('Post not found');
        }

        $currentUser = $this->getUser();

        if ($action === 'close' || $action === 'unclose') {
            if ($currentUser !== $postEntity->getUser() && !$currentUser->isModeratorForCategory($postEntity->getCategory())) {
                throw new AccessDeniedException('You are not authorized to close or unclose this post.');
            }

            $postEntity->setClosed($action === 'close');
            $em->persist($postEntity);
            $em->flush();
        } else {
            throw $this->createNotFoundException('Invalid action');
        }

        $posts = $postRepository->findAll();

        return $this->render('homepage/moderator-posts-to-close.html.twig', [
            'post' => $postEntity,
            'posts' => $posts
        ]);
    }


    /**
     * @Route("/post-comment/{id}", name="post-comment")
     * @throws Exception
     */
    public function postComment(Request $request, EntityManagerInterface $em, $id,SluggerInterface $slugger): Response
    {

        $comment = $request->request->get('comment');
        $post = $em->getRepository(Post::class)->find($id);
        $user = $this->getUser(); /// functie default

        if (!$post) {
            $response = [
                'success' => false,
                'message' => 'Post not found.'
            ];
            return $this->json($response, Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('file');
        $reply = new Reply();

        if ($file) {
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($filename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
            try {
                $file->move($this->getParameter('files_directory'), $newFilename);
            } catch (FileException $e) {
                dd($e->getMessage());
            }
            $reply->setFile($newFilename);

        }

        $reply->setBody($comment);
        $reply->setPost($post);
        $reply->setUser($user);
        $reply->setPostedOn(new \DateTime('now', new \DateTimeZone('UTC')));

        $em->persist($reply);
        $em->flush();

        $response = [
            'success' => true,
            'message' => 'Comment posted successfully.'
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/my-posts", name="my-posts")
     */
    public function myPosts(Request $request, PostRepository $postRepository): Response
    {
        $user = $this->getUser();
        $posts = $postRepository->findPostsByUser($user);

        return $this->render('post/my-posts.html.twig', [
            'posts' => $posts
        ]);
    }
}
