<?php
class PasswordPagesController extends AppController {
	public $name = 'PasswordPages';

//	public $components = array('PasswordProtect.PasswordProtect');

	public $uses = array();

	public function request() {
		if (!empty($this->request->query['redirect'])) {
			$this->request->data['PasswordProtect']['redirect'] = $this->request->query['redirect'];
		}
		$this->PasswordProtect->renderPasswordRequest();
	}
}