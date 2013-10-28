<?php // $Id: leaderboard.php 202 2013-01-30 09:18:03Z malu $

defined('MOODLE_INTERNAL') || die;

if (false) {
    $DB = new mysqli_native_moodle_database;
}

const LEADERBOARD_LIMIT = 5;
/*
$mostdownloadedcoursewares = $DB->get_records_sql(
    'SELECT cw.*, (SELECT COUNT(*) FROM {majhub_courseware_downloads} d WHERE d.coursewareid = cw.id) AS num_downloads
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY num_downloads DESC, cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);
    */
$mostdownloadedcoursewares = $DB->get_records_sql(
    'SELECT cw.*, (SELECT COUNT(*) FROM {majhub_courseware_downloads} d WHERE d.siteid = cw.siteid AND d.sitecourseid = cw.sitecourseid) AS num_downloads
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY num_downloads DESC, cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);

/*
$topratedcoursewares = $DB->get_records_sql(
    'SELECT cw.*, (SELECT AVG(r.rating) FROM {majhub_courseware_reviews} r WHERE r.coursewareid = cw.id) AS avg_rating,
                  (SELECT COUNT(*) FROM {majhub_courseware_reviews} r WHERE r.coursewareid = cw.id) AS num_reviews
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY avg_rating DESC, num_reviews DESC, cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);
    */
$topratedcoursewares = $DB->get_records_sql(
    'SELECT cw.*, (SELECT AVG(r.rating) FROM {majhub_courseware_reviews} r WHERE r.siteid = cw.siteid AND r.sitecourseid = cw.sitecourseid) AS avg_rating,
                  (SELECT COUNT(*) FROM {majhub_courseware_reviews} r WHERE r.coursewareid = cw.id) AS num_reviews
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY avg_rating DESC, num_reviews DESC, cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);

/*
$mostreviewedcoursewares = $DB->get_records_sql(
    'SELECT cw.*, (SELECT COUNT(*) FROM {majhub_courseware_reviews} r WHERE r.coursewareid = c.id) AS num_reviews
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY num_reviews DESC, cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);
    */
$mostreviewedcoursewares = $DB->get_records_sql(
    'SELECT cw.*, (SELECT COUNT(*) FROM {majhub_courseware_reviews} r WHERE r.siteid = cw.siteid AND r.sitecourseid = cw.sitecourseid) AS num_reviews
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY num_reviews DESC, cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);

$latestcoursewares = $DB->get_records_sql(
    'SELECT cw.*
     FROM {majhub_coursewares} cw JOIN {course} c ON c.id = cw.courseid WHERE cw.deleted = 0
     ORDER BY cw.timeuploaded DESC',
    null, 0, LEADERBOARD_LIMIT);


function leaderboard_print_list($title, array $coursewares)
{
    static $previewicon = null;
    if ($previewicon === null) {
        $previewicon = $GLOBALS['OUTPUT']->pix_icon('t/preview', get_string('previewthiscourseware', 'local_majhub'));
    }
    echo html_writer::tag('h4', $title);
    echo html_writer::start_tag('ol', array('style' => 'margin:0.5em 0 0 2em;'));
    foreach ($coursewares as $courseware) {
        $previewurl = new moodle_url('/course/view.php', array('id' => $courseware->courseid));
        $item = html_writer::tag('span', $courseware->fullname, array('style' => 'margin-right:0.5em;'))
              . html_writer::link($previewurl, $previewicon);
        echo html_writer::tag('li', $item, array('style' => 'line-height:1.5em;'));
    }
    echo html_writer::end_tag('ol');
}

?>
<table class="generaltable" style="margin:0; width:100%; border-spacing:8px; border-collapse:separate; border:none; background-color:transparent;">
<col width="25%" />
<col width="25%" />
<col width="25%" />
<col width="25%" />
<tr>
  <td><?php leaderboard_print_list(get_string('mostdownloaded', 'local_majhub'), $mostdownloadedcoursewares); ?></td>
  <td><?php leaderboard_print_list(get_string('toprated', 'local_majhub'), $topratedcoursewares); ?></td>
  <td><?php leaderboard_print_list(get_string('mostreviewed', 'local_majhub'), $mostreviewedcoursewares); ?></td>
  <td><?php leaderboard_print_list(get_string('latest', 'local_majhub'), $latestcoursewares); ?></td>
</tr>
</table>
