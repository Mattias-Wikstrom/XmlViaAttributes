<?php

namespace MattiasWikstrom\XmlViaAttributes;

use Attribute;

#[Attribute]
class XmlElements
{
    public string $class;

    public function __construct($class)
    {
        $this->class = $class;
    }
}