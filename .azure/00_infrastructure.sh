#!/usr/bin/env bash

__DIR__="$(cd "$(dirname "$BASH_SOURCE")" >/dev/null 2>&1 && pwd)"
source ${__DIR__}/_common.sh
source ${__DIR__}/.env

databaseServerFqdn=''

function create_resource_group {
	printf "Checking existence of ${COLOR_YELLOW}resource group${NC} ${COLOR_BLUE}$1${NC}... "
	az group show --name "$1" --output none 2>/dev/null
	if [[ $? != 0 ]]; then
		echo -e "${COLOR_RED}Missing!${NC}"
		printf "Creating ${COLOR_YELLOW}resource group${NC} ${COLOR_BLUE}$1${NC}... "
		az group create \
			--location "${REGION}" \
			--name "$1" \
			--output none
	fi
	echo -e "${COLOR_GREEN}OK${NC}"
}

function create_database_server {
	printf "Checking existence of ${COLOR_YELLOW}SQL database server${NC} ${COLOR_BLUE}$2${NC} in resource group ${COLOR_BLUE}$1${NC}... "
	az sql server show --resource-group "$1" --name "$2" --output none 2>/dev/null
	if [[ $? != 0 ]]; then
		echo -e "${COLOR_RED}Missing!${NC}"
		printf "Creating ${COLOR_YELLOW}SQL database server${NC} ${COLOR_BLUE}$2${NC}... "
		databaseServerFqdn=$(az sql server create \
			--resource-group "$1" \
			--name "$2" \
			--admin-user "$3" \
			--admin-password "$4" \
			--query fullyQualifiedDomainName \
			--output tsv)
		echo "done"

		printf "Creating ${COLOR_YELLOW}SQL server firewall rule${NC} ${COLOR_BLUE}default${NC} (allows access from Azure services)... "
		az sql server firewall-rule create \
			--resource-group "$1" \
			--server "$2" \
			--name default \
			--start-ip-address 0.0.0.0 \
			--end-ip-address 0.0.0.0 \
			--output none
		echo "done"

		printf "Creating ${COLOR_YELLOW}SQL server firewall rule${NC} ${COLOR_BLUE}$5${NC}... "
		az sql server firewall-rule create \
			--resource-group "$1" \
			--server "$2" \
			--name "$5" \
			--start-ip-address "$6" \
			--end-ip-address "$7" \
			--output none
		echo "done"
	else
		echo -e "${COLOR_GREEN}OK${NC}"
		databaseServerFqdn=$(az sql server show \
			--resource-group "$1" \
			--name "$2" \
			--query fullyQualifiedDomainName\
			--output tsv)
	fi
}

function create_app_service_plan {
	printf "Checking existence of ${COLOR_YELLOW}app-service plan${NC} ${COLOR_BLUE}$2${NC} in resource group ${COLOR_BLUE}$1${NC}... "
	res=$(az appservice plan show --resource-group "$1" --name "$2")
	if [[ -z ${res} ]]; then
		echo -e "${COLOR_RED}Missing!${NC}"
		printf "Creating ${COLOR_YELLOW}app service plan${NC} ${COLOR_BLUE}$2${NC} in resource group ${COLOR_BLUE}$1${NC}... "
		az appservice plan create \
			--resource-group "$1" \
			--name "$2" \
			--is-linux \
			--sku "${SERVICE_PLAN_SKU}" \
			--output none
	fi
	echo -e "${COLOR_GREEN}OK${NC}"
}

function create_database {
	printf "Checking existence of ${COLOR_YELLOW}SQL database${NC} ${COLOR_BLUE}$3${NC} at SQL server ${COLOR_BLUE}$2${NC}... "
	az sql db show --resource-group "$1" --server "$2" --name "$3" --output none 2>/dev/null
	if [[ $? != 0 ]]; then
		echo -e "${COLOR_RED}Missing!${NC}"
		printf "Creating ${COLOR_YELLOW}SQL database${NC} ${COLOR_BLUE}$3${NC}... "
		az sql db create \
			--resource-group "$1" \
			--server "$2" \
			--name "$3" \
			--service-objective "$4" \
			--output none
	fi
	echo -e "${COLOR_GREEN}OK${NC}"
}

function create_app_service_plan {
	printf "Checking existence of ${COLOR_YELLOW}app-service plan${NC} ${COLOR_BLUE}$2${NC} in resource group ${COLOR_BLUE}$1${NC}... "
	res=$(az appservice plan show --resource-group "$1" --name "$2")
	if [[ -z ${res} ]]; then
		echo -e "${COLOR_RED}Missing!${NC}"
		printf "Creating ${COLOR_YELLOW}app service plan${NC} ${COLOR_BLUE}$2${NC} in resource group ${COLOR_BLUE}$1${NC}... "
		az appservice plan create \
			--resource-group "$1" \
			--name "$2" \
			--is-linux \
			--sku ${SERVICE_PLAN_SKU} \
			--output none
	fi
	echo -e "${COLOR_GREEN}OK${NC}"
}


create_resource_group "${RESOURCE_GROUP_APP}"
create_database_server ${RESOURCE_GROUP_APP} ${DATABASE_SERVER_NAME} \
	${DATABASE_SERVER_ADMIN_USER} \
	${DATABASE_SERVER_ADMIN_PASSWORD} \
	${DATABASE_SERVER_FIREWALL_RULE_NAME} \
	${DATABASE_SERVER_FIREWALL_START_IP_ADDRESS} \
	${DATABASE_SERVER_FIREWALL_END_IP_ADDRESS}
create_database ${RESOURCE_GROUP_APP} ${DATABASE_SERVER_NAME} ${DATABASE_NAME} ${DATABASE_SKU}
	echo -e "  ${COLOR_BLUE}DATABASE_URL=${COLOR_GREEN}sqlsrv:Server=${databaseServerFqdn},1433;Database=${DATABASE_NAME}${NC}"
	echo -e "  ${COLOR_BLUE}DATABASE_USER=${COLOR_GREEN}${DATABASE_SERVER_ADMIN_USER}${NC}"
	echo -e "  ${COLOR_BLUE}DATABASE_PASSWORD=${COLOR_GREEN}${DATABASE_SERVER_ADMIN_PASSWORD}${NC}\n"
create_app_service_plan ${RESOURCE_GROUP_APP} ${SERVICE_PLAN_NAME}
echo -e "All infrastructure tasks ${COLOR_GREEN}completed successfully!${NC}"
