#!/usr/bin/env bash

sudo chown -R www-data:www-data /var/www/html
sudo node_modules/gulp/bin/gulp.js css
sudo node_modules/gulp/bin/gulp.js js
sudo mkdir -p src/lib
sudo cp -R vendor/boxtal/boxtal-php-poc/src/* src/lib
sudo -H -u www-data bash -c "rm -rf /var/www/html/modules/boxtalconnect"
sudo -H -u www-data bash -c "mkdir -p /var/www/html/modules/boxtalconnect"
sudo -H -u www-data bash -c "cp -R src/* /var/www/html/modules/boxtalconnect"
sudo -H -u www-data bash -c "rm -rf /var/www/html/boxtal-unit-tests"
sudo -H -u www-data bash -c "mkdir -p /var/www/html/boxtal-unit-tests"
sudo -H -u www-data bash -c "cp -R test/unit-tests/Test*.php /var/www/html/boxtal-unit-tests"
sudo -H -u www-data bash -c "rm -rf /var/www/html/boxtal-unit-tests-helpers"
sudo -H -u www-data bash -c "mkdir -p /var/www/html/boxtal-unit-tests-helpers"
sudo -H -u www-data bash -c "cp -R test/unit-tests/helper/* /var/www/html/boxtal-unit-tests-helpers"
sudo -H -u www-data bash -c "chmod -R 775 /var/www/html"
sudo -H -u www-data bash -c "cp -R phpunit.xml /var/www/html"
sudo -H -u www-data bash -c "cp -R test/unit-tests/bootstrap.php /var/www/html"
sudo -H -u www-data bash -c "chown -R www-data:www-data /var/www/html/modules/boxtalconnect"
sudo -H -u www-data bash -c "find /var/www/html/modules/boxtalconnect -type d -exec chmod 775 {} \;"
sudo -H -u www-data bash -c "find /var/www/html/modules/boxtalconnect -type f -exec chmod 644 {} \;"
