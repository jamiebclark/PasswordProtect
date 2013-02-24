<?php
class PasswordProtectComponent extends Component {
	var $name = 'PasswordProtect';

	var $controller;
	var $components = array('Session', 'Cookie', 'Security');

	private $__sessionName = 'PasswordProtect';
	private $__passEncrypted;
	
	function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->settings = array_merge(array(
			'password' => 'default_password',
			'logout' => '/',
			'defaultUrl' => '/',
			'require' => array('prefix' => 'admin'),
		), $settings);
		
		$this->__passEncrypted = $this->_encrypt($this->settings['password'], null, true);
	}
	
	function startup(Controller $controller) {
		if (!empty($controller->request->data['PasswordProtect']['pass'])) {
			$data = $controller->request->data['PasswordProtect'];
			if ($this->_encrypt($data['pass']) == $this->__passEncrypted) {
				$redirect = !empty($data['redirect']) ? $data['redirect'] : $this->settings['defualtUrl'];
				//$this->set($controller);
				$controller->redirect($redirect);
			} else {
				$this->Session->setFlash('Incorrect Password');
				$this->_redirectPasswordRequest($controller);
			}
		} else if (!empty($controller->request->query['logout'])) {
			$this->delete();
			$this->Session->setFlash('Successfully Logged out');
			$controller->redirect($this->settings['logout']);
		}
		$controller->set('hasPassword', $this->check());
		parent::startup($controller);
	}
	
	function beforeRender(Controller $controller) {
		if ($this->_needsPassword($controller) && !$this->check()) {
			$this->_redirectPasswordRequest($controller);
		}
		parent::beforeRender($controller);
	}
	
	public function check() {
		$pass = null;
		if ($this->Session->check($this->__sessionName)) {
			$pass = $this->Session->read($this->__sessionName);
		} else if ($this->Cookie->check($this->__sessionName)) {
			$pass = $this->Cookie->read($this->__sessionName);
		}
		return $pass == $this->__passEncrypted;
	}
	
	private function set($password) {
		$this->Session->write($this->__sessionName, $password);
		$this->Cookie->write($this->__sessionName, $password);
		return true;
	}
	
	private function delete() {
		$this->Session->delete($this->__sessionName);
		$this->Cookie->delete($this->__sessionName);
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

	private function _redirectPasswordRequest($controller) {
		$element = HOME . DS . 'Plugin' . DS . 'PasswordProtect' . DS;
		$element .= 'View' . DS . 'Element' . DS . 'form';
		$this->request->data['PasswordProtect']['redirect'] = $controller->request->here;
		return $controller->render($element);
	}
}