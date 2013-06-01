<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: criterion.php 176 2013-01-24 12:11:41Z malu $
 */
namespace majhub;

/**
 *  SQL criterion
 */
class criterion
{
    /** @var string */
    public $expression;
    /** @var string[] */
    public $parameters;

    /**
     *  Joins criteria
     *  
     *  @param string      $andor     'AND' | 'OR'
     *  @param criterion[] $criteria
     *  @return criterion
     */
    public static function join($andor, array $criteria)
    {
        if (empty($criteria))
            return self::everything();
        $expressions = array();
        $parameters  = array();
        foreach ($criteria as $criterion) {
            $expressions[] = $criterion->expression;
            $parameters = array_merge($parameters, $criterion->parameters);
        }
        $criterion = new criterion;
        $criterion->expression = '(' . implode(" $andor ", $expressions) . ')';
        $criterion->parameters = $parameters;
        return $criterion;
    }

    /**
     *  Creates a criterion for text input
     *  
     *  @global \moodle_database $DB
     *  @param string $fieldname
     *  @param string $formvalue
     *  @return criterion
     */
    public static function text($fieldname, $formvalue)
    {
        global $DB;

        $criterion = new criterion;
        $criterion->expression = $DB->sql_like($fieldname, '?', false);
        $criterion->parameters = array('%' . $DB->sql_like_escape($formvalue) . '%');
        return $criterion;
    }

    /**
     *  Creates a criterion for radio buttons
     *  
     *  @global \moodle_database $DB
     *  @param string $fieldname
     *  @param string $formvalue
     *  @return criterion
     */
    public static function radio($fieldname, $formvalue)
    {
        global $DB;

        $criterion = new criterion;
        $criterion->expression = $DB->sql_like($fieldname, '?', false);
        $criterion->parameters = array($DB->sql_like_escape($formvalue));
        return $criterion;
    }

    /**
     *  Creates a criterion for checkboxes
     *  
     *  @param string $fieldname
     *  @param string[] $formvalue
     *  @return criterion
     */
    public static function check($fieldname, array $formvalue)
    {
        $criteria = array();
        foreach ($formvalue as $option => $checked) {
            if ($checked)
                $criteria[] = self::text($fieldname, "\n" . $option . "\n");
        }
        return self::join('OR', $criteria);
    }

    /**
     *  Creates a criterion for select everything
     */
    public static function everything()
    {
        $criterion = new criterion;
        $criterion->expression = '1 = 1';
        $criterion->parameters = array();
        return $criterion;
    }

    /**
     *  Creates a criterion for select nothing
     */
    public static function nothing()
    {
        $criterion = new criterion;
        $criterion->expression = '1 = 0';
        $criterion->parameters = array();
        return $criterion;
    }
}
