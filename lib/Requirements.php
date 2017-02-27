<?php
/**
 * @see https://github.com/dsawardekar/wp-requirements
 */
if ( class_exists( 'WP_Requirements' ) === false ) {
	class WP_Requirements {
		private static $text_domain;
		protected $results = array();
		
		public function __construct( $text_domain = 'WP_Requirements' ) {
			self::$text_domain = $text_domain;
		}
		
		public static function _t( $string ) {
			return __( $string, self::$text_domain );
		}
		
		/* abstract */
		function getRequirements() {
			return array();
		}
		
		function satisfied() {
			$requirements = $this->getRequirements();
			$results      = array();
			$success      = true;
			foreach ( $requirements as $requirement ) {
				$result = array(
					'satisfied'   => $requirement->check(),
					'requirement' => $requirement
				);
				array_push( $results, $result );
				if ( ! $result['satisfied'] ) {
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
	
	class WP_Min_Requirements extends WP_Requirements {
		function getRequirements() {
			$requirements = array();
			// Min requirements for Composer
			$requirement                 = new WP_PHP_Requirement();
			$requirement->minimumVersion = '5.3.2';
			array_push( $requirements, $requirement );
			$requirement                 = new WP_WordPress_Requirement();
			$requirement->minimumVersion = '3.5.0';
			array_push( $requirements, $requirement );
			
			return $requirements;
		}
	}
	
	class WP_Modern_Requirements extends WP_Requirements {
		function getRequirements() {
			$requirements                = array();
			$requirement                 = new WP_PHP_Requirement();
			$requirement->minimumVersion = '5.5.0';
			array_push( $requirements, $requirement );
			$requirement                 = new WP_WordPress_Requirement();
			$requirement->minimumVersion = '3.8.0';
			array_push( $requirements, $requirement );
			$requirement             = new WP_PHP_Extension_Requirement();
			$requirement->extensions = array(
				'mysql',
				'mysqli',
				'session',
				'pcre',
				'json',
				'gd',
				'mbstring',
				'phar',
				'zlib'
			);
			array_push( $requirements, $requirement );
			
			return $requirements;
		}
	}
	
	// For Testing
	class WP_Failing_Requirements extends WP_Requirements {
		function getRequirements() {
			$requirements                = array();
			$requirement                 = new WP_PHP_Requirement();
			$requirement->minimumVersion = '100.0.0';
			array_push( $requirements, $requirement );
			$requirement                 = new WP_WordPress_Requirement();
			$requirement->minimumVersion = '100.0.0';
			array_push( $requirements, $requirement );
			
			return $requirements;
		}
	}
	
	class WP_PHP_Requirement {
		public $minimumVersion = '5.3.2';
		
		function check() {
			return version_compare(
				phpversion(), $this->minimumVersion, '>='
			);
		}
		
		function message() {
			$version = phpversion();
			
			return sprintf( WP_Requirements::_t( "PHP %s+ Required, Detected %s" ), $this->minimumVersion, $version );
		}
	}
	
	class WP_WordPress_Requirement {
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
			
			return sprintf( WP_Requirements::_t( "WordPress %s+ Required, Detected %s" ), $this->minimumVersion, $version );
		}
	}
	
	class WPMU_WordPress_Requirement {
		private $only_multisite = true;
		
		function check() {
			return is_multisite() && $this->isForMultisite();
		}
		
		function message() {
			$message = WP_Requirements::_t( 'This plugins is ' );
			if ( ! $this->isForMultisite() ) {
				$message .= WP_Requirements::_t( 'not ' );
			}
			$message .= WP_Requirements::_t( 'for multisite installation.' );
			
			return $message;
		}
		
		/**
		 * @return mixed
		 */
		public function isForMultisite() {
			return $this->only_multisite;
		}
		
		/**
		 * @param mixed $only_multisite
		 */
		public function setIsForMultisite( $only_multisite ) {
			$this->only_multisite = $only_multisite;
		}
	}
	
	class WP_PHP_Extension_Requirement {
		public $extensions = array();
		public $notFound = array();
		
		function check() {
			$result         = true;
			$this->notFound = array();
			foreach ( $this->extensions as $extension ) {
				if ( ! extension_loaded( $extension ) ) {
					$result = false;
					array_push( $this->notFound, $extension );
				}
			}
			
			return $result;
		}
		
		function message() {
			$extensions = implode( ', ', $this->notFound );
			
			return sprintf( WP_Requirements::_t( "PHP Extensions Not Found: %s" ), $extensions );
		}
	}
	
	class WP_Faux_Plugin {
		public $pluginName;
		public $results;
		
		function __construct( $pluginName, $results ) {
			$this->pluginName = $pluginName;
			$this->results    = $results;
		}
		
		/**
		 * NOTE This don't work in multisite
		 */
		function activate( $pluginFile ) {
			register_activation_hook(
				$pluginFile, array( $this, 'onActivate' )
			);
		}
		
		function onActivate() {
			$this->showError( $this->resultsToNotice() );
		}
		
		function showError( $message ) {
			if ( $this->isErrorScraper() ) {
				echo $message;
				$this->quit();
			} else {
				throw new WP_Requirements_Exception();
			}
		}
		
		function quit() {
			if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
				exit();
			}
		}
		
		function isErrorScraper() {
			return isset( $_GET['action'] ) && $_GET['action'] === 'error_scrape';
		}
		
		function resultsToNotice() {
			$html = $this->getStyles();
			$html .= $this->getHeading();
			foreach ( $this->results as $result ) {
				if ( ! $result['satisfied'] ) {
					$html .= $this->resultToNotice( $result );
				}
			}
			
			return $this->toDiv( $html, 'error' );
		}
		
		function resultToNotice( $result ) {
			$message = $result['requirement']->message();
			
			return "<li>$message</li>";
		}
		
		function toDiv( $content, $classname ) {
			return "<div class='$classname'>$content</div>";
		}
		
		function getHeading() {
			$html = WP_Requirements::_t( "<p>Minimum System Requirements not satisfied for: " );
			$html .= "<strong>$this->pluginName</strong>.";
			$html .= WP_Requirements::_t( " The plugins wont be activated.</p>" );
			
			return $html;
		}
		
		function getStyles() {
			$styles = 'body { font-family: sans-serif; font-size: 12px; color: #a00; }; ';
			$styles = "<style type='text/css' scoped>$styles</style>";
			
			return $styles;
		}
		
		/**
		 * Show te result and disable the plugins.
		 *
		 * NOTE: it work in wpmu and wp
		 *
		 * @param string $FILE primary file of the plugins
		 */
		public function show_result( $FILE ) {
			echo $this->resultsToNotice();
			if ( is_multisite() ) {
				$plugins = get_site_option( 'active_sitewide_plugins' );
				if ( isset( $plugins[ $FILE ] ) ) {
					unset( $plugins[ $FILE ] );
					$result = update_site_option( 'active_sitewide_plugins', $plugins );
				}
			} else {
				$plugins = get_option( 'active_plugins' );
				foreach ( $plugins as $key => $name ) {
					if ( $name == $FILE ) {
						unset( $plugins[ $key ] );
						$result = update_option( 'active_plugins', $plugins );
						break;
					}
				}
			}
		}
	}
	
	class WP_Requirements_Exception extends \Exception {
	}
}