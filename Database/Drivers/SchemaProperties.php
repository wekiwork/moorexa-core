<?php
namespace Lightroom\Database\Drivers;

/**
 * @package Schema Properties
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait SchemaProperties
{
	public  $buildQuery = [];
	public  $seeded = 0;
	public  $lastsuccess = "";
	public  $queryInfo = [];
	public  static $dbName = ""; 
	public  $inserting = false;
	public  static $seed = 0;
	public  static $last = "";
	public  $driver = "";
    public  $databaseSource = "";
    private $other = '';
	private $number = '';
	public  $promises = [];
	public  $dropTables = [];
	public  $tableOptions = [];
	private $tableOptionsUsed = false;
	public  $createSQL = '';
	public  static $forceSQL = false;
	public  $tableRename = [];
	public  static $saveSql = true;
	public  $updateSchemaFromExternal = false;
}