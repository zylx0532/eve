<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller\Skills;

use App\Esi\ApiClient;
use App\Esi\Endpoint\Characters\Skills;
use App\Esi\Endpoint\Universe\Category;
use App\Esi\Endpoint\Universe\Group;
use App\Esi\Endpoint\Universe\Type;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(service="controller.skills.trained")
 */
class TrainedController
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
     * @var \Http\Client\HttpAsyncClient
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
     * @Route("/skills/trained", name="skills.trained")
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
        $request = $this->apiClient->createRequest(new Skills(['character_id' => $user->getId()]), $options);
        $skills = $this->httpClient->sendAsyncRequest($request);
        $parameters = $this->handleSkillsResponse($skills->wait());

        $tree = [];

        $request = $this->apiClient->createRequest(new Category(['category_id' => 16]));
        $promise = $this->httpClient->sendAsyncRequest($request);

        $promise->then(function (ResponseInterface $response) use (&$tree) {
            $this->buildTree($response, $tree);
        })->wait(false);

        usort($tree, function ($left, $right) {
            return strcasecmp($left['name'], $right['name']);
        });

        $parameters['tree'] = $tree;

        $response = new Response($this->engine->render('skills/trained.html.twig', $parameters), 200);

        return $response;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function handleSkillsResponse(ResponseInterface $response): array
    {
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $skills = array_column($data['skills'], null, 'skill_id');

        list($trained, $untrained) = array_reduce($skills, function ($carry, $skill) {
            if (0 < $skill['current_skill_level']) {
                ++$carry[0];
            } else {
                ++$carry[1];
            }

            return $carry;
        }, [0 => 0, 1 => 0]);

        return [
            'skillpoints' => $data['total_sp'],
            'skills' => $skills,
            'trained' => $trained,
            'untrained' => $untrained,
        ];
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $tree
     */
    protected function buildTree(ResponseInterface $response, array &$tree)
    {
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $groups = $data['groups'];

        $promises = [];
        foreach ($groups as $id) {
            $request = $this->apiClient->createRequest(new Group(['group_id' => $id]));
            $promises[] = $this->httpClient->sendAsyncRequest($request);
        }

        /** @var Promise $promise */
        foreach ($promises as $promise) {
            $promise->then(function (ResponseInterface $response) use (&$tree) {
                $this->addGroupToTree($response, $tree);
            })->wait(false);
        }
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $tree
     */
    protected function addGroupToTree(ResponseInterface $response, array &$tree)
    {
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $types = $data['types'];

        $tree[$data['group_id']] = $data;

        $promises = [];
        foreach ($types as $id) {
            $request = $this->apiClient->createRequest(new Type(['type_id' => $id]));
            $promises[] = $this->httpClient->sendAsyncRequest($request);
        }

        /** @var Promise $promise */
        foreach ($promises as $promise) {
            $promise->then(function (ResponseInterface $response) use (&$tree, $data) {
                if (!array_key_exists('_children', $tree[$data['group_id']])) {
                    $tree[$data['group_id']]['_children'] = [];
                }

                $tree[$data['group_id']]['_children'][] = json_decode($response->getBody()->getContents(), true);
            })->wait(false);
        }
    }
}
