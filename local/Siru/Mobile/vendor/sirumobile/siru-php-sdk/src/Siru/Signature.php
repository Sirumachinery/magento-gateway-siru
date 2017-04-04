<?php
namespace Siru;

/**
 * Used to calculate signature for messages sent to Siru API.
 *
 * You can use this class as standalone to calculate signature for your own code
 * or with Siru\API class which can do all the heavy lifting for you.
 */
class Signature {

    const SORT_FIELDS = 1;
    const FILTER_EMPTY = 2;

    /**
     * Merchant id.
     * @var string
     */
    private $merchantId;

    /**
     * Merchant secret.
     * @var string
     */
    private $merchantSecret;

    /**
     * Constructor takes your merchant id and secret code as parameter and uses it to
     * create and validate message signatures.
     *
     * @param int    $merchantId     Your merchant id provided by Siru Mobile
     * @param string $merchantSecret Your merchant secret provided by Siru Mobile
     */
    public function __construct($merchantId, $merchantSecret)
    {
        $this->merchantId = $merchantId;
        $this->merchantSecret = $merchantSecret;
    }

    /**
     * Returns merchantId given in constructor.
     * 
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * Takes message array and adds signature to the array.
     *
     * merchantId is also prepended to array if it does not already exist.
     * 
     * @param  array  $fields       Array of request fields where key is field name
     * @param  array  $signedFields Optional array of field names in correct order which are used for signature
     * @param  int    $flags        Optional bit SORT_FIELDS, FILTER_EMPTY or combination of both
     * @return array                Array of field/value pairs you can send to Siru API
     */
    public function signMessage(array $fields, array $signedFields = [], $flags = 0)
    {
        return array_merge([
            'merchantId' => $this->merchantId,
            'signature' => $this->createMessageSignature($fields, $signedFields, $flags)
        ], $fields);
    }

    /**
     * Creates signature string from provided fields.
     * 
     * Merchant id is automatically prepended to signature calculation if it is mssing from fields.
     * 
     * @param  array  $fields       Array of request fields where key is field name
     * @param  array  $signedFields Optional array of field names in correct order which are used for signature
     * @param  int    $flags        Optional SORT_FIELDS, FILTER_EMPTY or combination of both
     * @return string
     */
    public function createMessageSignature(array $fields, array $signedFields = [], $flags = 0)
    {
        $fields = array_merge(['merchantId' => $this->merchantId], $fields);

        if(empty($signedFields) == false) {
            $fields = $this->extractSelectFields($fields, $signedFields);
        }

        if($flags & self::FILTER_EMPTY) {
            $fields = array_filter($fields, function($value) {
                return !($value === '' || $value === null);
            });
        }

        if($flags & self::SORT_FIELDS) {
            ksort($fields);
        }

        return $this->calculateHash($fields);
    }

    /**
     * Returns key/value pairs from $fields which keys exist in $signedFields array.
     * The key/value pairs are returned in same order as appear in $signedFields.
     * 
     * @param  array $fields
     * @param  array $signedFields
     * @return array
     */
    private function extractSelectFields($fields, $signedFields)
    {
        $newFields = [];
        foreach($signedFields as $field) {
            if(isset($fields[$field])) {
                $newFields[$field] = $fields[$field];
            }
        }
        return $newFields;
    }

    /**
     * Calculates SHA512-HMAC hash from fields using provided merchant secret.
     * 
     * @param  array  $fields
     * @return string
     */
    private function calculateHash(array $fields)
    {
        return hash_hmac("sha512", implode(';', $fields), $this->merchantSecret);
    }

    /**
     * Validates response signature received from Siru API.
     * 
     * The fields are sent as GET parameters when user is redrected back to your site.
     * If you provided notifyAfter* URLs, the fields are in message body as JSON object.
     * Remember to convert JSON object to array before passing to this method.
     * 
     * @param  array   $fields Response fields
     * @return boolean         True if signature is valid, otherwise false
     */
    public function isNotificationAuthentic(array $fields)
    {
        if(isset($fields['siru_signature']) === false) {
            return false;
        }

        $signedFields = ['siru_uuid', 'siru_merchantId', 'siru_submerchantReference', 'siru_purchaseReference', 'siru_event'];

        $signature = $this->createMessageSignature($fields, $signedFields);

        return $fields['siru_signature'] === $signature;
    }

}
