<?php
/**
 * Component for password protecting a section of your website
 *
 * This can be used if you don't need something as complicated as maintaining a user and password database, 
 * but rather simply maintain one master password for access to the admin sections of the site
 *
 **/

App::uses('Security', 'Utility');

class PasswordProtectComponent extends Component {
	public $name = 'PasswordProtect';

	public $controller;
	public $settings;

	public $components = array('Session', 'Cookie', 'Security');

	const SESSION_NAME = 'PasswordProtect';

/**
 * Stores the encrypted password data
 *
 * @var string
 **/
	private $_passEncrypted;

/**
 * Set to true if currently rendering the password form
 *
 * @var bool
 **/
	private $_renderingPasswordForm = false;
	
/** 
 * Assign the component settings
 *
 * - 'password': The master password for the application
 * - 'logoutUrl': The url to log the user out of the password session
 * - 'logoutMsg': The message to display when logging out
 * - 'defaultUrl': 
 * - 'require': The rules for when a password is required
 * 		- 'prefix': Include any prefixes
 *
 * @param ComponentCollection $collection The collection of controller components
 * @param array $settings The user-specified settings
 * @return void;
 **/
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		
		// Default Settings
		$default = array(
			'password' => 'default_password',
			'logoutUrl' => '/',
			'logoutMsg' => 'Successfully Logged out',

			'successMsg' => 'Successfully entered password.',
			'failMsg' => 'Incorrect Password',

			'defaultUrl' => '/',
			'require' => array('prefix' => 'admin'),
		);
		$this->settings = array_merge($default, $settings);
		
		// Stores the encrypted version of the password
		$this->_passEncrypted = $this->_encrypt($this->settings['password']);
	}
	
	public function startup(Controller $controller) {
		$this->controller = $controller;
		if (!empty($controller->request->data['PasswordProtect']['pass'])) {
			$data = $controller->request->data['PasswordProtect'];
			$redirect = null;
			if (!empty($data['redirect'])) {
				$redirect = $data['redirect'];
				if ($decodedRedirect = base64_decode($redirect)) {
					$redirect = $decodedRedirect;
				}
			}
			$dataPassEncrypted = $this->_encrypt($data['pass']);

			if ($dataPassEncrypted == $this->_passEncrypted) {
				if (empty($redirect)) {
					$redirect = $this->settings['defaultUrl'];
				}

				$this->set($dataPassEncrypted);
				$this->Session->setFlash($this->settings['successMsg']);
				$controller->redirect($redirect);
			} else {
				$this->Session->setFlash($this->settings['failMsg']);
				$this->redirectPasswordRequest($data['redirect']);
			}
		} else if (!empty($controller->request->query['logout'])) {
			$this->delete();
			$this->Session->setFlash($this->settings['logoutMsg']);
			$controller->redirect($this->settings['logoutUrl']);
		}

		$hasPassword = $this->check();
		$controller->set(compact('hasPassword'));
		$controller->set('passwordUrl', $this->settings['defaultUrl']);

		if ($this->_needsPassword($controller) && !$hasPassword) {
			$this->redirectPasswordRequest();
		}
		parent::startup($controller);
	}

	public function redirectPasswordRequest($redirect = null) {
		if (empty($redirect)) {
			$redirect = $this->_getRelativeHere();
		}
		$redirectUrl = array(
			'controller' => 'password_pages',
			'action' => 'request',
			'plugin' => 'password_protect',
			
			'?' => array('redirect' => base64_encode($redirect))
		);
		if (!empty($this->controller->request->params['prefix'])) {
			$redirectUrl[$this->controller->request->params['prefix']] = false;
		}
		$this->controller->redirect($redirectUrl);
	}

	public function renderPasswordRequest() {
		$controller = $this->controller;

		$element = DS . '..' . DS . 'Plugin' . DS . 'PasswordProtect' . DS;
		$element .= 'View' . DS . 'Elements' . DS . 'form';

		// $controller->request->data['PasswordProtect']['redirect'] = $this->_getRelativeHere();
		$controller->autoRender = false;
		return $controller->render($element);
	}

	// Session Values
	public function check() {
		$pass = null;
		if ($this->Session->check(self::SESSION_NAME)) {
			$pass = $this->Session->read(self::SESSION_NAME);
		} else if ($this->Cookie->read(self::SESSION_NAME)) {
			$pass = $this->Cookie->read(self::SESSION_NAME);
		}
		return $pass == $this->_passEncrypted;
	}
	
	private function set($password) {
		$this->Session->write(self::SESSION_NAME, $password);
		$this->Cookie->write(self::SESSION_NAME, $password);
		return true;
	}
	
	private function delete() {
		$this->Session->delete(self::SESSION_NAME);
		$this->Cookie->delete(self::SESSION_NAME);
		return true;
	}
	
	private function _needsPassword($controller) {
		if (!empty($this->settings['require'])) {
			if ($this->settings['require'] === true) {
				return true;
			}
			$matched = false;
			foreach ($this->settings['require'] as $key => $val) {
				if (isset($controller->request->params[$key])) {
					$matched = true;
					if ($controller->request->params[$key] != $val) {
						return false;
					}
				}
			}
			if ($matched) {
				return true;
			}
		}
	}
	
	private function _encrypt($phrase) {
		return Security::hash($phrase, null, true);
	}

	private function _getRelativeHere() {
		return substr($this->controller->request->here, strlen($this->controller->request->base));
	}
}