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
The tests are written for phpunit which is installed via composer. Install composer following
the instructions [here](https://getcomposer.org/download/). Run the tests like this:

```
% composer install
% ./vendor/bin/phpunit tests/*
```

## Running locally
1. Install docker and docker-machine as described [here](https://confluence.knewton.net/x/7IKTB).
2. Build the project.

    ```nop p b```

3. Setup aws credential environment variables. These will be used by nop to download our database
and pagerduty credentials. Run the export commands output by the command:

    ```kva aws -p dev s3r```

4. Start opsweekly with dependencies in docker-local.

    ```nop p u -d docker-local```

5. Manually create the sql schema for the mysql database.

    ```mysql -u root --password=insecure -h boot2docker opsweekly < opsweekly.sq```

6. Start opsweekly again, this time specifying aws environment keys and not re-launching
dependencies.

    ```nop p u docker-local --pass-aws-env-vars```

7. Visit [http://boot2docker:41811](http://boot2docker:41811) in your browser.