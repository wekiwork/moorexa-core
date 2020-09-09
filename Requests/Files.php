<?php
namespace Lightroom\Requests;

use Lightroom\Requests\Interfaces\FilesInterface;

/**
 * @package Files
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Files
{
    /**
     * @var array $file
     */
    private $file = [];

    /**
     * @method FilesInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_FILES has a key
     */
    public function has(string $key) : bool
    {
        return isset($_FILES[$key]) ? true : false;
    }

    /**
     * @method FilesInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_FILES has multiple keys
     */
    public function hasMultiple(...$multiple) : bool
    {
        // @var int $count
        $count = 0;

        // using foreach
        foreach ($multiple as $key):

            // checking
            if ($this->has($key)) $count++;

        endforeach;

        // return bool
        return ($count == count($multiple)) ? true : false;
    }

    /**
     * @method FilesInterface get
     * @param string $key
     * @return mixed
     * 
     * Gets a value from $_FILES with a $key
     */
    public function get(string $key)
    {
        // get all
        $all = $this->all();

        // @var string $value
        $value = isset($all[$key]) ? $all[$key] : '';

        // update file
        $this->file = $value;

        // return string
        return $value;
    }

    /**
     * @method Files __get
     * @param string $key 
     * @return string
     */
    public function __get(string $key) : string 
    {
        return $this->get($key);
    }

    /**
     * @method FilesInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_FILES
     */
    public function drop(string $key) : bool
    {
        // dropped
        $dropped = false;

        // check if key exists
        if (isset($_FILES[$key])) :

            // drop key
            unset($_FILES[$key]);

            // update dropped
            $dropped = true;

        endif;


        // return bool
        return $dropped;
    }

    /**
     * @method FilesInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_FILES
     */
    public function dropMultiple(...$multiple) : bool
    {
        // dropped
        $dropped = false;

        // drop count
        $count = 0;

        // using foreach loop
        foreach ($multiple as $key) :

            if ($this->drop($key)) $count++;

        endforeach;

        // update dropped
        if ($count >= 1) $dropped = true;

        // return bool
        return $dropped;
    }

    /**
     * @method FilesInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple array from $_FILES with respective keys
     */
    public function pick(...$keys) : array
    {
        // get all 
        $get = $this->all();

        // @var array picked
        $picked = [];

        // using foreach loop
        foreach ($keys as $key) :

            // check if key exists
            $picked[$key] = isset($get[$key]) ? $get[$key] : false;

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method Files except
     * @param array $keys 
     * @return array
     */
    public function except(...$keys) : array 
    {
        // @var array $picked
        $picked = $this->all();

        // using foreach loop
        foreach ($keys as $key) :

            // add to picked
            if (isset($picked[$key])) unset($picked[$key]);

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method FilesInterface all
     * @return array
     * 
     * Returns aLL $_FILES
     */
    public function all() : array
    {
        // @var array files
        $files = $_FILES;

        // new file
        $newFile = [];

        // run loop and clean it
        foreach ($files as $key => $value) :

            // check if value is array
            if (is_array($value) && is_array($value['name'])) :

                // run loop
                foreach ($value['name'] as $index => $name) :

                    // @var array $newValue
                    $newValue = ['name' => '', 'type' => '', 'error' => 0, 'tmp_name' => '', 'size' => ''];

                    // set new value
                    $newValue['name'] = $name;
                    $newValue['type'] = $value['type'][$index];
                    $newValue['error'] = $value['error'][$index];
                    $newValue['tmp_name'] = $value['tmp_name'][$index];
                    $newValue['size'] = $value['size'][$index];

                    // new value
                    $newFile[$key][] = $this->createClass($newValue);

                endforeach;

            else: 

                $newFile[$key] = $this->createClass($value);

            endif;

        endforeach;

        // return array
        return $newFile;
    }

    /**
     * @method FilesInterface empty
     * @return bool
     * 
     * Returns true if $_FILES is empty
     */
    public function empty() : bool
    {
        return count($_FILES) > 0 ? false : true;
    }

    /**
     * @method FilesInterface moveTo
     * @param string $destination
     * @return bool
     * 
     * Moves a file into a directory
     */
    public function moveTo(string $destination) : bool
    {
        // @var bool moved
        $moved = false;

        // check if file has data
        if (count($this->file) > 0) :

            // check multiple or single
            if (isset($this->file['name'])) : 
                // single
                if (move_uploaded_file($this->tmp_name, $destination . '/' . $this->name)) :
                    // update moved
                    $moved = true;
                endif;

                // Not moved
                if (!$moved) :
                    if (\copy($this->tmp_name, $destination . '/' . $this->name)) $moved = true;
                endif;

            else :
                // multiple. run foreach loop
                foreach ($this->file as $file) :
                    // try move file
                    if (move_uploaded_file($file->tmp_name, $destination . '/' . $file->name)) :
                        // update moved
                        $moved = true;
                    endif;

                    // Not moved
                    if (!$moved) :
                        if (\copy($file->tmp_name, $destination . '/' . $file->name)) $moved = true;
                    endif;
                endforeach;
            endif;
        endif;

        // return bool
        return $moved;
    }

    /**
     * @method FilesInterface clear
     * @return bool
     * 
     * Clears the $_FILES array
     */
    public function clear() : bool
    {
        // get all
        $all = $this->all();

        // get keys
        $keys = array_keys($all);

        // drop multiple
        call_user_func_array([$this, 'dropMultiple'], $keys);

        // clean up
        $all = null;

        // return boolean
        return true;
    }

    /**
     * @method Files createClass
     * @param array $file
     * @return mixed
     */
    private function createClass(array $file)
    {
        return new class($file)
        {
            use \Lightroom\Requests\Files;

            /**
             * @var array $file
             */
            private $file = [];

            /**
             * @method Files constructor
             * @param array $file
             */
            public function __construct(array $file)
            {
                $this->file = $file;
            }

            /**
             * @method Files getter
             * @param string $name
             * @return mixed
             */
            public function __get(string $name)
            {
                return $this->file[$name];
            }

            /**
             * @method Files getFile
             */
            public function getFile()
            {
                return $this->file;
            }
        };
    }
}