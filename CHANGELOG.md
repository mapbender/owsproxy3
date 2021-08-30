## v3.3.0
- Removed CommonProxy. Use service `owsproxy.http_foundation_client` instead.
- Removed kriswallsmith/buzz integration and dependency.
- Removed ProxyQuery::getGetUrl (urls are not method specific; use getUrl).
- Removed ProxyQuery::createFromUrl (use createGet, createPost or createFromRequest as appropriate)

## v3.2.0
- Removed html reformatting of error responses (all responses are forwarded)
- Removed database logging (use web server log and / or Symfony log)
- Removed legacy workaround for url-encoded proxy user / password configuration; do not pre-encode basic auth credentials
- Removed Buzz-specific header utility methods
- Removed unrouted (no url) "genericProxyAction". Use service `owsproxy.http_foundation_client` service to replace kernel subrequests to this action.
- Removed `owsproxy.buzz_client` service. Use service `owsproxy.http_foundation_client` instead.

## v3.1.9
- Fix configuration loading error on Symfony 5

## v3.1.8
- Fix controller incompatibilities with Symfony 4 / 5
- Resolve reliance on undeclared dependency sensio/framework-extra-bundle

## v3.1.7
- Fix outgoing headers (v3.1.6 regression)

## v3.1.6
- Fix subrequests to genericProxyAction invariantly producing outgoing POST requests
- Fix missing content type on HTML-reformatted error responses
- Fix cascading internal exception when reformatting HttpException to HTML
- Fix query parameter deduplication to also deduplicate params with equal case
- Fix query parameter deduplication to also deduplicate no-value parameters
- Fix support for proxy credentials containing url-reserved characters
- Fix extraneous leading space on forwarded header values
- Fold immediate double-logging in CommonProxy into single message
- Add `owsproxy.http_foundation_client` service for sane manual handling of `ProxyQuery` objects (Symfony-style responses)
- Add `owsproxy.buzz_client` service for semi-sane manual handling of `ProxyQuery` objects ("Buzz"-style responses)

## v3.1.5
- Drop session interactions; owsproxy uses signatures

## v3.1.4
- Avoid undesirable implicit entity flushing and repeat of failing transaction commits on request end with enabled `logging` configuration
- Change `logging` default true => false
- Accept null for `logging` and `obfuscate_client_ip`
- Add [CONFIGURATION.md](https://github.com/mapbender/owsproxy3/blob/master/CONFIGURATION.md)

## v3.1.3
- Fix credentials encoding issues causing signature errors on secured URLs

## v3.1.2
- Update basic auth handling to work with buzz 0.16.1; require buzz 0.16.1

## v3.1.1
- Fix errors in logging for unauthenticated users ([PR#18](https://github.com/mapbender/owsproxy3/pull/18))
- Misc spelling corrections

## v3.1.0
- Remove WmsProxy / WfsProxy specializations
- Remove unused components Clipping, SrsPoint, AbstractWorker, AbstractClipWorker
- Remove unused view svg.html.twig
- Remove custom Http exceptions
- Remove custom events owsproxy.before_proxy, owsproxy.after_proxy, owsproxy.terminate
- Reduce ProxyQuery public API
- Remove interactive HTTP auth challenge forwarding
- Allow installation of kriswallsmith/buzz 0.16.x on PHP>=5.4

## v3.0.6.7
- Drop session interactions; owsproxy uses signatures

## v3.0.6.6
- Avoid undesirable implicit entity flushing and repeat of failing transaction commits on request end with enabled `logging` configuration
- Change `logging` default true => false
- Accept null for `logging` and `obfuscate_client_ip`
- Add [CONFIGURATION.md](https://github.com/mapbender/owsproxy3/blob/release/3.0.6/CONFIGURATION.md)

## v3.0.6.5
- Resolve Symfony 3 compatibility issues ([PR#17](https://github.com/mapbender/owsproxy3/pull/17))
- Support forwarding of valid empty and 304 Not Modified responses
- Support forwarding of requests with no `SERVICE=` parameter
- Replace custom HTTP Exceptions with Symfony equivalents
- Replace HTTP exceptions thrown from internal components with appropriate non-HTTP exceptions

## v3.0.6.4
- Add public utility method to convert Buzz Response to HttpFoundation Response
- Fix invalid user agent in WfsProxy
- Removed redundant translations that were
  - for messages that could never be translated
  - for messages that could never be emitted
  - not translating (input = output)

## v3.0.6.3
- Allow updated versions of buzz to be installed

## v3.0.6.2
- When rejecting request with invalid signature, send correct status (500 => 403)
- Deprecate custom HTTP exceptions, extend from HttpException
- When debug-logging headers in proxy requests, log the actual headers sent
- Replaced all deprecated logger->err calls with PSR-conformant logger->error
- Remove deprecated call, fix junk after response body

## v3.0.6.1
- Delete Log.php~

## v3.0.6.0
- Encode name and password by getting URL in ProxyQuery
- Merge pull request #8 from RobinSchwammborn/release/3.0.6
- Merge branch 'release/3.0.6' into release/3.0.6
- Fixed minor typos
- Update README.md
- Improve and fix configuration options description
- Improve documentation. Describe checkssl option
- Improve readme configuration
- Improve contributing and readme documentation
- Improve contributing configuration options
- Fix contributing config path
- Add configuration to contributing documentation
- Add contributing documentation
- Add "arsgeografica/signing" library
- Add "kriswallsmith/buzz" composer library
- Add composer definition
- Merge pull request #7 from mapbender/hotfix/changelog
- Improve communication.puml diagramm

## v3.0.5.4
- When rejecting request with invalid signature, send correct status (500 => 403)
- Deprecate custom HTTP exceptions, extend from HttpException
- When debug-logging headers in proxy requests, log the actual headers sent
- Replaced all deprecated logger->err calls with PSR-conformant logger->error
- Encode name and password by getting URL in ProxyQuery
- Remove deprecated call, fix junk after response body
- Add README.md
- Add communication schema (src/OwsProxy3/CoreBundle/Documentation/communication.puml)

## v3.0.5.1
-  add checkssl parameter for curl
-  add ru translations

## v3.0.5.0
-  added some oracle workarounds

## v3.0.4.1
- add logger to CommonProxy, WmsProxy

## v3.0.4.0
- Switched to MIT license
- Bug fixes
- Enhanced exception handling
- Fix cURL behavior when closing connections
- Added user-agent "OWSproxy3"
- Added request/response logging
- Oracle support for logging
