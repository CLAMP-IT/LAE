<?php
/**
 * XOR encrypts a given string with a given key phrase.
 *
 * @param     string    $InputString    Input string
 * @param     string    $KeyPhrase      Key phrase
 * @return    string    Encrypted string
 *
 */

function XORStrings($InputString, $KeyPhrase){
    $KeyPhraseLength = strlen($KeyPhrase);

    // Loop through input string
    for ($i = 0; $i < strlen($InputString); $i++){

      // Get key phrase character position
      $rPos = $i % $KeyPhraseLength;

      // Magic happens here:
      $r = ord($InputString[$i]) ^ ord($KeyPhrase[$rPos]);

      // Replace characters
      $InputString[$i] = chr($r);
    }

    return $InputString;
}

// Helper functions, using base64 to
// create readable encrypted texts:
function XOREncode($InputString, $KeyPhrase){
  $InputString = XORStrings($InputString, $KeyPhrase);
  $InputString = base64_encode($InputString);
  return $InputString;
}

function XORDecode($InputString, $KeyPhrase){
  $InputString = base64_decode($InputString);
  $InputString = XORStrings($InputString, $KeyPhrase);
  return $InputString;
}

/* Additional methods for using XOR'd strings in URLs */
function encrypt_and_encode($str) {
  global $SESSION;

  return XOREncode($str, $SESSION->zipsecret);
}

function decrypt_and_decode($str) {
  global $SESSION;

  return XORDecode($str, $SESSION->zipsecret);
}

?>
