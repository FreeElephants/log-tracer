PATH := $(shell pwd)/bin:$(PATH)
$(shell cp -n dev.env .env)
include .env

install: build
	composer install
	cp -n phpunit.xml.dist phpunit.xml

build:
	docker build --build-arg PHP_VERSION=$(PHP_VERSION) -t $(PHP_DEV_IMAGE):$(REVISION) .

test:
	php vendor/bin/phpunit

code-style-check:
	./bin/php vendor/bin/php-cs-fixer check --diff --config=.php-cs-fixer.dist.php

code-style-fix:
	./bin/php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
