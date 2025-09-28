#!/bin/bash

# Path to the WordPress installation
WP_PATH="/path/to/wordpress"

# Run the import_products method
# shellcheck disable=SC2016
wp eval '$spa = new spa(); $spa->products->import_products();' --path=$WP_PATH