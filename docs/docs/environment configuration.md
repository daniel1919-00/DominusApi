# Environment configuration

It is often helpful to have different configuration values based on the environment where your application is running. 

Dominus uses `.env` files to achieve this.

## Retrieving environment configuration values

All the variables listed in the .env file will be loaded into the $_ENV PHP super-global when your application receives a request. However, you may use the `env` function to retrieve values from these variables in your configuration files:

``` php
<?php
echo env('APP_NAMESPACE', 'DefaultNamespace\\');
```

The second value passed to the `env` function is the default value. This value will be returned if no environment variable exists for the given key.


## Dominus configuration

The default `.env` file contains some common configuration values that you may want to change, depending on yor needs, below is a list of the default environment variables and what they represent.

### General

| Key                            | Default value | Description                                                                                    |
|--------------------------------|---------------|------------------------------------------------------------------------------------------------|
| APP_ENV                        | dev           | The current running 'mode' of your application.                                                |
| APP_DISPLAY_LOGS               | 1             | Whether to print logged messages. Only works if APP_ENV is set to dev. Possible values: 0 or 1 |
| APP_DISPLAY_LOG_TYPES          | WARNING,ERROR | #Comma separated values. Possible values: INFO, WARNING, ERROR                                 |
| DB_(CONNECTION_ALIAS)_DSN      |               | DSN used for this connection alias                                                             |
| DB_(CONNECTION_ALIAS)_USERNAME |               |                                                                                                |
| DB_DEFAULT_PASSWORD            |               |                                                                                                |

### Database connections

Add new connections as DB_(CONNECTION_ALIAS)_*
Example:
```
DB_MY_ALIAS_DSN="mysql:host=db;port=3306;dbname=myDatabase"
DB_MY_ALIAS_USERNAME="user"
DB_MY_ALIAS_PASSWORD="pass"
```

| Key                            | Default value | Description                        |
|--------------------------------|---------------|------------------------------------|
| DB_(CONNECTION_ALIAS)_DSN      |               | DSN used for this connection alias |
| DB_(CONNECTION_ALIAS)_USERNAME |               |                                    |
| DB_(CONNECTION_ALIAS)_PASSWORD |               |                                    |

### Dominus Services

| Key                              | Default value | Description                                                                        |
|----------------------------------|---------------|------------------------------------------------------------------------------------|
| SERVICES_HTTP_CONNECTION_TIMEOUT | 30            | Maximum number of seconds that the connection can stay open (execution time)       |
| SERVICES_HTTP_CONNECT_TIMEOUT    | 30            | The number of seconds to wait while trying to connect. Use 0 to wait indefinitely. |
| SERVICES_HTTP_USERAGENT          | Dominus API   |                                                                                    |
| SERVICES_HTTP_SSL_VERIFY_HOST    | true          |                                                                                    |
| SERVICES_HTTP_SSL_VERIFY_PEER    | true          |                                                                                    |