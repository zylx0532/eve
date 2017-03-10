<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller\Skills;

use App\Esi\Endpoint\Characters\SkillQueue;
use App\Esi\Endpoint\Universe\Names;
use App\Esi\ApiClient;
use Http\Client\HttpClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(service="app.controller.skills.queued")
 */
class QueuedController
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $engine;
    /**
     * @var \App\Esi\ApiClient
     */
    private $api;
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $engine
     * @param \App\Esi\ApiClient $api
     * @param \Http\Client\HttpClient $httpClient
     */
    public function __construct(EngineInterface $engine, ApiClient $api, HttpClient $httpClient)
    {
        $this->engine = $engine;
        $this->api = $api;
        $this->httpClient = $httpClient;
    }

    /**
     * @Route("/skills/queued", name="skills.queued")
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
        $request = $this->api->createRequest(new SkillQueue(['character_id' => $user->getId()]), $options);
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            $response = new Response($this->engine->render('skills/queued.html.twig', [
                'queue' => [],
                'names' => [],
            ]), 200);

            return $response;
        }

        $json = $response->getBody()->getContents();
        $queue = json_decode($json, true);

        usort($queue, function ($left, $right) {
            if ($left['queue_position'] === $right['queue_position']) {
                throw new \RuntimeException('Two skills cannot occupy the same queue position.');
            }

            return $left['queue_position'] < $right['queue_position'] ? -1 : 1;
        });

        $identifiers = array_unique(array_column($queue, 'skill_id'));

        $request = $this->api->createRequest(new Names(), array_merge($options, [
            'body' => \GuzzleHttp\Psr7\stream_for(json_encode(array_values($identifiers)))
        ]));
        $response = $this->httpClient->sendRequest($request);
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $names = array_column($data, null, 'id');

        $response = new Response($this->engine->render('skills/queued.html.twig', [
            'queue' => $queue,
            'names' => $names,
        ]), 200);

        return $response;
    }
}
