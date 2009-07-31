<?php // $Id$
/**
 * jjg7:8/9/2004
 *
 * @version $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lesson
 **/

function removedoublecr($filename) {
// This function will adjust a file in roughly Aiken style by replacing extra newlines with <br/> tags
// so that instructors can have newlines wherever they like as long as the overall format is in Aiken
    
    $filearray = file($filename);
    /// Check for Macintosh OS line returns (ie file on one line), and fix
    if (ereg("\r", $filearray[0]) AND !ereg("\n", $filearray[0])) {
        $outfile = explode("\r", $filearray[0]);
    } else {
        $outfile = $filearray;
    }
    
    foreach ($outfile as $line) {
        // remove leading and trailing whitespace
        trim($line);
        // check it's length, if 0 do not output... if it is > 0 output
        if ($line[0] == "\n" OR strlen($line)==0 ) {
            if (count($outarray) ) {
                // get the last item in the outarray
                $cur_pos = (count($outarray) - 1);
                $outarray[$cur_pos] = trim($outarray[$cur_pos])."<br/>\n";
            }
        }
        else {
            $length=strlen($line);
            if ($length==0) {
                // don't do anything
            }
            else {
                if ($line[$length-1] == "\n") {
                    $outarray[] = $line;
                }
                else {
                    $outarray[] = $line."\n";
                }
            }
        }
    }
    // output modified file to original
    if ( is_writable($filename) ) { 

        if (! $handle =fopen ($filename ,'w' )) { 
            echo "Cannot open file ($filename)" ;
            exit; 
        } 
        foreach ($outarray as $outline) {
            fwrite($handle, $outline);
        }
        fclose($handle);
    } 
    else { 
        // file not writeable
    }
}

// jjg7:8/9/2004
function importmodifiedaikenstyle($filename) {
// This function converts from Brusca style to Aiken
    $lines = file($filename);
    $answer_found = 0;
    $responses = 0;
    $outlines = array();
    foreach ($lines as $line) {
        // strip leading and trailing whitespace
        $line = trim($line);
        // add a space at the end, quick hack to make sure words from different lines don't run together
        $line = $line. ' ';
        
        // ignore lines less than 2 characters
        if (strlen($line) < 2) {
            continue;
        }

        
        // see if we have the answer line
        if ($line[0] =='*') {
            if ($line[0] == '*') {
                $answer_found = 1;                    
                $line[0]="\t";
                $line = ltrim($line);
                $answer = $line[0];
            }
        }

        $leadin = substr($line, 0,2);
        if (strpos(".A)B)C)D)E)F)G)H)I)J)a)b)c)d)e)f)g)h)i)j)A.B.C.D.E.F.G.H.I.J.a.b.c.d.e.f.g.h.i.j.", $leadin)>0) {

            // re-add newline to indicate end of previous question/response
            if (count($outlines)) {
                $cur_pos = (count($outlines) - 1);
                $outlines[$cur_pos] = $outlines[$cur_pos]."\n";
            }

        
            $responses = 1;
            // make character uppercase
            $line[0]=strtoupper($line[0]);
            
            // make entry followed by '.'
            $line[1]='.';
        }
        elseif ( ($responses AND $answer_found) OR (count($outlines)<=1) ) {
        // we have found responses and an answer and the current line is not an answer
            switch ($line[0]) {
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                case 8:
                case 9:

                    // re-add newline to indicate end of previous question/response
                    if (count($outlines)) {
                        $cur_pos = (count($outlines) - 1);
                        $outlines[$cur_pos] = $outlines[$cur_pos]."\n";
                    }

                    // this next ugly block is to strip out the numbers at the beginning
                    $np = 0;
                    // this probably could be done cleaner... it escapes me at the moment
                    while ($line[$np] == '0' OR $line[$np] == '1' OR $line[$np] == '2'
                            OR $line[$np] == '3' OR $line[$np] == '4'  OR $line[$np] == '5' 
                            OR $line[$np] == '6'  OR $line[$np] == '7' OR $line[$np] == '8'
                            OR $line[$np] == '9' ) {
                        $np++;
                    }
                    // grab everything after '###.'
                    $line = substr($line, $np+1, strlen($line));                    

                    if ($responses AND $answer_found) {
                        $responses = 0;
                        $answer_found = 0;
                        $answer = strtoupper($answer);
                        $outlines[] = "ANSWER: $answer\n\n";
                    }
                    break;
            }
        }
        if (substr($line, 0, 14) == 'ANSWER CHOICES') {
            // don't output this line
        }
        else {
            $outlines[]=$line;
        }
    } // close for each line

    // re-add newline to indicate end of previous question/response
    if (count($outlines)) {
        $cur_pos = (count($outlines) - 1);
        $outlines[$cur_pos] = $outlines[$cur_pos]."\n";
    }

    // output the last answer
    $answer = strtoupper($answer);
    $outlines[] = "ANSWER: $answer\n\n";
    
    // output modified file to original
    if ( is_writable($filename) ) { 
        if (! $handle =fopen ($filename ,'w' )) { 
            echo "Cannot open file ($filename)" ;
            exit; 
        }
        foreach ($outlines as $outline) {
            fwrite($handle, $outline);
        }
        fclose($handle);
        return true;
    } 
    else { 
        return false;
    }
}    
?>
