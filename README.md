# Lighthouse Redis Broadcaster [![Build Status](https://travis-ci.org/thekonz/lighthouse-redis-broadcaster.svg?branch=master)](https://travis-ci.org/thekonz/lighthouse-redis-broadcaster)

[Lighthouse](https://lighthouse-php.com/) already supports pusher, but does not deliver its own redis based solution.
This package enables graphql subscriptions using presence channels of the [laravel-echo-server](https://github.com/tlaverdure/laravel-echo-server).

## Installation

*I assume that you already have [Lighthouse](https://lighthouse-php.com/) and [laravel-echo-server](https://github.com/tlaverdure/laravel-echo-server) installed. If not, please check out their installation steps before continuing.* 

Install the package with composer:
```bash
composer require thekonz/lighthouse-redis-broadcaster
```

Add the service provider **after** the Lighthouse subscription service provider in the `config/app.php`:
```php
        /*
         * Package Service Providers...
         */
        \Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider::class,
        \thekonz\LighthouseRedisBroadcaster\SubscriptionServiceProvider::class, 
```

## Setting up automatic removal of subscription channels

Lighthouse by default does not remove vacated channels. In order to prevent redis from running low on memory all the time, you need to configure the laravel-echo-server to publish updates about its presence channels and run a subscriber that removes vacated channels from redis.   

Enable presence channel updates in your `laravel-echo-server.json` by setting `publishPresence` to `true`:
```json
  "databaseConfig": {
    ...
    "publishPresence": true
  }
```

Run the subscription command to remove vacated channels:
```bash
php artisan lighthouse:subscribe
```

## Usage

Create a subscription as described in the [Lighthouse docs](https://lighthouse-php.com/4.12/subscriptions/defining-fields.html). For the purpose of demonstration, I assume the subscription is `postUpdated` like in the docs.

Now query the api:
```graphql
subscription test {
  postUpdated {
    id
    title
  }
}
```

The response will be:
```json
{
  "data": {
    "postUpdated": null
  },
  "extensions": {
    "lighthouse_subscriptions": {
      "version": 1,
      "channels": {
        "test": "presence-lighthouse-9RrjQE84nqaxXt58ZsgREPaI9AxGjAv4-1588101712"
      }
    }
  }
}
```

Now you may use laravel echo to monitor the subscription as a presence channel (notice that `presence-` was cut off, because laravel echo prefixes the channel itself):
```js
Echo.join('lighthouse-9RrjQE84nqaxXt58ZsgREPaI9AxGjAv4-1588101712')
    .listen('lighthouse.subscription', ({ data }) => {
        console.log(data);
    })
```

The `data` object will be just like a normal graphql response body.

## Contributing and issues

Feel free to contribute to this package using the issue system and pull requests on the `develop` branch.

Automated unit tests must be added or changed to cover your changes or reproduce bugs. 
