<?php
/*
 * Copyright (C) 2005 Alfresco, Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

 * As a special exception to the terms and conditions of version 2.0 of 
 * the GPL, you may redistribute this Program in connection with Free/Libre 
 * and Open Source Software ("FLOSS") applications as described in Alfresco's 
 * FLOSS exception.  You should have recieved a copy of the text describing 
 * the FLOSS exception, and it is also available here: 
 * http://www.alfresco.com/legal/licensing"
 */
 
 // change by moodle
 require_once($CFG->libdir."/alfresco/Service/Repository.php");
 require_once($CFG->libdir."/alfresco/Service/Session.php");
 
 /**
  * Uploads a file into content store and returns the content data string which
  * can be used to populate a content property.
  * 
  * @param $session the session
  * @param $filePath the file location
  * @return String the content data that can be used to update the content property
  */
 function upload_file($session, $filePath, $mimetype=null, $encoding=null)
 {
 	$result = null;
 	
 	// Check for the existance of the file
 	if (file_exists($filePath) == false)
 	{
 		throw new Exception("The file ".$filePath."does no exist.");
 	}
 	
	// Get the file name and size
	$fileName = basename($filePath);
	$fileSize = filesize($filePath);
	  	
	// Get the address and the   	 
	$host = $session->repository->host; 
	$port = $session->repository->port;
 	
	// Get the IP address for the target host
	$address = gethostbyname($host);
	
	// Create a TCP/IP socket
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket === false) 
	{
	    throw new Exception ("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
	} 	
	
	// Connect the socket to the repository
	$result = socket_connect($socket, $address, $port);
	if ($result === false) 
	{
	    throw new Exception("socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)));
	} 
	
	// Write the request header onto the socket
	$url = "/alfresco/upload/".urlencode($fileName)."?ticket=".$session->ticket;
	if ($mimetype != null)
	{
		// Add mimetype if specified
		$url .= "&mimetype=".$mimetype;
	}
	if ($encoding != null)
	{
		// Add encoding if specified
		$url .= "&encoding=".$encoding;
	}
	$in = "PUT ".$url." HTTP/1.1\r\n".
	              "Content-Length: ".$fileSize."\r\n".
	              "Host: ".$address.":".$port."\r\n".
	              "Connection: Keep-Alive\r\n".
	              "\r\n";		
	socket_write($socket, $in, strlen($in));
	
	// Write the content found in the file onto the socket
	$handle = fopen($filePath, "r");
	if (!$handle) {
                throw new Exception("The file ".$filePath." could not be opened for reading");
        }
	while (feof($handle) == false)
	{
		$content = fread($handle, 1024);
		socket_write($socket, $content, strlen($content));		
	}		
	fclose($handle);
	
	// Read the response
	$recv = socket_read ($socket, 2048, PHP_BINARY_READ);
	$index = strpos($recv, "contentUrl");
	if ($index !== false)
	{
		$result = substr($recv, $index);	
	}
	
	// Close the socket
	socket_close($socket);
		
	return $result;
 } 
?>
