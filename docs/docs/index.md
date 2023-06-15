# Getting started

## Installation

You can quickly create a Dominus api backends with the [Dominus CLI](https://github.com/daniel1919-00/DominusCli). Before continuing, please make sure you have `python` and `pip` module installed on your machine, the CLI depends on them to function :).

Start by cloning the CLI files from the main repository.
``` shell
git clone https://github.com/daniel1919-00/DominusCli
```

Access the CLI using the starter scripts: `dominus.sh` for Linux/macOS or `dominus.bat` for Windows.

You may create a new project by changing the current directory to the desired path and using the `new` command.
``` shell
new my-project
```
![Dominus CLI](img/first-project-cli-1.png "Dominus CLI")

After the project has been created, you can access it either by installing a web server yourself, or using the docker configuration (if prompted yes when asked by the cli) from the Dominus framework which includes `nginx` with `php8.1` and `xdebug` installed.

Optionally set the app namespace, by default it is `App`, this namespace is also used when creating components for your application (Modules, Controllers, Services, etc.)

## Configuration

You can further configure the project by checking out the [configuration docs](configuration.md).

## Directory Structure

- Dominus project directory
    - App
        - [Modules](modules.md)
            - [Controllers](controllers.md)
            - [Models](models.md)
            - [Repositories](repositories.md)
            - [Services](services.md)
        - [Services](services.md)
        - [Middleware](middleware.md)
    - [Logs](#Logs)
    - [Tests](#Tests)
    - [Dominus](#Dominus)

### Logs
If you have set the `APP_LOG_TO_FILE` [environment variable](configuration.md#environment-configuration) to `1` then all the logging done by the framework are stored (you can change the location by editing `APP_LOG_FILE_LOCATION` [environment variable](configuration.md#environment-configuration)). 

### Tests
This is where all your test suites go, and where the testing framework looks for them.

### Dominus
The Dominus framework system files are stored here and should not be modified.

## Your first module

[You are now ready to create your first module.](modules.md)