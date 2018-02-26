# Changelog

* **v3.0.5.4** - 2018-02.20
    - When rejecting request with invalid signature, send correct status (500 => 403)
    - When debug-logging headers in proxy requests, log the actual headers sent
    - Replaced all deprecated logger->err calls with PSR-conformant logger->error
    - Encode name and password by getting URL in ProxyQuery
    - Remove deprecated call, fix junk after response body
    - Add README.md
    - Add communication schema (src/OwsProxy3/CoreBundle/Documentation/communication.puml)

* **v3.0.5.3** - 2016-02-04
    - none

* **v3.0.5.2** - 2015-10-27
    - none

* **v3.0.5.1** - 2015-08-26
    -  add checkssl parameter for curl
    -  add ru translations

* **v3.0.5.0** - 2015-07-01
    -  added some oracle workarounds

* **v3.0.4.1** - 2015-01-23
    - add logger to CommonProxy, WmsProxy

* **v3.0.4.0** - 2014-09-12
    - Switched to MIT license
    - Bug fixes
    - Enhanced exception handling
    - Fix cURL behavior when closing connections
    - Added user-agent "OWSproxy3"
    - Added request/response logging
    - Oracle support for logging
