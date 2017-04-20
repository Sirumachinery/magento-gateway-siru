<?php
namespace Siru\API;

use Siru\Exception\ApiException;

/**
 * API for checking if given IP-address is allowed to use variant2 mobile payments.
 */
class FeaturePhone extends AbstractAPI {

    /**
     * @param  string  $ip IPv4 address
     * @return boolean     True if variant2 payments are possible from this IP-address
     * @throws Siru\Exception\InvalidResponseException
     * @throws Siru\Exception\ApiException
     */
    public function isFeaturePhoneIP($ip)
    {
        $signedFields = $this->signature->signMessage([ 'ip' => $ip ]);

        list($httpStatus, $body) = $this->send('/payment/ip/feature-check', 'GET', $signedFields);

        // Validate response
        $json = $this->parseJson($body);

        if($httpStatus <> 200) {
            throw $this->createException($httpStatus, $json, $body);
        }

        return $json['ipPaymentsEnabled'] == true;
    }

    private function createException($httpCode, $json, $body)
    {
        if(isset($json['error']['message'])) {
            $message = $json['error']['message'];
        } else {
            $message = 'Unknown error';
        }

        return new ApiException($message, 0, null, $body);
    }

}
