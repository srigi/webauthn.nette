#!/usr/bin/env bash

__DIR__="$(cd "$(dirname "$BASH_SOURCE")" >/dev/null 2>&1 && pwd)"
source ${__DIR__}/lib.sh
source ${__DIR__}/.env

LOCAL_PORT=7777

function ssh_into_app-service {
	az webapp create-remote-connection \
		--subscription "$1" \
		--resource-group "$2" \
		--name "$3" \
		--port ${LOCAL_PORT}
}

ssh_into_app-service "${SUBSCRIPTION}" "${RESOURCE_GROUP_APP}" "${APP_SERVICE_NAME}"
