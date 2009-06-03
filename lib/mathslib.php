<?php

require_once $CFG->dirroot.'/lib/evalmath/evalmath.class.php';

/**
 * This class abstracts evaluation of spreadsheet formulas.
 * See unit tests in lib/simpletest/testmathslib.php for sample usage.
 *
 * @author Petr Skoda (skodak)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class calc_formula {

    // private properties
    var $_em;
    var $_nfx   = false;   // postfix notation
    var $_error = false; // last error

    /**
     * Constructor for spreadsheet formula with optional parameters
     *
     * @param string $formula with leading =
     * @param array $params associative array of parameters used in formula. All parameter names must be lowercase!
     */
    function calc_formula($formula, $params=false) {
        $this->_em = new EvalMath();
        $this->_em->suppress_errors = !debugging('', DEBUG_DEVELOPER);
        if (strpos($formula, '=') !== 0) {
            $this->_error = "missing leading '='";
            return;
        }
        $formula = substr($formula, 1);
        if (strpos($formula, '=') !== false) {
            $this->_error = "too many '='";
            return;
        }
        $this->_nfx = $this->_em->nfx($formula);
        if ($this->_nfx == false) {
            $this->_error = $this->_em->last_error;
            return;
        }
        if ($params != false) {
            $this->set_params($params);
        }
    }

    /**
     * Raplace parameters used in existing formula,
     * parameter names must contain only lowercase [a-z] letters, no other characters are allowed!
     *
     * @param array $params associative array of parameters used in formula
     */
    function set_params($params) {
        $this->_em->v = $params;
    }

    /**
     * Evaluate formula
     *
     * @return mixed number if ok, false if error
     */
    function evaluate() {
        if ($this->_nfx == false) {
            return false;
        }
        $res = $this->_em->pfx($this->_nfx);
        if ($res === false) {
            $this->_error = $this->_em->last_error;
            return false;
        } else {
            $this->_error = false;
            return $res;
        }
    }

    /**
     * Get last error.
     * TODO: localize the strings from contructor and EvalMath library
     *
     * @return mixed string with last error description or false if ok
     */
    function get_error() {
        return $this->_error;
    }

    /**
     * Similar to format_float, formats the numbers and list separators
     * according to locale specifics.
     * @param string $formula
     * @return string localised formula
     */
    function localize($formula) {
        $formula = str_replace('.', '$', $formula); // temp placeholder
        $formula = str_replace(',', get_string('listsep'), $formula);
        $formula = str_replace('$', get_string('decsep'), $formula);
        return $formula;
    }

    /**
     * Similar to unformat_float, converts floats and lists to PHP standards.
     * @param string $formula localised formula
     * @return string 
     */
    function unlocalize($formula) {
        $formula = str_replace(get_string('decsep'), '$', $formula);
        $formula = str_replace(get_string('listsep'), ',', $formula);
        $formula = str_replace('$', '.', $formula); // temp placeholder
        return $formula;
    }
}

?>