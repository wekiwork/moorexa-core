<?php
namespace Lightroom\Common;

use Closure;

/**
 * @package A Simple File handler
 * @author Amadi ifeanyi <amadiify.com>
 * 
 * This package can read to a file, write to a file, append to the end of a file, append once to the end of a file,
 * check if a file exists and works well with closure functions.
 */
class File
{
    /**
     * @var string $filepath
     */
    public $filepath = '';

    /**
     * @var File $instance
     */
    private static $instance;
    
    /**
     * @method File read
     * @param string $filepath
     * @param mixed $fallback_content
     * @return mixed
     * 
     * Reads a file from the filesystem. A fallback content can be added just in case file doesn't exists
     */
    public static function read(string $filepath, $fallback_content = '') 
    {
        // get file content
        $fileContent = file_exists($filepath) ? file_get_contents($filepath) : '';

        if ($fallback_content != '' && $fileContent == '') :

            // replace file content
            $fileContent = $fallback_content;

        endif;

        // return string
        return $fileContent;
    }

    /**
     * @method File write
     * @param mixed $content
     * @param mixed $filepath (can be an array of write paths)
     * @param mixed $callback
     * @param string $mode (write mode)
     * @return bool
     *
     * Writes array, string, object to a file.
     */
    public static function write($content, $filepath, $callback = false, string $mode = 'w+') : bool
	{ 
        // content returned
        $_content = $content;

        // get instance of class
        $instance = self::getInstance();

        // update file path
        $instance->filepath = $filepath;

        // write to file
        $write = false;

        // do we have an array or object?
		if (!is_string($content)) :
		
            // get content before buffer
			$before = ob_get_contents();
			ob_start();

            // export content to buffer 
			var_export($content);

            // get exported content
            $_content = ob_get_contents();
            
            // clean buffer
			ob_end_clean();

            // echo content before buffer
            echo $before;
            
        endif;


        // write to paths in $filepath array
		if (is_array($filepath)) :
        
            foreach ($filepath as $f => $path) :
            
                // write to file
                $write = self::write($content, $path);

            endforeach;

            // clean up
            unset($filepath, $f, $path);
        
        else:
        
            // create file if it doesn't exists  
            if (!self::exists($filepath))
            {
                $fh = fopen($filepath, $mode);

                // is $fh a resource, i mean can we write to this file?
                if (is_resource($fh)) :
                    
                    // write to file with content returned
                    fwrite($fh, $_content);

                    // close file handle
                    fclose($fh);

                    // written
                    $write = true;

                endif;

            }

            // write to file if it exists and we have a full write permission
            if (self::exists($filepath) && is_writable($filepath)) :
            
                // try update file permission
                @chmod($filepath, 0777);

                // open file for writing.
                $fh = fopen($filepath, $mode);
                
                // write to file
                fwrite($fh, $_content);

                // close file handle
                fclose($fh);

                // written
                $write = true;

            endif;
        
        endif;

        // call callback function if it was passed
        if (is_object($callback) && get_class($callback) == 'Closure') :
            
            // read data from filepath
            $data = self::read($filepath);

            // bind class to callback
            $callback = $callback->bindTo($instance, \get_class($instance));

            // call closure function.
            call_user_func($callback, $data);

        endif;


        return $write;
    }

    /**
     * @method File exists
     * @param string $filepath
     * @param mixed $callback
     * @return bool
     *
     * Checks if a file does exists. Has the ability to retrieve the file content if it exists,
     * Just pass a closure function to retrieve the file content.
     */
	public static function exists(string $filepath, $callback = false) :  bool
	{
        // file exists?
        $fileExists = false;

        // get instance of class
        $instance = self::getInstance();

        // update file path
        $instance->filepath = $filepath;

        // the check
		if (file_exists($filepath)) :
            
            // update file exists
            $fileExists = true;

            // closure passed ?
			if (is_object($callback) && get_class($callback) == 'Closure') :
            
                // read data
				$data = self::read($filepath);

                // call closure function
                call_user_func($callback->bindTo($instance, \get_class($instance)), $data);
                
            endif;
        
        endif;

        // return bool
		return $fileExists;
    }
    
    /**
     * @method File append
     * @param mixed $content
     * @param mixed $filepath
     * @param mixed $callback
     * @return mixed
     * 
     * Append to the end of a file, creates it if it doesn't exists.
     */
	public static function append($content, $filepath, $callback = false)
	{
        // write to the end of $filepath 
		return self::write($content, $filepath, $callback, 'a+');
	}

    /**
     * @method File appendOnce
     * @param mixed $content
     * @param mixed $filepath
     * @param mixed $callback
     * @return bool
     * 
     * Append to the end of a file once, will not create file if it doesn't exists.
     * With the method, it's assumed you already have that file in existence.
     */
	public static function appendOnce($content, $filepath, $callback = false) : bool
	{
        // file data collected
        $fileData = null;
        
        // append status
        $status = false;
        
        // get instance of class
        $instance = self::getInstance();

        // update file path
        $instance->filepath = $filepath;

        // check if file does exists.
		file::exists($filepath, function($data) use ($content, $filepath, &$fileData, &$status){

            // update file data to be used with closure
			$fileData = $data;

            // check the end to find a match
			if (stristr($data, $content) === false) :
			
                // no match, so we update end of file.
                $status = true;
                
                // write to file now
				$fileData = file::write($content, $filepath, false, 'a+');
            
            endif;
		});

        // call closure and pass data collected
		if (is_object($callback) && get_class($callback) == 'Closure') :
            
            // make the call
			call_user_func($callback->bindTo($instance, \get_class($instance)), $fileData);
        
        endif;

        // return bool
		return $status;
    }
    
    /**
     * @method File getInstance
     * A private method that returns an instance of the file class
     * @return File
     */
    private static function getInstance() : File
    {
        if (is_null(self::$instance)) :

            // create class instance
            self::$instance = new File();

        endif;

        // return class instance
        return self::$instance;
    }
}