<a id="Random-Check-Results"></a>Random check results
=====================================================

The internal random check provided by Icinga 2 makes no good candidate for
simulating a real-live environment. Our requirements are: 

* Objects should change their state only from time to time
* Only a small percentage of objects should be in a problem state
* The probability that a problematic check recovers should be way higher
  than the probability that a currently green check should fail
* Text output should change constantly. This is important to visualize the
  difference between **state** and **volatile state** information, being
  shipped in different ways

To get more realistic data without wasting too much time we decided to opt for
fortune-cookies. They are available everywhere and easy to install:

```sh
apt-get install fortune-mod fortunes-de
```

As a small check plugin wrapper we create `/usr/bin/dummy-fortune`:

```sh
#!/bin/bash

/usr/games/fortune -u -o -s de | grep -v '\--' | tr -s '\n' ' '
exit $1
```

Do not forget to make this script executable!


Configuration through Icinga Director
-------------------------------------

In our Director setup, we create a related `Plugin Check Command`. It's *name*
might be `random fortune`, *command* is obviously `/usr/bin/dummy-fortune` and
it gets one single Argument. Just add it, with no name, set *value type* to
`Icinga DSL` and paste the following as it's value:

```icinga2
var rand = Math.random() * 1000
if (host.state > 0) {
    if (rand > 900) {
        return 0
    } else {
        return 2
    }
} else {
    if (rand > 995) {
        return 2
    } else {
        return 0
    }
}
```

This gives `DOWN` for a green host with 0.5% probability and let's the host
recover with a probability of 10%. Thresholds could be parametrized or fine-tuned
of course. As we start our tests with hosts this is enough for now. Once services
come into play this should be extended with warning/unknown states.

Last but not least please create a host template named `Random Fortune`.
