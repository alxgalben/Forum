<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\CategoryModerator;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CategoryType;
use App\Form\EditCategoryType;
use App\Form\EditUserType;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class DashboardController extends AbstractController
{

    /**
     * @Route("/", name="main")
     */
    public function mainImpersonationList(UserRepository $userRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $users = $userRepository->findAll();

            return $this->render('dashboard/impersonation-list.html.twig', [
                'users' => $users
            ]);
        }

        return $this->render('user_management/profile.html.twig');
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboardHomepage(): Response
    {
        return $this->render('dashboard/homepage.html.twig');
    }

    /**
     * @Route("/dashboard/members", name="dashboard-members")
     */
    public function dashboardMembers(UserRepository $userRepository): Response
    {

        $userRole = 'ROLE_MEMBER';
        $usersData = $userRepository->findByRole($userRole);

        return $this->render('dashboard/dashboard-members.html.twig', [
            'users' => $usersData
        ]);
    }

    /**
     * @Route("/dashboard/moderators", name="dashboard-moderators")
     */
    public function dashboardModerators(UserRepository $userRepository): Response
    {

        $userRole = 'ROLE_MODERATOR';
        $usersData = $userRepository->findByRole($userRole);

        return $this->render('dashboard/dashboard-moderators.html.twig', [
            'users' => $usersData
        ]);
    }

    /**
     * @Route("/dashboard/categories", name="dashboard-categories")
     */
    public function dashboardCategories(CategoryRepository $categoryRepository): Response
    {

        $categoryData = $categoryRepository->findAll();

        return $this->render('dashboard/dashboard-categories.html.twig', [
            'categories' => $categoryData
        ]);
    }

    /**
     * @Route("dashboard/{action}/{id}", name="set-category-actions", requirements={"action"="delete|edit"})
     */
    public function markCategoryAction(Request $request, EntityManagerInterface $em, $id, $action): Response
    {
        $category = $em->getRepository(Category::class)->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        if ($action === 'delete') {
            $em->remove($category);
            $em->flush();
        } elseif ($action === 'edit') {
            $form = $this->createForm(EditCategoryType::class, $category);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($category);
                $em->flush();
            }

            return $this->render('dashboard/edit-category.html.twig', [
                'editForm' => $form->createView(),
                'client' => $category
            ]);
        } else {
            throw $this->createNotFoundException('Invalid action');
        }

        $em->flush();

        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/dashboard/category/add", name="category-add")
     */
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedModerators = $form->get('users')->getData();

            foreach ($selectedModerators as $selectedModerator) {
                if (in_array(User::queryRole, $selectedModerator->getRoles(), true)) {
                    $categoryModerator = new CategoryModerator();
                    $categoryModerator->setCategory($category);
                    $categoryModerator->setUser($selectedModerator);

                    $em->persist($categoryModerator);
                }
            }

            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('dashboard-categories');
        }

        return $this->render('dashboard/add-category.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("dashboard/{user}/{id}/{action}", name="set-user-actions", requirements={"user"="members|moderators", "action"="activate|deactivate|delete|edit"})
     */
    public function markUserAction(Request $request, EntityManagerInterface $em, $user, $id, $action): Response
    {
        $userEntity = $em->getRepository(User::class)->find($id);

        if (!$userEntity) {
            throw $this->createNotFoundException('User not found');
        }

        if ($action === 'activate') {
            $userEntity->setActive(true);
        } elseif ($action === 'deactivate') {
            $userEntity->setActive(false);
        } elseif ($action === 'delete') {
            $em->remove($userEntity);
            $em->flush();

            if ($user === 'members') {
                return $this->redirectToRoute('dashboard-members');
            } elseif ($user === 'moderators') {
                return $this->redirectToRoute('dashboard-moderators');
            }
        } elseif ($action === 'edit') {
            $form = $this->createForm(EditUserType::class, $userEntity);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($userEntity);
                $em->flush();

                if ($user === 'members') {
                    return $this->redirectToRoute('dashboard-members');
                } elseif ($user === 'moderators') {
                    return $this->redirectToRoute('dashboard-moderators');
                }
            }

            return $this->render('dashboard/edit-user.html.twig', [
                'editForm' => $form->createView(),
                'user' => $userEntity
            ]);
        } else {
            throw $this->createNotFoundException('Invalid action');
        }

        $em->flush();

        if ($user === 'members') {
            return $this->redirectToRoute('dashboard-members');
        }

        return $this->redirectToRoute('dashboard');
    }


}
