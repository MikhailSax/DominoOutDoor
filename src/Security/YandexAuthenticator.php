<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Aego\OAuth2\Client\Provider\YandexResourceOwner;

class YandexAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): bool
    {
        // Продолжаем только если маршрут совпадает с callback'ом Яндекса
        return $request->attributes->get('_route') === 'connect_yandex_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('yandex');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var YandexResourceOwner $yandexUser */
                $yandexUser = $client->fetchUserFromToken($accessToken);

                // Получаем основные данные пользователя
                $yandexId = $yandexUser->getId();
                $userData = $yandexUser->toArray();
                $email = $yandexUser->getEmail() ?? ($userData['default_email'] ?? null);
                $name = $userData['first_name'] ?? 'Yandex User';
                $lastName = $userData['last_name'] ?? null;
                $phone = $userData['default_phone'] ?? null;
                $password = '1234';
                // Проверяем, существует ли пользователь с таким Яндекс ID
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['yandexId' => $yandexId]);

                if ($existingUser) {
                    return $existingUser;
                }

                // Если не найден — создаём нового пользователя
                $user = new User();
                $user->setYandexId($yandexId);
                $user->setEmail($email);
                $user->setFirstName($name);
                $user->setLastName($lastName);
                $user->setPassword($password);
                $user->setPhone($phone);


                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Перенаправляем на главную после успешной авторизации
        return new RedirectResponse($this->router->generate('profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // В случае ошибки возвращаем на страницу логина
        $message = 'Ошибка авторизации через Яндекс: ' . $exception->getMessage();
        // Можно добавить логирование или flash-сообщение

        return new RedirectResponse($this->router->generate('login'));
    }
}
