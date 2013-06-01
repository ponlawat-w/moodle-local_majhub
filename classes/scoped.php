<?php
/**
 *  MAJ Hub
 *  
 *  @author  VERSION2, Inc. (http://ver2.jp)
 *  @version $Id: scoped.php 104 2012-11-23 04:59:04Z malu $
 */
namespace majhub;

/**
 *  Scoped closure
 */
class scoped
{
    /** @var callable */
    private $callback;

    /**
     *  Constructor
     *  
     *  @param callable $callback
     */
    public function __construct(/*callable*/ $callback)
    {
        $this->callback = $callback;
    }

    /**
     *  Destructor
     */
    public function __destruct()
    {
        call_user_func($this->callback);
    }
}
