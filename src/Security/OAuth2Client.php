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
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class OAuth2Client
{
    /**
     * @var string key under which the state will be stored in session
     */
    public const SESSION_STATE = '_guard.oauth2.state';
    /**
     * @var \Alcohol\OAuth2\Client\Provider\EveOnline
     */
    private $provider;

    /**
     * @param \Alcohol\OAuth2\Client\Provider\EveOnline $provider
     */
    public function __construct(EveOnline $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     *
     * @return \League\OAuth2\Client\Token\AccessToken
     */
    public function verifyToken(Request $request)
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
     * @param \League\OAuth2\Client\Token\AccessToken $existingAccessToken
     *
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     *
     * @return \League\OAuth2\Client\Token\AccessToken
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
     * @param \League\OAuth2\Client\Token\AccessToken $accessToken
     *
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     *
     * @return \Alcohol\OAuth2\Client\Provider\EveOnlineResourceOwner
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
}
