PHP vendor libraries
====================

Can be installed here unless available elsewhere on your system. Currently,
there is only Predis - and it only works when installed to library/vendor.

Installing Predis
-----------------

```sh
TARGET=library/vendor/predis
URL=https://github.com/nrk/predis/archive/v1.1.1.tar.gz
mkdir $TARGET
wget -q -O - $URL | tar -C $TARGET --strip-components=1 -xz
```
