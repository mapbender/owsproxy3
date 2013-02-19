<?php

namespace OwsProxy3\CoreBundle\Component\Exception;

/**
 * The HTTPStatus502Exception
 * @package OwsProxy3
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class HTTPStatus502Exception
        extends \Exception
{
    /**
     * Creates the HTTPStatus502Exception exception
     * 
     * @param type $message the exception message
     * @param type $code the exception code
     */
    public function __construct($message = "502 Bad Gateway", $code = 502)
    {
        parent::__construct($message, $code);
    }
}
