# OWS Proxy

 Can be used to relay requests and results form clients to servers that are otherwise not directly accessible to the client.
 
## Features

* [Significates](CONTRIBUTING.md#signing-urls) base of external URL
* Prohibits to communicate with not verified URL's
* Allows to use server proxies
* Let OpenLayers2 communicate with external WMS servers through OwsProxy
* Uses a HTTP proxy to work in tightly secured environments
* Logs proxy calls for billing

## Configuration

The configuration is done in `app/config/config.yml` at `ows_proxy3_core` section.

### Options

#### Logging 

Turns logging on/off to save logs in `owsproxy_log` table

* Key name: `logging`
* Default: `true`

#### Client IP obfuscating

Conceals client IP, set `true` to hide the last byte of the client's IP address

* Key name: `obfuscate_client_ip`
* Default: `false`

#### Proxy


Proxy option allows to communicate services via custom proxy server.
The option need some own configurations:

* `host`: Proxy server host name. If sets to `null`, OwsProxy works without proxy.
* `port`: Proxy server port number.
* `timeout`: Give up, if OwsProxy don't retrieved response from given proxy in `number` seconds.  Default: 60 seconds. 
* `connecttimeout`: Give up, if OwsProxy don't reach `host` in ` number` seconds. Default: 30 seconds.
* `user`:  Proxy server user name. Default: `null`.
* `password`: Proxy server password for proxy server. Default: `null`.
* `noproxy`:  Exclude hosts from connecting throught proxy server. Default: `null`. Hostnames and IP's can be given as an YAML array.
* `checkssl`: Checks SSL. Default: false

### Configuration example

```yaml
ows_proxy3_core:
    logging: true               
    obfuscate_client_ip: true 
    proxy:                
        connecttimeout: 30    
        timeout: 60           
        host: localhost            
        port: 8080                 
        noproxy:               
            - localhost           
            - 127.0.0.1           
```


##  Sequence diagram

![Sequence diagram](http://plantuml.com/plantuml/proxy?src=https://raw.githubusercontent.com/mapbender/owsproxy3/release/3.0.5/src/OwsProxy3/CoreBundle/Documentation/communication.puml)
