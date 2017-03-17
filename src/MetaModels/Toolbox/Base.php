<?php

/**
 * Created by PhpStorm.
 * User: stefan.heimes
 * Date: 17.03.2017
 * Time: 10:56
 */
 
namespace MetaModels\Toolbox;

use MetaModels\IItem;

class Base
{
    /**
     * Some information.
     *
     * @var array
     */
    protected $cache = array();

    /**
     * The data of a object.
     *
     * @var array
     */
    protected $data = array();

    /**
     * The metamodels item.
     *
     * @var IItem|null
     */
    protected $item = null;

    /**
     * Add the data of the object as array with raw/text/html5.
     * This function will make a copy of the array.
     *
     * @param $data
     *
     * @return void
     */
    public function setArrayData($data)
    {
        // Clear.
        $this->cache = array();
        $this->item  = null;
        // Add.
        $this->data = $data;
    }

    /**
     * Add the data of the object as array with raw/text/html5.
     * This function will use the memory address instead of a copy from the array.
     * User with care.
     *
     * @param $data
     *
     * @return void
     */
    public function setArrayDataAsReference(&$data)
    {
        // Clear.
        $this->cache = array();
        $this->item  = null;
        // Add.
        $this->data = $data;
    }



    /**
     * Set the metamodels item. Don't use this and the addObjectData or addObjectDataAsReference function.
     *
     * @param IItem $object The item from metamodels.
     *
     * @return void
     */
    public function setObject($object)
    {
        // Clear.
        $this->cache = array();
        $this->data  = array();
        // Add.
        $this->item = $object;
    }

    /**
     * Get the data from the object.
     *
     * @return IItem
     */
    public function getObjectData()
    {
        return $this->item;
    }

    /**
     * Get the data from the object.
     *
     * @return array
     */
    public function getArrayData()
    {
        return $this->data;
    }

    /**
     * Get some data from the array.
     *
     * @param string $_ The keys for the value you want.
     *
     * @return mixed
     */
    public function getDataFor($_)
    {
        // Get the field information.
        $args = func_get_args();

        try {
            // Check if we have a item.
            if ($this->item !== null) {
                $field = array_shift($args);

                // If we have the raw/text/html5 parse the values.
                if ($field == 'raw' || $field == 'text' || $field == 'html5') {
                    // If we have no other args return all.
                    if (count($args) == 0) {
                        $data            = $this->item->parseValue($field);
                        $currentPosition = $data[$field];
                    } else {
                        $format = $field;
                        $field  = array_shift($args);
                        if ($field == 'id' || $field == 'pid' || $field == 'tstamp') {
                            $currentPosition = $this->item->get($field);
                        } else {
                            $data            = $this->item->parseAttribute($field, $format);
                            $currentPosition = $data[$format];
                        }
                    }
                } else { // If not get the attribute normal.
                    $currentPosition = $this->item->get($field);
                }
            } else {
                $currentPosition = &$this->data;
            }
        } catch (\Exception $e) {
            $currentPosition = array();
        }

        // Run through the array.
        foreach ($args as $arg) {
            if (!isset($currentPosition[$arg])) {
                return null;
            }
            $currentPosition = &$currentPosition[$arg];
        }

        return $currentPosition;
    }
}
