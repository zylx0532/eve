<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Alcohol\OAuth2\Client\Provider\EveOnline;
use Alcohol\OAuth2\Client\Provider\EveOnlineResourceOwner;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuth2Client
{
    public const SESSION_STATE = '_guard.oauth2.state';
    private $provider;

    public function __construct(EveOnline $provider)
    {
        $this->provider = $provider;
    }

    public function getAuthorizationUrl(array $options = []): string
    {
        $authorizationUrl = $this->provider->getAuthorizationUrl($options);

        return $authorizationUrl;
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function verifyToken(Request $request): AccessToken
    {
        if (!$request->query->has('code')) {
            throw new AuthenticationException('OAuth2: request does not contain "code" parameter.');
        }

        if (!$request->query->has('state')) {
            throw new AuthenticationException('OAuth2: request does not contain "state" parameter.');
        }

        $session = $request->getSession();
        $state = $session->get(self::SESSION_STATE);
        $session->remove(self::SESSION_STATE);

        if ($state !== $request->query->get('state')) {
            throw new AuthenticationException('OAuth2: invalid state.');
        }

        return $this->provider->getAccessToken('authorization_code', ['code' => $request->query->get('code')]);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function refreshToken(AccessToken $existingAccessToken): AccessToken
    {
        try {
            $newAccessToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $existingAccessToken->getRefreshToken(),
            ]);

            return $newAccessToken;
        } catch (\Exception $e) {
            throw new AuthenticationException('OAuth2: could not refresh access token.', 0, $e);
        }
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function fetchUserForToken(AccessToken $accessToken): EveOnlineResourceOwner
    {
        try {
            $resource = $this->provider->getResourceOwner($accessToken);
        } catch (IdentityProviderException $exception) {
            throw new AuthenticationException('OAuth2: unable to retrieve access token resource owner.', 0, $exception);
        }

        return $resource;
    }

    public function setStateInSession(SessionInterface $session): void
    {
        $session->set(self::SESSION_STATE, $this->provider->getState());
    }
}
