#!/bin/sh

echo "Installing dependencies"
composer install

echo "Starting Apache"
exec apache2-foreground