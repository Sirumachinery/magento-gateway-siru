<?php
namespace Siru\API;

use Siru\Exception\ApiException;
use DateTime;
use DateTimeZone;

/**
 * Siru purchase status API methods.
 */
class PurchaseStatus extends AbstractAPI {
    
    /**
     * Find a single purchase by purchase UUID that you received from Payment API.
     *
     * Note: that if purchase is not found, method throws ApiException.
     *
     * Example response array:
     * Array
     * (
     *     [uuid] => 88a0f51b-e6aa-41f2-8663-4a0112990a7c
     *     [status] => confirmed
     *     [basePrice] => 5.00
     *     [finalPrice] => 7.50
     *     [createdAt] => 2017-02-08T12:41:45+0000
     *     [startedAt] => 2017-02-08T12:42:00+0000
     *     [finishedAt] => 2017-02-08T12:42:09+0000
     *     [customerNumber] => 358441234567
     * )
     * 
     * @param  string $uuid Uuid received from Payment API
     * @return array        Single purchase details as an array
     * @throws Siru\Exception\InvalidResponseException
     * @throws Siru\Exception\ApiException
     */
    public function findPurchaseByUuid($uuid)
    {
        $fields = $this->signature->signMessage([ 'uuid' => $uuid ]);

        list($httpStatus, $body) = $this->send('/payment/byUuid.json', 'GET', $fields);

        return $this->parseResponse($httpStatus, $body);
    }

    /**
     * Returns array of purchases that match given parameters or
     * empty array if no matches are found.
     * 
     * Example response array:
     * Array
     * (
     *     [0] => Array
     *         (
     *             [uuid] => 88a0f51b-e6aa-41f2-8663-4a0112990a7c
     *             [status] => confirmed
     *             [basePrice] => 5.00
     *             [finalPrice] => 7.50
     *             [createdAt] => 2017-02-08T12:41:45+0000
     *             [startedAt] => 2017-02-08T12:42:00+0000
     *             [finishedAt] => 2017-02-08T12:42:09+0000
     *             [customerNumber] => 358441234567
     *         )
     * )
     * 
     * @param  string      $purchaseReference    Purchase reference sent to API
     * @param  string|null $submerchantReference Optional submerchantReference
     * @return array
     * @throws Siru\Exception\InvalidResponseException
     * @throws Siru\Exception\ApiException
     */
    public function findPurchasesByReference($purchaseReference, $submerchantReference = null)
    {
        $fields = $this->signature->signMessage([
            'submerchantReference' => $submerchantReference,
            'purchaseReference' => $purchaseReference
        ]);

        list($httpStatus, $body) = $this->send('/payment/byPurchaseReference.json', 'GET', $fields);

        $json = $this->parseResponse($httpStatus, $body);
        return $json['purchases'];
    }

    /**
     * Returns array of purchases for given time period. Timestamps are automatically sent to API in UTC timezone. 
     * 
     * Example response array:
     * Array
     * (
     *     [0] => Array
     *         (
     *             [id] => 408
     *             [uuid] => 88a0f51b-e6aa-41f2-8663-4a0112990a7c
     *             [merchantId] => 1
     *             [submerchantReference] => siru-international
     *             [customerReference] => 
     *             [purchaseReference] => demoshop
     *             [customerNumber] => 358441234567
     *             [basePrice] => 5.00
     *             [finalPrice] => 7.50
     *             [currency] => EUR
     *             [status] => confirmed
     *             [createdAt] => 2017-02-08T12:41:45+0000
     *             [startedAt] => 2017-02-08T12:42:00+0000
     *             [finishedAt] => 2017-02-08T12:42:09+0000
     *         )
     * )
     * 
     * @param  DateTime $from  Lower date limit. Purchases with this datetime or higher will be included in the result.
     * @param  DateTime $to    Upper date limit. Purchases created before this datetime are included in the result.
     * @return array
     * @throws Siru\Exception\InvalidResponseException
     * @throws Siru\Exception\ApiException
     */
    public function findPurchasesByDateRange(DateTime $from, DateTime $to)
    {
        $searchFrom = clone $from;
        $searchTo = clone $to;
        $searchFrom->setTimezone(new DateTimeZone('UTC'));
        $searchTo->setTimezone(new DateTimeZone('UTC'));

        $dateFormat = 'Y-m-d H:i:s';
        $fields = $this->signature->signMessage([
            'from' => $searchFrom->format($dateFormat),
            'to' => $searchTo->format($dateFormat)
        ]);

        list($httpStatus, $body) = $this->send('/payment/byDate.json', 'GET', $fields);

        $json = $this->parseResponse($httpStatus, $body);
        return $json['purchases'];
    }

    /**
     * Checks HTTP status code and parses response body to JSON.
     * 
     * @param  int    $httpStatus
     * @param  string $body
     * @return array|stdClass
     */
    private function parseResponse($httpStatus, $body)
    {
        $json = $this->parseJson($body);

        if($httpStatus <> 200) {
            throw $this->createException($httpStatus, $json, $body);
        }
        
        return $json;        
    }

    /**
     * Creates an exception if error has occured.
     * 
     * @param  int            $httpStatus
     * @param  array|stdClass $json
     * @param  string         $body
     * @return ApiException
     */
    private function createException($httpStatus, $json, $body)
    {
        if(isset($json['error']['message'])) {
            $message = $json['error']['message'];
        } else {
            $message = 'Unknown error';
        }

        return new ApiException($message, 0, null, $body);
    }

}
