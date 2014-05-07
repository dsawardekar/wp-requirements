## WP Requirements [![Build Status](https://travis-ci.org/dsawardekar/wp-requirements.svg?branch=develop)](https://travis-ci.org/dsawardekar/wp-requirements) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/dsawardekar/wp-requirements/badges/quality-score.png?s=66085bea27b9812f920eaa512591191230424230)](https://scrutinizer-ci.com/g/dsawardekar/wp-requirements/)

A small library to easily handle detection of minimum system
requirements in WordPress plugins.

![Screenshot][1]

# Features

* Detects PHP versions incompatible with your Plugin.
* Detects WordPress versions incompatible with your Plugin.
* Detects absence of PHP extensions.
* Displays errors to users without activating your Plugin.
* Simple API to support custom minimum requirements.

# Getting Started

The library comes with a `MinRequirements` class that can be used to
detect some basic system requirements like WordPress and PHP versions.

First we create a new instance of the class and call it's `satisfied`
method in a conditional. If the condition is satisfied you can
instantiate your plugin inside it.

```php
<?php
$requirements = new MinRequirements();

if ($requirements->satisfied()) {
  // minimum requirements satisfied
  // create your plugin here
}
```

Next we need to handle the case when the minimum requirements are not
satisfied. The library comes with a `FauxPlugin` that does this by
notifying the user that the minimum requirements are not satisfied.

All you need to do is create it and passing it the name of your plugin
and the results object from the requirements.

```php
<?php
$requirements = new MinRequirements();

if ($requirements->satisified()) {
  // create your plugin here
} else {
  $fauxPlugin = new FauxPlugin('My Plugin Name', $requirements->getResults());
  $fauxPlugin->enable(__FILE__);
}
```

When the requirements are not met, users will see a message like shown
above.

## Detecting Specific Versions of PHP & WordPress

To create custom requirements you extend the `Requirements` class and
provide a custom `getRequirements` method. It should return an array of
requirement objects.

Below is the `MinRequirements` class that matches against PHP 5.3.2 and
WordPress 3.5.

```php
<?php
class MinRequirements extends Requirements {

  function getRequirements() {
    $requirements = array();

    // Min requirements for Composer
    $requirement = new PHPRequirement();
    $requirement->minimumVersion = '5.3.2';
    array_push($requirements, $requirement);

    $requirement = new WordPressRequirement();
    $requirement->minimumVersion = '3.5.0';
    array_push($requirements, $requirement);

    return $requirements;
  }

}
```

## Custom Requirements

You can also customize the requirements specific to your plugin. First you need to
create a custom `Requirement` class with 2 methods, `check` and `message`.

Your `check` method should perform your custom detection and return
true or false. And the `message` method should return the message to
display to the user if the requirement is not met.

For example to check if the `Akismet` plugin is in use,

```php
<?php
class AkismetRequirement {

  function check() {
    return is_plugin_active('akismet/akismet.php');
  }

  function message() {
    return 'Akismet Required';
  }

}
```

Then wrap this up into a new class that extends the `Requirements` class
and put this requirement into use.

```php
<?php
class MyCustomRequirements extends Requirements {

  function getRequirements() {
    $requirements = array();
    array_push($requirements, new AkismetRequirement());

    return $requirements;
  }

}
```

## Other Requirements

Use the [ModernRequirements][3] class to ensure that PHP 5.5 and required
extensions are present.

To test the requirements message, use the [FailingRequirements][4] class instead.

## Usage

The library and all it's classes are bundled inside the
[Requirements.php][9] file.

1. Copy the [Requirements.php][9] file into your project.
1. Replace the default namespace `MyWordPressPlugin` to match your
   plugin.
1. Use `require_once` to include the project into your plugin's main
   file.
1. Instantiate the Requirements object and call it's `satisfied` method
   as described above.

## Examples

1. [Sample plugin using MinRequirements][5].
1. [Sample WooCommerce plugin][6].

## Thanks

* Thanks to [Square Penguin][7] for the error message idea.

## Contributing

Please include your system and environment details for faster resolution
of bug reports. Failing tests alongside bug reports are awesome!

Pull Requests are Welcome!

The project comes with a test suite that is integrated with [Travis][8].
Please ensure that the test suite passes before submitting PRs. Also try to include
tests alongside any major changes.

Also note, this project uses the [git flow][2] branching model. PRs should be made
against the `develop` branch.

## License

MIT License. Copyright © 2014 Darshan Sawardekar

[1]: http://i.imgur.com/0d9d6HF.png
[2]: https://github.com/nvie/gitflow
[3]: https://github.com/dsawardekar/wp-requirements/blob/develop/lib/MyWordPressPlugin/Requirements.php#L61
[4]: https://github.com/dsawardekar/wp-requirements/blob/develop/lib/MyWordPressPlugin/Requirements.php#L87
[5]: https://github.com/dsawardekar/sample-requirements-plugin
[6]: https://github.com/dsawardekar/sample-woocommerce-requirements-plugin
[7]: http://www.squarepenguin.com/wordpress/?p=6
[8]: https://travis-ci.org
[9]: https://raw.githubusercontent.com/dsawardekar/wp-requirements/master/lib/MyWordPressPlugin/Requirements.php
