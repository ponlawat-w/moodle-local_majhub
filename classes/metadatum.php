<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: metadatum.php 116 2012-11-24 09:20:41Z malu $
 */
namespace majhub;

require_once __DIR__.'/metafield.php';

/**
 *  Meta datum
 *  
 *  @property-read int $id
 *  @property-read string $name
 *  @property-read int $type
 *  @property-read string[] $options
 *  @property-read boolean $required
 *  @property-read boolean $optional
 *  @property-read int $weight
 */
class metadatum
{
    const TABLE = 'majhub_courseware_metadata';

    /** @var metafield */
    public $field;
    /** @var string|string[] */
    public $value;

    /**
     *  Shortcut to get a field property
     *  
     *  @param string $name
     *  @return mixed
     */
    public function __get($name)
    {
        if (!property_exists($this->field, $name))
            throw new \InvalidArgumentException();
        return $this->field->$name;
    }

    /**
     *  Renders the value as a static text
     *  
     *  @param string $separator
     *  @return string
     */
    public function render($separator = "\n")
    {
        return is_array($this->value)
            ? implode($separator, array_map('htmlspecialchars', $this->value))
            : htmlspecialchars($this->value);
    }

    /**
     *  Renders a form element with the value
     *  
     *  @param string $formname
     *  @param string $separator
     *  @return string
     */
    public function render_form_element($formname, $separator = "\n")
    {
        return $this->field->render_form_element($formname, $separator, $this->value);
    }

    /**
     *  Sets a form value
     *  
     *  @param mixed $formvalue
     */
    public function set_form_value($formvalue)
    {
        if (is_array($this->value) && is_array($formvalue)) {
            $this->value = array_keys(array_filter($formvalue));
        } elseif (is_string($this->value) && is_string($formvalue)) {
            $this->value = trim($formvalue);
        }
    }
}
