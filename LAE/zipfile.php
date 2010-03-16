<?php
// This script creates a zip file from a specially encoded list
require_once('../config.php');
require_once($CFG->dirroot . '/lib/LAE/xor.php');

$err_base = 'Could not create .zip file: ';

if(!isset($_POST['t'])) {
  die($err_base . 'Your request did not contain the list of source files.');
 } else {
  $str = decrypt_and_decode($_POST['t']);

  if(!($zipinfo = unserialize($str))) {
    die($err_base . 'Unable to decode the list of source files you provided.');
  }
 }

// set up file name
$name           = preg_replace("/[^a-zA-Z0-9]/", '', $zipinfo['name']);
$zip_file       = 'Moodle_' . $name . '_' . date('m-d-y') . '.zip';
$zip_path       = $CFG->dataroot . "/temp/" . $zip_file;

// Organize filenames
$zip_files = array();
foreach ($zipinfo['files'] as $file) {
  array_push($zip_files,
             array('file_location' => chop($file['path']),
                   'new_name' =>  $name . '/' . $file['author'] . '_' . $file['file']));
}

/* Build the zip archive.

   If zlib is available, use that. Otherwise use the packages pclzip package. */
if (class_exists(ZipArchive) && false) {
  $zip = new ZipArchive();

  if($zip->open($zip_path, ZIPARCHIVE::CREATE) !== TRUE) {
    die($err_base . "Check validity of and permissions on $zip_path");
  } else {
    foreach($zip_files as $file) {
      $zip->addFile($file['file_location'], $file['new_name']);
    }

    $zip->close();
  }
 } else {
  include_once("$CFG->libdir/pclzip/pclzip.lib.php");

  foreach($zip_files as $file) {
    $tf = array();
    $tf[PCLZIP_ATT_FILE_NAME] = $file['file_location'];
    $tf[PCLZIP_ATT_FILE_NEW_FULL_NAME] = $file['new_name'];
    $zipfiles[] = $tf;
  }

  //create the archive
  $archive = new PclZip(cleardoubleslashes($zip_path));
  if (($list = $archive->create($zipfiles) == 0)) {
    notice($archive->errorInfo(true));
    return false;
  }
 }

// send file
// TODO: Let web server stream the file instead of tying up a PHP process
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_file . '"');
readfile($zip_path);

// clean up
unlink($zip_path);
?>
