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

class Window {

	protected $socket;
	public $document;

	public function __construct() {

		$host = $_ENV['PDR_MOZREPL_HOST'];
		$port = $_ENV['PDR_MOZREPL_PORT'];

		if (empty($host)) {
			trigger_error("\$host is empty", E_USER_ERROR);
		} elseif (empty($port)) {
			trigger_error("\$port is empty", E_USER_ERROR);
		}

		trigger_error("connecting .. to $host port $port", E_USER_NOTICE);
		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if (!$socket) {
			socket_strerror($socket)."\n";
			throw new \Exception("socket create fail");
		}
		$connection = socket_connect($socket,$host,$port);
		if (!$connection) {
			socket_strerror($connection)."\n";
			socket_close($socket);
			throw new \Exception("sockect connect fail");
		}
		trigger_error("connected ", E_USER_NOTICE);
		$text = $this->socketRead($socket);

		trigger_error("ready", E_USER_NOTICE);
		$this->socket = $socket;
		$this->document = new \Pdr\MozRepl\Document($this);
	}

	public function __destruct() {
		if (is_resource($this->socket)) {
			socket_close($this->socket);
		}
	}

	public function getDocument() {
		return $this->document;
	}

	public function quit() {
		for ($i = 0; $i < $this->tabCount(); $i++){
			$this->tabDelete($i);
		}
	}

	public function tabCount(){
		return $this->send('gBrowser.tabContainer.childNodes.length');
	}

	public function tabCreate($url){
		$url = "'".addslashes($url)."'";
		return $this->send('gBrowser.addTab('.$url.')');
	}

	public function tabDelete($tabIndex){
		return $this->send('gBrowser.removeTab('
			.'gBrowser.tabContainer.childNodes['.$tabIndex.']'
			.')');
	}

	public function tabSelect($tabIndex){
		return $this->send(
			'gBrowser.tabContainer.selectedItem'
				.'= gBrowser.tabContainer.childNodes['.$tabIndex.']'
			);
	}


	/**
	 * Read until get a "repl>" prompt
	 **/

	protected function socketRead($socket) {
		$text = '';
		while (TRUE) {
			$buffer = socket_read($socket,65536,PHP_BINARY_READ);
			if ($buffer === FALSE) {
				throw new \Exception("read socket fail");
			}

			if ($buffer === '') {
				// no more data
				break;
			}
			// error
			if (preg_match("/\.+>\s*$/",$buffer,$match)) {
				socket_write($socket, ";\n");
				$error = $this->socketRead($socket);
				if ($error === 'function() {...}'){
					// function creation
					break;
				}

				throw new \Exception($error);
			}
			if (preg_match('|^(.*)\s*repl\d*>\s*$|s',$buffer,$match)) {
				$text .= $match[1];
				break;
			}
			if (preg_match('|^(.*)\s*repl\d*>\s*$|s',$buffer,$match)) {
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

	public function send($command) {
		$command .= ";\n";
		$exitCode = socket_write($this->socket, $command);
		if ($exitCode === FALSE) {
			throw new \Exception("ERROR socket_write()");
		}
		return $this->socketRead($this->socket);
	}

	/**
	 * @param integer $waitEvent
	 *   0 - wait document load and wait busy
	 *   1 - wait document load
	 *   2 - wait busy
	 *   3 - no wait
	 * @parame integer $timeout
	 *   0 - no timeout
	 *   n - timeout in n seconds
	 **/

	public function navigate($url, $waitEvent = 0, $timeout = NULL) {

		$timeout = $_ENV['PDR_MOZREPL_WINDOW_NAVIGATE_TIMEOUT'];
		$this->send(
			'this.getBrowser().contentWindow.location.replace(\''
			.addslashes($url)
			.'\')'
		);
		return $this->waitReady($waitEvent, $timeout);;
	}

	/**
	 * Wait while the page is loading.
	 * see _FFLoadWait
	 * @link https://github.com/elpatron68/fftlrec/blob/master/FF.au3
	 **/

	public function waitReady($waitEvent = 0, $timeout = NULL) {

		$timeout = $_ENV['PDR_MOZREPL_WINDOW_WAITREADY_TIMEOUT'];

		trigger_error(__FUNCTION__." $timeout", E_USER_NOTICE);

		$timerStart = time();
		while (TRUE){
			$isLoadingDocument = $this->send('this.getBrowser().webProgress.isLoadingDocument');
			$busyFlags = $this->send('this.getBrowser().webProgress.busyFlags');
			$timerStop = time();
			if (	$waitEvent == 0
				&&	$isLoadingDocument == 'false'
				&&	$busyFlags == '0'
				){
// 				trigger_error('Done.'
// 					.' isLoadingDocument: '.$isLoadingDocument
// 					.' busyFlags: '.$busyFlags
// 					, E_USER_NOTICE);
				break;
			} elseif ($waitEvent == 1 && $isLoadingDocument == 'false'){
// 				trigger_error('Done.'
// 					.' isLoadingDocument: '.$isLoadingDocument
// 					.' busyFlags: '.$busyFlags
// 					, E_USER_NOTICE);
				break;
			} elseif ($waitEvent == 2 && $busyFlags == '0'){
// 				trigger_error('Done.'
// 					.' isLoadingDocument: '.$isLoadingDocument
// 					.' busyFlags: '.$busyFlags
// 					, E_USER_NOTICE);
				break;
			} elseif ($waitEvent == 3){
// 				trigger_error('Done.'
// 					.' isLoadingDocument: '.$isLoadingDocument
// 					.' busyFlags: '.$busyFlags
// 					, E_USER_NOTICE);
				break;
			} elseif ($timerStop - $timerStart > $timeout) {
// 				trigger_error('Timeout.'
// 					.' isLoadingDocument: '.$isLoadingDocument
// 					.' busyFlags: '.$busyFlags
// 					, E_USER_ERROR);
				break;
			}
			usleep(100);
		}
		trigger_error('wait done', E_USER_NOTICE);
		return TRUE;
	}

	/**
	 * Download URL
	 * @param string $downloadUrl The URL will be downloaded.
	 * @return string Downloaded file path.
	 **/

	public function download($downloadUrl) {

		// Create temporary file

		$type = $this->send("typeof _MozReplDownloadUrl");
		if ($type == 'undefined'){
			$this->send('_MozReplDownloadUrl = function(downloadUrl){

sourceWindow = this.getBrowser().contentWindow

Components.utils.import("resource://gre/modules/FileUtils.jsm");

var obj_TargetFile = FileUtils.getFile("TmpD", ["mozRepl.tmp"]);
obj_TargetFile.createUnique(
Components.interfaces.nsIFile.NORMAL_FILE_TYPE, FileUtils.PERMS_FILE
);

var obj_URI = Components.classes["@mozilla.org/network/io-service;1"]
	.getService(Components.interfaces.nsIIOService)
	.newURI(downloadUrl, null, null)
;

if(!obj_TargetFile.exists()) {
	obj_TargetFile.create(0x00,0644);
}

var obj_Persist = Components
	.classes["@mozilla.org/embedding/browser/nsWebBrowserPersist;1"]
	.createInstance(Components.interfaces.nsIWebBrowserPersist)
;

const nsIWBP = Components.interfaces.nsIWebBrowserPersist;
const flags = nsIWBP.PERSIST_FLAGS_REPLACE_EXISTING_FILES;
obj_Persist.persistFlags = flags | nsIWBP.PERSIST_FLAGS_FROM_CACHE;

var privacyContext = sourceWindow
	.QueryInterface(Components.interfaces.nsIInterfaceRequestor)
	.getInterface(Components.interfaces.nsIWebNavigation)
	.QueryInterface(Components.interfaces.nsILoadContext)
;

obj_Persist.saveURI(obj_URI,null,null,null,null,null,obj_TargetFile,privacyContext)

return obj_TargetFile.path;
}');
		}
		$downloadFile = $this->send("_MozReplDownloadUrl('$downloadUrl')");
		return $downloadFile;
	}

	/**
	 * Get cookie from host
	 *
	 * Tool > Options > Privacy > History > Firefox will : Remember history
	 *
	 **/

	public function getCookieFromHost($hostname, $path=null) {

		$cookieService = uniqid('_');
		$cookieEnum = uniqid('_');
		$this->send("var $cookieService  = Services.cookies");
		$this->send("var $cookieEnum  = $cookieService.getCookiesFromHost('$hostname')");

		$cookieHeaderValueItem = array();

		while ($this->send("$cookieEnum.hasMoreElements()") == 'true'){
			$hostCookie  = uniqid('_');
			$this->send("var $hostCookie = $cookieEnum.getNext()");
			$cookiePath = $this->send("$hostCookie.QueryInterface(Ci.nsICookie2).path");
			$cookieName = $this->send("$hostCookie.QueryInterface(Ci.nsICookie2).name");
			$cookieValue = $this->send("$hostCookie.QueryInterface(Ci.nsICookie2).value");
			if (is_null($path) == false){
				if ($path == $cookiePath){
					// cookie accepted, do nothing
				} else {
					continue;
				}
			}
			$cookieItem = new \stdClass;
			$cookieItem->path = $cookiePath;
			$cookieItem->name = $cookieName;
			$cookieItem->value = $cookieValue;
			$cookieHeaderValueItem[] = $cookieItem->name.'='.$cookieItem->value;
		}

		$cookieHeaderValue = implode('; ', $cookieHeaderValueItem);
		return $cookieHeaderValue;
	}

}
