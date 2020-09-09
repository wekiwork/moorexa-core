<?php
namespace Lightroom\Database\Promises\QueryPromise;

/**
 * @package QueryPromise Properties
 */
trait Properties
{
    public  $errors    = [];
    public  $hasError  = false;
    public  static $pdo       = null;
    public  static $loopid = null;
    private $_loopid = 0;
    protected $pdoStatement = null;
    private $fetch_records = null;
    public  $error = null;
    public  $ok = true;
    private $_rows = 0;
    public  $table = null;
    protected $bindData;
    private $fetchClass = null;
    public  $allowSlashes = false;
    public  $configData = ['allowSaveQuery' => true];
}