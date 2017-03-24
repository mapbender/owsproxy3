# Client

The URL for the proxy is available as the `Mapbender.configuration.application.urls.proxy` variable.
You can send WMS and WFS requests there, giving the target URL as the URL parameter. Be aware that the
target URL must be signed (see below).

# Signing URLs

The proxy is secured by checking a cryptographical signature on the target URL. This is basically a SHA1-Hash of the
secret token defined in the parameters.yml, the base part of the URL and a salt.

That means that you are responsible for signing your URLs before passing them to the public proxy URL. You can do this
in PHP by using the `signing` service provided by Mapbender:

```php
    $url = 'http://example.com';
    $signer = $this->container->get('signer');
    $signedUrl = $signer->signUrl(); // http://example.com?_signature=18:ePbX2vK9dy6AvaJq31URCvSz3EM
```

Checking that the URL and signature still match is also easy:

```php
$url = 'http://example.com?foo=bar&_signature=18:ePbX2vK9dy6AvaJq31URCvSz3EM';
$signer->checkSignedUrl($url);
```

This will throw an `ArsGeografica\Signing\BadSignatureException` if they don't match or no signature at all was included.


# Sequence diagram

![Sequence diagram](http://plantuml.com/plantuml/proxy?src=https://raw.githubusercontent.com/mapbender/owsproxy3/release/3.0.5/src/OwsProxy3/CoreBundle/Documentation/communication.puml)
