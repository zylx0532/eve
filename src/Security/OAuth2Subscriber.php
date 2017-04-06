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
    /**
     * @var \App\Security\OAuth2Client
     */
    private $oauth2Client;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @param \App\Security\OAuth2Client $oauth2Client
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        OAuth2Client $oauth2Client,
        RouterInterface $router,
        LoggerInterface $logger = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->oauth2Client = $oauth2Client;
        $this->router = $router;
        $this->logger = $logger;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return ['kernel.request' => 'refreshToken'];
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function refreshToken(GetResponseEvent $event)
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
