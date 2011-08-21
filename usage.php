<?php

/**
 * Demonstration of usage
 *
 * bsMailer library
 * byStac. 2009. iphraimov@gmail.com
 *
 */
include 'bsMailer.php';

/* for now support onlu mail() but Zend_mail also supports SMTP just need to implement it */
$bsMailer = new bsMailer('sample-tpls', 'en');
$bsMailer->setTemplate('account-suspended');
$bsMailer->setEmailFrom('stac@bystac.net');

$list_suspended = $objDb
			->query("SELECT email, fullname
					FROM users
					WHERE suspended=1 AND suspend_date>'?'"
					, array(strtotime('-1 hour')))
			->fetchArray_list();

/*
 * $user = array ('email'=>'some@email.com', 'fullname'=>'byStac');
 */
$sent_out = 0;
foreach ($list_suspended as $user) {
	$bsMailer->setVariables($user);
	$bsMailer->setEmailTo($user['email'], $user['fullname']);
	if ($bsMailer->send()) {
		/* update db row, email sent to the user */
		$sent_out++;
	}
}

print 'Total suspend email sent: '.$sent_out;



/* Additional usage as template engine */
$bsMailer = new bsMailer('some_templates_dir', 'en', 'thank-you-page');
$bsMailer->setVariables(array('order_amount'=>'100$', 'costumer_name'=>'shachar'));
print '<h1>'.$bsMailer->getTemplateContent('subject', true).'</h1>';
print $bsMailer->getTemplateContent('html', true);

/*
	testing the new edit files at github.
*/