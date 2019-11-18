## v3.0.6.6
- Avoid undesirable implicit entity flushing and repeat of failing transactions commits on request end with enabled `logging` configuration
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
