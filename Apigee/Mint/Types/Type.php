<?php

namespace Apigee\Mint\Types;

use Apigee\Exceptions\ParameterException;
use \ReflectionClass;

abstract class Type
{

    private static $concreteTypes = array();

    /**
     * This method verifies that $value is declared as a constant in the
     * subclass, if it declared then the value of the constant is returned otherwise
     * a ParameterException is thrown. This function should be used to validate that
     * a variable holding a type value is assigned a value type.
     *
     * Example:
     *
     * <code>
     * <?php
     * class StatusType extends Type {
     *   const ACTIVE = 'ACTIVE';
     *   const INACTIVE = 'INACTIVE';
     * }
     *
     * // $status is assigned 'ACTIVE'
     * $status =  StatusType::get("ACTIVE");
     *
     * // Will throw a ParameterException since there is no constant DELETED defined in StatusType
     * $status = StatusType::get('DELETED');
     * ?>
     * </code>
     *
     * @param string $value
     *   Name of the constant to be searched.
     * @throws ParameterException
     *   Value type not found in class type
     * @return string the value of the constant
     */
    public static function get($value)
    {
        $type = get_called_class();
        if (!array_key_exists($type, self::$concreteTypes)) {
            $class = new ReflectionClass($type);
            self::$concreteTypes[$type] = $class->getConstants();
        }
        if (in_array($value, self::$concreteTypes[$type])) {
            return $value;
        } else {
            throw new ParameterException('Value type \'' . $value . '\' is not defined in type \'' . $type . '\'');
        }
    }

}
