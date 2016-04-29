#!/usr/bin/env bash

# Get ApiGen.phar
wget http://www.apigen.org/apigen.phar

# Set identity
git config --global user.email "travis@travis-ci.org"
git config --global user.name "Travis"

# Init project
git clone --branch gh-pages --depth 1 https://${GH_TOKEN}@github.com/elboletaire/Watimage.git ../gh-pages
rm -fr ../gh-pages/api

# Generate Api
php apigen.phar generate \
  --source src \
  --destination ../gh-pages/api \
  --template-theme bootstrap \
  --title "Watimage API"

cd ../gh-pages/

# Push generated files
git add .
git commit -m "API updated"
git push origin gh-pages -q > /dev/null
