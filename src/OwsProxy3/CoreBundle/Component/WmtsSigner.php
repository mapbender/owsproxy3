<?php

namespace OwsProxy3\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Signer;

/**
 * Class WmtsSigner
 */
class WmtsSigner extends Signer {

    /**
     * The signWmtsUrl strips out the path before signing
     * @param type $url
     * @return type
     */
    public function signWmtsUrl($url) {
        $signature = $this->getSignature($this->removePath($url));
        if (!preg_match('#\?.+$#', rtrim($url, '?'))) {
            $paramSeparator = '?';
        } else {
            $paramSeparator = '&';
        }

        return rtrim($url, '?') . $paramSeparator . '_signature=' . urlencode($signature);
    }

    public function checkSignedWmtsUrl($url) {
        return $this->checkSignedUrl($this->removePath($url));
    }

    public function removePath($url)
    {
        $parts = parse_url($url);

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = isset($parts['user']) ? $parts['user'] : '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return "$scheme$user$pass$host$port/$query$fragment"; 
    }
}
