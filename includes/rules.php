<?php

$arr = [
	[
		"key" => "mailbox_full",
		"name" => __('Mailbox Full', 'bounce-handler-mailpoet'),
		"title" => __('When mailbox is full', 'bounce-handler-mailpoet'),
		"regex" => '((mailbox|mailfolder|storage|quota|space) *(is)? *(over)? *(exceeded|size|storage|allocation|full|quota|maxi))|((over|exceeded|full) *(mail|storage|quota))'
	],
	[
		"key" => "mailbox_not_available",
		"name" => __('Mailbox not available', 'bounce-handler-mailpoet'),
		"title" => __('When mailbox is not available', 'bounce-handler-mailpoet'),
		"regex" => '(Invalid|no such|unknown|bad|des?activated|undelivered|inactive|unrouteable|delivery|mail ID|failed to|may not|no known user|email account) *(mail|destination|recipient|user|address|person|failure|has failed|does not exist|deliver to|exist|with this email|is closed)|RecipNotFound|status(-code)? *(:|=)? *5\.(1\.[1-6]|0\.0|4\.[0123467])|(user|mailbox|address|recipients?|host|account|domain) *(is|has been)? *(error|disabled|failed|unknown|unavailable|not *(found|available)|.{1,30}inactiv)|recipient *address *rejected|does *not *like *recipient|no *mailbox *here|user does.?n.t have.{0,20}account'
	],
	[
		"key" => "message_delayed",
		"name" => __('Message delayed', 'bounce-handler-mailpoet'),
		"title" => __('When message is delayed', 'bounce-handler-mailpoet'),
		"regex" => 'possible *mail *loop|too *many *hops|Action: *delayed|has.*been.*delayed|delayed *mail|temporary *failure'
	],
	[
		"key" => "failed_permanent",
		"name" => __('Failed Permanently', 'bounce-handler-mailpoet'),
		"title" => __('When failed permanently', 'bounce-handler-mailpoet'),
		"regex" => 'failed *permanently|permanent *(fatal)? *(failure|error)|Unrouteable *address|not *accepting *(any)? *mail'
	],
	[
		"key" => "action_required",
		"name" => __('Action Required', 'bounce-handler-mailpoet'),
		"title" => __('When you need to confirm you\'re a human being, forward to:', 'bounce-handler-mailpoet') ,
		"regex" => 'action *required|verif'
	],
	[
		"key" => "blocked_ip",
		"name" => __('Blocked IP', 'bounce-handler-mailpoet'),
		"title" => __('When you are flagged as a spammer forward the bounced message to', 'bounce-handler-mailpoet'),
		"regex" => 'is *(currently)? *blocked *by|block *list|spam *detected|(unacceptable|banned|offensive|filtered|blocked) *(content|message|e-?mail)|administratively *denied'
	]

];
