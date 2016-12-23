# ehough/guzzle-commands

[![Build Status](https://travis-ci.org/ehough/guzzle-commands.svg?branch=develop)](https://travis-ci.org/ehough/guzzle-commands)
[![Latest Stable Version](https://poser.pugx.org/ehough/guzzle-commands/v/stable)](https://packagist.org/packages/ehough/guzzle-commands)
[![License](https://poser.pugx.org/ehough/guzzle-commands/license)](https://packagist.org/packages/ehough/guzzle-commands)


A PHP 5.3-compatible fork of [Guzzle Commands](https://github.com/guzzle/command).

# Why?

Sadly, [60%](https://w3techs.com/technologies/details/pl-php/5/all) of all PHP web servers still run PHP 5.4 and lower, but Guzzle Commands needs PHP 5.5 or higher. This fork makes Guzzle Commands compatible with PHP 5.3.29 through 7.1.

# How to Use This Fork

Usage is identical to [`guzzle/command`](https://github.com/guzzle/command), except that the code in this library is 
namespaced under `Hough\Guzzle\Command` instead of `GuzzleHttp\Command`.

--- 

This library uses Guzzle (``ehough/guzzle``, version 6.x) and provides the
foundations to create fully-featured web service clients by abstracting Guzzle
HTTP **requests** and **responses** into higher-level **commands** and
**results**. A **middleware** system, analogous to — but separate from — the one
in the HTTP layer may be used to customize client behavior when preparing
commands into requests and processing responses into results.

### Commands
    
Key-value pair objects representing an operation of a web service. Commands have a name and a set of parameters.

### Results

Key-value pair objects representing the processed result of executing an operation of a web service.

## Service Clients

Service Clients are web service clients that implement the
``Hough\Guzzle\Command\ServiceClientInterface`` and use an underlying Guzzle HTTP
client (``GuzzleHttp\Client``) to communicate with the service. Service clients
create and execute **commands** (``Hough\Guzzle\Command\CommandInterface``),
which encapsulate operations within the web service, including the operation
name and parameters. This library provides a generic implementation of a service
client: the ``Hough\Guzzle\Command\ServiceClient`` class.

## Instantiating a Service Client

@TODO Add documentation

* ``ServiceClient``'s constructor
* Transformer functions (``$commandToRequestTransformer`` and ``$responseToResultTransformer``)
* The ``HandlerStack``

## Executing Commands

Service clients create command objects using the ``getCommand()`` method.

```php
$commandName = 'foo';
$arguments = ['baz' => 'bar'];
$command = $client->getCommand($commandName, $arguments);

```

After creating a command, you may execute the command using the ``execute()``
method of the client.

```php
$result = $client->execute($command);
```

The result of executing a command will be a ``Hough\Guzzle\Command\ResultInterface``
object. Result objects are ``ArrayAccess``-ible and contain the data parsed from
HTTP response.

Service clients have magic methods that act as shortcuts to executing commands
by name without having to create the ``Command`` object in a separate step
before executing it.

```php
$result = $client->foo(['baz' => 'bar']);
```

## Asynchronous Commands

@TODO Add documentation

* ``-Async`` suffix for client methods
* Promises

```php
// Create and execute an asynchronous command.
$command = $command = $client->getCommand('foo', ['baz' => 'bar']);
$promise = $client->executeAsync($command);

// Use asynchronous commands with magic methods.
$promise = $client->fooAsync(['baz' => 'bar']);
```

@TODO Add documentation

* ``wait()``-ing on promises.

```php
$result = $promise->wait();

echo $result['fizz']; //> 'buzz' 
```

## Concurrent Requests

@TODO Add documentation

* ``executeAll()``
* ``executeAllAsync()``.
* Options (``fulfilled``, ``rejected``, ``concurrency``)

## Middleware: Extending the Client

Middleware can be added to the service client or underlying HTTP client to
implement additional behavior and customize the ``Command``-to-``Result`` and
``Request``-to-``Response`` lifecycles, respectively.

## Todo

* Middleware system and command vs request layers
* The ``HandlerStack``
