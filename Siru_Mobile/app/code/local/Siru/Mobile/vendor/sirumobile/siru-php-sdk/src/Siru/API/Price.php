<?php
namespace Siru\API;

use Siru\Exception\ApiException;

/**
 * Siru Price calculation API methods.
 * Can be used to calculate final call price in variant1 payments if needed.
 */
class Price extends AbstractAPI {
    
    /**
     * Returns actual price that will be charged from the end user.
     * 
     * @param  string      $purchaseCountry      Country code, for example FI
     * @param  string      $basePrice            Price with two decimal points, fe "5.00"
     * @param  string|null $submerchantReference Optional submerchant reference
     * @param  int|null    $taxClass             Optional tax class number
     * @param  string      $variant              Variant, usually variant1 which is default
     * @param  int         $merchantId           MerchantId. If empty, merchantId from signature is used
     * @return string
     * @throws Siru\Exception\InvalidResponseException
     * @throws Siru\Exception\ApiException
     */
    public function calculatePrice($purchaseCountry, $basePrice, $submerchantReference = null, $taxClass = null, $variant = 'variant1', $merchantId = null)
    {
        $fields = array_filter([
            'purchaseCountry' => $purchaseCountry,
            'basePrice' => $basePrice,
            'submerchantReference' => $submerchantReference,
            'taxClass' => $taxClass,
            'variant' => $variant,
            'merchantId' => is_numeric($merchantId) ? $merchantId : $this->signature->getMerchantId()
        ]);

        list($httpStatus, $body) = $this->send('/payment/price.json', 'GET', $fields);

        $json = $this->parseJson($body);

        if($httpStatus <> 200) {
            throw $this->createException($httpStatus, $json, $body);
        }
        
        return $json['finalCallPrice'];
    }

    private function createException($httpCode, $json, $body)
    {
        if(isset($json['error']) && is_string($json['error'])) {
            $message = $json['error'];
        } else {
            $message = 'Unknown error';
        }

        return new ApiException($message, 0, null, $body);
    }

}
