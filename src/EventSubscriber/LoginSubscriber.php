<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getAuthenticatedToken()->getUser();

        // Block if email not verified yet
        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Please verify your email before logging in.'
            );
        }

        // Block if admin disabled the account
        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException(
                'Your account has been disabled. Contact an administrator.'
            );
        }

        // Stamp lastLogin
        $user->setLastLogin(new \DateTime());
        $this->em->flush();
    }
}