<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App;

/**
 * Identifier is a citadel (or any other player made object).
 */
function is_citadel(int $identifier): bool
{
    return $identifier > 1020000000000;
}

/**
 * Identifier is a station (either npc station or null-sec outpost).
 */
function is_station(int $identifier): bool
{
    return is_npc_station($identifier)
        || is_outpost($identifier);
}

/**
 * Identifier is an npc station.
 */
function is_npc_station(int $identifier): bool
{
    return $identifier > 60000000
        && $identifier < 61000000;
}

/**
 * Identifier is an outpost (can be player owned).
 */
function is_outpost(int $identifier): bool
{
    return $identifier > 61000000
        && $identifier < 62000000;
}

/**
 * Identifier is k-space.
 */
function is_kspace(int $identifier): bool
{
    return is_kspace_region($identifier)
        || is_kspace_constellation($identifier)
        || is_kspace_system($identifier);
}

/**
 * Identifier is w-space.
 */
function is_wspace(int $identifier): bool
{
    return is_wspace_region($identifier)
        || is_wspace_constellation($identifier)
        || is_wspace_system($identifier);
}

/**
 * Identifier is a k-space region.
 */
function is_kspace_region(int $identifier): bool
{
    return $identifier > 10000000
        && $identifier < 11000000;
}

/**
 * Identifier is jove space.
 */
function is_jove_region(int $identifier): bool
{
    return in_array(
        $identifier,
        [
            10000004,
            10000017,
            10000019,
        ],
        true
    );
}

/**
 * Identifier is a w-space region.
 */
function is_wspace_region(int $identifier): bool
{
    return $identifier > 11000000
        && $identifier < 12000000;
}

/**
 * Identifier is shattered w-space region.
 */
function is_shattered_region(int $identifier): bool
{
    return $identifier === 11000032;
}

/**
 * Identifier is drifter w-space region.
 */
function is_drifter_region(int $identifier): bool
{
    return $identifier === 11000033;
}

/**
 * Identifier is a k-space constellation.
 */
function is_kspace_constellation(int $identifier): bool
{
    return $identifier > 20000000
        && $identifier < 21000000;
}

/**
 * Identifier is a w-space constellation.
 */
function is_wspace_constellation(int $identifier): bool
{
    return $identifier > 21000000
        && $identifier < 22000000;
}

/**
 * Identifier is Thera w-space constellation.
 */
function is_thera_constellation(int $identifier): bool
{
    return $identifier === 21000324;
}

/**
 * Identifier is drifter w-space constellation.
 */
function is_drifter_constellation(int $identifier): bool
{
    return $identifier === 21000334;
}

/**
 * Identifier is a k-space system.
 */
function is_kspace_system(int $identifier): bool
{
    return $identifier > 30000000
        && $identifier < 31000000;
}

/**
 * Identifier is a w-space system.
 */
function is_wspace_system(int $identifier): bool
{
    return $identifier > 31000000
        && $identifier < 32000000;
}

/**
 * Identifier is an abyssal space system.
 */
function is_abyssal_system(int $identifier): bool
{
    return $identifier > 32000000
        && $identifier < 33000000;
}

/**
 * Identifier is a drifter w-space system.
 */
function is_drifter_system(int $identifier): bool
{
    return in_array(
        $identifier,
        [
            31000001,
            31000002,
            31000003,
            31000004,
            31000006,
        ],
        true
    );
}

/**
 * Identifier is Thera w-space system.
 */
function is_thera_system(int $identifier): bool
{
    return $identifier === 31000005;
}

/**
 * Identifier is a planet, moon, asteroid/ice belt or other celestial.
 */
function is_celestial(int $identifier): bool
{
    return $identifier > 40000000
        && $identifier < 41000000;
}

/**
 * Identifier is a stargate.
 */
function is_stargate(int $identifier): bool
{
    return $identifier > 50000000
        && $identifier < 51000000;
}

/**
 * Merge arrays recursively.
 *
 * Overwrites non-array values instead of converting them to an array like `array_merge_recursive` does.
 */
function array_merge_recursive_overwrite(array $array, array ...$arrays): array
{
    $merged = $array;

    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_merge_recursive_overwrite($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
    }

    return $merged;
}
