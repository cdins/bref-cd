SHELL := /bin/bash
.PHONY: publish layers docker-images

# Publish the layers on AWS Lambda
publish: layers
	cd layers ; php publish.php

# Build the layers
layers: export/console.zip export/php-73.zip export/php-73-fpm.zip

# The PHP runtimes
export/php%.zip: docker-images
	PHP_VERSION=$$(echo $@ | cut -d'/' -f 2 | cut -d'.' -f 1);\
	rm -f $@;\
	cd export ; \
	set -e ; \
	rm -rf opt ; \
	CID=$$(docker create --entrypoint=scratch bref/$$PHP_VERSION:latest) ; \
	docker cp $${CID}:/opt . ; \
	docker rm $${CID} ; \
	cd opt ; \
	zip -qq -y -r - {*,.[!.]*} > ../$$PHP_VERSION.zip
	cd ../ ; \
	rm -rf opt ;

# The console runtime
export/console.zip: layers/console/bootstrap
	rm -f export/console.zip
	cd layers/console && zip ../../export/console.zip bootstrap

# Build Docker images
docker-images:
	# Build the base environment (without PHP)
	cd base ; docker build --file base.Dockerfile -t bref/tmp/step-1/build-environment .
	# Build the `bref/build-php-XX` images
	# (build only the first `FROM` section of the Dockerfile)
	cd base ; docker build --file php-73.Dockerfile -t bref/build-php-73 --target build-environment .
	# Build the whole Dockerfile to generate the cleaned images that will be used in the next step
	cd base ; docker build --file php-73.Dockerfile -t bref/tmp/cleaned-build-php-73 .
	# - function
	cd layers/function ; docker build -t bref/php-73 --build-arg PHP_VERSION=73 .
	# - fpm
	cd layers/fpm ; docker build -t bref/php-73-fpm --build-arg PHP_VERSION=73 .
	# Other Docker images
	cd layers/fpm-dev ; docker build -t bref/php-73-fpm-dev --build-arg PHP_VERSION=73 .
	cd layers/web; docker build -t bref/fpm-dev-gateway .
	# Run tests
	php layers/tests.php
