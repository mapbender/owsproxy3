<?php


namespace OwsProxy3\CoreBundle\Component;

/**
 * Curl-specific portion of BuzzClientCommon / CommonProxy
 * @todo future (break): drop Buzz-aware API, standardize on bare curl (better maintenance, less overhead) or upcoming
 *    plain-PHP / Symfony HTTP APIs
 * @internal
 */
class CurlClientCommon extends BaseClient
{
    /**
     * @param string $hostName
     * @param array $config
     * @return array
     */
    public static function getCurlOptions($hostName, $config)
    {
        $options = array(
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
        );
        if (isset($config['timeout'])) {
            $options[CURLOPT_TIMEOUT] = $config['timeout'];
        }
        if (isset($config['connecttimeout'])) {
            $options[CURLOPT_CONNECTTIMEOUT] = $config['connecttimeout'];
        }
        if (isset($config['checkssl'])) {
            $options[CURLOPT_SSL_VERIFYPEER] = !!$config['checkssl'];
        }
        if (isset($config['host']) && (empty($config['noproxy']) || !in_array($hostName, $config['noproxy']))) {
            $proxyOptions = array(
                CURLOPT_PROXY => $config['host'],
                CURLOPT_PROXYPORT => $config['port'],
            );
            if (isset($config['user']) && isset($config['password'])) {
                /** @todo v3.2: remove special detection, invariantly encode */
                $encodedPattern = '#[%][0-9a-f]{2}#i';
                if (preg_match($encodedPattern, $config['user']) || preg_match($encodedPattern, $config['password'])) {
                    @trigger_error("Deprecated: proxy credentials look like they may be pre-encoded. Please supply them as plain text (no url encoding). Special handling for pre-encoded credentials will be removed in v3.2", E_USER_DEPRECATED);
                    $proxyOptions = array_replace($proxyOptions, array(
                        CURLOPT_PROXYUSERPWD => "{$config['user']}:{$config['password']}",
                    ));
                } else {
                    // must be encoded, at the very least to disambiguate embedded colon from separator colon
                    // see https://curl.haxx.se/libcurl/c/CURLOPT_PROXYUSERPWD.html
                    $proxyOptions = array_replace($proxyOptions, array(
                        CURLOPT_PROXYUSERPWD => rawurlencode($config['user']) . ':' . rawurlencode($config['password']),
                    ));
                }
            }
            $options = array_replace($options, $proxyOptions);
        }
        return $options;
    }
}
