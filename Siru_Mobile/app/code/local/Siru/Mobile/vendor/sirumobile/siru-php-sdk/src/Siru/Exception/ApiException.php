<?php
namespace Siru\Exception;

/**
 * This exception is thrown when API responds with an error message.
 * In case of Payment API, there can be multiple error messages returned at once. You can retrieve list
 * of these using getErrorStack().
 */
class ApiException extends AbstractApiException {
    
    private $errorStack = [];

    public function __construct($message = '', $code = 0, \Exception $e = null, $body ='', array $errorStack = [])
    {
        parent::__construct($message, $code, $e, $body);

        $this->errorStack = $errorStack;
    }

    /**
     * Returns all error messages received from API.
     * 
     * @return array
     */
    public function getErrorStack()
    {
        return $this->errorStack;
    }

}
