<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Fn;

/**
 * Identifier is a citadel.
 *
 * @param $identifier
 * @return bool
 */
function is_citadel($identifier)
{
    return $identifier > 1020000000000;
}

/**
 * Identifier is a station (either npc station or null-sec outpost).
 *
 * @param $identifier
 * @return bool
 */
function is_station($identifier)
{
    return is_npc_station($identifier)
        || is_outpost($identifier);
}

/**
 * Identifier is an npc station.
 *
 * @param $identifier
 * @return bool
 */
function is_npc_station($identifier)
{
    return $identifier > 60000000
        && $identifier < 61000000;
}

/**
 * Identifier is an outpost (can be player owned).
 *
 * @param $identifier
 * @return bool
 */
function is_outpost($identifier)
{
    return $identifier > 61000000
        && $identifier < 62000000;
}

/**
 * Identifier is k-space.
 *
 * @param $identifier
 * @return bool
 */
function is_kspace($identifier)
{
    return is_kspace_region($identifier)
        || is_kspace_constellation($identifier)
        || is_kspace_system($identifier);
}

/**
 * Identifier is w-space.
 *
 * @param $identifier
 * @return bool
 */
function is_wspace($identifier)
{
    return is_wspace_region($identifier)
        || is_wspace_constellation($identifier)
        || is_wspace_system($identifier);
}

/**
 * Identifier is a k-space region.
 *
 * @param $identifier
 * @return bool
 */
function is_kspace_region($identifier)
{
    return $identifier > 10000000
        && $identifier < 11000000;
}

/**
 * Identifier is jove space.
 *
 * @param $identifier
 * @return bool
 */
function is_jove_region($identifier)
{
    return in_array($identifier, [
        10000004,
        10000017,
        10000019,
    ], true);
}

/**
 * Identifier is a w-space region.
 *
 * @param $identifier
 * @return bool
 */
function is_wspace_region($identifier)
{
    return $identifier > 11000000
        && $identifier < 12000000;
}

/**
 * Identifier is shattered w-space region.
 *
 * @param $identifier
 * @return bool
 */
function is_shattered_region($identifier)
{
    return $identifier === 11000032;
}

/**
 * Identifier is drifter w-space region.
 * @param $identifier
 * @return bool
 */
function is_drifter_region($identifier)
{
    return $identifier === 11000033;
}

/**
 * Identifier is a k-space constellation.
 *
 * @param $identifier
 * @return bool
 */
function is_kspace_constellation($identifier)
{
    return $identifier > 20000000
        && $identifier < 21000000;
}

/**
 * Identifier is a w-space constellation.
 *
 * @param $identifier
 * @return bool
 */
function is_wspace_constellation($identifier)
{
    return $identifier > 21000000
        && $identifier < 22000000;
}

/**
 * Identifier is Thera w-space constellation.
 *
 * @param $identifier
 * @return bool
 */
function is_thera_constellation($identifier)
{
    return $identifier === 21000324;
}

/**
 * Identifier is drifter w-space constellation.
 *
 * @param $identifier
 * @return bool
 */
function is_drifter_constellation($identifier)
{
    return $identifier === 21000334;
}

/**
 * Identifier is a k-space system.
 *
 * @param $identifier
 * @return bool
 */
function is_kspace_system($identifier)
{
    return $identifier > 30000000
        && $identifier < 31000000;
}

/**
 * Identifier is a w-space system.
 *
 * @param $identifier
 * @return bool
 */
function is_wspace_system($identifier)
{
    return $identifier > 31000000
        && $identifier < 32000000;
}

/**
 * Identifier is a drifter w-space system.
 *
 * @param $identifier
 * @return bool
 */
function is_drifter_system($identifier)
{
    return in_array($identifier, [
        31000001,
        31000002,
        31000003,
        31000004,
        31000006,
    ], true);
}

/**
 * Identifier is Thera w-space system.
 *
 * @param $identifier
 * @return bool
 */
function is_thera_system($identifier)
{
    return $identifier === 31000005;
}

/**
 * Identifier is a planet, moon, asteroid/ice belt or other celestial.
 *
 * @param $identifier
 * @return bool
 */
function is_celestial($identifier)
{
    return $identifier > 40000000
        && $identifier < 41000000;
}

/**
 * Identifier is a stargate.
 *
 * @param $identifier
 * @return bool
 */
function is_stargate($identifier)
{
    return $identifier > 50000000
        && $identifier < 51000000;
}

/**
 * @param array $array
 * @param array[] $arrays
 *
 * @return array
 */
function array_merge_recursive_overwrite(array $array, array ...$arrays)
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