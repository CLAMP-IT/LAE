<?php // $Id: latex.php,v 1.9.2.1 2009/03/31 10:23:53 skodak Exp $
    // latex.php
    // render TeX stuff using latex - this will not work on all platforms
    // or configurations. Only works on Linux and Mac with appropriate 
    // software installed. 
    // Much of this inspired/copied from Benjamin Zeiss' work

    class latex {

        var $temp_dir;
        var $error;

        /**
         * Constructor - create temporary directories and build paths to
         * external 'helper' binaries.
         * Other platforms could/should be added
         */
        function latex() {
            global $CFG; 
            
            // construct directory structure
            $this->temp_dir = $CFG->dataroot . "/temp/latex";
            if (!file_exists("$CFG->dataroot/temp")) {
                mkdir( "$CFG->dataroot/temp", $CFG->directorypermissions );
            }
            if (!file_exists( $this->temp_dir )) {
                mkdir( $this->temp_dir, $CFG->directorypermissions );
            }

        }

        /**
         * Accessor function for support_platform field. 
         * @return boolean value of supported_platform
         */
        function supported() {
            return $this->supported_platform;
        }

        /**
         * Turn the bit of TeX into a valid latex document
         * @param string $forumula the TeX formula
         * @param int $fontsize the font size
         * @return string the latex document
         */
        function construct_latex_document( $formula, $fontsize=12 ) {
            global $CFG;

            $formula = tex_sanitize_formula($formula);

            // $fontsize don't affects to formula's size. $density can change size
            $doc =  "\\documentclass[{$fontsize}pt]{article}\n"; 
            $doc .=  $CFG->filter_tex_latexpreamble;
            $doc .= "\\pagestyle{empty}\n";
            $doc .= "\\begin{document}\n";
//dlnsk            $doc .= "$ {$formula} $\n";
            if (preg_match("/^[[:space:]]*\\\\begin\\{(gather|align|alignat|multline).?\\}/i",$formula)) {
               $doc .= "$formula\n";
            } else {
               $doc .= "$ {$formula} $\n";
            }
            $doc .= "\\end{document}\n";
            return $doc;
        }

        /** 
         * execute an external command, with optional logging
         * @param string $command command to execute
         * @param file $log valid open file handle - log info will be written to this file
         * @return return code from execution of command
         */
        function execute( $command, $log=null ) {
            $output = array();
            exec( $command, $output, $return_code );
            if ($log) {
                fwrite( $log, "COMMAND: $command \n" );
                $outputs = implode( "\n", $output );
                fwrite( $log, "OUTPUT: $outputs \n" );
                fwrite( $log, "RETURN_CODE: $return_code\n " );
            }
            return $return_code;
        }

        /**
         * Render TeX string into gif
         * @param string $formula TeX formula
         * @param string $filename base of filename for output (no extension)
         * @param int $fontsize font size
         * @param int $density density value for .ps to .gif conversion
         * @param string $background background color (e.g, #FFFFFF).
         * @param file $log valid open file handle for optional logging (debugging only)
         * @return bool true if successful
         */
        function render( $formula, $filename, $fontsize=12, $density=240, $background='', $log=null ) {
            
            global $CFG;
  
            // quick check - will this work?
            if (empty($CFG->filter_tex_pathlatex)) {
                return false;
            }

            $doc = $this->construct_latex_document( $formula, $fontsize );

            // construct some file paths
            $tex = "{$this->temp_dir}/$filename.tex";
            $dvi = "{$this->temp_dir}/$filename.dvi";
            $ps  = "{$this->temp_dir}/$filename.ps";
            $gif = "{$this->temp_dir}/$filename.gif";

            // turn the latex doc into a .tex file in the temp area
            $fh = fopen( $tex, 'w' );
            fputs( $fh, $doc );
            fclose( $fh );

            // run latex on document
            $command = "{$CFG->filter_tex_pathlatex} --interaction=nonstopmode $tex";
            chdir( $this->temp_dir );
            if ($this->execute($command, $log)) { // It allways False on Windows
//                return false;
            } 

            // run dvips (.dvi to .ps)
            $command = "{$CFG->filter_tex_pathdvips} -E $dvi -o $ps";
            if ($this->execute($command, $log )) {
                return false;
            }

            // run convert on document (.ps to .gif)
            if ($background) {
                $bg_opt = "-transparent \"$background\""; // Makes transparent background
            } else {
                $bg_opt = "";
            }
            $command = "{$CFG->filter_tex_pathconvert} -density $density -trim $bg_opt $ps $gif";
            if ($this->execute($command, $log )) {
                return false;
            }

            return $gif;
        }

        /**
         * Delete files created in temporary area
         * Don't forget to copy the final gif before calling this
         * @param string $filename file base (no extension)
         */
        function clean_up( $filename ) {
            unlink( "{$this->temp_dir}/$filename.tex" );
            unlink( "{$this->temp_dir}/$filename.dvi" );
            unlink( "{$this->temp_dir}/$filename.ps" );
            unlink( "{$this->temp_dir}/$filename.gif" );
            unlink( "{$this->temp_dir}/$filename.aux" );
            unlink( "{$this->temp_dir}/$filename.log" );
            return;
        }

    }


?>
