#!/bin/sh

set -eu  # fail hard

envsubst '\$BACKEND_SERVICE \$FCGI_READ_TIMEOUT' < ./frontcontroller.template.conf > ./frontcontroller.conf
envsubst '\$HEALTHCHECK_TOKEN' < ./maps.template.conf > ./maps.conf
cat ./nginx.template.conf > ./nginx.conf

exec nginx -c /app/nginx.conf -g 'daemon off;'
