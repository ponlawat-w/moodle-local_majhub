<?php // $Id: frontpage.php 177 2013-01-25 12:39:58Z malu $

require_once __DIR__.'/../../../config.php';

require_once __DIR__.'/form.php';

$form = new majhub\admin\form('frontpage');
$form->add_text('coursewaresperpageoptions', '5, 10, 50, 100', function ($value)
{
    $items = array_map('intval', explode(',', $value));
    $items = array_filter($items, function ($v) { return $v > 0; });
    sort($items);
    return implode(', ', $items);
});
$form->add_int('coursewaresperpagedefault', '10', function ($v) { return max(0, $v); });

echo $form;
