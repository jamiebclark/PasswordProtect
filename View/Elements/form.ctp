<?php
if (!$this->Html->value('PasswordProtect.redirect') && !empty($this->request->query['redirect'])) {
	$this->request->data['PasswordProtect']['redirect'] = $this->request->query['redirect'];
}
echo $this->Form->create('PasswordProtect');
echo $this->Form->hidden('redirect');
echo $this->Form->input('pass', array(
	'label' => 'Enter your Password',
	'type' => 'password',
));
echo $this->Form->end('Log in');