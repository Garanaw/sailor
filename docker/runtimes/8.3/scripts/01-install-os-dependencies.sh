#!/bin/bash

# Install the required packages
apt-get update

apt-get install -y gnupg
apt-get install -y libbz2-dev
apt-get install -y wget
apt-get install -y gosu
apt-get install -y ca-certificates
apt-get install -y zip unzip
apt-get install -y git
apt-get install -y supervisor
apt-get install -y libcap2-bin
apt-get install -y libpng-dev
apt-get install -y python3
apt-get install -y dnsutils
apt-get install -y librsvg2-bin
apt-get install -y fswatch
apt-get install -y libcurl4-openssl-dev
apt-get install -y unixodbc unixodbc-dev
apt-get install -y libc-client-dev

