<?php
namespace Siru\API;

use Siru\Signature;
use Siru\Exception\InvalidResponseException;
use Siru\Exception\ApiException;

/**
 * API to create new pending payment to Siru Mobile API.
 * API returns payment UUID and URL where user should be redirected to confirm payment.
 * From there user is redrected back to one of your redirectAfter* URLs.
 */
class Payment extends AbstractAPI {

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $signatureFields = [
        'basePrice',
        'customerReference',
        'instantPay',
        'merchantId',
        'notifyAfterCancel',
        'notifyAfterFailure',
        'notifyAfterSuccess',
        'purchaseCountry',
        'purchaseReference',
        'smsAfterSuccess',
        'submerchantReference',
        'variant'
    ];

    /**
     * @var array
     */
    private $variantSignatureFields = [
        'variant1' => [
            'customerNumber',
            'serviceGroup',
            'taxClass'
        ],
        'variant2' => [
            'instantPay',
            'serviceGroup',
            'taxClass',
        ],
        'variant3' => [],
        'variant4' => [
            'customerNumber',
            'description',
            'serviceGroup',
            'taxClass',
            'title'
        ]
    ];

    /**
     * Set a field value to payment request.
     * 
     * @param   string $key   Field name
     * @param   mixed  $value Field value
     * @return  Payment
     */
    public function set($key, $value = null)
    {
        if($value === null) {
            unset($this->fields[$key]);
        } else {
            $this->fields[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns list of fields in correct order that are included when calculating signature for select variant.
     * @param  string $variant Variant name
     * @return array
     */
    public function getSignatureFieldsForVariant($variant)
    {
        $fields = $this->signatureFields;

        if(isset($this->variantSignatureFields[$variant])) {
            $fields = array_merge($fields, $this->variantSignatureFields[$variant]);
        }

        sort($fields);
        return $fields;
    }

    /**
     * Sends payment request to API.
     *
     * If everything checks out, method returns array with payment UUID and URL where
     * user should be redirected next to make payment.
     * 
     * @return array
     * @throws Siru\Exception\InvalidResponseException
     * @throws Siru\Exception\ApiException
     */
    public function createPayment()
    {

        $signedFields = $this->signature->signMessage(
            $this->fields,
            $this->getSignatureFieldsForVariant($this->fields['variant']),
            Signature::FILTER_EMPTY | Signature::SORT_FIELDS
        );

        list($httpCode, $body) = $this->send('/payment.json', 'POST', $signedFields);

        // Validate response
        $json = $this->parseJson($body);
        if(isset($json['success']) == false) {
            throw new InvalidResponseException("Invalid response from API", 0, null, $body);
        }

        if($json['success'] == false) {
            throw $this->createErrorException($json, $body);
        }

        return [
            'uuid' => $json['purchase']['uuid'],
            'redirect' => $json['purchase']['redirect']
        ];
    }

    private function createErrorException(array $json, $body)
    {
        if(isset($json['errors'])) {
            $errorStack = $json['errors'];
        } else {
            $errorStack = [];
        }

        return new ApiException('An error occured while initiating payment.', 0, null, $body, $errorStack);
    }

}
