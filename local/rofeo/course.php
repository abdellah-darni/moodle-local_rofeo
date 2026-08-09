<?php
require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
$context = context_course::instance($course->id);

if ($CFG->forcelogin) {
    require_login();
}

if (!core_course_category::can_view_course_info($course) && !is_enrolled($context, null, '', true)) {
    throw new \moodle_exception('cannotviewcategory', '', $CFG->wwwroot . '/');
}

$PAGE->set_url('/local/rofeo/course.php', ['id' => $course->id]);
$PAGE->set_course($course);
$PAGE->set_pagelayout('base');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading('');

$detail = new \local_rofeo\output\course_detail($course);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_rofeo/course_detail', $detail->export_for_template($OUTPUT));
echo $OUTPUT->footer();