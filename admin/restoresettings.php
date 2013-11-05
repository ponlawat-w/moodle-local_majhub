<?php // $Id: frontpage.php 177 2013-01-25 12:39:58Z malu $

require_once __DIR__.'/../../../config.php';

require_once __DIR__.'/form.php';

$form = new majhub\admin\form('restore');
$form->add_int('maxrestorablebackupsize', '1073741824', function ($v) { return max(0, $v); },20);
$form->add_int('minrestorableversion', '2011120500', function ($v) { return max(0, $v); },20);

echo $form;
