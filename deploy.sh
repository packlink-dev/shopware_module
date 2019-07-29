#!/bin/bash

echo
echo -e "\e[48;5;124m ALWAYS RUN UNIT TESTS BEFORE CREATING DEPLOYMENT PACKAGE! \e[0m"
echo
sleep 2

# Cleanup any leftovers
echo -e "\e[32mCleaning up...\e[0m"
rm -f ./bin/Packlink.zip
rm -fR ./deploy

# Create deployment source
echo -e "\e[32mSTEP 1:\e[0m Copying plugin source..."
mkdir ./deploy
cp -R ./Packlink ./deploy/Packlink

# Ensure proper composer dependencies
echo -e "\e[32mSTEP 2:\e[0m Installing composer dependencies..."
cd deploy/Packlink
# remove resources that will be copied from the core in the post-install script
rm -rf views/img/carriers/de/*
rm -rf views/img/carriers/es/*
rm -rf views/img/carriers/fr/*
rm -rf views/img/carriers/it/*
rm -rf views/js/core
rm -rf views/js/location
rm -rf vendor

composer install --no-dev
cd ../..

# Remove unnecessary files from final release archive
echo -e "\e[32mSTEP 3:\e[0m Removing unnecessary files from final release archive..."
rm -rf deploy/Packlink/lib
rm -rf deploy/Packlink/Tests
rm -rf deploy/Packlink/phpunit.xml
rm -rf deploy/Packlink/vendor/packlink/integration-core/.git
rm -rf deploy/Packlink/vendor/packlink/integration-core/.gitignore
rm -rf deploy/Packlink/vendor/packlink/integration-core/.idea
rm -rf deploy/Packlink/vendor/packlink/integration-core/tests
rm -rf deploy/Packlink/vendor/packlink/integration-core/generic_tests
rm -rf deploy/Packlink/vendor/packlink/integration-core/README.md
rm -rf deploy/Packlink/vendor/setasign/fpdf/tutorial/

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
php bin/sw.phar plugin:zip:dir $PWD/deploy/Packlink/

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
