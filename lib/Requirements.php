<?php
/**
 * @see https://github.com/dsawardekar/wp-requirements
 */
if ( class_exists( 'WP_Requirement' ) === false ) {
	class WP_Requirement {
		private static $text_domain;
		protected $results = array();
		
		public function __construct( $text_domain = 'WP_Requirement' ) {
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
	
	if ( class_exists( 'WP_Min_Requirements' ) === false ) {
		class WP_Min_Requirements extends WP_Requirement {
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
	}
	
	if ( class_exists( 'WP_Modern_Requirements' ) === false ) {
		class WP_Modern_Requirements extends WP_Requirement {
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
	}
	
	if ( class_exists( 'WP_Failing_Requirements' ) === false ) {
		class WP_Failing_Requirements extends WP_Requirement {
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
	}
	
	if ( class_exists( 'WP_PHP_Requirement' ) === false ) {
		class WP_PHP_Requirement {
			public $minimumVersion = '5.3.2';
			
			function check() {
				return version_compare(
					phpversion(), $this->minimumVersion, '>='
				);
			}
			
			function message() {
				$version = phpversion();
				
				return sprintf( WP_Requirement::_t( "PHP %s+ Required, Detected %s" ), $this->minimumVersion, $version );
			}
		}
	}
	
	if ( class_exists( 'WP_WordPress_Requirement' ) === false ) {
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
				
				return sprintf( WP_Requirement::_t( "WordPress %s+ Required, Detected %s" ), $this->minimumVersion, $version );
			}
		}
	}
	
	if ( class_exists( 'WPMU_WordPress_Requirement' ) === false ) {
		class WPMU_WordPress_Requirement {
			private $only_multisite = true;
			
			function check() {
				return is_multisite() == $this->isForMultisite();
			}
			
			function message() {
				$message = WP_Requirement::_t( 'This plugins is ' );
				if ( ! $this->isForMultisite() ) {
					$message .= WP_Requirement::_t( 'not ' );
				}
				$message .= WP_Requirement::_t( 'for multisite installation.' );
				
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
	}
	
	if ( class_exists( 'WP_Plugins_Requirement' ) === false ) {
		class WP_Plugins_Requirement {
			public $plugins = array();
			public $notFound = array();
			private $notVersion = array();
			
			private function load_plugins_dependency() {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			
			function check() {
				$this->load_plugins_dependency();
				$result          = true;
				$this->notFound  = array();
				$this->notVerion = array();
				
				foreach ( $this->plugins as $plugin ) {
					if ( ! is_plugin_active( $plugin['id'] ) ) {
						$result = false;
						array_push( $this->notFound, $plugin['name'] );
					} else {
						if ( isset( $plugin['min_version'] ) ) {
							$plugin_path   = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin['id'];
							$plugin_header = get_plugin_data( $plugin_path );
							if ( ! empty( $plugin_header ) ) {
								$version = version_compare( $plugin_header['Version'], $plugin['min_version'], '>=' );
								if ( ! $version ) {
									$result = false;
									array_push( $this->notVersion, sprintf( WP_Requirement::_t( '%s %s minimum require %s' ), $plugin['name'], $plugin_header['Version'], $plugin['min_version'] ) );
								}
							}
						}
					}
				}
				
				return $result;
			}
			
			function message() {
				$result              = WP_Requirement::_t( "Requirements Plugins." );
				$plugins_not_version = implode( ', ', $this->notVersion );
				if ( ! empty( $this->notFound ) ) {
					$plugins_not_found = implode( '</li><li class="requirement_sub_item">', $this->notFound );
					$result            .= "<ul class='requirement_sub_list'>" . sprintf( WP_Requirement::_t( " Not Found: %s" ), '<li class="requirement_sub_item">' . $plugins_not_found . '</li>' ) . "</ul>";
				}
				
				if ( ! empty( $this->notVersion ) ) {
					$plugins_not_version = implode( '</li><li class="requirement_sub_item">', $this->notVersion );
					$result              .= "<ul class='requirement_sub_list'>" . sprintf( WP_Requirement::_t( " Requirement Version Fail: %s" ), '<li class="requirement_sub_item">' . $plugins_not_version . '</li>' ) . "</ul>";
				}
				
				return $result;
			}
		}
	}
	
	if ( class_exists( 'WP_Class_Requirement' ) === false ) {
		class WP_Class_Requirement {
			public $class = array();
			public $notFound = array();
			
			function check() {
				$result = true;
				foreach ( $this->class as $class_name => $class_error ) {
					if ( ! class_exists( $class_name ) ) {
						$result = false;
						array_push( $this->notFound, $class_error );
					}
				}
				
				return $result;
			}
			
			function message() {
				$result = '';
				if ( ! empty( $this->notFound ) ) {
					$result = implode( ', ', $this->notFound );
				}
				
				return $result;
			}
		}
	}
	
	if ( class_exists( 'WP_PHP_Extension_Requirement' ) === false ) {
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
				
				return sprintf( WP_Requirement::_t( "PHP Extensions Not Found: %s" ), $extensions );
			}
		}
	}
	
	if ( class_exists( 'WP_Faux_Plugin' ) === false ) {
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
				$html = WP_Requirement::_t( "<p>Minimum System Requirements not satisfied for: " );
				$html .= "<strong>$this->pluginName</strong>.";
				$html .= WP_Requirement::_t( " The plugins wont be activated.</p>" );
				
				return $html;
			}
			
			function getStyles() {
				$styles = '.requirement_sub_list{ margin-left: 50px; }.requirement_sub_item{margin-left: 15px;} ';
				$styles = "<style type='text/css' scoped>$styles</style>";
				
				return $styles;
			}
			
			/**
			 * Show te result and disable the plugins.
			 *
			 * NOTE: it work in wpmu and wp
			 *
			 * @param string $file primary file of the plugins
			 */
			public function show_result( $file ) {
				if ( is_multisite() ) {
					add_action( 'network_admin_notices', function () use ( $file ) {
						echo $this->resultsToNotice();
						$plugins = get_site_option( 'active_sitewide_plugins' );
						if ( isset( $plugins[ $file ] ) ) {
							unset( $plugins[ $file ] );
							$result = update_site_option( 'active_sitewide_plugins', $plugins );
						}
					} );
				} else {
					add_action( 'admin_notices', function () use ( $file ) {
						echo $this->resultsToNotice();
						$plugins = get_option( 'active_plugins' );
						foreach ( $plugins as $key => $name ) {
							if ( $name == $file ) {
								unset( $plugins[ $key ] );
								$result = update_option( 'active_plugins', $plugins );
								break;
							}
						}
					} );
					
				}
			}
		}
	}
	
	if ( class_exists( 'WP_Requirements_Exception' ) === false ) {
		class WP_Requirements_Exception extends \Exception {
		}
	}
}