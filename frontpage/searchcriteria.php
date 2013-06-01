<?php // $Id: searchcriteria.php 176 2013-01-24 12:11:41Z malu $

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/../classes/metafield.php';
require_once __DIR__.'/../classes/setting.php';

use majhub\metafield;
use majhub\setting;

if (false) {
    $PAGE = new moodle_page;
}

$fixedcriteria = array(
    'title'       => get_string('title', 'local_majhub'),
    'contributor' => get_string('contributor', 'local_majhub'),
    );

////////////////////////////////////////////////////////////////////////////////
// <form>
echo html_writer::start_tag('form', array('action' => $PAGE->url, 'method' => 'get', 'class' => 'mform'));

// criteria
$keywords = optional_param('keywords', '', PARAM_TEXT);
searchcriteria_print_fitem(get_string('keywords', 'local_majhub'),
    html_writer::empty_tag('input',
        array('type' => 'text', 'name' => 'keywords', 'value' => trim($keywords), 'size' => 50)
        ),
    '',
    array('style' => 'margin-bottom:1em;')
    );

foreach ($fixedcriteria as $key => $name) {
    $value = optional_param($key, '', PARAM_TEXT);
    $element = html_writer::empty_tag('input', array('type' => 'text', 'name' => $key, 'value' => $value));
    searchcriteria_print_fitem($name, $element);
}

$anylabel = get_string('any');
foreach (metafield::all() as $metafield) {
    $value = isset($_REQUEST['metadata'][$metafield->id]) ? $_REQUEST['metadata'][$metafield->id] : null;
    $element = $metafield->render_form_element('metadata', ' ', $value, $anylabel);
    searchcriteria_print_fitem($metafield->name, $element, $metafield->optional ? 'optional' : '');
}

// order
searchcriteria_print_fitem(
    get_string('sortby'),
    html_writer::select(
        array(
            'newest'      => get_string('sortby:newest', 'local_majhub'),
            'oldest'      => get_string('sortby:oldest', 'local_majhub'),
            'title'       => get_string('sortby:title', 'local_majhub'),
            'contributor' => get_string('sortby:contributor', 'local_majhub'),
            'rating'      => get_string('sortby:rating', 'local_majhub'),
            ),
        'sortby', optional_param('sortby', 'newest', PARAM_TEXT), false),
    '',
    array('style' => 'margin-top:1em;')
    );

// limit
$limits = array_map('intval', explode(',', setting::get('coursewaresperpageoptions')));
$limit = optional_param('limit', setting::get('coursewaresperpagedefault'), PARAM_INT);
searchcriteria_print_fitem(
    get_string('coursewaresperpage', 'local_majhub'),
    html_writer::select(array_combine($limits, $limits), 'limit', $limit, false)
    );

// submit
$searchbutton = html_writer::empty_tag('input',
    array('type' => 'submit', 'name' => 'search', 'value' => get_string('searchforcoursewares', 'local_majhub'))
    );
echo html_writer::tag('div',
    html_writer::tag('div', $searchbutton, array('class' => 'felement', 'style' => 'margin-bottom:0.5em;')),
    array('class' => 'fitem')
    );

echo html_writer::end_tag('form');
// </form>
////////////////////////////////////////////////////////////////////////////////


function searchcriteria_print_fitem($title, $element, $additionalclass = '', array $attributes = array())
{
    $classes = array('fitem');
    if (!empty($additionalclass))
        $classes[] = $additionalclass;
    echo html_writer::start_tag('div', array('class' => implode(' ', $classes)) + $attributes);
    echo html_writer::tag('div', $title, array('class' => 'fitemtitle'));
    echo html_writer::tag('div', $element, array('class' => 'felement'));
    echo html_writer::end_tag('div');
}
