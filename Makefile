SHELL := /bin/sh

test:
	@docker-compose -f docker-compose.yml run --rm php vendor/bin/phpunit --no-coverage

test-cover:
	@docker-compose -f docker-compose.yml run --rm php ./vendor/bin/phpunit

stat:
	@docker-compose -f docker-compose.yml run --rm php vendor/bin/phan

fix:
	@docker-compose -f docker-compose.yml run --rm php composer fix

install:
	@docker-compose -f docker-compose.yml run --rm php composer i

.PHONY: all
all:
	@docker-compose -f docker-compose.yml run --rm php sh -c "vendor/bin/php-cs-fixer fix && vendor/bin/phan && vendor/bin/phpunit --no-coverage"

.PHONY: test-supports-php
PHP_IMAGES := php:8.1-cli-alpine php:8.2-cli-alpine php:8.3-cli-alpine php:8.4-cli-alpine
CMD_PREPARE := rm -f composer.lock && rm -rf vendor && composer install -q -n --no-progress
CMD_TEST := $(CMD_PREPARE) && vendor/bin/phpunit --no-coverage

test-supports-php:
	@$(foreach IMG,$(PHP_IMAGES),\
		docker-compose build --build-arg PHP_IMAGE=$(IMG);\
		docker-compose -f docker-compose.yml run --rm php sh -c "$(CMD_TEST)"; \
	)

	docker-compose build #build container defined in .env file as PHP_IMAGE
	docker-compose -f docker-compose.yml run --rm php sh -c "$(CMD_PREPARE)"
