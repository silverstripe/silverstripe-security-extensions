# SilverStripe Security Extensions

[![Build Status](https://travis-ci.com/creative-commoners/silverstripe-security-extensions.svg?branch=master)](https://travis-ci.com/creative-commoners/silverstripe-security-extensions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/creative-commoners/silverstripe-security-extensions/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/creative-commoners/silverstripe-security-extensions/?branch=master)
[![codecov](https://codecov.io/gh/creative-commoners/silverstripe-security-extensions/branch/master/graph/badge.svg)](https://codecov.io/gh/creative-commoners/silverstripe-security-extensions)
[![License](https://poser.pugx.org/silverstripe/security-extensions/license.svg)](https://github.com/creative-commoners/silverstripe-security-extensions#license)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Overview

This module is a polyfill for some security related features that will become part of the core SilverStripe
product, but are required for older SilverStripe 3.7 and 4.x support in the meantime.

## Installation

```
$ composer require silverstripe/security-extensions 1.x-dev
```

## Features

### Sudo mode

Sudo mode represents a heightened level of permission in that you are more certain that the current user is actually
the person whose account is logged in. This is performed by re-validating that the account's password is correct, and
will then last for a certain amount of time (configurable) until it will be checked again.

#### Enabling sudo mode for controllers

You can add the `SilverStripe\SecurityExtensions\Services\SudoModeServiceInterface` as a dependency to a controller
that requires sudo mode for one of its actions:

```php
class MyController extends Controller
{
    private $sudoModeService;
    
    private static $dependencies = ['SudoModeService' => '%$' . SudoModeServiceInterface::class];
    
    public function setSudoModeService(SudoModeServiceInterface $sudoModeService): self
    {
        $this->sudoModeService = $sudoModeService;
        return $this;
    }
}
```

Performing a sudo mode verification check in a controller action is simply using the service to validate the request:

```php
public function myAction(HTTPRequest $request): HTTPResponse
{
    if (!$this->sudoModeService->check($request->getSession()) {
        return $this->httpError(403, 'Sudo mode is required for this action');
    }
    // ... continue with sensitive operations
}
``` 

### Using sudo mode in a React component

This module defines a [React Higher-Order-Component](https://reactjs.org/docs/higher-order-components.html) which can
be applied to React components in your module or code to intercept component rendering and show a "sudo mode required"
information and login screen, which will validate, activate sudo mode, and re-render the wrapped component afterwards
on success.

**Note:** the JavaScript injector [does not currently support injecting transformations/HOCs](https://github.com/silverstripe/react-injector/issues/4),
so we have coupled the application of these [injector transformations](https://docs.silverstripe.org/en/4/developer_guides/customising_the_admin_interface/reactjs_redux_and_graphql/#transforming-services-using-middleware)
into this module itself for the silverstripe/mfa module. Unfortunately, if you want to apply this to your own code
you will need to either duplicate the `SudoMode` HOC into your project or module and apply the transformation at that
point.

![Sudo mode HOC example](docs/_images/sudomode.png)

#### Requirements for adding to a component

You will need to ensure that the component's respective PHP FormField has the following attributes added to its
form schema (see `FormField::getSchemaData()`):

* `SecurityID`: for Cross-Site-Request-Forgery protection - can be obtained via `SecurityToken::inst()->getValue()`
* `sudoModeActive`: whether sudo mode is currently active - can be obtained via `SudoModeServiceInterface::check()`

While the `sudoModeActive` prop will be used in the SudoMode HOC, backend validation is also implemented to ensure
that the frontend UI cannot simply be tampered with to avoid re-validation on sensitive operations.

## Versioning

This library follows [Semver](http://semver.org). According to Semver,
you will be able to upgrade to any minor or patch version of this library
without any breaking changes to the public API. Semver also requires that
we clearly define the public API for this library.

All methods, with `public` visibility, are part of the public API. All
other methods are not part of the public API. Where possible, we'll try
to keep `protected` methods backwards-compatible in minor/patch versions,
but if you're overriding methods then please test your work before upgrading.

## Reporting Issues

Please [create an issue](https://github.com/creative-commoners/silverstripe-security-extensions/issues)
for any bugs you've found, or features you're missing.

## License

This module is released under the [BSD 3-Clause License](LICENSE.md).
