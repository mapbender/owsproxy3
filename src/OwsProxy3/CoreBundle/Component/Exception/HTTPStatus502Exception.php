<?php

namespace OwsProxy3\CoreBundle\Component\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The HTTPStatus502Exception
 * @package OwsProxy3
 * @author Paul Schmidt
 *
 * @deprecated
 * @internal
 *
 * Constructor does not support supplying $previous.
 *
 * Use Symfony\Component\HttpKernel\Exception\HttpException directly
 */
class HTTPStatus502Exception extends HttpException
{

    /**
     * Creates the HTTPStatus502Exception exception
     * 
     * @param string $message the exception message
     * @param int $code the exception code ignored, fixed at 502
     */
    public function __construct($message = "502 Bad Gateway", $code = -1)
    {
        parent::__construct(502, $message);
    }

}
