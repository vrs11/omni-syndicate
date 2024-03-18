include mk/docker.common.mk

## up	:	Start up containers.
.PHONY: up
up:
	@echo "Starting up containers for $(PROJECT_NAME)..."
	docker-compose pull
	docker-compose -f docker-compose.yml -f docker-compose.darwin.yml up -d --remove-orphans
	@echo "Starting up syncs for $(PROJECT_NAME)..."
	@mutagen sync create --name="$(shell tr '_' '-' <<< '$(PROJECT_NAME)-php')" --label $(PROJECT_NAME) \
		"$(PWD)" "docker://wodby@$(PROJECT_NAME)_php/var/www/html" \
		--sync-mode two-way-resolved

## down	:	Stop containers.
.PHONY: down
down: stop

## stop	:	Stop containers.
.PHONY: stop
stop:
	@echo "Stopping containers for $(PROJECT_NAME)..."
	@docker-compose stop
	@echo "Shouting down syncs for $(PROJECT_NAME)..."
	@mutagen sync terminate "$(shell tr '_' '-' <<< '$(PROJECT_NAME)-php')"

## syncs	:	Syncs list.
.PHONY: syncs
syncs:
	@mutagen sync list --label-selector $(PROJECT_NAME)
