<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OAuth2Subscriber implements EventSubscriberInterface
{
    private $oauth2Client;
    private $logger;
    private $router;
    private $tokenStorage;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        OAuth2Client $oauth2Client,
        RouterInterface $router,
        ?LoggerInterface $logger = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->oauth2Client = $oauth2Client;
        $this->router = $router;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return ['kernel.request' => 'refreshToken'];
    }

    public function refreshToken(GetResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getPathInfo() === $this->router->generate('security.login')) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!($token instanceof TokenInterface)) {
            return;
        }

        $user = $token->getUser();

        if (!($user instanceof User)) {
            return;
        }

        $currentToken = $user->getAccessToken();

        if (!($currentToken instanceof AccessToken)) {
            return;
        }

        if (!$currentToken->hasExpired()) {
            return;
        }

        $newToken = $this->oauth2Client->refreshToken($currentToken);

        if (null !== $this->logger) {
            $this->logger->info('OAuth2: access token has been refreshed.', [
                'old_access_token' => $currentToken,
                'new_access_token' => $newToken,
            ]);
        }

        $user->setAccessToken($newToken);
    }
}
