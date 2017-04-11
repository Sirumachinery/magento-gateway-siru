<?php
namespace Siru\Exception;

abstract class AbstractApiException extends \Exception {

    private $responseBody = false;

    public function __construct($message = '', $code = 0, \Exception $e = null, $body ='')
    {
        parent::__construct($message, $code, $e);

        $this->responseBody = $body;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }

}
