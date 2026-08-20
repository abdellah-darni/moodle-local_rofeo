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
$PAGE->add_body_class('rofeo-has-footer');

$detail = new \local_rofeo\output\course_detail($course);

$ogtitle = format_string($course->fullname, true, ['context' => $context, 'escape' => false]);

$ogdescription = format_text($course->summary, $course->summaryformat, ['context' => $context]);
$ogdescription = strip_tags($ogdescription);
$ogdescription = html_entity_decode($ogdescription, ENT_COMPAT);
$ogdescription = trim($ogdescription);
$ogdescription = shorten_text($ogdescription, 200);

$ogimageurl = null;
$listelement = new core_course_list_element($course);
foreach ($listelement->get_course_overviewfiles() as $file) {
    if ($file->is_valid_image()) {
        $ogimageurl = moodle_url::make_file_url(
            $CFG->wwwroot . '/pluginfile.php',
            '/' . $file->get_contextid() . '/' . $file->get_component() . '/'
                . $file->get_filearea() . $file->get_filepath() . $file->get_filename()
        );
        break;
    }
}

$ogtags = [
    html_writer::empty_tag('meta', ['property' => 'og:type', 'content' => 'article']),
    html_writer::empty_tag('meta', ['property' => 'og:title', 'content' => $ogtitle]),
    html_writer::empty_tag('meta', ['property' => 'og:description', 'content' => $ogdescription]),
    html_writer::empty_tag('meta', ['property' => 'og:url', 'content' => $PAGE->url]),
    html_writer::empty_tag('meta', ['name' => 'twitter:card', 'content' => 'summary_large_image']),
];

if ($ogimageurl) {
    $ogtags[] = html_writer::empty_tag('meta', ['property' => 'og:image', 'content' => $ogimageurl]);
}

if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .= "\n" . implode("\n", $ogtags);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_rofeo/course_detail', $detail->export_for_template($OUTPUT));
echo $OUTPUT->footer();