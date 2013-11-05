<?php // $Id: searchresults.php 222 2013-02-22 03:52:44Z malu $

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/../classes/criterion.php';
require_once __DIR__.'/../classes/metafield.php';
require_once __DIR__.'/../classes/extension.php';
require_once __DIR__.'/../classes/setting.php';
require_once __DIR__.'/../classes/point.php';

use majhub\criterion;
use majhub\metafield;
use majhub\extension;
use majhub\setting;
use majhub\point;

if (false) {
    $DB     = new mysqli_native_moodle_database;
    $OUTPUT = new core_renderer;
}

echo html_writer::start_tag('div', array('class' => 'course-content path-course-view'));

$defaultlimit = setting::get('coursewaresperpagedefault');
if ($defaultlimit > 0) {
    $keywords    = preg_split('/\s+/', optional_param('keywords', '', PARAM_TEXT), -1, PREG_SPLIT_NO_EMPTY);
    $title       = optional_param('title', '', PARAM_TEXT);
    $contributor = optional_param('contributor', '', PARAM_TEXT);
    $metadata    = isset($_REQUEST['metadata']) && is_array($_REQUEST['metadata']) ? $_REQUEST['metadata'] : array();
    $sortby      = optional_param('sortby', 'newest', PARAM_TEXT);
    $offset      = max(0, optional_param('offset', 0, PARAM_INT));
    $limit       = min(optional_param('limit', $defaultlimit, PARAM_INT), 100);

    $criteria = array();
    foreach ($keywords as $keyword) {
        $keywordcriteria = array(
            criterion::text('cw.fullname', $keyword), criterion::text('cw.shortname', $keyword),
            criterion::text('u.firstname', $keyword), criterion::text('u.lastname', $keyword),
            );
        foreach (metafield::all() as $metafield) {
            $fieldname = "m{$metafield->id}.value";
            $keywordcriteria[] = criterion::text($fieldname, $keyword);
        }
        $criteria[] = criterion::join('OR', $keywordcriteria);
    }
    if (strlen($title) != 0) {
        $criteria[] = criterion::join('OR',
            array(criterion::text('cw.fullname', $title), criterion::text('cw.shortname', $title))
            );
    }
    if (strlen($contributor) != 0) {
        // considers "firstname lastname" or "lastname firstname" given
        $namecriteria = array();
        foreach (preg_split('/\s+/', $contributor, -1, PREG_SPLIT_NO_EMPTY) as $name) {
            $namecriteria[] = criterion::join('OR',
                array(criterion::text('u.firstname', $name), criterion::text('u.lastname', $name))
                );
        }
        $criteria[] = criterion::join('AND', $namecriteria);
    }
    foreach (metafield::all() as $metafield) {
        if (isset($metadata[$metafield->id])) {
            $fieldname = "m{$metafield->id}.value";
            switch ($metafield->type) {
            case metafield::TYPE_TEXT:
                if (strlen($value = trim($metadata[$metafield->id])) != 0) {
                    $criteria[] = criterion::text($fieldname, $value);
                }
                break;
            case metafield::TYPE_RADIO:
                if (strlen($metadata[$metafield->id]) != 0) {
                    $criteria[] = criterion::radio($fieldname, $metadata[$metafield->id]);
                }
                break;
            case metafield::TYPE_CHECK:
                $criteria[] = criterion::check($fieldname, $metadata[$metafield->id]);
                break;
            }
        }
    }
    $criterion = criterion::join('AND', $criteria);

    $sql = 'SELECT cw.id, cw.fullname, cw.courseid, cw.demourl, cw.backuprelease, cw.backupversion, cw.unrestorable, 
			cw.timeuploaded, u.firstname, u.lastname,
                   (SELECT AVG(r.rating) FROM {majhub_courseware_reviews} r WHERE r.coursewareid = cw.id) AS rating,
                   (SELECT COUNT(*) FROM {majhub_courseware_reviews} r WHERE r.coursewareid = cw.id) AS num_reviews
            FROM {majhub_coursewares} cw
            JOIN {course} c ON c.id = cw.courseid
            JOIN {user} u ON u.id = cw.userid';
    foreach (metafield::all() as $metafield) {
        $sql .= " LEFT JOIN {majhub_courseware_metadata} m{$metafield->id}
                         ON m{$metafield->id}.coursewareid = cw.id AND m{$metafield->id}.metafieldid = $metafield->id";
    }
    $sql .= ' WHERE cw.deleted = 0 AND ' . $criterion->expression;
    $sql .= ' GROUP BY cw.id, cw.fullname, cw.courseid, cw.demourl, cw.timeuploaded, u.firstname, u.lastname';
    $orderby = 'timeuploaded DESC';
    switch ($sortby) {
    case 'newest': $orderby = 'timeuploaded DESC'; break;
    case 'oldest': $orderby = 'timeuploaded ASC'; break;
    case 'title' : $orderby = 'fullname ASC'; break;
    case 'rating': $orderby = 'rating DESC, num_reviews DESC'; break;
    case 'contributor':
        $fullnamefields = fullname((object)array('firstname' => 'firstname', 'lastname' => 'lastname'));
        $matches = array();
        if (preg_match_all('/firstname|lastname/', $fullnamefields, $matches, PREG_PATTERN_ORDER)) {
            $orderby =  implode(', ', $matches[0]);
        }
        break;
    }
    $coursewares = $DB->get_records_sql($sql . ' ORDER BY ' . $orderby, $criterion->parameters, $offset, $limit);
    $coursewares_count = $DB->count_records_sql("SELECT COUNT(*) FROM ($sql) _tmp", $criterion->parameters);

    $strpreview  = get_string('preview', 'local_majhub');
    $strdownload = get_string('download', 'local_majhub');
    $strdemosite = get_string('demosite', 'local_majhub');
    $strpreviewtip  = get_string('previewthiscourseware', 'local_majhub');
    $strdownloadtip = get_string('downloadthiscourseware', 'local_majhub');
    $strdemositetip = get_string('visitauthorsdemosite', 'local_majhub');

    $previewicon   = $OUTPUT->pix_icon('t/preview', '', '', array('style' => 'margin:0 2px 0 0;'));
    $downloadicon  = $OUTPUT->pix_icon('t/download', '', '', array('style' => 'margin:-1px 2px -4px 0;'));
    $visitdemoicon = $OUTPUT->pix_icon('i/moodle_host', '', '', array('style' => 'margin:-1px 2px -4px 0;'));

    $buttonstyle = 'float:left; margin:0.5em 0.5em 0.5em 0; width:8em; height:24px; line-height:24px;'
                 . ' text-align:center; border:1px solid silver; border-radius:5px; box-shadow:1px 1px 2px silver;';
    $infostyle = 'float:left; margin:0.5em; width:8em; height:24px; line-height:24px; text-align:center;';

    $pager = '';
    if (count($coursewares) < $coursewares_count) {
        $queryurl = new moodle_url('/#searchresults');
        $addparam = function ($name, $value) use (&$addparam, $queryurl)
        {
            if (!is_array($value))
                $queryurl->param($name, $value);
            else foreach ($value as $k => $v)
                $addparam("{$name}[$k]", $v);
        };
        foreach ($_REQUEST as $name => $value)
            $addparam($name, $value);
        $prevpage = $offset - $limit >= 0
                  ? html_writer::link(new moodle_url($queryurl, array('offset' => $offset - $limit)), $OUTPUT->larrow())
                  : $OUTPUT->larrow();
        $nextpage = $offset + $limit < $coursewares_count
                  ? html_writer::link(new moodle_url($queryurl, array('offset' => $offset + $limit)), $OUTPUT->rarrow())
                  : $OUTPUT->rarrow();
        $pagestyle = 'margin:0 0.5em;';
        $pager .= html_writer::tag('span', $prevpage, array('style' => $pagestyle));
        for ($i = 0; $i < $coursewares_count; $i += $limit) {
            $page = 1 + $i / $limit;
            if ($i != $offset) {
                $page = html_writer::link(new moodle_url($queryurl, array('offset' => $i)), $page);
            }
            $pager .= html_writer::tag('span', $page, array('style' => $pagestyle));
        }
        $pager .= html_writer::tag('span', $nextpage, array('style' => $pagestyle));
    }

    echo html_writer::tag('div', $pager, array('style' => 'text-align:center; margin:0 1em 0.5em 1em;'));
    echo html_writer::start_tag('ul', array('class' => 'topics'));
    if (empty($coursewares)) {
        echo html_writer::start_tag('li', array('class' => 'section main clearfix'));
        {
            echo html_writer::tag('div', get_string('noresult', 'local_majhub'), array('class' => 'content'));
        }
        echo html_writer::end_tag('li');
    } else foreach ($coursewares as $courseware) {
        $previewurl = new moodle_url('/course/view.php', array('id' => $courseware->courseid));
        $downloadurl = new moodle_url('/local/majhub/download.php', array('id' => $courseware->id));
        echo html_writer::start_tag('li', array('class' => 'section main clearfix'));
        {
            // special informations and action buttons
            echo html_writer::start_tag('div', array('style' => 'float:right; white-space:nowrap;'));
            foreach (extension::all() as $extension) {
                if ($info = $extension->get_courseware_info($courseware)) {
                    echo html_writer::tag('div', $info, array('style' => $infostyle));
                }
            }
			if($courseware->unrestorable){
				echo html_writer::tag('div', $strpreview, array('style' => $buttonstyle . ' color:silver;', 'title' => get_string('error:coursecannotbepreviewed', 'local_majhub')));
			}else{
				echo html_writer::tag('div',
					html_writer::link($previewurl, $previewicon . $strpreview, array('title' => $strpreviewtip)),
					array('style' => $buttonstyle)
					);
			}
                
		//download Justin 20131030 The old download link had different behaviour to the link the courseware block
    	$paid = $DB->record_exists('majhub_courseware_downloads',
            array('userid' => $USER->id, 'coursewareid' => $courseware->id));
        $userpoint = majhub\point::from_userid($USER->id);
        $downloadcost = majhub\point::get_settings()->pointsfordownloading;
        if ($paid) {
            echo html_writer::tag('div',
                html_writer::link($downloadurl, $downloadicon . $strdownload, array('title' => $strdownloadtip)),
                array('style' => $buttonstyle)
                );
        } elseif ($userpoint->total >= $downloadcost) {
            $confirmpurchase = new confirm_action(get_string('confirm:payfordownload', 'local_majhub', $downloadcost));
            echo html_writer::tag('div', 
            	$OUTPUT->action_link($downloadurl, $downloadicon . $strdownload, $confirmpurchase, array('title' => $strdownloadtip)),
            	 array('style' => $buttonstyle)
            	 );
        } else {
            echo html_writer::tag('div', $strdownload, array('style' => $buttonstyle . ' color:silver;', 'title' => get_string('error:youdonthaveenoughpoints', 'local_majhub')));
        }
			  /*  
            echo html_writer::tag('div',
                html_writer::link($downloadurl, $downloadicon . $strdownload, array('title' => $strdownloadtip)),
                array('style' => $buttonstyle)
                );
                */
                
            if (empty($courseware->demourl)) {
                echo html_writer::tag('div', $strdemosite, array('style' => $buttonstyle . ' color:silver;'));
            } else {
                echo html_writer::tag('div',
                    html_writer::link($courseware->demourl, $visitdemoicon . $strdemosite, array('title' => $strdemositetip)),
                    array('style' => $buttonstyle)
                    );
            }
            echo html_writer::end_tag('div');

            // courseware infomation
            echo html_writer::start_tag('div', array('class' => 'content', 'style' => 'margin:0 20px;'));
            echo html_writer::tag('h4', $courseware->fullname, array('class' => 'sectionname'));
            echo html_writer::start_tag('div', array('style' => 'margin:2px 0 0 10px; color:gray;'));
            echo html_writer::tag('span', get_string('contributor', 'local_majhub') . ': ' . fullname($courseware));
			echo html_writer::tag('span', get_string('moodleversion', 'local_majhub') . ': ' . $courseware->backuprelease,
					array('style' => 'margin:2px 0 0 10px; color:gray;'));
            // TODO: show more details
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        }
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
    echo html_writer::tag('div', $pager, array('style' => 'text-align:center; margin:0.5em 1em 0 1em;'));
}

echo html_writer::end_tag('div');
