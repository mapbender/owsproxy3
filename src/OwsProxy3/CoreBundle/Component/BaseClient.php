<?php


namespace OwsProxy3\CoreBundle\Component;


class BaseClient
{
    /** @var string */
    const DEFAULT_USER_AGENT = 'OWSProxy3';

    /**
     * @return string
     */
    protected function getUserAgent()
    {
        return self::DEFAULT_USER_AGENT;
    }
}
