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


