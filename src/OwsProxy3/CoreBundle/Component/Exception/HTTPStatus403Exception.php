<?php

namespace OwsProxy3\CoreBundle\Component\Exception;

/**
 * The HTTPStatus403Exception
 * @package OwsProxy3
 * @author Paul Schmidt
 */
class HTTPStatus403Exception extends \Exception
{

    /**
     * Creates the HTTPStatus403Exception exception
     * 
     * @param string $message the exception message
     * @param int $code the exception code
     */
    public function __construct($message = "403 Forbidden", $code = 403)
    {
        parent::__construct($message, $code);
    }

}
