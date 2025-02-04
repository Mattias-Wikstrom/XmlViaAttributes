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

use Attribute;

#[Attribute]
class XmlElement
{
    public string|null $name;

    public function __construct(string|null $name = null)
    {
        $this->name = $name;
    }
}