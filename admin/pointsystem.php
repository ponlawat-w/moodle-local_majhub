<?php // $Id: pointsystem.php 208 2013-02-04 00:39:45Z malu $

require_once __DIR__.'/../../../config.php';

require_once __DIR__.'/form.php';
require_once __DIR__.'/../classes/point.php';

$form = new majhub\admin\form('pointsystem');

$pointsettings = new majhub\pointsettings;

$form->add_heading('pointacquisitions');
$form->add_int('pointsforregistration', $pointsettings->pointsforregistration);
$form->add_int('pointsforuploading', $pointsettings->pointsforuploading);
$form->add_int('pointsforreviewing', $pointsettings->pointsforreviewing);
$form->add_int('pointsforquality', $pointsettings->pointsforquality);
$form->add_int('pointsforpopularity', $pointsettings->pointsforpopularity);
$form->add_int('countforpopularity', $pointsettings->countforpopularity);
$form->add_int('lengthforreviewing', $pointsettings->lengthforreviewing);

$form->add_heading('pointconsumptions');
$form->add_int('pointsfordownloading', $pointsettings->pointsfordownloading);

echo $form;
