<?php
/**
 * Base service
 *
 * @author: tuanha
 * @date: 27-July-2022
 */
namespace App\Services;

use Exception;
use GuzzleHttp\Client;

class Base
{
    /**
     * @var GuzzleHttp\Client $client
     */
    protected $client;

    /**
     * Create instance
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('settings.api_endpoint'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('settings.api_token'),
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    /**
     * GET a path and return the JSON-decoded array, or null on any failure.
     * (The legacy statement/fundamental methods deliberately keep returning the
     * raw body string; this helper is for the newer array-returning methods.)
     *
     * @param  string  $path
     * @return array|null
     */
    protected function getDecoded(string $path)
    {
        try {
            $res = $this->client->request('GET', $path);
            if ($res->getStatusCode() == 200) {
                $data = json_decode($res->getBody()->getContents(), true);
                return is_array($data) ? $data : null;
            }
        } catch (Exception $e) {
            // fall through — caller treats null as "no data"
        }

        return null;
    }
}
