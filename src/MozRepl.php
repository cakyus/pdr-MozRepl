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

namespace Pdr;

class MozRepl {

	protected static $_socket;

	/**
	 * Connect to existing MozRepl
	 **/
	public function __construct() {

		$host = $_ENV['PDR_MOZREPL_HOST'];
		$port = $_ENV['PDR_MOZREPL_PORT'];

		if (is_null(self::$_socket)) {

			trigger_error("connecting to $host port $port ..", E_USER_NOTICE);
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if (!$socket) {
				socket_strerror($socket)."\n";
				throw new \Exception("socket create fail");
			}

			$connection = socket_connect($socket, $host, $port);
			if (!$connection) {
				socket_strerror($connection)."\n";
				socket_close($socket);
				throw new \Exception("sockect connect fail");
			}

			trigger_error("connected ", E_USER_NOTICE);
			self::$_socket = $socket;

			$text = $this->_read();
			trigger_error("ready", E_USER_NOTICE);
		}
	}

	/**
	 * Read until get a "repl>" prompt
	 **/

	public function _read() {

		$text = '';

		while (TRUE) {

			$buffer = socket_read(self::$_socket, 65536, PHP_BINARY_READ);
			if ($buffer === FALSE) {
				throw new \Exception("read socket fail");
			}

			if ($buffer === '') {
				// no more data
				break;
			}

			// error
			if (preg_match("/\.+>\s*$/", $buffer, $match)) {
				socket_write(self::$_socket, ";\n");
				$error = $this->_read();
				throw new \Exception($error);
			}

			if (preg_match('|^(.*)\s*repl\d*>\s*$|s', $buffer, $match)) {
				$text .= $match[1];
				break;
			}

			if (preg_match('|^(.*)\s*repl\d*>\s*$|s', $buffer, $match)) {
				$text .= $match[1];
				break;
			}

			$text .= $buffer;
		}

		$text = trim($text, "\r\n\"");
		return $text;
	}

	/**
	 * Send raw command
	 **/

	public function _send($command) {
		$command .= ";\n";
		socket_write(self::$_socket, $command);
		return $this->_read();
	}

	public function __call($methodName, $arguments) {

		if ($arguments){
			foreach ($arguments as $index => $argumentValue) {
				$arguments[$index] = "'".addslashes($argumentValue)."'";
			}
		}

		$variableName = uniqid('_');
		$this->_send('var '.$variableName.' = '
			.'this.'.$methodName.'('.implode(',', $arguments).')'
			);
		$type = $this->_mozRepl->_send('typeof('.$variableName.')');

		if ($type == 'object') {
			return new \Pdr\MozRepl\Node($this->_mozRepl, $variableName);
		} elseif ($type == 'undefined') {
			return NULL;
		} else {
			throw new \Exception("Unknown type: $type property: $propertyName");
		}
	}

	public function __get($propertyName) {

		$variableName = uniqid('_');
		$this->_send('var '.$variableName.' = this.'.$propertyName);
		$type = $this->_send('typeof('.$variableName.')');

		if ($type == 'object') {
			return new \Pdr\MozRepl\Node($this, $variableName);
		} elseif ($type == 'undefined') {
			throw new \Exception("Undefined property: $propertyName");
		} else {
			throw new \Exception("Unknown type: $type property: $propertyName");
		}
	}

	public function __set($propertyName, $propertyValue) {

		$text = $this->_send('this.'.$propertyName
			.' = \''.addslashes($propertyValue).'\''
		);

		if ($text != $propertyValue) {
			throw new \Exception(
				 "invalid propertyValue for $propertyName: $propertyValue"
				."\n$text"
				);
		}
	}
}
