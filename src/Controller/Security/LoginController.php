<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller\Security;

use Alcohol\OAuth2\Client\Provider\EveOnline;
use App\Security\OAuth2Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @Route(service="app.controller.security.login")
 */
class LoginController
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $engine;
    /**
     * @var \Alcohol\OAuth2\Client\Provider\EveOnline
     */
    private $provider;
    /**
     * @var array
     */
    private $scopes;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $engine
     * @param \Alcohol\OAuth2\Client\Provider\EveOnline $provider
     * @param array $scopes
     */
    public function __construct(EngineInterface $engine, EveOnline $provider, array $scopes = [])
    {
        $this->engine = $engine;
        $this->provider = $provider;
        $this->scopes = $scopes;
    }

    /**
     * @Route("/security/login", name="security.login")
     * @Method({"GET"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request): Response
    {
        $session = $request->getSession();

        if (!($session instanceof SessionInterface)) {
            throw new \RuntimeException('No session available, please check server configuration.');
        }

        $authorizationUrl = $this->provider->getAuthorizationUrl(['scope' => $this->scopes]);

        $session->set(OAuth2Client::SESSION_STATE, $this->provider->getState());

        return new Response($this->engine->render('login.html.twig', [
            'authorizationUrl' => $authorizationUrl,
        ]), 200);
    }
}
