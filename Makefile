DEBUG=1

ifeq "$(DEBUG)" "0"
    REDIRECT=>/dev/null
endif

docker-clean:
	@docker kill phpstan $(REDIRECT) 2>/dev/null || :
	@docker rm -f phpstan $(REDIRECT) 2>/dev/null || :

lint: docker-clean
	@docker run --name phpstan -t --rm -v $(PWD):/app/phlesk/ phpstan:latest

stylecheck: docker-clean
	@docker run --name phpstan -t --rm -v $(PWD):/app/phlesk/ phpstan:latest \
		/composer/vendor/bin/phpcs -s /app/phlesk/src/

