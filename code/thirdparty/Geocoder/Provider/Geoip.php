<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider;

use Geocoder\Exception\NoResult;
use Geocoder\Exception\ExceptionNotLoaded;
use Geocoder\Exception\UnsupportedOperation;

/**
 * @see http://php.net/manual/ref.geoip.php
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class Geoip extends AbstractProvider implements Provider
{
    /**
     * No need to pass a HTTP adapter.
     */
    public function __construct()
    {
        if (!function_exists('geoip_record_by_name')) {
            throw new ExceptionNotLoaded('You have to install GeoIP');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getGeocodedData($address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The GeoipProvider does not support Street addresses.');
        }

        // This API does not support IPv6
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new UnsupportedOperation('The GeoipProvider does not support IPv6 addresses.');
        }

        if ('127.0.0.1' === $address) {
            return array($this->getLocalhostDefaults());
        }

        $results = @geoip_record_by_name($address);

        if (!is_array($results)) {
            throw new NoResult(sprintf('Could not find %s ip address in database.', $address));
        }

        $timezone = @geoip_time_zone_by_country_and_region($results['country_code'], $results['region']) ?: null;
        $region   = @geoip_region_name_by_code($results['country_code'], $results['region']) ?: $results['region'];

        return array($this->fixEncoding(array_merge($this->getDefaults(), array(
            'latitude'    => $results['latitude'],
            'longitude'   => $results['longitude'],
            'locality'    => $results['city'],
            'postalCode'  => $results['postal_code'],
            'region'      => $region,
            'regionCode'  => $results['region'],
            'country'     => $results['country_name'],
            'countryCode' => $results['country_code'],
            'timezone'    => $timezone,
        ))));
    }

    /**
     * {@inheritDoc}
     */
    public function getReversedData(array $coordinates)
    {
        throw new UnsupportedOperation('The GeoipProvider is not able to do reverse geocoding.');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'geoip';
    }
}
