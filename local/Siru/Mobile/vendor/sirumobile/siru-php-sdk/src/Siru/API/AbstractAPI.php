<?php
namespace Siru\API;

use Siru\Signature;
use Siru\Exception\InvalidResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Base class for each Siru API class.
 *
 * Uses GuzzleHttp client to send requests to Siru API.
 */
abstract class AbstractAPI {
    
    /**
     * Signature creator.
     * @var Signature
     */
    protected $signature;

    /**
     * Siru API endpoint host name
     * @var string
     */
    private $endpoint;

    /**
     * GuzzleHttp client for making requests.
     * @var Client
     */
    private $client;

    /**
     * Signature object and API endpoint address are required.
     * 
     * @param Signature $signature
     * @param string    $endpoint
     * @todo  should we allow user to set defaults to client??
     * @todo  could we remove dependency to Guzzle?
     */
    public function __construct(Signature $signature, $endpoint)
    {
        $this->signature = $signature;
        $this->endpoint = $endpoint;

        $this->client = new Client(['base_uri' => $endpoint, 'verify' => false]);
    }

    /**
     * Sends request to Siru API.
     * 
     * @param  string $path   Path that is appended to endpoint address
     * @param  string $method HTTP method GET or POST
     * @param  array  $fields Values that are sent to API
     * @return array          Array where first index is HTTP status and second is response body
     */
    protected function send($path, $method = 'GET', array $fields = [])
    {
        $options = ['verify' => false];

        if($method === 'GET') {
            $options['query'] = $fields;
        } elseif(!empty($fields)) {
            $options['json'] = $fields;
        }

        // Send request
        try {
            $response = $this->client->request($method, $path, $options);
        } catch(BadResponseException $e) {
            $response = $e->getResponse();
        }

        return [$response->getStatusCode(), (string)$response->getBody()];
    }

    /**
     * Tries to convert JSON string to array.
     * 
     * @param  string $body
     * @return array|false
     * @throws InvalidResponseException
     */
    protected function parseJson($body)
    {
        if(empty($body) == false) {
            $json = json_decode($body, true);
        }

        if(empty($json) === true) {
            throw new InvalidResponseException("Invalid response from API", 0, null, $body);
        }

        return $json;
    }

}
