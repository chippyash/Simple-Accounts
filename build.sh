#!/bin/bash
cd ~/Projects/chippyash/source/Accounts
vendor/phpunit/phpunit/phpunit -c test/phpunit.xml --testdox-html contract.html test/
tdconv -t "Chippyash Simple Accounts" contract.html docs/Test-Contract.md
rm contract.html

