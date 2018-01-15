<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *  Sharing Cart - Restore Operation
 *
 *  @package    block_majhub
 *  @copyright  2018 (C) Ponlawat Weerapanpisit
 *  @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/courseware.php');
require_once(__DIR__ . '/classes/point.php');

$courseid = required_param('courseid', PARAM_INT);
$rating = optional_param('rating', 0, PARAM_INT);
$comment = optional_param('comment','', PARAM_TEXT);
$proceed = optional_param('proceed', 0, PARAM_INT);

$courseware = \majhub\courseware::from_courseid($courseid);

$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/majhub/postpreview.php');
$PAGE->set_title(get_string('postreview', 'local_majhub', $courseware->fullname));
$PAGE->set_heading(get_string('postreview', 'local_majhub', $courseware->fullname));
$PAGE->navbar->add($courseware->fullname, new moodle_url("/course/view.php?id={$courseid}"));

echo $OUTPUT->header();

if ($rating < 1 || $rating > 10) {
    // User did not rate the course
    echo html_writer::start_tag('form', array('action' => new moodle_url('/course/view.php'), 'method' => 'post'));
    echo html_writer::tag('div', get_string('noratings', 'local_majhub'), array('class' => 'alert alert-danger'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $courseid));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'editreview', 'value' => 1));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'comment', 'value' => $comment));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'back', 'value' => get_string('back')));
    echo html_writer::end_tag('form');
}
else {
    $commentlength = mb_strlen($comment);
    $minlength = \majhub\point::get_settings()->lengthforreviewing;

    if ($commentlength >= $minlength || $proceed) {
        $review = $DB->get_record('majhub_courseware_reviews', array('userid' => $USER->id, 'coursewareid' => $courseware->id));
        if ($review) {
            // Edit record
            $review->rating = $rating;
            $review->comment = $comment;

            $DB->update_record('majhub_courseware_reviews', $review);
        }
        else {
            // New record
            $review = new stdClass();
            $review->userid = $USER->id;
            $review->coursewareid = $courseware->id;
            $review->siteid = $courseware->siteid;
            $review->sitecourseid = $courseware->sitecourseid;
            $review->rating = $rating;
            $review->comment = $comment;
            $review->timecreated = time();
            $review->timemodified = time();

            $DB->insert_record('majhub_courseware_reviews', $review);
        }

        if ($commentlength < $minlength) {
            echo html_writer::tag('div', get_string('reviewfinished', 'local_majhub'), array('class' => 'alert alert-success'));
        }
        else {
            $a = new stdClass();
            $a->point = \majhub\point::get_settings()->pointsforreviewing;
            $a->minlength = $minlength;
            echo html_writer::tag('div', get_string('pointsawarded', 'local_majhub', $a), array('class' => 'alert alert-success'));
        }

        echo html_writer::tag('a', get_string('back'), array('href' => new moodle_url('/course/view.php', array('id' => $courseid)), 'class' => 'btn btn-primary text-center'));
    }
    else {
        // Letters not enough

        $a = new stdClass();
        $a->commentlength = $commentlength;
        $a->minlength = $minlength;
        echo html_writer::tag('div', get_string('reviewlengthunderminimum', 'local_majhub', $a), array('class' => 'alert alert-warning'));

        $form_html = html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'comment', 'value' => $comment));
        $form_html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'rating', 'value' => $rating));

        // Write more?
        echo html_writer::start_tag('form', array('style' => 'display: inline-block;', 'action' => new moodle_url('/course/view.php'), 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $courseid));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'editreview', 'value' => 1));
        echo $form_html;
        echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'back', 'value' => get_string('addmorecharacter', 'local_majhub')));
        echo html_writer::end_tag('form');

        // Proceed anyway
        echo html_writer::start_tag('form', array('style' => 'display: inline-block;', 'action' => new moodle_url('/local/majhub/postreview.php'), 'method' => 'post'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $courseid));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'proceed', 'value' => 1));
        echo $form_html;
        echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'value' => get_string('finishreviewing', 'local_majhub')));
        echo html_writer::end_tag('form');
    }
}

echo $OUTPUT->footer();
exit;