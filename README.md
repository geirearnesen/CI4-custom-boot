# CI4-custom-boot
Customized Codeigniter 4 Boot.php

## Usage
This repository contains a customized version of the `Boot.php` file for CodeIgniter 4. The custom `Boot.php` file allows you to load a custom configuration file and a custom boot file.

With this customization, you can easily manage your CodeIgniter 4 application by separating the configuration and bootstrapping process into different files. The setup is facilitated by the `Paths.php` file, which is responsible for defining the paths to various directories in your application.

## Features
Load the configuration files from in the following order
1. app/AppConfig/localhost_docker_foo
   - .env
   - AppConstants.php 
   - Constants.php 
2. app/AppConfig/.config
   - .env
   - AppConstants.php
   - Constants.php
3. app/Config
   - .env
   - AppConstants.php
   - Constants.php

I use the `hostname_folder` to load the configuration files based on the hostname. This allows you to have different configurations for different environments (e.g., development, staging, production) without modifying the core CodeIgniter files. In my Dockerfile I declare ie:
```dockerfile
    hostname: localhost_docker_foo
```

With this setup you may have a common configuration for all environments in the `app/Config` directory, and then override specific settings in the `app/AppConfig/.config` directory for each environment.

## Installation

1. Modify the `index.php` to include the path to the custom `Boot.php` file.
```php
// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->customBootDirectory . '/Boot.php';
//require $paths->systemDirectory . '/Boot.php';

exit(CodeIgniter\Boot::bootWeb($paths));
```
         
2. Add the `customBootDirectory` and `customConfigDirectory` to the `Paths.php` file.
```php
public string $customBootDirectory = __DIR__ . '/..';
public string $customConfigDirectory = __DIR__ . '/../AppConfig';
```
             
The `app/AppConfig` is excluded when deploying the application to production. 

The `app/AppConfig/.config` is created on the server production environment and contains the configuration files for the production environment.
- .env
- AppConstants.php
- Constants.php

