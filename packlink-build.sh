#!/bin/bash
set -e

GREEN='\033[0;32m'
NOCOLOR='\033[0m'

# Cleanup any leftovers
echo -e "${GREEN}Cleaning up...${NOCOLOR}"
rm -rf ./Packlink.zip
rm -rf ./build

# Create deployment source
echo -e "${GREEN}STEP 1:${NOCOLOR} Copying plugin source..."
mkdir build
cp -R ./Packlink ./build/Packlink

echo -e "${GREEN}STEP 2:${NOCOLOR} Installing composer dependencies..."
cd build/Packlink
rm -rf vendor
composer install --no-dev
cd ../..

# Remove unnecessary files from final release archive
echo -e "${GREEN}STEP 3:${NOCOLOR} Removing unnecessary files from final release archive..."
rm -rf build/Packlink/Lib
rm -rf build/Packlink/Tests
rm -rf build/Packlink/phpunit.xml
# Core is now part of the integration
rm -rf build/Packlink/vendor/packlink
rm -rf build/Packlink/composer.json
rm -rf build/Packlink/composer.lock
rm -rf build/Packlink/integrate-core.sh

# Create plugin archive
echo -e "${GREEN}STEP 4:${NOCOLOR} Creating new archive..."
cd build/

zip -r -q  Packlink.zip Packlink/

cd ../

cp build/Packlink.zip Packlink.zip
rm build/Packlink.zip
