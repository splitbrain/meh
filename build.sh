#!/usr/bin/env sh
set -e

# This installs and builds the backend and frontend dependencies, then copies over the minimum required files
# to the release directory. The release directory is used to create the Docker image and and for distribution.
#
# For development, you can serve the application as is from the main directory as shown in the quick start
# guide in the README.md file.


# Install backend dependencies
cd backend
composer install --no-interaction --no-progress --no-suggest --no-dev
cd ..

# Install and build frontend dependencies
cd frontend
npm install
npm run build
cd ..

# remove previous build if exists
if [ -d "release" ]; then
  rm -rf release
fi

# create release directory
mkdir release

# copy documentation
cp -r doc release/
for file in frontend/src/components/*/readme.md; do
  dir_name=$(dirname "$file")
  mkdir -p "release/$dir_name"
  cp "$file" "release/$dir_name/"
done
cp README.md release/

# copy backend
cp -r backend release/
rm -rf release/backend/tests
rm -rf release/backend/vendor/bin
rm -rf release/backend/.gitignore
rm -rf release/backend/composer.json
rm -rf release/backend/composer.lock
rm -rf release/backend/phpunit.xml
rm -rf release/backend/rector.php
rm -rf release/backend/vendor/*/*/tests/

# copy frontend
mkdir -p release/frontend/dist
cp -r frontend/dist/meh release/frontend/dist/

# copy web files
cp -r public release/
cp  meh.svg release/

# copy other files
cp meh release/
cp docker-entrypoint.sh release/

# make sure the data directory exists
mkdir -p release/data
