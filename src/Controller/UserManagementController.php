<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileType;
use App\Form\ForgotPasswordType;
use App\Form\RegistrationType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserManagementController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function userLogin(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('user_management/login.html.twig', [
            'lastUsername' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/dashboard/login", name="dashboard-login")
     */
    public function adminLogin(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('dashboard/login.html.twig', [
            'lastUsername' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function userLogout(AuthenticationUtils $authenticationUtils)
    {
    }

    /**
     * @Route("/dashboard/logout", name="dashboard-logout")
     */
    public function dashboardLogout(AuthenticationUtils $authenticationUtils)
    {
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function userProfile(UserRepository $userRepository): Response
    {
        return $this->render('user_management/profile.html.twig');
    }

    /**
     * @Route("/profile/edit", name="profile-edit")
     */
    public function userEditProfile(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(EditProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            if ($passwordEncoder->isPasswordValid($user, $currentPassword)) {
                $newPassword = $form->get('newPassword')->getData();
                $hashedNewPassword = $passwordEncoder->hashPassword($user, $newPassword);

                $user->setPassword($hashedNewPassword);

                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('profile');
            } else {
                $form->get('currentPassword')->addError(new FormError('Invalid current password!'));
            }
        }

        return $this->render('user_management/edit_profile.html.twig', [
            'editForm' => $form->createView()
        ]);
    }


    /**
     * @Route("/register", name="register")
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function userRegistration(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $selectedRole = $form->get('role')->getData();
            $user->setRole($selectedRole);
            $token = bin2hex(random_bytes(32));
            $user->setToken($token);

            $user->setCreatedAt(new DateTime());

            $entityManager->persist($user);
            $entityManager->flush();

            $signedUrl = $this->generateUrl('confirmed', [
                'token' => $token
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new TemplatedEmail())
                ->from('galbenalex2@gmail.com')
                ->to($user->getEmail())
                ->subject('Please, confirm your email!')
                ->htmlTemplate('user_management/sent_emails/registration_confirmation.html.twig')
                ->context([
                    'signedUrl' => $signedUrl
                ]);

            $mailer->send($email);
        }

        return $this->render('user_management/registration.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/confirmed", name="confirmed")
     */
    public function userConfirmed(): Response
    {
        return $this->render('user_management/user_confirmed.html.twig');
    }

    /**
     * @Route("/forgot-password", name="forgot-password")
     * @throws Exception|TransportExceptionInterface
     */
    public function userForgotPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $user = $userRepository->findOneBy(['email' => $formData->getEmail()]);

            if (!$user) {
                $this->addFlash('danger', 'This email is invalid.');
                return $this->redirectToRoute('forgot-password');
            }

            $token = bin2hex(random_bytes(32));
            try {
                $user->setToken($token);
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (Exception $exception) {
                $this->addFlash('warning', 'Error: ' . $exception->getMessage());
                return $this->redirectToRoute('login');
            }

            $url = $this->generateUrl('reset-password', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);

            $email = (new Email())
                ->from('noreply@4oom.com')
                ->to($user->getEmail())
                ->subject('Retrieve your password!')
                ->html($this->renderView(
                    'user_management/sent_emails/forgot_password.html.twig',
                    ['url' => $url]));

            $mailer->send($email);
        }

        return $this->render('user_management/forgot_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/reset-password/{token}", name="reset-password")
     */
    public function userResetPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, string $token): Response
    {

        $user = $entityManager->getRepository(User::class)->findOneBy(['token' => $token]);
        if (!$user) {
            $this->addFlash('danger', 'Password reset link or expired');
            return $this->redirectToRoute('forgot-password');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordEncoder->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($encodedPassword);
            $user->setToken(null);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been successfully reset.');

            return $this->redirectToRoute('login');
        }

        return $this->render('user_management/reset_password.html.twig', [
            'resetForm' => $form->createView()
        ]);
    }
}












































