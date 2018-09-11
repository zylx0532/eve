<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Esi\ApiClient;
use App\Esi\Endpoint\Characters\Assets;
use App\Esi\Endpoint\Universe\Names;
use App\Esi\Endpoint\Universe\Station;
use App\Esi\Endpoint\Universe\Structure;
use Http\Client\Exception\HttpException;
use Http\Client\HttpClient;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use function App\is_citadel;
use function App\is_station;

class AssetsController
{
    private $engine;
    private $apiClient;
    private $httpClient;

    public function __construct(EngineInterface $engine, ApiClient $apiClient, HttpClient $httpClient)
    {
        $this->engine = $engine;
        $this->apiClient = $apiClient;
        $this->httpClient = $httpClient;
    }

    public function __invoke(Request $request, UserInterface $user): Response
    {
        /** @var \App\Entity\User $user */
        $options = ['headers' => ['Authorization' => 'Bearer ' . $user->getAccessToken()]];
        $placeholders = ['character_id' => $user->getId()];
        $assetsRequest = $this->apiClient->createRequest(new Assets($placeholders), $options);
        $response = $this->httpClient->sendRequest($assetsRequest);
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $assets = array_column($data, null, 'item_id');

        // group all assets by location_id
        $grouped = $this->groupByLocation($assets);

        // lookup location data (only for locations that could be a station/citadel/etc, exclude ships)
        $locationFlags = array_column($data, 'location_flag', 'location_id');
        $locations = $this->getLocationsDetails(
            $options,
            array_keys(array_filter(
                $locationFlags,
                static function (string $flag): bool {
                    return in_array(
                        $flag,
                        [
                            'FleetHangar',
                            'Hangar',
                            'HangarAll',
                            'ShipHangar',
                            'Deliveries',
                            'AssetSafety',
                        ],
                        true
                    );
                }
            ))
        );

        $namesRequest = $this->apiClient->createRequest(new Names(), array_merge($options, [
            'body' => \GuzzleHttp\Psr7\stream_for(
                json_encode(array_values(array_unique(array_merge(
                    array_column($assets, 'type_id'),
                    array_column($locations, 'type_id')
                ))))
            ),
        ]));
        $response = $this->httpClient->sendRequest($namesRequest);
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $names = array_column($data, null, 'id');

        $tree = $this->asTree($assets);

        $response = new Response($this->engine->render('pilot/assets.html.twig', [
            'locations' => $locations,
            'assets' => $assets,
            'grouped' => $grouped,
            'tree' => $tree,
            'names' => $names,
        ]), 200);

        return $response;
    }

    private function getStationDetails(array $options, int $id): array
    {
        $request = $this->apiClient->createRequest(new Station(['station_id' => $id]), $options);
        $response = $this->httpClient->sendRequest($request);
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);

        return $data;
    }

    private function getStructureDetails(array $options, int $id): array
    {
        $request = $this->apiClient->createRequest(new Structure(['structure_id' => $id]), $options);
        $response = $this->httpClient->sendRequest($request);
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);

        return $data;
    }

    private function getLocationsDetails(array $options, $identifiers): array
    {
        $locations = [];

        foreach ($identifiers as $id) {
            if (is_station($id)) {
                try {
                    $locations[$id] = $this->getStationDetails($options, $id);
                    continue;
                } catch (HttpException $exception) {
                }
            }

            if (is_citadel($id)) {
                try {
                    $locations[$id] = $this->getStructureDetails($options, $id);
                    continue;
                } catch (HttpException $exception) {
                }
            }

            $locations[$id] = null;
        }

        return $locations;
    }

    private function groupByLocation(array $assets): array
    {
        $groupedByLocation = array_reduce($assets, function ($carry, $asset) {
            $carry[$asset['location_id']][] = $asset;

            return $carry;
        }, []);

        return $groupedByLocation;
    }

    private function asTree(array $assets): array
    {
        $itemIdentifiers = array_column($assets, 'item_id');

        $locationIdentifiers = array_values(array_unique(array_filter(
            array_column($assets, 'location_id'),
            static function ($id) use ($itemIdentifiers) {
                return !in_array($id, $itemIdentifiers);
            }
        )));

        list(/* $flat */, $tree) = array_reduce($assets, static function ($carry, $asset) use ($locationIdentifiers) {
            if (!array_key_exists($asset['item_id'], $carry[0])) {
                $carry[0][$asset['item_id']] = [];
            }

            if (!array_key_exists($asset['location_id'], $carry[0])) {
                $carry[0][$asset['location_id']] = [];
            }

            if (!array_key_exists($asset['item_id'], $carry[0][$asset['location_id']])) {
                $carry[0][$asset['location_id']][$asset['item_id']] = &$carry[0][$asset['item_id']];
            }

            if (!in_array($asset['location_id'], $locationIdentifiers)) {
                return $carry;
            }

            if (!array_key_exists($asset['location_id'], $carry[1])) {
                $carry[1][$asset['location_id']] = &$carry[0][$asset['location_id']];
            }

            return $carry;
        }, [[], []]);

        return $tree;
    }
}
