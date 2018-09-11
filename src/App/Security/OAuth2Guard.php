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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class OAuth2Guard implements AuthenticatorInterface
{
    private $router;
    private $oauth2Client;

    public function __construct(RouterInterface $router, OAuth2Client $oauth2Client)
    {
        $this->router = $router;
        $this->oauth2Client = $oauth2Client;
    }

    public function supports(Request $request): bool
    {
        if ($request->getPathInfo() !== $this->router->generate('security.check')) {
            return false;
        }

        return true;
    }

    public function getCredentials(Request $request): AccessToken
    {
        $accessToken = $this->oauth2Client->verifyToken($request);

        return $accessToken;
    }

    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        $resource = $this->oauth2Client->fetchUserForToken($credentials);

        $user = new User($resource->toArray());
        $user->setAccessToken($credentials);

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $credentials instanceof AccessToken && false === $credentials->hasExpired();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?RedirectResponse
    {
        if ($request->getPathInfo() !== $this->router->generate('security.check')) {
            return null;
        }

        $targetPath = $request->getSession()->get(
            '_security.' . $providerKey . '.target_path',
            $this->router->generate('index')
        );

        return new RedirectResponse($targetPath);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('security.login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('security.login'));
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public function createAuthenticatedToken(UserInterface $user, $providerKey): PostAuthenticationGuardToken
    {
        $token = new PostAuthenticationGuardToken(
            $user,
            $providerKey,
            $user->getRoles()
        );

        return $token;
    }
}
