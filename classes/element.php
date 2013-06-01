<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: element.php 176 2013-01-24 12:11:41Z malu $
 */
namespace majhub;

/**
 *  HTML element
 *  
 *  @method element id(string $id)
 *  @method element onclick(string $onclick)
 *  
 *  <form>
 *  @method element action(string $action)
 *  @method element method(string $method)
 *  @method element enctype(string $enctype)
 *  </form>
 *  
 *  <input>
 *  @method element name(string $name)
 *  @method element value(string $value)
 *  @method element type(string $type)
 *  @method element size(int $size)
 *  @method element checked(boolean $checked)
 *  </input>
 *  
 *  <option>
 *  @method element selected(boolean $selected)
 *  </option>
 *  
 *  <textarea>
 *  @method element cols(int $cols)
 *  @method element rows(int $rows)
 *  </textarea>
 *  
 *  <a>
 *  @method element href(string $href)
 *  </a>
 */
class element
{
    /** @var string */
    private $tagName;
    /** @var string[] */
    private $attributes;
    /** @var string[] */
    private $styles;
    /** @var element[] */
    private $children;
    /** @var string 'start' | 'end' */
    private $state;

    /**
     *  Constructor
     *  
     *  @param string $tagName
     */
    public function __construct($tagName)
    {
        $this->tagName    = strtolower($tagName);
        $this->attributes = array();
        $this->styles     = array();
        $this->children   = array();
        $this->state      = null;
    }

    /**
     *  Stringify
     */
    public function __toString()
    {
        if ($this->state === 'end')
            return \html_writer::end_tag($this->tagName);
        $styles = array();
        if (isset($this->attributes['style']))
            $styles[] = $this->attributes['style'];
        foreach ($this->styles as $name => $value)
            $styles[] = $name . ':' . $value . ';';
        $attr = empty($styles) ? array() : array('style' => implode(' ', $styles));
        if ($this->state === 'start')
            return \html_writer::start_tag($this->tagName, $this->attributes + $attr);
        if (empty($this->children) && $this->tagName !== 'script')
            return \html_writer::empty_tag($this->tagName, $this->attributes + $attr);
        return \html_writer::tag($this->tagName,
            implode('', $this->children), $this->attributes + $attr);
    }

    /**
     *  Sets an attribute
     *  
     *  @param string $name
     *  @param array $args
     *  @return element
     */
    public function __call($name, array $args)
    {
        if (count($args) != 1)
            throw new \InvalidArgumentException();
        $value = reset($args);
        if (is_bool($value)) {
            if ($value)
                $this->attributes[$name] = $name;
            else
                unset($this->attributes[$name]);
        } else {
            $this->attributes[$name] = $value;
        }
        return $this;
    }

    /**
     *  Sets classes
     *  
     *  @param string[] $class ...
     *  @return element
     */
    public function classes(/*$class/*, ...*/)
    {
        $classes = self::flatten(func_get_args());
        $this->attributes['class'] = implode(' ', iterator_to_array($classes));
        return $this;
    }

    /**
     *  Sets a style
     *  
     *  @param string $name
     *  @param string $value
     *  @return element
     */
    public function style($name, $value)
    {
        $this->styles[$name] = $value;
        return $this;
    }

    /**
     *  Sets styles
     *  
     *  @param array $styles
     *  @return element
     */
    public function styles(array $styles)
    {
        foreach ($styles as $name => $value)
            $this->styles[$name] = $value;
        return $this;
    }

    /**
     *  Appends a child element
     *  
     *  @param mixed[] $element ...
     *  @return element
     */
    public function append(/*element $element, ...*/)
    {
        foreach (func_get_args() as $element) {
            if (is_array($element))
                call_user_func_array(array($this, 'append'), $element);
            else
                $this->children[] = $element;
        }
        return $this;
    }

    /**
     *  Renders a start tag
     *  
     *  @return element
     */
    public function start()
    {
        $this->state = 'start';
        return $this;
    }

    /**
     *  Renders a end tag
     *  
     *  @return element
     */
    public function end()
    {
        $this->state = 'end';
        return $this;
    }

    /**
     *  Flattens an array recursively
     *  
     *  @param array $array
     *  @return \Traversable
     */
    private static function flatten(array $array)
    {
        return new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));
    }
}
