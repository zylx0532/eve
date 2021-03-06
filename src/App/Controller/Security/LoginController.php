<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller\Security;

use App\Security\OAuth2Client;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController
{
    private $engine;
    private $oauth2Client;
    private $scopes;

    public function __construct(EngineInterface $engine, OAuth2Client $oauth2Client, array $scopes)
    {
        $this->engine = $engine;
        $this->oauth2Client = $oauth2Client;
        $this->scopes = $scopes;
    }

    public function __invoke(Request $request): Response
    {
        $authorizationUrl = $this->oauth2Client->getAuthorizationUrl(['scope' => $this->scopes]);
        $this->oauth2Client->setStateInSession($request->getSession());

        return new Response($this->engine->render('login.html.twig', [
            'authorizationUrl' => $authorizationUrl,
        ]), 200);
    }
}
