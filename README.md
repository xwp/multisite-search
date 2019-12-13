# multisite-search

This plugin base requires at least PHP 5.3+ to use interfaces, abstract classes and anonymous functions. PHP 5.4+ is required to make use of *traits*.

---
#### PHPUnit Tests

Tests can be run by executing `make test`. These tests will run inside Docker containers. Minimum requirement
to run tests are:

- Docker (e.g. DockerCE for Mac)
- Docker Compose CLI (usually installed with Docker)
- GNU make (usually already on *nix based system. For Windows see: http://gnuwin32.sourceforge.net/packages/make.htm)

Test variables can be altered (with caution) in `./tests/docker/.env`

---