<a id="vagrant-box"></a> Vagrant Box
====================================

Test and development environment.

```
vagrant up
```

```
vagrant ssh
sudo -i
```

Event Streamer (to be replaced by Icinga 2 RedisWriter)
```
icingacli ddolab event stream --debug
```

Event to State (Redis)

```
icingacli ddolab event process --debug
```

Config sync
```
icingacli ddolab config sync --debug
```

The Redis container forwards the local port 6379 outside of the Vagrant box. This allows
for testing with local Icinga 2 branches.
