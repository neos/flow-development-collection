#!/usr/bin/env bash

cd ../Resources/Private
git clone git://github.com/skurfuerst/php-peg.git
rm -Rf php-peg/.git
rm -Rf PHP/php-peg
mv php-peg PHP/
