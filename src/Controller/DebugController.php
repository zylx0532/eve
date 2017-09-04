<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use Alcohol\OAuth2\Client\Provider\EveOnline;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(service="controller.debug")
 */
class DebugController
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $engine;
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $storage;
    /**
     * @var \Alcohol\OAuth2\Client\Provider\EveOnline
     */
    private $provider;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $engine
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $storage
     * @param \Alcohol\OAuth2\Client\Provider\EveOnline $provider
     */
    public function __construct(EngineInterface $engine, TokenStorageInterface $storage, EveOnline $provider)
    {
        $this->engine = $engine;
        $this->storage = $storage;
        $this->provider = $provider;
    }

    /**
     * @Route("/debug", name="debug")
     * @Method({"GET"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request, UserInterface $user): Response
    {
        $response = new Response($this->engine->render('debug.html.twig', [
            'debug' => [
                'user' => $user,
                'token' => $this->storage->getToken(),
            ],
        ]), 200);

        return $response;
    }
}
