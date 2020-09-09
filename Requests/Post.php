<?php
namespace Lightroom\Requests;

use function Lightroom\Requests\Functions\server;
use Lightroom\Requests\Interfaces\PostRequestInterface;
/**
 * @package Post
 * @author Amadi Ifeanyi <amadiify.com>
 * @author Fregatelab <fregatelab.com>
 */
trait Post
{
    /**
     * @method PostRequestInterface has
     * @param string $key
     * @return bool
     * 
     * Checks if $_POST has a key
     */
    public function has(string $key) : bool
    {
        return isset($_POST[$key]) ? true : false;
    }

    /**
     * @method PostRequestInterface hasMultiple
     * @param array $multiple
     * @return bool
     * 
     * Checks if $_POST has multiple keys
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
     * @method PostRequestInterface get
     * @param string $key
     * @param string $default
     * @return string
     *
     * Gets a value from $_POST with a $key
     */
    public function get(string $key, string $default = '') : string
    {
        // @var array $all
        $all = $this->all();

        // return string
        return isset($all[$key]) ? $all[$key] : $default;
    }

    /**
     * @method Post except
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
     * @method Get __get
     * @param string $key 
     * @return string
     */
    public function __get(string $key) : string 
    {
        return $this->get($key);
    }

    /**
     * @method Get __set
     * @param string $key 
     * @return string
     */
    public function __set(string $key, $value) 
    {
        $this->set($key, $value);
    }

    /**
     * @method PostRequestInterface set
     * @param string $key
     * @param mixed $value
     * @return PostRequestInterface
     * 
     * Sets a value in $_POST with a key
     */
    public function set(string $key, $value) : PostRequestInterface
    {
        // set 
        $_POST[$key] = $this->filter($value);

        // return instance
        return $this;
    }

    /**
     * @method PostRequestInterface setMultiple
     * @param array $multiple
     * @return PostRequestInterface
     * 
     * Sets multiple value in $_POST with a key => value
     */
    public function setMultiple(array $multiple) : PostRequestInterface
    {
        // using foreach loop
        foreach ($multiple as $key => $value):
            // set
            $this->set($key, $value);
        endforeach;

        // return instance
        return $this;
    }

    /**
     * @method PostRequestInterface drop
     * @param string $key
     * @return bool
     * 
     * Removes a key from $_POST
     */
    public function drop(string $key) : bool
    {
        // @var bool dropped
        $dropped = false;

        // drop
        if (isset($_POST[$key])) :

            // unset
            unset($_POST[$key]);

            // update dropped
            $dropped = true;

        endif;

        // return bool
        return $dropped;
    }

    /**
     * @method PostRequestInterface pop
     * @param string $identifier
     * @return mixed
     * 
     * Returns the value of a post and removes it.
     */
    public function pop(string $identifier)
    {
        // get the value
        $value = $this->get($identifier);

        // remove it
        $this->drop($identifier);

        // return value
        return $value;
    }

    /**
     * @method PostRequestInterface dropMultiple
     * @param array $multiple
     * @return bool
     * 
     * Removes multiple keys from $_POST
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
     * @method PostRequestInterface pick
     * @param array $keys
     * @return array
     * 
     * Returns multiple values from $_POST with respective keys
     */
    public function pick(...$keys) : array
    {
        // @var array $picked
        $picked = [];

        // using foreach loop
        foreach ($keys as $key) :

            // add to picked
            $picked[$key] = $this->get($key);

        endforeach;

        // return array
        return $picked;
    }

    /**
     * @method PostRequestInterface all
     * @return array
     * 
     * Returns a filtered $_POST array
     */
    public function all() : array
    {
        // @var array $post
        $post = $_POST;

        // using foreach
        foreach ($post as $key => $value) :

            // update
            $post[$key] = $this->filter($value);

        endforeach;

        // return array
        return $post;
    }
    

    /**
     * @method PostRequestInterface getToken
     * @return string
     * 
     * Returns a csrf token used in form.
     */
    public function getToken() : string
    {
        return isset($_POST['CSRF_TOKEN']) ? $_POST['CSRF_TOKEN'] : '';
    }

    /**
     * @method PostRequestInterface empty
     * @return bool
     * 
     * Returns true if $_POST is empty
     */
    public function empty() : bool
    {
        return count($_POST) == 0 ? true : false;
    }

    /**
     * @method PostRequestInterface method
     * @return string
     * 
     * Returns the REQUEST_METHOD
     */
    public function method() : string
    {
        return server()->get('request_method');
    }

    /**
     * @method PostRequestInterface filter
     * @param mixed $value
     * @return mixed
     * 
     * Filters a value
     */
    public function filter($value)
    {
        // add fliter
        switch (gettype($value)) :

            // string
            case 'string':
                $value = env('bootstrap', 'filter-input') ? filter_var($value, FILTER_SANITIZE_STRING) : $value;
            break;

            // int
            case 'integer':
            case 'number':
                $value = filter_var($value, FILTER_VALIDATE_INT);
            break;

        endswitch;

        // return string
        return $value;
    }

    /**
     * @method Post input
     * @return array
     * 
     * Convert Content-Disposition to a post data
     */
	public function input() : array
	{
        // @var string $input
        $input = file_get_contents('php://input');

        // continue if $_POST is empty
		if (strlen($input) > 0 && count($_POST) == 0 || count($_POST) > 0) :
		
			$postsize = "---".sha1(strlen($input))."---";

			preg_match_all('/([-]{2,})([^\s]+)[\n|\s]{0,}/', $input, $match);

            // update input
			if (count($match) > 0) $input = preg_replace('/([-]{2,})([^\s]+)[\n|\s]{0,}/', '', $input);

			// extract the content-disposition
			preg_match_all("/(Content-Disposition: form-data; name=)+(.*)/m", $input, $matches);

			// let's get the keys
			if (count($matches) > 0 && count($matches[0]) > 0)
			{
				$keys = $matches[2];
                
                foreach ($keys as $index => $key) :
                    $key = trim($key);
					$key = preg_replace('/^["]/','',$key);
					$key = preg_replace('/["]$/','',$key);
                    $key = preg_replace('/[\s]/','',$key);
                    $keys[$index] = $key;
                endforeach;

				$input = preg_replace("/(Content-Disposition: form-data; name=)+(.*)/m", $postsize, $input);

				$input = preg_replace("/(Content-Length: )+([^\n]+)/im", '', $input);

				// now let's get key value
				$inputArr = explode($postsize, $input);

                // @var array $values
                $values = [];
                
                foreach ($inputArr as $index => $val) :
                    $val = preg_replace('/[\n]/','',$val);
                    
                    if (preg_match('/[\S]/', $val)) $values[$index] = trim($val);

                endforeach;
                
				// now combine the key to the values
				$post = [];

                // @var array $value
				$value = [];

                // update value
				foreach ($values as $i => $val) $value[] = $val;

                // push to post
				foreach ($keys as $x => $key) $post[$key] = isset($value[$x]) ? $value[$x] : '';

				if (is_array($post)) :
				
					$newPost = [];

					foreach ($post as $key => $val) :
					
						if (preg_match('/[\[]/', $key)) :
						
							$k = substr($key, 0, strpos($key, '['));
							$child = substr($key, strpos($key, '['));
							$child = preg_replace('/[\[|\]]/','', $child);
							$newPost[$k][$child] = $val;
						
                        else:
						
                            $newPost[$key] = $val;
                            
						endif;
                    
                    endforeach;

                    $_POST = count($newPost) > 0 ? $newPost : $post;
                    
				endif;
			}
        
        endif;

        // return array
		return $this->all();
    }
    
    /**
     * @method PostRequestInterface clear
     * @return bool
     * 
     * Clears the $_POST array
     */
    public function clear() : bool
    {
        // get all
        $all = $this->input();

        // get keys
        $keys = array_keys($all);

        // drop multiple
        call_user_func_array([$this, 'dropMultiple'], $keys);

        // clean up
        $all = null;

        // return boolean
        return true;
    }
}
