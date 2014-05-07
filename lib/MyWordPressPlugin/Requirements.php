<?php

namespace MyWordPressPlugin;

class Requirements {

  protected $results = array();

  /* abstract */
  function getRequirements() {
    return array();
  }

  function satisfied() {
    $requirements = $this->getRequirements();
    $results = array();
    $success = true;

    foreach ($requirements as $requirement) {
      $result = array(
        'satisfied' => $requirement->check(),
        'requirement' => $requirement
      );

      array_push($results, $result);

      if (!$result['satisfied']) {
        $success = false;
      }
    }

    $this->results = $results;
    return $success;
  }

  function getResults() {
    return $this->results;
  }

}

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

class ModernRequirements extends Requirements {

  function getRequirements() {
    $requirements = array();

    $requirement = new PHPRequirement();
    $requirement->minimumVersion = '5.5.0';
    array_push($requirements, $requirement);

    $requirement = new WordPressRequirement();
    $requirement->minimumVersion = '3.8.0';
    array_push($requirements, $requirement);

    $requirement = new PHPExtensionRequirement();
    $requirement->extensions = array(
      'mysql', 'mysqli', 'session', 'pcre',
      'json', 'gd', 'mbstring', 'phar', 'zlib'
    );
    array_push($requirements, $requirement);

    return $requirements;
  }

}

// For Testing
class FailingRequirements extends Requirements {

  function getRequirements() {
    $requirements = array();

    $requirement = new PHPRequirement();
    $requirement->minimumVersion = '100.0.0';
    array_push($requirements, $requirement);

    $requirement = new WordPressRequirement();
    $requirement->minimumVersion = '100.0.0';
    array_push($requirements, $requirement);

    return $requirements;
  }
}

class PHPRequirement {

  public $minimumVersion = '5.3.2';

  function check() {
    return version_compare(
      phpversion(), $this->minimumVersion, '>='
    );
  }

  function message() {
    $version = phpversion();
    return "PHP $this->minimumVersion+ Required, Detected $version";
  }
}

class WordPressRequirement {

  public $minimumVersion = '3.5.0';

  function check() {
    return version_compare(
      $this->getWordPressVersion(), $this->minimumVersion, '>='
    );
  }

  function getWordPressVersion() {
    global $wp_version;
    return $wp_version;
  }

  function message() {
    $version = $this->getWordPressVersion();
    return "WordPress $this->minimumVersion+ Required, Detected $version";
  }
}

class PHPExtensionRequirement {

  public $extensions = array();
  public $notFound = array();

  function check() {
    $result = true;
    $this->notFound = array();

    foreach ($this->extensions as $extension) {
      if (!extension_loaded($extension)) {
        $result = false;
        array_push($this->notFound, $extension);
      }
    }

    return $result;
  }

  function message() {
    $extensions = implode(', ', $this->notFound);
    return "PHP Extensions Not Found: $extensions";
  }

}

class FauxPlugin {

  public $pluginName;
  public $results;

  function __construct($pluginName, $results) {
    $this->pluginName = $pluginName;
    $this->results = $results;
  }

  function activate($pluginFile) {
    register_activation_hook(
      $pluginFile, array($this, 'onActivate')
    );
  }

  function onActivate() {
    $this->showError($this->resultsToNotice());
  }

  function showError($message) {
    if ($this->isErrorScraper()) {
      echo $message;
      $this->quit();
    } else {
      throw new RequirementsException();
    }
  }

  function quit() {
    if (!defined('PHPUNIT_RUNNER')) {
      exit();
    }
  }

  function isErrorScraper() {
    return isset($_GET['action']) && $_GET['action'] === 'error_scrape';
  }

  function resultsToNotice() {
    $html  = $this->getStyles();
    $html .= $this->getHeading();

    foreach ($this->results as $result) {
      if (!$result['satisfied']) {
        $html .= $this->resultToNotice($result);
      }
    }

    return $this->toDiv($html, 'error');
  }

  function resultToNotice($result) {
    $satisfied = $result['satisfied'];
    $message   = $result['requirement']->message();

    return "<li>$message</li>";
  }

  function toDiv($content, $classname) {
    return "<div class='$classname'>$content</div>";
  }

  function getHeading() {
    $html  = "<p>Minimum System Requirements not satisfied for: ";
    $html .= "<strong>$this->pluginName</strong></p>";

    return $html;
  }

  function getStyles() {
    $styles  = 'body { font-family: sans-serif; font-size: 12px; color: #a00; }; ';
    $styles  = "<style type='text/css' scoped>$styles</style>";

    return $styles;
  }
}

class RequirementsException extends \Exception {

}
