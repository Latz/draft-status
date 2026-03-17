#!/bin/bash

# This script sets up the WordPress testing environment.
#
# You'll need a database, and the following environment variables:
# DB_NAME, DB_USER, DB_PASS, DB_HOST

DB_NAME=${DB_NAME:-wordpress_test}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}
DB_HOST=${DB_HOST:-localhost}
DB_NAME=${DB_NAME:-wordpress_tests}
DB_USER=${DB_USER:-latz}
DB_PASS=${DB_PASS:-x}
DB_HOST=${DB_HOST:-db}
WP_VERSION=${WP_VERSION:-latest}
WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}

set -ex

# Download test library
if [[ ! -d "$WP_TESTS_DIR" ]] || [[ ! -f "$WP_TESTS_DIR/wp-tests-config-sample.php" ]]; then
	# Download WordPress test library
	echo "Downloading WordPress test library..."
	mkdir -p "$WP_TESTS_DIR"
	if [[ "$WP_VERSION" == 'latest' ]]; then
		REPO_URL="https://develop.svn.wordpress.org/trunk"
	else
		REPO_URL="https://develop.svn.wordpress.org/tags/${WP_VERSION}"
	fi
	svn co --quiet "${REPO_URL}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
	svn co --quiet "${REPO_URL}/tests/phpunit/data/" "$WP_TESTS_DIR/data"
	svn co --quiet "${REPO_URL}/src/" "$WP_TESTS_DIR/src"
	svn export --quiet --force "${REPO_URL}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config-sample.php"
fi

if [[ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]]; then
	# Copy sample config file
	cp "$WP_TESTS_DIR/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"

	# Configure database settings
	sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
	sed -i "s|localhost|$DB_HOST|" "$WP_TESTS_DIR/wp-tests-config.php"
fi

echo "Test setup complete."
echo "Run tests with 'composer test' or 'vendor/bin/phpunit'."
