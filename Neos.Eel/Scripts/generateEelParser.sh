#!/usr/bin/env bash
SCRIPTPATH=`dirname $0`

php ${SCRIPTPATH}/../Resources/Private/PHP/php-peg/cli.php ${SCRIPTPATH}/../Resources/Private/Grammar/AbstractParser.peg.inc ${SCRIPTPATH}/../Classes/AbstractParser.php
php ${SCRIPTPATH}/../Resources/Private/PHP/php-peg/cli.php ${SCRIPTPATH}/../Resources/Private/Grammar/Eel.peg.inc ${SCRIPTPATH}/../Classes/EelParser.php

php ${SCRIPTPATH}/../Resources/Private/PHP/php-peg/cli.php ${SCRIPTPATH}/../Resources/Private/Grammar/Fizzle.peg.inc ${SCRIPTPATH}/../Classes/FlowQuery/FizzleParser.php
