#!/bin/bash -e

# Get the directory with the repo
SOURCE="${BASH_SOURCE[0]}"
BASEDIR="$(dirname $(dirname $(realpath ${SOURCE})))"

# Directory where files should be placed
TARGET="$1"

if [[ -z "${TARGET}" || ! -d "${TARGET}" ]]; then
	echo "Usage: $0 targetdir"
	echo "  Where the target directory is the directory where the github checkout resides"
fi

# Determine version to add
cd "${BASEDIR}"
VERSION="$(git describe --abbrev=0)"

echo "About to release PHP SDK version ${VERSION}..."

# First, remove everything currently in the target directory
rm -Rf "${TARGET}"/*

# Now copy everything from the source to the target
cp -a "${BASEDIR}"/* "${TARGET}"

# Get rid of package and tools directory
rm -Rf "${TARGET}"/package "${TARGET}"/tools

# Commit and create tag
cd "${TARGET}"
git add .
git commit -m "Release version ${VERSION}"
git tag -a "${VERSION}" -m "StreamOne PHP SDK version ${VERSION}"

echo "Done! Check ${TARGET} to see if it went OK and push to github"
