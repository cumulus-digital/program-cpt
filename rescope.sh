#!/bin/bash
if ! command -v php-scoper &> /dev/null; then
	echo "php-scoper must be installed and available in your PATH."
	exit
fi
if ! command -v composer &> /dev/null; then
	echo "composer must be installed and available in your PATH."
	exit
fi

echo "Setting up composer vendors without dev"
composer install --no-scripts --no-dev --prefer-dist
echo "Scoping..."
mkdir build/composer
php-scoper add-prefix --working-dir ./ --output-dir ./build/composer --force
echo "Updating vendor_keep autoloader"
composer dump-autoload --working-dir build/composer --classmap-authoritative
echo "Reinstalling dev"
composer install --no-scripts --prefer-dist