<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Esi\Endpoint\Characters\Character;
use App\Esi\Endpoint\Characters\Portrait;
use App\Esi\Endpoint\Characters\Skills;
use App\Esi\Endpoint\Characters\Wallets;
use App\Esi\Endpoint\Universe\Bloodlines;
use App\Esi\Endpoint\Universe\Races;
use App\Esi\ApiClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Message\ResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(service="app.controller.index")
 */
class IndexController
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $engine;
    /**
     * @var \App\Esi\ApiClient
     */
    private $apiClient;
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $engine
     * @param \App\Esi\ApiClient $apiClient
     * @param \Http\Client\HttpAsyncClient $httpClient
     */
    public function __construct(EngineInterface $engine, ApiClient $apiClient, HttpAsyncClient $httpClient)
    {
        $this->engine = $engine;
        $this->apiClient = $apiClient;
        $this->httpClient = $httpClient;
    }

    /**
     * @Route("/", name="index")
     * @Method({"GET"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request, UserInterface $user): Response
    {
        /** @var \App\Entity\User $user */
        $options = ['headers' => ['Authorization' => 'Bearer ' . $user->getAccessToken()]];
        $placeholders = ['character_id' => $user->getId()];

        $request = $this->apiClient->createRequest(new Character($placeholders), $options);
        $character = $this->httpClient->sendAsyncRequest($request);

        $request = $this->apiClient->createRequest(new Skills($placeholders), $options);
        $skills = $this->httpClient->sendAsyncRequest($request);

        $request = $this->apiClient->createRequest(new Portrait($placeholders), $options);
        $portrait = $this->httpClient->sendAsyncRequest($request);

        $request = $this->apiClient->createRequest(new Bloodlines());
        $bloodlines = $this->httpClient->sendAsyncRequest($request);

        $request = $this->apiClient->createRequest(new Races());
        $races = $this->httpClient->sendAsyncRequest($request);

        $request = $this->apiClient->createRequest(new Wallets($placeholders), $options);
        $wallets = $this->httpClient->sendAsyncRequest($request);

        /** @var ResponseInterface $response */

        $response = $character->wait();
        $character = json_decode($response->getBody()->getContents(), true);

        $response = $skills->wait();
        $skills = json_decode($response->getBody()->getContents(), true);

        $response = $portrait->wait();
        $portrait = json_decode($response->getBody()->getContents(), true);

        $response = $bloodlines->wait();
        $bloodlines = array_column(json_decode($response->getBody()->getContents(), true), null, 'bloodline_id');

        $response = $races->wait();
        $races = array_column(json_decode($response->getBody()->getContents(), true), null, 'race_id');

        $response = $wallets->wait();
        $wallets = array_column(json_decode($response->getBody()->getContents(), true), null, 'wallet_id');
        $isk = array_reduce($wallets, function ($carry, $wallet) {
            $carry += $wallet['balance'];

            return $carry;
        }, 0) / 100;

        $response = new Response($this->engine->render('pilot/overview.html.twig', [
            'character_details' => $character,
            'character_portrait' => $portrait,
            'bloodline' => $bloodlines[$character['bloodline_id']],
            'race' => $races[$character['race_id']],
            'wallets' => $wallets,
            'isk' => $isk,
            'sp' => $skills['total_sp'],
        ]), 200);

        return $response;
    }
}
