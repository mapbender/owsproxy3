<?php


namespace OwsProxy3\CoreBundle\Component;


class BaseClient
{
    /** @var string */
    const DEFAULT_USER_AGENT = 'OWSProxy3';

    /**
     * @return string
     * @todo: for service-type child classes, provide parameter-configurable value
     */
    protected function getUserAgent()
    {
        return self::DEFAULT_USER_AGENT;
    }
}
