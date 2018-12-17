<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 **/

namespace Pdr\MozRepl;

class NodeList {

	public $length;
	protected $_items;

	public function __construct($items) {
		$this->length = count($items);
		$this->_items = $items;
	}

	public function item($index) {
		if (array_key_exists($index, $this->_items)) {
			return $this->_items[$index];
		}
		throw new \Exception("undefined index: $index");
	}
}
