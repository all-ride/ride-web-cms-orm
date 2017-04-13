# Ride: Web CMS ORM

ORM integration for the CMS of a Ride web application.

## Behaviour Processors

When the selected model in the widget has active behaviours, a behaviour processor can be defined to enable extra logic for that behaviour.
For example, the publish behaviour has a processor to check on the publish state and publication dates automatically without you having to write the condition.

Behaviour processors are automatically enabled and should be defined in the dependency injector with the `ride\web\cms\orm\processor\BehaviourProcessor` interface.

You can set a widget property to disable a specific behaviour. Eg behaviour.publish = "0".

## Related Modules

- [ride/app](https://github.com/all-ride/ride-app)
- [ride/app-orm](https://github.com/all-ride/ride-app-orm)
- [ride/lib-cms](https://github.com/all-ride/ride-lib-cms)
- [ride/lib-common](https://github.com/all-ride/ride-lib-common)
- [ride/lib-event](https://github.com/all-ride/ride-lib-event)
- [ride/lib-form](https://github.com/all-ride/ride-lib-form)
- [ride/lib-html](https://github.com/all-ride/ride-lib-html)
- [ride/lib-http](https://github.com/all-ride/ride-lib-http)
- [ride/lib-i18n](https://github.com/all-ride/ride-lib-i18n)
- [ride/lib-mvc](https://github.com/all-ride/ride-lib-mvc)
- [ride/lib-router](https://github.com/all-ride/ride-lib-router)
- [ride/lib-template](https://github.com/all-ride/ride-lib-template)
- [ride/lib-validation](https://github.com/all-ride/ride-lib-validation)
- [ride/lib-widget](https://github.com/all-ride/ride-lib-widget)
- [ride/web](https://github.com/all-ride/ride-web)
- [ride/web-base](https://github.com/all-ride/ride-web-base)
- [ride/web-cms](https://github.com/all-ride/ride-web-cms)
- [ride/web-cms-widgets](https://github.com/all-ride/ride-web-cms-widgets)
- [ride/web-orm](https://github.com/all-ride/ride-web-orm)

## Installation

You can use [Composer](http://getcomposer.org) to install this application.

```
composer require ride/web-cms-orm
```
