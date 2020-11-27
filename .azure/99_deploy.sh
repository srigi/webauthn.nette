#!/usr/bin/env bash

__DIR__="$(cd "$(dirname "$BASH_SOURCE")" >/dev/null 2>&1 && pwd)"
source ${__DIR__}/lib.sh
source ${__DIR__}/../.env
source ${__DIR__}/.env

GIT_DEFAULT_BRANCH='master'
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
GIT_REV=$(git rev-parse HEAD)


function check_git_branch {
	if [[ $1 != "$GIT_DEFAULT_BRANCH" ]]; then
		printf "> ${COLOR_RED}you are not on default branch!${NC} Do you want to deploy current branch ${COLOR_BLUE}${GIT_BRANCH}${NC}? [y/N]: ";
		read -r INP
		if [[ ${INP} != 'y' && $INP != 'Y' ]]; then
			exit 1
		fi
	fi
}

function create_webapp {
	printf "Checking existence of ${COLOR_YELLOW}webapp${NC} ${COLOR_BLUE}$3${NC}... "
	if ! az webapp show --resource-group "$1" --name "$3" --output none 2>/dev/null; then
		echo -e "${COLOR_RED}Missing!${NC}"
		echo -e "Creating ${COLOR_YELLOW}webapp${NC} ${COLOR_BLUE}$3${NC} under app-service plan ${COLOR_BLUE}$2${NC}:"
			printf "  - setting-up ${COLOR_GREEN}deployment user${NC} ${COLOR_BLUE}${DEPLOYMENT_USER}${NC}... "
			az webapp deployment user set \
				--user-name "${DEPLOYMENT_USER}" \
				--password "${DEPLOYMENT_PASSWORD}" \
				--output none
			echo "done"

			printf "  - creating ${COLOR_GREEN}webapp${NC} ${COLOR_BLUE}$3${NC}... "
			git_repo=$(az webapp create \
				--resource-group "$1" \
				--plan "$2" \
				--name "$3" \
				--runtime "${APP_SERVICE_RUNTIME}" \
				--deployment-local-git \
				--query deploymentLocalGitUrl \
				--output tsv)
			echo "done"

			if [[ ${SERVICE_PLAN_SKU} != "F1" && ${SERVICE_PLAN_SKU} != "FREE" ]]; then
				printf "  - configuring ${COLOR_GREEN}always-on${NC} for webapp ${COLOR_BLUE}$3${NC}... "
				az webapp config set \
					--resource-group "$1" \
					--name "$3" \
					--always-on true \
					--output none
				echo "done"
			fi

			git remote rm azure 2>/dev/null
			git remote add azure "${git_repo}"
		echo -e "Webapp ${COLOR_GREEN}Ready!${NC}"
	else
		echo -e "${COLOR_GREEN}OK${NC}"
	fi
}

function check_update_env {
	printf "> update ${COLOR_YELLOW}webapp's${NC} ${COLOR_BLUE}ENV vars${NC}? [y/N]: "
	read -r INP
	if [[ ${INP} == 'y' || $INP == 'Y' ]]; then
		printf "Configuring ${COLOR_GREEN}app settings${NC} for webapp ${COLOR_BLUE}$2${NC}... "
		az webapp config appsettings set \
			--resource-group "$1" \
			--name "$2" \
			--settings \
				DATABASE_URL="${DATABASE_URL}" \
				TIMEZONE="${TIMEZONE}" \
			--output none
		echo -e "${COLOR_GREEN}done${NC}... waiting 10s"
		sleep 10
	fi
}

function deploy_sources {
	echo -e "Deploying branch ${COLOR_YELLOW}$3${NC} with rev ${COLOR_GREEN}$4${NC} to webapp ${COLOR_BLUE}$3${NC}..."
	git push -f azure "$3":master

	printf "Updating ${COLOR_GREEN}GIT_REV${NC} for webapp ${COLOR_BLUE}$2${NC} to ${COLOR_YELLOW}${GIT_REV}${NC}... "
	az webapp config appsettings set \
		--resource-group "$1" \
		--name "$2" \
		--settings GIT_REV="$4" \
		--output none
	echo -e "${COLOR_GREEN}done${NC}"
}

check_git_branch "${GIT_BRANCH}"
create_webapp "${RESOURCE_GROUP_APP}" "${SERVICE_PLAN_NAME}" "${APP_SERVICE_NAME}"
check_update_env "${RESOURCE_GROUP_APP}" "${APP_SERVICE_NAME}"
deploy_sources "${RESOURCE_GROUP_APP}" "${APP_SERVICE_NAME}" "${GIT_BRANCH}" "${GIT_REV}"
echo -e "All deployment tasks ${COLOR_GREEN}completed successfully!${NC}"
