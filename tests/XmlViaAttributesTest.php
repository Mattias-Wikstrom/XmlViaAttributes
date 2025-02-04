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

use PHPUnit\Framework\TestCase;
use MattiasWikstrom\XmlViaAttributes\Parser;
use MattiasWikstrom\XmlViaAttributes\XmlElement;
use MattiasWikstrom\XmlViaAttributes\XmlElements;
use MattiasWikstrom\XmlViaAttributes\XmlAttribute;
use MattiasWikstrom\XmlViaAttributes\InnerXml;
use MattiasWikstrom\XmlViaAttributes\ParseException;
use MattiasWikstrom\XmlViaAttributes\CallableWrapper;
use MattiasWikstrom\XmlViaAttributes\ConversionFunction;
use MattiasWikstrom\XmlViaAttributes\ValidationFunction;
use Closure;

class Example1
{
    #[XmlElement]
    public string $Name;

    #[XmlElement("Age")]
    public int $Age;

    #[XmlAttribute("id")]
    public int $myId;
}

class Example2
{
    #[XmlElement()]
    public $name; // NOTE: No type (e.g. string or int) specified. This should lead to an exception.
}

class Example3
{
    #[XmlElement()]
    public int $integer; 

    #[XmlAttribute("id")]
    public int $myId;
}

class Example4
{
    #[XmlElement()]
    public Example1 $Example1;
}

class Example5
{
    #[XmlElement()]
    public int | null $integerWhichMayBeMissing;

    #[XmlElement()]
    public string | null $stringWhichMayBeMissing;

    #[XmlElement()]
    public int | string $intOrString;

    #[XmlElement()]
    public int | string | null $intOrStringOrMissing;
}

class Example6
{
    #[XmlElements(string::class)]
    public array $name;
}

class Example7
{
    #[XmlElements(int::class)]
    public array $number;
    
    #[XmlElements(bool::class)]
    public array $boolean;
}

class Example8
{
    #[InnerXml]
    public string $innerXml;
}

class Example9
{
    #[XmlElements(Example1::class)]
    public array $Example1;
}

class Constants {
    public static Closure $ENSURE_INTEGER_FROM_1_TO_6;
    public static Closure $CONVERT_YES_NO_TO_BOOL;
}

Constants::$ENSURE_INTEGER_FROM_1_TO_6 = fn($i) => 1 <= $i && $i <= 6;

Constants::$CONVERT_YES_NO_TO_BOOL = fn($yn) => strtolower($yn) == 'yes' ? true : false;

class Example10
{
    #[XmlElement]
    #[ValidationFunction(Constants::ENSURE_INTEGER_FROM_1_TO_6)]
    public int $diceThrow;
}

class Example11
{
    #[XmlElement]
    #[ConversionFunction(Constants::CONVERT_YES_NO_TO_BOOL)]
    public bool $hasSignedAgreement;
}

class Example12
{
    #[XmlElement]
    #[Optional]
    public int $OptionalElement;
}


class XmlViaAttributesTest extends TestCase
{
    public function testCanParseSimpleClass()
    {
        $xmlData = "<Example1 id='1'><Name>John Doe</Name><Age>30</Age></Example1>";

        $obj = Parser::parseXML($xmlData, Example1::class)->result;

        $this->assertEquals("John Doe", $obj->Name);
        $this->assertEquals("30", $obj->Age);
        $this->assertEquals("1", $obj->myId);
    }

    public function testExceptionWhenNoTypeSpecified()
    {
        $xmlData = "<Example2><name>John Doe</name></Example2>";
        
        $this->expectException(ParseException::class); 
        $obj = Parser::parseXML($xmlData)->result;
    }
    
    public function testIntEnforcedForElements()
    {
        $xmlData = "<Example3 id='1'><integer>NotAnInt</integer></Example3>";

        $this->expectException(ParseException::class); 
        $obj = Parser::parseXML($xmlData)->result;
    }

    public function testIntEnforcedForAttributes()
    {
        $xmlData = "<Example3 id='NotAnInt'><integer>45</integer></Example3>";

        $this->expectException(ParseException::class); 
        $obj = Parser::parseXML($xmlData)->result;
    }
    
    public function testCanGetInnerXml()
    {
        $xmlData = "<Example8><integer>45</integer></Example8>";

        $obj = Parser::parseXML($xmlData)->result;
        
        $this->assertEquals("<integer>45</integer>", $obj->innerXml);
    }

    public function testCanReadInnerElement()
    {
        $xmlData = "<Example4><Example1 id='1'><Name>John Doe</Name><Age>30</Age></Example1></Example4>";
        
        $objOuter = Parser::parseXML($xmlData, Example4::class)->result;

        $obj = $objOuter->Example1;

        $this->assertEquals("John Doe", $obj->Name);
    }

    public function testCanParseArrayOfIntegers()
    {
        $xmlData = "<Example7><number>2</number><number>3</number><number>5</number><boolean>true</boolean></Example7>";
        
        $obj = Parser::parseXML($xmlData)->result;

        $this->assertEquals(3, count($obj->number));
        $this->assertEquals(5, $obj->number[2]);
        
    }

    public function testCanParseArrayOfBooleans()
    {
        $xmlData = "<Example7><number>2</number><number>3</number><number>5</number><boolean>true</boolean></Example7>";
        
        $obj = Parser::parseXML($xmlData)->result;

        $this->assertEquals(1, count($obj->boolean));
        $this->assertEquals(true, $obj->boolean[0]);
    }

    public function testCanParseArrayOfObjectsFromCustomClass()
    {
        $xmlData = "<Example9><Example1 id='1'><Name>Adam Jones</Name><Age>31</Age></Example1><Example1 id='2'><Name>John Doe</Name><Age>30</Age></Example1></Example9>";
        
        $obj = Parser::parseXML($xmlData)->result;
        
        $this->assertEquals(2, count($obj->Example1));
        $this->assertEquals('Adam Jones', $obj->Example1[0]->Name);
        $this->assertEquals(2, $obj->Example1[1]->myId);
    }
}

