#!/bin/bash
set -e

GREEN='\033[0;32m'
NOCOLOR='\033[0m'

# Cleanup existing core
echo -e "${GREEN}Cleaning up...${NOCOLOR}"
rm -rf Infrastructure
rm -rf BusinessLogic
rm -rf Tests/Core

# Copy core source
echo -e "${GREEN}STEP 1:${NOCOLOR} Copying core source..."

mkdir Infrastructure
mkdir BusinessLogic
mkdir Tests/Core

cp -R ./vendor/packlink/integration-core/src/BusinessLogic/* BusinessLogic
cp -R ./vendor/packlink/integration-core/src/Infrastructure/* Infrastructure
cp -R ./vendor/packlink/integration-core/tests/* Tests/Core

# Update namespaces
echo -e "${GREEN}STEP 2:${NOCOLOR} Update namespaces..."
grep -rl 'Logeecom\\Infrastructure' BusinessLogic | xargs sed -i s^'Logeecom\\Infrastructure'^'Packlink\\Infrastructure'^g
grep -rl 'Logeecom\\Infrastructure' Infrastructure | xargs sed -i s^'Logeecom\\Infrastructure'^'Packlink\\Infrastructure'^g
grep -rl 'Logeecom\\Infrastructure' Tests/Core | xargs sed -i s^'Logeecom\\Infrastructure'^'Packlink\\Infrastructure'^g
grep -rl 'Logeecom\\Tests' Tests/Core | xargs sed -i s^'Logeecom\\Tests'^'Packlink\\Tests\\Core'^g
grep -rl 'Logeecom' Tests/Core | xargs sed -i s^'Logeecom'^'Packlink'^g