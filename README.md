# OWS Proxy

Secure communicate remote hosts through themselves.

## How to use the OWSProxy3?

We include OWSProxy3, our W*S proxy solution. It is secure and can log proxy calls for billing. It can use a HTTP proxy
itself to work in tightly secured environments.

## Features

* significates base of external URL
* prohibit to communicate not signifiert URL's
* allows to use server proxies
* through OwsProxy OpenLayer2 communicates external WMS Servers,


## Configuration

The configuration is done in the file `app/config/config.yml` at the section `ows_proxy3_core`.

### Options

#### Logging 

Turn logging  on/off. 
Saves logs in `owsproxy_log` table

* Key name: `logging`
* Default: `true`

#### Client IP obfuscating

Obfuscate client ip, set `true` to hide the last byte of the client's ip address

* Key name: `obfuscate_client_ip`
* Default: `true`

#### Proxy

Proxy definition for connection via a proxy server at least `host` and `port` are needed for proxy definition.

* **host**:                   host name of the proxy server (define a host for a connection via a proxy server)
* **port**:                   port number of the proxy server (define a host for a connection via a proxy server)
* **timeout**:           Default 60s 
* **connecttimeout**:      Default 30s
* **user**:                   user name for proxy server (set user for proxy server if needed)
* **password**:               password for proxy server (set password for proxy server if defined)
* **noproxy**:                list of hosts to except from proxing



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
            - host_a           
```


##  Sequence diagram

![Sequence diagram](http://plantuml.com/plantuml/proxy?src=https://raw.githubusercontent.com/mapbender/owsproxy3/release/3.0.5/src/OwsProxy3/CoreBundle/Documentation/communication.puml)