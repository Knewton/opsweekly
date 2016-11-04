# Contribute

1. Fork the repo
2. Clone your fork
3. Hack away
4. If you are adding new functionality, document it in the README
5. If necessary, rebase your commits into logical chunks, without errors
6. Verify your code by running the test suite, and adding additional tests if able.
7. Push the branch up to GitHub
8. Send a pull request

We'll do our best to get your changes in!

## How to run tests
The tests are written for phpunit which is installed via composer. Run them
like this:

```
% composer install
% ./vendor/bin/phpunit tests/*
```

## Running locally

1. Add a secureconfig.php file to the phplib folder with contents like

```
<?php

// Login details for the MySQL database, where all the data is stored.
// The empty database schema is stored in opsweekly.sql
$mysql_credentials = array(
    "username" => "root",
    "password" => "insecure"
);

$pagerduty_credentials = array(
    "apikey" => "YOUR PD API KEY"
)
```

2. Install docker and docker-machine as described at https://confluence.knewton.net/x/7IKTB
3. Create and start a docker container to host mysql `./bin/setup.sh`
4. Create and start a docker container hosting the opsweekly website `./bin/run.sh`. This script
   can also be used to redeploy after making code changes.
5. Visit http://boot2docker in your browser