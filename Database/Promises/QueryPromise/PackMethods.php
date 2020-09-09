<?php
namespace Lightroom\Database\Promises\QueryPromise;

trait PackMethods
{
    /**
     * @var array $getPacked
     */
    public  $getPacked = [];
    
    // keys
    public function keys()
    {
        if ( count($this->getPacked) > 0)
        {
            return array_keys($this->getPacked);
        }
    }

    // values
    public function values()
    {
        if ( count($this->getPacked) > 0)
        {
            return array_values($this->getPacked);
        }
    }

    // row
    public function row()
    {
        if (count($this->getPacked) > 0) :
        
            return func()->toObject($this->getPacked);

        endif;

        return null;
    }

    //
    public function has($column)
    {
        if (isset($this->getPacked[$column]))
        {
            return true;
        }

        return false;
    }

    // get values
    public function val($index)
    {
        $row = $this->row();

        // convert $row to array
        $row = func()->toArray($row);

        // get values
        $values = array_values($row);

        if (is_int($index))
        {
            // return index
            if ($index >= 0)
            {
                return $values[$index];
            }
        }

        if (isset($row[$index]))
        {
            return $row[$index];
        }

        return $values;
    }

    // get keys
    public function key($index)
    {
        $row = $this->row();

        // convert $row to array
        $row = func()->toArray($row);

        // get keys
        $keys = array_keys($row);

        if (is_int($index))
        {
            // return index
            if ($index >= 0)
            {
                return $keys[$index];
            }
        }

        if (isset($row[$index]))
        {
            return $row[$index];
        }

        return $keys;
    }
}