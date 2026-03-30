.PHONY: up down logs shell wp-cli install test build wp-setup

up:
	docker-compose up -d
	@echo "WordPress running at http://localhost:8080"
	@echo "MailHog at http://localhost:8025"

down:
	docker-compose down

logs:
	docker-compose logs -f wordpress

shell:
	docker-compose exec wordpress bash

wp-cli:
	docker-compose exec wordpress bash -c "curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp"

install:
	composer install
	npm install

wp-setup:
	docker-compose exec wordpress wp --allow-root core install \
		--url="http://localhost:8080" \
		--title="WP AI Agent Dev" \
		--admin_user=admin \
		--admin_password=admin \
		--admin_email=admin@example.com

test:
	composer test
	npm run test

build:
	npm run build
