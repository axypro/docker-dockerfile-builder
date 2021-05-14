# axy\docker\dockerfile\builder

[![Latest Stable Version](https://img.shields.io/packagist/v/axy/docker-dockerfile-builder.svg?style=flat-square)](https://packagist.org/packages/axy/docker-dockerfile-builder)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg?style=flat-square)](https://php.net/)
[![License](https://poser.pugx.org/axy/docker-dockerfile-builder/license)](LICENSE)

The class that builds dockerfile.

The package is used for specific things.
Some options are not tested.
Some things are done ineffectively.
Using this package is recommended for nobody.

## Hierarchy of classes

* `DockerfileBuileder` - common builder
    * `DockerfilePHPBuilder` - added some PHP specific features

## Example

```php
use axy\docker\dockerfile\builder\DockerfilePHPBuilder;

$builder = new DockerfilePHPBuilder('8.0', 'fpm', false);

$builder->labels['author'] = 'Me';
$builder->env['var'] = 'value';
$builder->user = '1000:1000';
$builder->cmd = 'php -m';

$builder->workdir('/var/www/app');
$builder->copy('file.php', 'index.php', '1000:1000');
$builder->packages(['curl', 'libpng-dev', 'libonig-dev', 'zip'], 'Install CURL etc');
$builder->extensions = ['bcmath', 'mbstring', 'pdo_mysql'];
$builder->pecl = ['xdebug'];
$builder->ini['error_reporting'] = 'E_ALL';
$builder->ini['post_max_size'] = '128M';

echo $builder->build();
```

Result:

```
FROM php:8.0-fpm

LABEL author="Me"

ENV var=value

WORKDIR /var/www/app

COPY --chown=1000:1000 file.php index.php

# Install CURL etc
RUN apt-get update && apt-get install -y \
    curl \
    libpng-dev \
    libonig-dev \
    zip

RUN docker-php-ext-install \
    bcmath \
    mbstring \
    pdo_mysql \
&& pecl install \
    xdebug \
&& docker-php-ext-enable \
    xdebug \
&& echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker.ini \
&& echo "post_max_size=128M" >> /usr/local/etc/php/conf.d/docker.ini \
&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN rm -rf /var/lib/apt/lists/*

CMD php -m

USER 1000:1000
```

## DockerfileBuilder

* `__construct(public string $image, public bool $isAlpine = true)`
    * `image` - the image name
    * `isAlpine` - use alpine distributive (see "Package Manager" below)

### Properties

* `string|array|null $cmd` - CMD instruction (NULL - no CMD)
* `string|array|null $entrypoint` - see CMD
* `string[] $labels` - LABELs (key => value)
* `string[] $env` - environment variables (key => value)
* `string[] $directives` - directives (key => value)
* `string[] $args` - ARGs (key => value)
* `?string $syntax` - `syntax` directive separately
* `?string $escape` - `escape` directive separately
* `string[] $volume` - list of volumes
* `string[] $expose` - list of expose ports
* `bool $isPackagesCacheDisabled` - see "Package Manager" below

### Methods

All these methods add one instruction to the dockerfile.
The argument `$comment` add comment line before the instruction.

* `instruction(string|string[] $instruction, ?string $comment)`
    * `$instruction` - an instruction as string or multiline (joined by "\")
* `run(string|string[] $commands, ?string $comment)`
    * `$commands` - a command for `RUN` instructions or a list of commands (joined by "&&")
* `copy(string|string[] $src, string $dest, ?string $chown)` - COPY instruction
* `add(string|string[] $src, string $dest, ?string $chown)` - ADD instruction
* `workdir(string $dir, ?string $comment = null)` - WORKDIR instruction
* `packages(string[] $packages, ?string $comment)`
    * `$packages` - a list of packages for installation
    * See "Package Manager" below

### Build

* `build(bool $pretty = true)` - returns the dockerfile content
    * `$pretty` - if FALSE, empty lines and comments will be deleted

### Package Manager

* Alpine uses "apk"
* Not alpine uses "apt-get"
* Before install "update" will be performed
* With multiple call `packages()`, "update" will be performed only for the first
* If `$isPackagesCacheDisabled` is TRUE (by default)
    * alpine - install will be executed with `--no-cache`
    * other - the cache directory will be deleted at the end

## DockerfilePHPBuilder

* `bool $isComposerRequired` - if TRUE (by default) the composer will be installed globally
* `string[] $extensions` - the list of names of extensions that required for installation
* `string[] $pecl` - the list of names of extensions that installed via pecl
* `string[] $ini` - the list of php.ini settings (key => value)
