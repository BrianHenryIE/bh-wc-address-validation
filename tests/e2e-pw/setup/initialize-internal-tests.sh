#!/bin/bash

# Print the script name.
echo $(basename "$0")



echo "Installing latest build of bh-wc-address-validation"
wp plugin install ./setup/bh-wc-address-validation.latest.zip --activate --force