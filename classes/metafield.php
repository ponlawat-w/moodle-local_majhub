<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: metafield.php 163 2012-12-03 07:33:39Z malu $
 */
namespace majhub;

require_once __DIR__.'/exception.php';

/**
 *  Meta field definition
 */
class metafield
{
    const TABLE = 'majhub_courseware_metafields';

    const TYPE_TEXT  = 0;
    const TYPE_RADIO = 1;
    const TYPE_CHECK = 2;

    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var int */
    public $type;
    /** @var string[] */
    public $options;
    /** @var boolean */
    public $requried;
    /** @var boolean */
    public $optional;
    /** @var int */
    public $weight;

    /**
     *  Renders a dummy form element
     *  
     *  @param string $separator
     *  @return string
     */
    public function render($separator = "\n")
    {
        switch ($this->type) {
        case self::TYPE_TEXT:
            return \html_writer::empty_tag('input', array('type' => 'text'));
        case self::TYPE_RADIO:
            return implode($separator, array_map(function ($option)
            {
                return \html_writer::tag('label',
                    \html_writer::empty_tag('input', array('type' => 'radio')) .
                    htmlspecialchars($option));
            }, $this->options));
        case self::TYPE_CHECK:
            return implode($separator, array_map(function ($option)
            {
                return \html_writer::tag('label',
                    \html_writer::empty_tag('input', array('type' => 'checkbox')) .
                    htmlspecialchars($option));
            }, $this->options));
        }
        return '';
    }

    /**
     *  Renders a form element
     *  
     *  @param string $formname        The name of form element
     *  @param string $separator       The separator for TYPE_RADIO, TYPE_CHECK
     *  @param string|string[] $value  The plain value or form request value
     *  @param string $anylabel        The label of 'any' option for TYPE_RADIO
     *  @return string
     */
    public function render_form_element($formname, $separator = "\n", $value = null, $anylabel = null)
    {
        switch ($this->type) {
        case metafield::TYPE_TEXT:
            $attrs = array('type' => 'text', 'name' => "{$formname}[$this->id]");
            if ($value !== null)
                $attrs += array('value' => $value);
            return \html_writer::empty_tag('input', $attrs);
        case metafield::TYPE_RADIO:
            $radios = array();
            if ($anylabel !== null) {
                $attrs = array('type' => 'radio', 'name' => "{$formname}[$this->id]", 'value' => '');
                if ($value === null || strlen($value) == 0) {
                    $attrs += array('checked' => 'checked');
                }
                $radios[] = \html_writer::tag('label', \html_writer::empty_tag('input', $attrs) . $anylabel);
            }
            foreach ($this->options as $option) {
                $option = htmlspecialchars($option);
                $attrs = array('type' => 'radio', 'name' => "{$formname}[$this->id]", 'value' => $option);
                if ($option === $value) {
                    $attrs += array('checked' => 'checked');
                }
                $radios[] = \html_writer::tag('label', \html_writer::empty_tag('input', $attrs) . $option);
            }
            return implode($separator, $radios);
        case metafield::TYPE_CHECK:
            $checks = array();
            foreach ($this->options as $option) {
                $option = htmlspecialchars($option);
                $attrs = array('type' => 'checkbox', 'name' => "{$formname}[$this->id][$option]", 'value' => 1);
                if (is_array($value)) {
                    if (array_keys($value) !== range(0, count($value) - 1)) {
                        $value = array_keys(array_filter($value));
                    }
                    foreach ($value as $v) {
                        if (strcasecmp($option, $v) == 0) {
                            $attrs += array('checked' => 'checked');
                            break;
                        }
                    }
                }
                $checks[] = \html_writer::tag('label', \html_writer::empty_tag('input', $attrs) . $option);
            }
            return implode($separator, $checks);
        }
        return $this->render();
    }

    /**
     *  Validates the field definition
     *  
     *  @global \moodle_database $DB
     *  @return metafield
     *  @throws \moodle_exception
     */
    public function validate()
    {
        global $DB;

        if (strlen($this->name) == 0)
            throw new exception('metafield:emptyname');
        if ($this->type != self::TYPE_TEXT && empty($this->options))
            throw new exception('metafield:emptyoptions');
        foreach ($this->options as $i => $option) {
            foreach (array_slice($this->options, $i + 1) as $other) {
                if (strcasecmp($option, $other) == 0)
                    throw new exception('metafield:duplicateoption');
            }
        }
        $select = 'id <> :id AND ' . $DB->sql_like('name', ':name', false, false);
        $params = array('id' => $this->id, 'name' => $this->name);
        if ($DB->record_exists_select(self::TABLE, $select, $params))
            throw new exception('metafield:duplicatename');
        return $this;
    }

    /**
     *  Saves as a record
     *  
     *  @global \moodle_database $DB
     *  @return metafield
     *  @throws \moodle_exception
     */
    public function save()
    {
        global $DB;

        $this->validate();
        $record = new \stdClass;
        $record->id       = $this->id;
        $record->name     = $this->name;
        $record->type     = $this->type;
        $record->options  = empty($this->options) ? '' : ("\n" . implode("\n", $this->options) . "\n");
        $record->required = $this->required;
        $record->optional = $this->optional;
        $record->weight   = $this->weight;
        if (empty($record->id)) {
            $record->timecreated  = time();
            $record->timemodified = time();
            $this->id = $DB->insert_record(self::TABLE, $record);
        } else {
            $record->timemodified = time();
            $DB->update_record(self::TABLE, $record);
        }
        return $this;
    }

    /**
     *  Deletes the record
     *  
     *  @global \moodle_database $DB
     */
    public function delete()
    {
        global $DB;

        $DB->delete_records(self::TABLE, array('id' => $this->id));
    }

    /**
     *  Creates an instance of metafield from a record
     *  
     *  @param object $record
     *  @return metafield
     */
    public static function from_record($record)
    {
        $metafield = new metafield;
        $metafield->id       = (int)$record->id;
        $metafield->name     = trim($record->name);
        $metafield->type     = (int)$record->type;
        $metafield->options  = array_map('trim', preg_split('/[\r\n]+/', $record->options, -1, PREG_SPLIT_NO_EMPTY));
        $metafield->required = !empty($record->required);
        $metafield->optional = !empty($record->optional);
        $metafield->weight   = (int)$record->weight;
        return $metafield;
    }

    /**
     *  Creates an instance of a metafield from id
     *  
     *  @global \moodle_database $DB
     *  @param int $id
     *  @param int $strictness
     *  @return competition|null
     */
    public static function from_id($id, $strictness = IGNORE_MISSING)
    {
        global $DB;

        $record = $DB->get_record(self::TABLE, array('id' => $id), '*', $strictness);
        return $record ? self::from_record($record) : null;
    }

    /**
     *  Gets all the meta fields
     *  
     *  @global \moodle_database $DB
     *  @return metafield[]
     */
    public static function all()
    {
        global $DB;

        static $metafields = null;
        if ($metafields === null) {
            $metafields = array();
            $records = $DB->get_records(self::TABLE, null, 'optional ASC, weight ASC, name ASC');
            foreach ($records as $record) {
                $metafields[$record->id] = self::from_record($record);
            }
        }
        return $metafields;
    }

    /**
     *  Re-orders all the meta fields
     *  
     *  @global \moodle_database $DB
     *  @return metafield[]
     */
    public static function reorder()
    {
        global $DB;

        $records = $DB->get_records(self::TABLE, null, 'optional ASC, weight ASC, name ASC', 'id');
        foreach (array_values($records) as $i => $record) {
            $DB->set_field(self::TABLE, 'weight', $i + 1, array('id' => $record->id));
        }
    }
}
