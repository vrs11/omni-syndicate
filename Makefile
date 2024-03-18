.PHONY: test

ENV ?= linux
DRUPAL_VER ?= 10
PHP_VER ?= 8.3

ifeq ($(shell uname -s),Darwin)
	ENV = darwin
endif

include mk/docker.$(ENV).mk

test:
	cd ./tests/$(DRUPAL_VER) && PHP_VER=$(PHP_VER) ./run.sh
