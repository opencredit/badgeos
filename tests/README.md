# BadgeOS Test Suite [![Build Status](https://travis-ci.org/opencredit/badgeos.png?branch=master)](https://travis-ci.org/opencredit/badgeos) #

-------------------------

The BadgeOS Test Suite uses PHPUnit to help us maintain the best possible code quality.

Travis-CI Automated Testing
-----------

The master branch of BadgeOS is automatically tested on [travis-ci.org](http://travis-ci.org). The image above will show you the latest test's output. Travis-CI will also automatically test all new Pull Requests to make sure they will not break our build.

Quick Start (For Manual Runs)
-----------------------------

# 1. Clone this repository
```bash
git clone git://github.com/opencredit/badgeos.git ./
```

# 2. [Install PHPUnit](https://github.com/sebastianbergmann/phpunit#installation).

# 3. Initialize the testing environment locally:
If you haven't already installed the WordPress testing library, we have a helpful script to do so for you.

Note: you'll need to already have `svn`, `wget`, and `mysql` available.

```bash
./tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
```
* `wordpress_test` is the name of the test database (**all data will be deleted!**)
* `root` is the MySQL user name
* `''` is the MySQL user password
* `localhost` is the MySQL server host
* `latest` is the WordPress version; could also be `3.7`, `3.6.2` etc.

# 4. Run the plugin tests:
Note: MySQL must be running in order for tests to run.
```bash
phpunit
```

# 5. Bonus Round: Automatically run tests prior to commit with help from Grunt
All you need to do is run these two commands, and then priort to accepting any commit grunt will run phpunit.
If a test fails, the commit will be rejected, giving you the opportunity to fix the problem first.

```bash
npm install
grunt githooks
```

-------------------------

# External Testing URLs #
* Travis-CI (automated unit testing): https://travis-ci.org/opencredit/badgeos/
* Coveralls.io (automated code coverage reports): https://coveralls.io/r/opencredit/badgeos/

Other Supported (manually run):
* PHPUnit: http://phpunit.de/manual/current/en/index.html
