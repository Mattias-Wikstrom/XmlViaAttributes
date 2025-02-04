# Basic Idea
 The idea behind this library is to allow you to parse and validate XML by declaring classes corresponding to the XML document structure, using attributes (a feature from PHP 8.0) to specify the details.

The library is loosely inspired by System.Xml.Serialization and Entity Framework in .NET.

# Basic Example
Here is a simple example of code that one can write using the library:
```php
<?php

require 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use MattiasWikstrom\XmlViaAttributes\Parser;
use MattiasWikstrom\XmlViaAttributes\XmlElement;
use MattiasWikstrom\XmlViaAttributes\XmlAttribute;
use MattiasWikstrom\XmlViaAttributes\XmlRoot;

class Person
{
    #[XmlElement]
    public string $Name;

    #[XmlElement]
    public int $Age;

    #[XmlAttribute]
    public int $id;
}

$xmlData = "<Person id='1'><Name>John Doe</Name><Age>30</Age></Person>";

$person = Parser::parseXML($xmlData, Person::class)->result;

echo("Name: $person->Name\n"); // Outputs "Name: John Doe"
echo("Age: $person->Age\n"); // Outputs "Age: 30"
echo("id: $person->id\n"); // Outputs "id: 1"
```

# More Complicated Example
