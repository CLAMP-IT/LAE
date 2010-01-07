<?php

// This provides a bolt-on to Moodle so you can easily add a download button
// for a collection of files pretty much anywhere.

require_once('xor.php');

if(empty($SESSION->zipsecret)) {
  $SESSION->zipsecret = md5(uniqid(rand(), true)) . md5(uniqid(rand(), true)) . md5(uniqid(rand(), true));
}

function file_collection_form($files_tozip, $collection_name) {
  global $CFG, $SESSION;

  $payload =      array('files' => $files_tozip, 'name' => $collection_name);
  $encoded =    encrypt_and_encode(serialize($payload), $SESSION->zipsecret);

  $output =       '<p><form method="post" action="' . $CFG->wwwroot . '/LAE/zipfile.php">';
  $output .=      '<input type="hidden" name="t" value="' . $encoded . '"/>';
  $output .=      '<input type="submit" name="download" value="Download all files (.zip)"/></form></p>';

  return $output;
}

?>
