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

class Node {

	protected $_mozRepl;
	protected $_mozReplVariableName;

	public function __construct($mozRepl, $mozReplVariableName) {
		$this->_mozRepl = $mozRepl;
		$this->_mozReplVariableName = $mozReplVariableName;
	}

	public function __get($propertyName) {

		$variableName = uniqid('_');
		$this->_mozRepl->send('var '.$variableName.' = '
			.$this->_mozReplVariableName.'.'.$propertyName
			);
		$type = $this->_mozRepl->send('typeof('.$variableName.')');

		if ($type == 'object') {
			return new \Pdr\MozRepl\Node($this->_mozRepl, $variableName);
		} elseif ($type == 'string' || $type == 'number') {
			return $this->_mozRepl->send($variableName);
		} elseif ($type == 'boolean') {
			$text = $this->_mozRepl->send($variableName);
			if ($text == 'true') {
				return TRUE;
			} else {
				return FALSE;
			}
		} elseif ($type == 'undefined') {
			throw new \Exception("Undefined property: $propertyName");
		} else {
			throw new \Exception("Unknown type: $type property: $propertyName");
		}
	}

	public function __set($propertyName, $propertyValue) {

		$text = $this->_mozRepl->send(
			$this->_mozReplVariableName.'.'.$propertyName
			.' = \''.addslashes($propertyValue).'\''
		);

		if ($text != $propertyValue) {
			throw new \Exception(
				 "invalid propertyValue for $propertyName: $propertyValue"
				."\n$text"
				);
		}
	}

	public function __call($methodName, $arguments) {

		if ($arguments){
			foreach ($arguments as $index => $argumentValue) {
				$arguments[$index] = "'".addslashes($argumentValue)."'";
			}
		}

		$variableName = uniqid('_');
		$this->_mozRepl->send('var '.$variableName.' = '
			.$this->_mozReplVariableName.'.'.$methodName
			.'('.implode(',', $arguments).')'
			);
		$type = $this->_mozRepl->send('typeof('.$variableName.')');

		if ($type == 'object') {
			return new \Pdr\MozRepl\Node($this->_mozRepl, $variableName);
		} elseif ($type == 'undefined') {
			return NULL;
		} else {
			throw new \Exception("Unknown type: $type property: $propertyName");
		}
	}
}
