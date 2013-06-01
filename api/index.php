<?php // $Id: index.php 50 2012-11-12 10:03:07Z malu $

require_once __DIR__.'/../../../config.php';

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;
$root = $doc->appendChild($doc->createElement('majhub'));
$root->setAttribute('title', $SITE->fullname);

send_headers('application/xml; charset=UTF-8');
echo $doc->saveXML();
