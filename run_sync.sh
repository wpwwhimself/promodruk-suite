#!/bin/bash

cd wpww_repos/promodruk-magazyn && php83 artisan schedule:run >> /dev/null 2>&1
