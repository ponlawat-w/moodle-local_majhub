<?php // $Id: form.php 177 2013-01-25 12:39:58Z malu $

namespace majhub\admin;

defined('MOODLE_INTERNAL') || die;

require_once __DIR__.'/../../../lib/adminlib.php';

require_once __DIR__.'/../classes/element.php';
require_once __DIR__.'/../classes/setting.php';

use majhub\element;
use majhub\setting;

function tag($tagName) { return new element($tagName); }

/**
 *  Admin form renderer
 */
class form
{
    /** @var object */
    private $data;
    /** @var string */
    private $heading;
    /** @var element[] */
    private $elements;

    /**
     *  Constructor
     *  
     *  @global \moodle_page $PAGE
     *  @global \core_renderer $OUTPUT
     *  @param string $name
     */
    public function __construct($name)
    {
        global $PAGE, $OUTPUT;

        $PAGE->set_url(new \moodle_url("/local/majhub/admin/{$name}.php"));
        $PAGE->navbar->ignore_active(true);
        $PAGE->navbar->add(\get_string('administrationsite'));
        $PAGE->navbar->add(\get_string('pluginname', 'local_majhub'));
        $PAGE->navbar->add(\get_string("settings/{$name}", 'local_majhub'), $PAGE->url);

        \admin_externalpage_setup("majhub/{$name}");

        $this->data = \data_submitted() and \require_sesskey();
        $this->heading = $OUTPUT->heading(\get_string("settings/{$name}", 'local_majhub'));
        $this->elements = array();
    }

    /**
     *  Renders a form
     *  
     *  @global \moodle_page $PAGE
     *  @global \core_renderer $OUTPUT
     *  @return string
     */
    public function __toString()
    {
        global $PAGE, $OUTPUT;

        $sesskey = tag('input')->type('hidden')->name('sesskey')->value(\sesskey());
        $submit = tag('input')->type('submit')->value(\get_string('savechanges', 'admin'));

        $html = '';
        $html .= $OUTPUT->header();
        $html .= $form = tag('form')->action($PAGE->url)->method('post')->id('adminsettings')->start();
        $html .= $div = tag('div')->classes('settingsform', 'clearfix')->start();
        $html .= tag('div')->style('display', 'none')->append($sesskey);

        $html .= $this->heading;
        $html .= $fieldset = tag('fieldset')->start();
        $html .= tag('div')->classes('clearer')->append('<!-- -->');
        foreach ($this->elements as $element) {
            $html .= $element;
        }
        $html .= $fieldset->end();

        $html .= tag('div')->classes('form-buttons')->append($submit->classes('form-submit'));
        $html .= $div->end();
        $html .= $form->end();
        $html .= $OUTPUT->footer();
        return $html;
    }

    /**
     *  Adds a heading
     *  
     *  @param string $name
     */
    public function add_heading($name)
    {
        $this->elements[] = tag('h3')->classes('main')
            ->append(\get_string($name, 'local_majhub'));
    }

    /**
     *  Adds a text field
     *  
     *  @param string $name
     *  @param string $default
     *  @param callback $filter
     *  @param int $size
     */
    public function add_text($name, $default = null, $filter = null, $size = 30)
    {
        $value = setting::get($name, $default);
        if (isset($this->data->{$name})) {
            if ($filter)
                $this->data->{$name} = $filter($this->data->{$name});
            if (strlen($this->data->{$name}) == 0 && $default !== null)
                $this->data->{$name} = $default;
            if ($this->data->{$name} !== $value)
                $value = setting::set($name, $this->data->{$name});
        }

        $label = \get_string($name, 'local_majhub');
        $element = tag('input')->type('text')->name($name)->value($value)->size($size);
        $defaultinfo = \get_string('defaultsettinginfo', 'admin', $default);

        $this->elements[] = tag('div')->classes('form-item', 'clearfix')->append(
            tag('div')->classes('form-label')->append(
                tag('label')->append($label),
                tag('span')->classes('form-shortname')->append($name)
                ),
            tag('div')->classes('form-setting')->append(
                tag('div')->classes('form-text', 'defaultsnext')->append($element),
                tag('div')->classes('form-defaultinfo')->append($defaultinfo)
                )
            );
    }

    /**
     *  Adds a numeric text field
     *  
     *  @param string $name
     *  @param string $default
     *  @param callback $filter
     *  @param int $size
     */
    public function add_int($name, $default = null, $filter = null, $size = 5)
    {
        if (isset($this->data->{$name}) && !ctype_digit($this->data->{$name})) {
            $this->data->{$name} = $default;
        }
        $this->add_text($name, $default, $filter, $size);
    }
}
