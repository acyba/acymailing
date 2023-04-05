<?php

namespace AcyMailing\Libraries;

class acymParameter
{
    public $params;
    public $paramObject;

    /**
     * acymParameter constructor.
     *
     * @param null $params We expect a json encoded object, but it could directly be an object with protected data in Joomla 3
     */
    public function __construct($params = null)
    {
        if (is_string($params)) {
            $this->params = json_decode($params);
        } elseif (is_object($params)) {
            $this->paramObject = $params;
        } elseif (is_array($params)) {
            $this->params = (object)$params;
        }
    }

    public function get($path, $default = null)
    {
        if (empty($this->paramObject)) {
            if (empty($this->params->$path) && !(isset($this->params->$path) && $this->params->$path === '0')) {
                return $default;
            }

            return $this->params->$path;
        } else {
            // Handle the object based parameters
            $value = $this->paramObject->get($path, 'noval');
            if ($value === 'noval') {
                $value = $this->paramObject->get('data.'.$path, $default);
            }

            return $value;
        }
    }
}
