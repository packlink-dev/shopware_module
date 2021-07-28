#!/bin/bash
set -e

GREEN='\033[0;32m'
NOCOLOR='\033[0m'

# Cleanup any leftovers
echo -e "${GREEN}Cleaning up...${NOCOLOR}"
rm -rf ./Packlink.zip
rm -rf ./deploy

# Create deployment source
echo -e "${GREEN}STEP 1:${NOCOLOR} Copying plugin source..."
mkdir deploy
cp -R ./Packlink ./deploy/Packlink

echo -e "${GREEN}STEP 2:${NOCOLOR} Installing composer dependencies..."
cd deploy/Packlink
rm -rf vendor
composer install --no-dev
cd ../..

# Remove unnecessary files from final release archive
echo -e "${GREEN}STEP 3:${NOCOLOR} Removing unnecessary files from final release archive..."
rm -rf deploy/Packlink/Lib
rm -rf deploy/Packlink/Tests
rm -rf deploy/Packlink/phpunit.xml
rm -rf deploy/Packlink/Resources/views/backend/_resources/packlink/countries/fromCSV.php
rm -rf deploy/Packlink/Resources/views/backend/_resources/packlink/countries/toCSV.php
# Core is now part of the integration
rm -rf deploy/Packlink/vendor/packlink
rm -rf deploy/Packlink/composer.json
rm -rf deploy/Packlink/composer.lock
rm -rf deploy/Packlink/integrate-core.sh

# get plugin version
echo -e "\e[32mSTEP 4:\e[0m Reading module version..."

version="$1"
if [ "$version" = "" ]; then
    version=$(php -r "echo json_decode(file_get_contents('Packlink/composer.json'), true)['version'];")
    if [ "$version" = "" ]; then
        echo "Please enter new plugin version (leave empty to use root folder as destination) [ENTER]:"
        read version
    else
      echo -e "\e[35mVersion read from the composer.json file: $version\e[0m"
    fi
fi

# Create plugin archive
echo -e "\e[32mSTEP 5:\e[0m Creating new archive..."
php bin/sw.phar plugin:zip:dir $PWD/deploy/Packlink/ -q

if [ "$version" != "" ]; then
    if [ ! -d ./PluginInstallation/ ]; then
        mkdir ./PluginInstallation/
    fi
    if [ ! -d ./PluginInstallation/"$version"/ ]; then
        mkdir ./PluginInstallation/"$version"/
    fi

    mv ./Packlink.zip ./PluginInstallation/${version}/
    touch "./PluginInstallation/$version/Release notes $version.txt"
    echo -e "\e[34;5;40mSUCCESS!\e[0m"
    echo -e "\e[93mNew release created under: $PWD/PluginInstallation/$version"
else
    echo -e "\e[40;5;34mSUCCESS!\e[0m"
    mv Packlink.zip ./bin/Packlink.zip
    echo -e "\e[93mNew plugin archive created: $PWD/bin/Packlink.zip"
fi

rm -fR ./deploy
