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

class Document {

	protected $_mozRepl;

	public function __construct($mozRepl) {
		$this->_mozRepl = $mozRepl;
	}

	/**
	 * @link https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelectorAll
	 **/

	public function querySelectorAll($statement) {
		while ($this->readyState <> 'complete'){
			// do nothing
		}
		$length = $this->_mozRepl->send(
			'this.getBrowser().contentWindow.document.querySelectorAll(\''
			.addslashes($statement)
			.'\').length'
		);
		$nodes = array();
		for ($i = 0; $i < $length; $i++){
			$variableName = uniqid('_');
			$text = $this->_mozRepl->send(
				 'var '.$variableName.' = '
				.'this.getBrowser().contentWindow.document.querySelectorAll(\''
				.addslashes($statement)
				.'\').item('.$i.')'
			);
			$element = new \Pdr\MozRepl\Node(
				 $this->_mozRepl, $variableName
				);
			$nodes[] = $element;
		}
		$nodeList = new \Pdr\MozRepl\NodeList($nodes);
		return $nodeList;
	}

	public function querySelector($statement) {
		while ($this->readyState <> 'complete'){
			// do nothing
		}
		$variableName = uniqid('_');
		$this->_mozRepl->send(
			'var '.$variableName.' = '
			.'this.getBrowser().contentWindow.document.querySelector(\''
			.addslashes($statement)
			.'\')'
		);
		$isNull = $this->_mozRepl->send($variableName.' == null');
		if ($isNull == 'true') {
			return NULL;
		}
		$node = new \Pdr\MozRepl\Node(
				 $this->_mozRepl, $variableName
				);
		return $node;
	}

	public function __get($propertyName) {
		$elements = array('body','location');
		if (in_array($propertyName, $elements)) {
			$variableName = uniqid('_');
			$text = $this->_mozRepl->send(
				'var '.$variableName.' = '
				.'this.getBrowser().contentWindow.document.'.$propertyName
			);
			$element = new \Pdr\MozRepl\Node(
				 $this->_mozRepl, $variableName
				);
			return $element;
		}
		$text = $this->_mozRepl->send(
			'this.getBrowser().contentWindow.document.'.$propertyName
		);
		return $text;
	}
}
