#!/bin/bash
set -e

GREEN='\033[0;32m'
NOCOLOR='\033[0m'

# Cleanup existing core
echo -e "${GREEN}Cleaning up...${NOCOLOR}"
rm -rf Core
rm -rf Tests/Core

# Copy core source
echo -e "${GREEN}STEP 1:${NOCOLOR} Copying core source..."

mkdir Core
mkdir Tests/Core

cp -R ./vendor/packlink/integration-core/src/BusinessLogic Core/BusinessLogic
cp -R ./vendor/packlink/integration-core/src/Infrastructure Core/Infrastructure
cp -R ./vendor/packlink/integration-core/tests Tests/Core

# Update namespaces
echo -e "${GREEN}STEP 2:${NOCOLOR} Update namespaces..."
grep -rl 'Logeecom\\Infrastructure' Core | xargs sed -i s^'Logeecom\\Infrastructure'^'Packlink\\Core\\Infrastructure'^g
grep -rl 'Packlink\\BusinessLogic' Core | xargs sed -i s^'Packlink\\BusinessLogic'^'Packlink\\Core\\BusinessLogic'^g
grep -rl 'Logeecom\\Infrastructure' Tests/Core | xargs sed -i s^'Logeecom\\Infrastructure'^'Packlink\\Core\\Infrastructure'^g
grep -rl 'Packlink\\BusinessLogic' Tests/Core | xargs sed -i s^'Packlink\\BusinessLogic'^'Packlink\\Core\\BusinessLogic'^g
grep -rl 'Logeecom\\Tests' Tests/Core | xargs sed -i s^'Logeecom\\Tests'^'Packlink\\Tests\\Core'^g