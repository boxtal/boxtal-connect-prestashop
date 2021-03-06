#!/usr/bin/env bash

PHP_VERSION=${1-5.6}
PS_VERSION=${2-1.7.3.4}
MULTISTORE=${3-0}

if [[ $MULTISTORE = "1" ]]; then
  APPEND="-multistore"
fi

docker run -di -p 80:80 -e APIURL=$APIURL -e ONBOARDINGURL=$ONBOARDINGURL --name "boxtal_connect_prestashop" 890731937511.dkr.ecr.eu-west-1.amazonaws.com/boxtal-prestashop:$PHP_VERSION-$PS_VERSION$APPEND
