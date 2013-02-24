<?php
class PasswordProtectComponent extends Component {
	var $name = 'PasswordProtect';

	var $controller;
	var $components = array('Session', 'Cookie');
	
	var $loginUrl = array('controller' => 'pages', 'action' => 'display', 'PasswordProtect', 'admin' => false);
	var $defaultUrl = array('controller' => 'gallery_images', 'action' => 'index', 'admin' => true);
	var $logoutUrl = '/';
	
	var $password = '7089';

	
	private $__sessionName = 'PasswordProtect';
	
	function startup(Controller $controller) {
		if (!empty($controller->request->data['PasswordProtect']['pass'])) {
			if ($controller->request->data['PasswordProtect']['pass'] == $this->password) {
				if (!empty($controller->request->data['PasswordProtect']['redirect'])) {
					$redirect = $controller->request->data['PasswordProtect']['redirect'];
				} else {
					$redirect = $this->defaultUrl;
				}
				$this->set($controller);
				$controller->redirect($redirect);
			} else {
				$this->Session->setFlash('Incorrect Password');
				$this->_redirectPasswordRequest($controller);
			}
		} else if (!empty($controller->request->query['logout'])) {
			$this->delete();
			$this->Session->setFlash('Successfully Logged out');
			$controller->redirect($this->logoutUrl);
		}
		
		$controller->set('passwordSuccess', $this->check());
		
		parent::startup($controller);
	}
	
	function beforeRender(Controller $controller) {
		if ($this->_needsPassword($controller) && !$this->check()) {
			$this->_redirectPasswordRequest($controller);
		}
		parent::beforeRender($controller);
	}
	
	public function check() {
		$success = $this->Session->check($this->__sessionName);
		if (!$success) {
			$success = $this->Cookie->read($this->__sessionName);
		}
		return $success;
	}
	
	private function set() {
		$this->Session->write($this->__sessionName, true);
		$this->Cookie->write($this->__sessionName, true);
		return true;
	}
	
	private function delete() {
		$this->Session->delete($this->__sessionName);
		$this->Cookie->delete($this->__sessionName);
		return true;
	}
	
	private function _needsPassword($controller) {
		return (!empty($controller->request->params['prefix']) && $controller->request->params['prefix'] == 'admin');
	}
	
	private function _redirectPasswordRequest($controller) {
		$element = HOME . DS . 'Plugin' . DS . 'PasswordProtect' . DS;
		$element .= 'View' . DS . 'Element' . DS . 'form';
		$this->request->data['PasswordProtect']['redirect'] = $controller->request->here;
		return $controller->render($element);
	}
}