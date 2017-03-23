# Configuration

The configuration is done in the file `app/config/config.yml` at the section `ows_proxy3_core`.

```yaml
ows_proxy3_core:
    logging: true               # logging of requests, default is true, true logs in table owsproxy_log 
    obfuscate_client_ip: true   # obfuscats a client ip, use 'true' to hide the last byte of the client's ip address
    proxy:                      # proxy definition for connnection via a proxy server
                                # at least 'host' and 'port' are needed for proxy definition
            connecttimeout: 30      # default 30s
            timeout: 60             # default 60s 
    host:                   # host name of the proxy server (define a host for a connection via a proxy server)
    port:                   # port number of the proxy server (define a host for a connection via a proxy server)
    user:                   # user name for proxy server (set user for proxy server if needed)
    password:               # password for proxy server (set password for proxy server if defined)
    noproxy:                # list of hosts for connnections without proxy server
        - host_a            # host name
```

#  Sequence diagram

![Sequence diagram](http://plantuml.com/plantuml/proxy?src=https://raw.githubusercontent.com/mapbender/owsproxy3/release/3.0.5/src/OwsProxy3/CoreBundle/Documentation/communication.puml)