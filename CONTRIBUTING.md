# ToopherAPI PHP Client

#### Installing Dependencies
Toopher manages dependencies with [Composer](http://getcomposer.org).

To install Composer with Homebrew run:
```shell
$ brew install composer
```

To ensure all dependencies are up-to-date enter:
```shell
$ composer install
```

#### Tests
To run all unit tests enter:
```shell
$ phpunit test/test_toopher_api.php
```

Note: `phpunit` may be found in `vendor/bin/php` so your test command would be:
```shell
$ vendor/bin/phpunit test/test_toopher_api.php
```
