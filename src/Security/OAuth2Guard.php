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
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class OAuth2Guard implements GuardAuthenticatorInterface
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;
    /**
     * @var \App\Security\OAuth2Client
     */
    private $oauth2Client;

    /**
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \App\Security\OAuth2Client $oauth2Client
     */
    public function __construct(RouterInterface $router, OAuth2Client $oauth2Client)
    {
        $this->router = $router;
        $this->oauth2Client = $oauth2Client;
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array). If you return null, authentication
     * will be skipped.
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \League\OAuth2\Client\Token\AccessToken|null
     */
    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() !== $this->router->generate('security.check')) {
            return null;
        }

        $accessToken = $this->oauth2Client->verifyToken($request);

        return $accessToken;
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param \Symfony\Component\Security\Core\User\UserProviderInterface $userProvider
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        $resource = $this->oauth2Client->fetchUserForToken($credentials);

        $user = new User($resource->toArray());
        $user->setAccessToken($credentials);

        return $user;
    }

    /**
     * Returns true if the credentials are valid.
     *
     * If any value other than true is returned, authentication will
     * fail. You may also throw an AuthenticationException if you wish
     * to cause authentication to fail.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * @param mixed $credentials
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $credentials instanceof AccessToken && false === $credentials->hasExpired();
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @param string $providerKey
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
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

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Exception\AuthenticationException $exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate('security.login'));
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *  A) For a form login, you might redirect to the login page
     *      return new RedirectResponse('/login');
     *  B) For an API token authentication system, you return a 401 response
     *      return new Response('Auth header required', 401);
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Exception\AuthenticationException|null $authException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('security.login'));
    }

    /**
     * Does this method support remember me cookies?
     *
     * Remember me cookie will be set if *all* of the following are met:
     *  A) This method returns true
     *  B) The remember_me key under your firewall is configured
     *  C) The "remember me" functionality is activated. This is usually
     *      done by having a _remember_me checkbox in your form, but
     *      can be configured by the "always_remember_me" and "remember_me_parameter"
     *      parameters under the "remember_me" firewall key
     *
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * Create an authenticated token for the given user.
     *
     * If you don't care about which token class is used or don't really
     * understand what a "token" is, you can skip this method by extending
     * the AbstractGuardAuthenticator class from your authenticator.
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @param string $providerKey
     *
     * @return \Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken
     */
    public function createAuthenticatedToken(UserInterface $user, $providerKey)
    {
        $token = new PostAuthenticationGuardToken(
            $user,
            $providerKey,
            $user->getRoles()
        );

        return $token;
    }
}
