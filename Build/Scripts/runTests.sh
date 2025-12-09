#!/usr/bin/env bash

#
# TYPO3 extension nr_extension_scanner_cli test runner based on docker/podman.
#

set -e

if [ "${CI}" != "true" ]; then
    trap 'echo "runTests.sh SIGINT signal emitted"; cleanUp; exit 2' SIGINT
fi

cleanUp() {
    if [ -n "${NETWORK}" ]; then
        ATTACHED_CONTAINERS=$(${CONTAINER_BIN} ps --filter network=${NETWORK} --format='{{.Names}}' 2>/dev/null || true)
        for ATTACHED_CONTAINER in ${ATTACHED_CONTAINERS}; do
            ${CONTAINER_BIN} kill ${ATTACHED_CONTAINER} >/dev/null 2>&1 || true
        done
        ${CONTAINER_BIN} network rm ${NETWORK} >/dev/null 2>&1 || true
    fi
}

loadHelp() {
    # read -r -d '' returns exit code 1, so we need || true to prevent set -e from killing us
    read -r -d '' HELP <<EOF || true
TYPO3 extension test runner. Execute unit, functional and other test suites in
a container based test environment.

Usage: $0 [options] [file]

Options:
    -s <...>
        Specifies which test suite to run
            - cgl: Check code style with PHP-CS-Fixer (dry-run)
            - cglFix: Fix code style with PHP-CS-Fixer
            - clean: Clean up build and cache files
            - composer: Run composer command with remaining arguments
            - composerUpdate: Update dependencies for specified TYPO3 version
            - lint: PHP syntax check
            - phpstan: Run PHPStan static analysis
            - unit (default): Run unit tests

    -t <12|13|14>
        TYPO3 core version to use
            - 12: TYPO3 v12.4 LTS
            - 13: (default) TYPO3 v13.4 LTS
            - 14: TYPO3 v14.0

    -p <8.2|8.3|8.4|8.5>
        PHP version to use
            - 8.2: (default) PHP 8.2
            - 8.3: PHP 8.3
            - 8.4: PHP 8.4
            - 8.5: PHP 8.5

    -b <docker|podman>
        Container runtime to use
            - docker: Use Docker
            - podman: Use Podman

    -x
        Enable Xdebug for debugging (default port 9003)

    -u
        Update container images

    -h
        Show this help

Examples:
    # Run unit tests with PHP 8.2 and TYPO3 13
    ./Build/Scripts/runTests.sh

    # Run unit tests with PHP 8.4 and TYPO3 14
    ./Build/Scripts/runTests.sh -p 8.4 -t 14

    # Run PHPStan
    ./Build/Scripts/runTests.sh -s phpstan

    # Run PHPStan with TYPO3 12
    ./Build/Scripts/runTests.sh -s phpstan -t 12

    # Fix code style
    ./Build/Scripts/runTests.sh -s cglFix

    # Update dependencies for TYPO3 14
    ./Build/Scripts/runTests.sh -s composerUpdate -t 14
EOF
}

# Go to extension root directory
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1
cd ../../ || exit 1
ROOT_DIR="${PWD}"

# Option defaults
TEST_SUITE="unit"
TYPO3_VERSION="13"
PHP_VERSION="8.2"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
CONTAINER_BIN=""
SUFFIX=$(od -A n -t d -N 1 /dev/urandom | tr -d ' ')
NETWORK="nr-ext-scanner-${SUFFIX}"
# Detect if we're running in a TTY
if [ -t 0 ]; then
    CONTAINER_INTERACTIVE="-it --init"
else
    CONTAINER_INTERACTIVE="--init"
fi
HOST_UID=$(id -u)
USERSET=""

# Option parsing
OPTIND=1
INVALID_OPTIONS=()
while getopts "s:t:p:b:xuh" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        t)
            TYPO3_VERSION=${OPTARG}
            if ! [[ ${TYPO3_VERSION} =~ ^(12|13|14)$ ]]; then
                INVALID_OPTIONS+=("-t ${OPTARG}")
            fi
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8\.2|8\.3|8\.4|8\.5)$ ]]; then
                INVALID_OPTIONS+=("-p ${OPTARG}")
            fi
            ;;
        b)
            CONTAINER_BIN=${OPTARG}
            if ! [[ ${CONTAINER_BIN} =~ ^(docker|podman)$ ]]; then
                INVALID_OPTIONS+=("-b ${OPTARG}")
            fi
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        u)
            TEST_SUITE=update
            ;;
        h)
            loadHelp
            echo "${HELP}"
            exit 0
            ;;
        \?)
            INVALID_OPTIONS+=("-${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("-${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "  ${I}" >&2
    done
    echo >&2
    echo "Use \"$0 -h\" to display help" >&2
    exit 1
fi

# Map TYPO3 version to constraint
case ${TYPO3_VERSION} in
    12)
        TYPO3_CONSTRAINT="^12.4"
        TESTING_FRAMEWORK="^8.0"
        ;;
    13)
        TYPO3_CONSTRAINT="^13.4"
        TESTING_FRAMEWORK="^8.0"
        ;;
    14)
        TYPO3_CONSTRAINT="^14.0"
        TESTING_FRAMEWORK="^9.0"
        ;;
esac

# CI mode: non-interactive
if [ "${CI}" == "true" ]; then
    CONTAINER_INTERACTIVE=""
fi

# Determine container binary
if [[ -z "${CONTAINER_BIN}" ]]; then
    if type "docker" >/dev/null 2>&1; then
        CONTAINER_BIN="docker"
    elif type "podman" >/dev/null 2>&1; then
        CONTAINER_BIN="podman"
    fi
fi

if ! type "${CONTAINER_BIN}" >/dev/null 2>&1; then
    echo "Container runtime \"${CONTAINER_BIN}\" not found. Install docker or podman." >&2
    exit 1
fi

# Set user for docker on Linux
if [ "$(uname)" != "Darwin" ] && [ "${CONTAINER_BIN}" == "docker" ]; then
    USERSET="--user ${HOST_UID}"
fi

# Create cache directory
mkdir -p .cache

# Container images
IMAGE_PHP="ghcr.io/typo3/core-testing-$(echo "php${PHP_VERSION}" | sed -e 's/\.//'):latest"

# Shift past options
shift $((OPTIND - 1))

# Create network
${CONTAINER_BIN} network create ${NETWORK} >/dev/null 2>&1 || true

# Common container params
if [ "${CONTAINER_BIN}" == "docker" ]; then
    CONTAINER_HOST="host.docker.internal"
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} --add-host ${CONTAINER_HOST}:host-gateway ${USERSET} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
else
    CONTAINER_HOST="host.containers.internal"
    CONTAINER_COMMON_PARAMS="${CONTAINER_INTERACTIVE} --rm --network ${NETWORK} -v ${ROOT_DIR}:${ROOT_DIR} -w ${ROOT_DIR}"
fi

# Xdebug configuration
if [ ${PHP_XDEBUG_ON} -eq 0 ]; then
    XDEBUG_MODE="-e XDEBUG_MODE=off"
    XDEBUG_CONFIG=""
else
    XDEBUG_MODE="-e XDEBUG_MODE=debug -e XDEBUG_TRIGGER=foo"
    XDEBUG_CONFIG="-e XDEBUG_CONFIG=client_port=${PHP_XDEBUG_PORT},client_host=${CONTAINER_HOST}"
fi

# Suite execution
case ${TEST_SUITE} in
    cgl)
        echo "Running PHP-CS-Fixer (dry-run)..."
        COMMAND=".Build/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    cglFix)
        echo "Fixing code style with PHP-CS-Fixer..."
        COMMAND=".Build/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name cgl-fix-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    clean)
        echo "Cleaning up..."
        rm -rf .Build .cache .php-cs-fixer.cache var
        echo "Done"
        SUITE_EXIT_CODE=0
        ;;
    composer)
        COMMAND="composer $*"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    composerUpdate)
        echo "Updating dependencies for TYPO3 ${TYPO3_CONSTRAINT}..."
        # Backup composer files, update, then restore originals
        COMMAND="cp composer.json composer.json.bak && cp composer.lock composer.lock.bak 2>/dev/null || true && composer require typo3/cms-core:${TYPO3_CONSTRAINT} typo3/cms-install:${TYPO3_CONSTRAINT} typo3/testing-framework:${TESTING_FRAMEWORK} --dev --no-progress --no-interaction -W && cp composer.json.bak composer.json && cp composer.lock.bak composer.lock 2>/dev/null || true && rm -f composer.json.bak composer.lock.bak"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name composer-update-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    lint)
        echo "Running PHP lint..."
        COMMAND="find Classes Tests -name '*.php' -exec php -l {} \;"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name lint-${SUFFIX} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    phpstan)
        echo "Running PHPStan with TYPO3 ${TYPO3_CONSTRAINT}..."
        # Backup composer files, update deps for target TYPO3 (including testing-framework for lock compatibility), run PHPStan, restore originals
        COMMAND="cp composer.json composer.json.bak && cp composer.lock composer.lock.bak 2>/dev/null || true && composer require typo3/cms-core:${TYPO3_CONSTRAINT} typo3/cms-install:${TYPO3_CONSTRAINT} typo3/testing-framework:${TESTING_FRAMEWORK} --dev --no-progress --no-interaction -W && .Build/bin/phpstan analyse -c Build/phpstan/phpstan.neon; RESULT=\$?; cp composer.json.bak composer.json && cp composer.lock.bak composer.lock 2>/dev/null || true && rm -f composer.json.bak composer.lock.bak; exit \$RESULT"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name phpstan-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer ${XDEBUG_MODE} ${XDEBUG_CONFIG} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    unit)
        echo "Running unit tests with PHP ${PHP_VERSION} and TYPO3 ${TYPO3_CONSTRAINT}..."
        # Backup composer files, update deps for target TYPO3, run tests, restore originals
        COMMAND="cp composer.json composer.json.bak && cp composer.lock composer.lock.bak 2>/dev/null || true && composer require typo3/cms-core:${TYPO3_CONSTRAINT} typo3/cms-install:${TYPO3_CONSTRAINT} typo3/testing-framework:${TESTING_FRAMEWORK} --dev --no-progress --no-interaction -W && .Build/bin/phpunit -c phpunit.xml $*; RESULT=\$?; cp composer.json.bak composer.json && cp composer.lock.bak composer.lock 2>/dev/null || true && rm -f composer.json.bak composer.lock.bak; exit \$RESULT"
        ${CONTAINER_BIN} run ${CONTAINER_COMMON_PARAMS} --name unit-${SUFFIX} -e COMPOSER_CACHE_DIR=.cache/composer ${XDEBUG_MODE} ${XDEBUG_CONFIG} ${IMAGE_PHP} /bin/sh -c "${COMMAND}"
        SUITE_EXIT_CODE=$?
        ;;
    update)
        echo "Updating container images..."
        ${CONTAINER_BIN} pull ghcr.io/typo3/core-testing-php82:latest
        ${CONTAINER_BIN} pull ghcr.io/typo3/core-testing-php83:latest
        ${CONTAINER_BIN} pull ghcr.io/typo3/core-testing-php84:latest
        SUITE_EXIT_CODE=0
        ;;
    *)
        echo "Unknown test suite: ${TEST_SUITE}" >&2
        echo "Use \"$0 -h\" to display help" >&2
        SUITE_EXIT_CODE=1
        ;;
esac

cleanUp

echo ""
echo "###########################################################################"
echo "Result of ${TEST_SUITE}"
echo "PHP: ${PHP_VERSION}"
echo "TYPO3: ${TYPO3_CONSTRAINT}"
if [ ${SUITE_EXIT_CODE} -eq 0 ]; then
    echo "SUCCESS"
else
    echo "FAILURE"
fi
echo "###########################################################################"
echo ""

exit ${SUITE_EXIT_CODE}
