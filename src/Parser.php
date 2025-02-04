<?php
/*
 * This file is part of XmlViaAttributes.
 *
 * XmlViaAttributes is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * XmlViaAttributes is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with XmlViaAttributes.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace MattiasWikstrom\XmlViaAttributes;

use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use SimpleXMLElement;

class Parser
{
    public static function parseXML(string $xml, string $className = null)
    {
        $xml = trim($xml);
        $simpleXml = simplexml_load_string($xml);

        if ($className == null)
        {
            $className = $simpleXml->getName();
        }

        $class = new ReflectionClass($className);

        return self::parseXMLElement($class, $simpleXml);
    }

    private static function getInfoFromProperty(ReflectionProperty $property)
    {
        $typeInPHP = $property->getType();
        $propertyNameInPHP = $property->getName();

        $retVal = [];

        $retVal['isElement'] = false;
        $retVal['isAttribute'] = false;
        $retVal['isInnerXml'] = false;
        $retVal['parseAsCustomType'] = null;
        $retVal['isArrayWithElementsOfPHPType'] = null;
        $retVal['typeInPHP'] = $typeInPHP;
        $retVal['isValidValue'] = function ($value) { return false;};
        $retVal['convertValue'] = function ($value) { throw new ParseException('Internal error.');};
        
        $isArray = false;
        $typeOfElementsInArray = null;

        if ($typeInPHP instanceof ReflectionNamedType) {
            
            // $type->getName() 
        } elseif ($typeInPHP instanceof ReflectionUnionType) {
            foreach ($typeInPHP->getTypes() as $unionType) {
                // $unionType->getName() 
            }
        } elseif ($typeInPHP instanceof ReflectionIntersectionType) {
            throw new ParseException("Property \${$propertyNameInPHP} has an unsupported type.");
            /*
            foreach ($type->getTypes() as $intersectionType) {
                // $intersectionType->getName() 
            }*/
            
        } else {
            throw new ParseException("Property \${$propertyNameInPHP} needs to have a type.");
        }
  
        $attributes = $property->getAttributes();
            
        foreach ($attributes as $attribute) {
            switch ($attribute->getName())
            {
                case XmlElement::class:
                {
                    $retVal['isElement'] = true;

                    $retVal['nameInXML'] = $attribute->newInstance()->name;
    
                    if ($retVal['nameInXML'] == null)
                    {
                        $retVal['nameInXML'] = $propertyNameInPHP;
                    }
                }
                break;
                case XmlAttribute::class:
                {
                    $retVal['isAttribute'] = true;

                    $retVal['nameInXML'] = $attribute->newInstance()->name;
    
                    if ($retVal['nameInXML'] == null)
                    {
                        $retVal['nameInXML'] = $propertyNameInPHP;
                    }
                }
                break;
                case InnerXml::class:
                {
                    $retVal['isInnerXml'] = true;
                }
                break;
                case ConversionFunction::class:
                {
                }
                break;
                case ValidationFunction::class:
                {
                }
                break;
                case Optional::class:
                {
                }
                break;
                case XmlElements::class:
                {
                    $attributeInstance = $attribute->newInstance();
                    
                    $typeOfElementsInArray = $attributeInstance->class;
                    
                    $retVal['nameInXML'] = $propertyNameInPHP;
                }
                break;
            }
        }

        if ($typeInPHP instanceof ReflectionNamedType && $typeInPHP->isBuiltin())
        {
            switch ($typeInPHP)
            {
                case 'int':
                    $retVal['isValidValue'] = function ($value) { return filter_var($value, FILTER_VALIDATE_INT) !== false;};
                    $retVal['convertValue'] = function ($value) { return (int) $value;};
                    break;
                case 'bool':
                    //$retVal['isValidValue'] = function ($value) { return filter_var($value, FILTER_VALIDATE_) !== false;};
                    $retVal['convertValue'] = function ($value) { return (bool) $value;};
                    break;
                case 'float':
                    //$retVal['isValidValue'] = function ($value) { return filter_var($value, FILTER_VALIDATE_) !== false;};
                    $retVal['convertValue'] = function ($value) { return (float) $value;};
                    break; 
                case 'string':
                    $retVal['isValidValue'] = function ($value) { return true;};
                    $retVal['convertValue'] = function ($value) { return $value;};
                    break;
                case 'array':
                    $isArray = true;
                    break;
                default:
                    throw new ParseException('Unexpected type: ' . $typeInPHP);
                    //$retVal['isValidValue'] = function ($value) { return true;};
                    //$retVal['convertValue'] = function ($value) { return $value;};
            }
        } else if ($typeInPHP instanceof ReflectionUnionType) {

        } else {
            $retVal['parseAsCustomType'] = (string) $typeInPHP;
        }

        if ($isArray || $typeOfElementsInArray) {
            if ($isArray && $typeOfElementsInArray) {
                $retVal['isArrayWithElementsOfPHPType'] = $typeOfElementsInArray;
            } else {
                if ($isArray && !$typeOfElementsInArray) {
                    throw new ParseException('The XmlElements attribute may only be used with arrays: ' . $propertyNameInPHP);
                }

                if (!$isArray && $typeOfElementsInArray) {
                    throw new ParseException('Array without the XmlElements attribute: ' . $propertyNameInPHP);
                }
            }
        }

        return (object) $retVal;
    }

    private static function isPropertyAcceptable(ReflectionProperty $property)
    {
        $infoFromProperty = self::getInfoFromProperty($property);

        // Exactly one of the following attributes needs to be set
        $arrayToCheck = [
            $infoFromProperty->isElement,
            $infoFromProperty->isArrayWithElementsOfPHPType,
            $infoFromProperty->isAttribute,
            $infoFromProperty->isInnerXml
        ];

        $trueCount = count(array_filter($arrayToCheck));

        return $trueCount == 1;
    }

    private static function isClassAcceptable(ReflectionClass $class)
    {
        
        foreach ($class->getProperties() as $property) {
            if (!self::isPropertyAcceptable($property)) {
                return false;
            }
        }
        
        return true;
    }

    private static function parseXMLElement(ReflectionClass $class, SimpleXMLElement $simpleXml)
    {
        if (!self::isClassAcceptable($class)) {
            throw new ParseException("Class {$class->getName()} cannot be used. [Reason]");
        }

        $retval = $class->newInstance();

        foreach ($class->getProperties() as $property) {
            $infoFromProperty = self::getInfoFromProperty($property);
            
            if ($infoFromProperty->isElement) {
                $propertyNameInXml = $infoFromProperty->nameInXML;

                if (isset($simpleXml->$propertyNameInXml)) {
                    $value = (string) $simpleXml->$propertyNameInXml;
                    
                    if ($infoFromProperty->parseAsCustomType)
                    {
                        $parseInfo = self::parseXMLElement(new ReflectionClass($infoFromProperty->parseAsCustomType), $simpleXml->$propertyNameInXml);
                        $property->setValue($retval, $parseInfo->result);
                    } else {
                        if (($infoFromProperty->isValidValue)($value)) {
                            $property->setValue($retval, ($infoFromProperty->convertValue)($value));
                        } else {
                            throw new ParseException("Cannot assign value '$value' to property $property->name of class {$class->getName()}.");
                        }
                    }
                }

            } else if ($infoFromProperty->isArrayWithElementsOfPHPType) {
                $arr = [];

                $elements = $simpleXml->xpath('/' . $simpleXml->getName() 
                    . '/' . $infoFromProperty->nameInXML);
                
                foreach($elements as $e) {
                    $elementToAdd = null;

                    switch ($infoFromProperty->isArrayWithElementsOfPHPType)
                    {
                        case 'int':
                            $elementToAdd = (int) $e[0];
                            break;
                        case 'bool':
                            $elementToAdd = (bool) $e[0];
                            break;
                        case 'float':
                            $elementToAdd = (float) $e[0];
                            break;
                        case 'string':
                            $elementToAdd = (string) $e[0];
                            break;
                        default:
                            $parseInfo = self::parseXMLElement(new ReflectionClass($infoFromProperty->isArrayWithElementsOfPHPType), $e[0]);
                        
                            $elementToAdd = $parseInfo->result;

                            //$reflectionClass->getName();
                            //throw new ParseException('Support for arrays of type ' . $reflectionClass->getName() . ' not implemented yet.');
                            break;
                    }

                    $arr []= $elementToAdd;
                }
                
                $property->setValue($retval, $arr);
            } else if ($infoFromProperty->isAttribute) {
                $attributeNameInXml = $infoFromProperty->nameInXML;
                
                if (isset($simpleXml[$attributeNameInXml])) {
                    $value = (string) $simpleXml[$attributeNameInXml];
                    
                    if (($infoFromProperty->isValidValue)($value)) {
                        $property->setValue($retval, ($infoFromProperty->convertValue)($value));
                    } else {
                        throw new ParseException("Cannot assign value '$value' to property $property->name of class {$class->getName()}.");
                    }
                }
                
            } else if ($infoFromProperty->isInnerXml) { 
                $text = '';

                foreach ($simpleXml->children() as $child)
                {
                    $text .= $child->asXML();
                }

                $value = (string) $text;

                $property->setValue($retval, $value);
            }
        }
        
        return (object) [
            'result' => $retval
        ];
    }
}