<?php

namespace TomyKho\Here;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use TomyKho\Here\Models\GeocodeQuery;

class Here
{
    const HERE_GEOCODE_URL = 'https://geocode.search.hereapi.com/v1/geocode';

    static function geocode(GeocodeQuery $query)
    {
        $q = $query->q();
        $key = 'geocode:' . sha1(urlencode($q));
        $connection = config('here-laravel.cache.connection');
        $value = null;
        $redis = null;
        if ($connection) {
            $redis = Redis::connection($connection);
            $value = $redis->get($key);
            if ($value) {
                $value = json_decode($value, true);
            }
        }
        if (!$value || count($value['items']) == 0) {
            $response = Http::get(self::HERE_GEOCODE_URL, [
                'apiKey' => config('here-laravel.api_key'),
                'q' => $q,
            ]);
            $value = $response->json();
            if ($redis) {
                $redis->set($key, json_encode($value), 'EX', config('here-laravel.cache.duration'));
            }
        }
        return $value['items'];
    }
}
