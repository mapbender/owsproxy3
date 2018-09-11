<?php

namespace OwsProxy3\CoreBundle\Component\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The HTTPStatus403Exception
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
class HTTPStatus403Exception extends HttpException
{

    /**
     * Creates the HTTPStatus403Exception exception
     * 
     * @param string $message the exception message
     * @param int $code the exception code ignored, fixed at 403
     */
    public function __construct($message = "403 Forbidden", $code = 403)
    {
        parent::__construct(403, $message);
    }

}
