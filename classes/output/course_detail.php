<?php
namespace local_rofeo\output;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_course_category;
use core_course_list_element;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

class course_detail implements renderable, templatable {

    /** @var \stdClass */
    protected $course;

    public function __construct(\stdClass $course) {
        $this->course = $course;
    }

    public function export_for_template(renderer_base $output): array {
        global $CFG;

        $course = $this->course;
        $context = context_course::instance($course->id);
        $listelement = new core_course_list_element($course);
        
        $canaccess = can_access_course($course);
        $isloggedin = isloggedin() && !isguestuser();

        if ($canaccess) {
            $state = 'access';
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        } else if (!$isloggedin || enrol_selfenrol_available($course->id)) {
            // Anonymous visitors always get the button: they have to log in first,
            // and enrol/index.php will tell them where they stand afterwards.
            $state = 'request';
            $url = new moodle_url('/enrol/index.php', ['id' => $course->id]);
        } else {
            $state = 'closed';
            $url = null;
        }

        $image = '';
        foreach ($listelement->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $image = moodle_url::make_file_url(
                    $CFG->wwwroot . '/pluginfile.php',
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/'
                        . $file->get_filearea() . $file->get_filepath() . $file->get_filename()
                )->out(false);
                break;
            }
        }

        $categoryname = '';
        if ($category = core_course_category::get($course->category, IGNORE_MISSING, true)) {
            $categoryname = $category->get_formatted_name();
        }

        return [
            'fullname'     => format_string($course->fullname, true, ['context' => $context]),
            'categoryname' => $categoryname,
            'hascategory'  => (bool) $categoryname,
            'image'        => $image,
            'hasimage'     => (bool) $image,
            'summary'      => format_text($course->summary, $course->summaryformat, ['context' => $context]),
            'hassummary'   => trim(strip_tags($course->summary)) !== '',
            'fields'       => $this->export_fields(),
            'isenrolled'   => $isenrolled,
            'actionurl'    => $isenrolled
                ? (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false)
                : (new moodle_url('/enrol/index.php', ['id' => $course->id]))->out(false),
            'canaccess'  => $state === 'access',
            'canrequest' => $state === 'request',
            'isclosed'   => $state === 'closed',
            'actionurl'  => $url ? $url->out(false) : '',
        ];
    }

    protected function export_fields(): array {
        $result = [];
        $handler = \core_course\customfield\course_handler::create();

        foreach ($handler->export_instance_data($this->course->id, true) as $data) {
            $value = $data->get_value();

            if ($value === '' || $value === null || $value === '-') {
                continue;
            }

            $result[] = [
                'name'   => $data->get_name(),
                'value'  => $value,
                'islong' => $data->get_type() === 'textarea',
            ];
        }

        return $result;
    }
}