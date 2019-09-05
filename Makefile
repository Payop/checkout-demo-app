up:
	./run-docker.sh
ps:
	docker-compose ps
rm:
	docker-compose rm
logs:
	docker-compose logs -f
kill:
	docker-compose kill
exec:
	./exec-php.sh
php:
	./exec-php.sh
nginx:
	./exec-nginx.sh
