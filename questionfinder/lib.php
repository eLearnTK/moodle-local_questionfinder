<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Functions for component 'local_questionfinder'
 *
 * @package   local_questionfinder
 * @copyright 2019 onwards Tobias Kutzner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_mockblock\search\area;
use core\oauth2\client;
use core_search\document;
use GeoIp2\Record\Location;
use mod_forum\local\data_mappers\legacy\post;


defined('MOODLE_INTERNAL') || die();


global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->libdir . '/formslib.php');


/**
 * Provide an array of search condition classes this plugin implements.
 *
 * @param \stdClass $caller
 * @return core_question\bank\search\condition[]
 */
function local_questionfinder_get_question_bank_search_conditions($caller) {
    return array(new local_questionfinder_question_bank_search_condition($caller));
}

class local_questionfinder_question_bank_search_condition  extends core_question\bank\search\condition {
    protected $tags;
    protected $where;
    protected $params;

    public function __construct() {
        // Setting the number of items on a page on 1000 and hiding the option for 20 items.
        if (!isset($_GET['checkbox_QB'])) {
            if ((strpos($_SERVER['REQUEST_URI'], "&qperpage=1000"))) {
                $_SERVER['REQUEST_URI'] = str_replace("&qperpage=1000", "", $_SERVER['REQUEST_URI']);
                echo html_writer::script("
                location.replace('{$_SERVER['REQUEST_URI']}')");
            }
        } else {
            if (!(strpos($_SERVER['REQUEST_URI'], "&qperpage=1000"))) {
                if ((strpos($_SERVER['REQUEST_URI'], "&qperpage=20"))) {
                    $_SERVER['REQUEST_URI'] = str_replace("&qperpage=20", "&qperpage=1000", $_SERVER['REQUEST_URI']);
                } else {
                    $_SERVER['REQUEST_URI'] .= ("&qperpage=1000");
                }
                if ((strpos($_SERVER['REQUEST_URI'], "&qpage="))) {
                    $strtoreplace = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "&qpage="), strlen("&qpage= "));
                    $_SERVER['REQUEST_URI'] = str_replace($strtoreplace, "", $_SERVER['REQUEST_URI']);
                }
                echo html_writer::script("
                 location.replace('{$_SERVER['REQUEST_URI']}')");
            }
            echo html_writer::script("
            window.addEventListener('load', function(event) {
                console.log(document.getElementsByClassName('paging')[0]);
                if(document.getElementsByClassName('paging')[0]){
                    (document.getElementsByClassName('paging')[0]).style.display='none';
                } });");
        }
        $this->searchtext = optional_param('searchtext', '', PARAM_TEXT);
        $this->username = optional_param('username', false, PARAM_BOOL);
        $this->firstname = optional_param('firstname', false, PARAM_BOOL);
        $this->lastname = optional_param('lastname', false, PARAM_BOOL);
        $this->searchauthor = optional_param('searchauthor', false, PARAM_BOOL);
        $this->searchanswers = optional_param('searchanswers', false, PARAM_BOOL);
        $this->searchmodified = optional_param('searchmodified', false, PARAM_BOOL);
        $this->searchcreatedate = optional_param_array('searchcreatedate', '', PARAM_TEXT);
        $this->searchcreatedate2 = optional_param_array('searchcreatedate2', '', PARAM_TEXT);
        $this->searchmodifieddate = optional_param_array('searchmodifieddate', '', PARAM_TEXT);
        $this->searchmodifieddate2 = optional_param_array('searchmodifieddate2', '', PARAM_TEXT);
        if (isset($_GET['format'])) {
            if ($_GET['format'] != 'creation' && $_GET['format'] != 'modified') {
                $this->init();
            } else {

                if (isset($_GET['checkbox_creation']) || isset($_GET['checkbox_modified'])) {
                    if (
                        !empty(json_encode($_GET['searchcreatedate'])) && !empty(json_encode($_GET['searchcreatedate2'])) ||
                        !empty(json_encode($_GET['searchmodifieddate'])) && !empty(json_encode($_GET['searchmodifieddate2']))
                    ) {

                        $this->initdaterange();
                    }
                } else if (!empty(json_encode($_GET['searchcreatedate'])) || !empty(json_encode($_GET['searchmodifieddate']))) {

                    $this->initdate();
                }
            }
        }
    }

    public function where() {

        return $this->where;
    }

    public function params() {
        return $this->params;
    }

    public function display_options_adv() {
        global $DB;

        // Initialising labels.
        $strsearchbytext = get_string('searchbytext', 'local_questionfinder');
        $strusername = get_string('username', 'local_questionfinder');
        $strfirstname = get_string('firstname', 'local_questionfinder');
        $strlastname = get_string('lastname', 'local_questionfinder');
        $strauthor = get_string('author', 'local_questionfinder');
        $strquestiontext = get_string('questiontext', 'local_questionfinder');
        $strlastmodifiedby = get_string('lastmodifiedby', 'local_questionfinder');
        $strsearchbydate = get_string('searchbydate', 'local_questionfinder');
        $strcreationdate = get_string('creationdate', 'local_questionfinder');
        $strmodificationdate = get_string('modificationdate', 'local_questionfinder');
        $strfrom = get_string('from', 'local_questionfinder');
        $strto = get_string('to', 'local_questionfinder');
        $strsearchinquestionbank = get_string('searchinquestionbank', 'local_questionfinder');
        $strapplysearch = get_string('applysearch', 'local_questionfinder');
        $strchoosetypeofnamesearch = get_string('choosetypeofnamesearch', 'local_questionfinder');
        $strsubmitbuttontext = get_string('submitbuttontext', 'local_questionfinder');

        $id = optional_param('id', false, PARAM_INT);
        $questions = $DB->get_record('question', array('id' => $id));
        require_login();
        echo "<hr />\n";

        // Creating a checkbox for activating the search in the question bank.
        $attr = array(
            'type' => 'checkbox', 'name' => 'checkbox_QB', 'id' => 'id_checkbox_QB',
            'class' => 'searchoptions mr-1',  'value' => "0"
        );
        echo html_writer::empty_tag('input', $attr);
        echo html_writer::label($strsearchinquestionbank, 'checkbox_QB');

        echo  '<br>';
        echo  '<br>';

        // New Form for the advanced search.
        $mform = new MoodleQuickForm('mform_advanced', 'post', '', null, true);

        $this->check_if_option_isset('checkbox_QB', $mform);
        if (isset($_GET['checkbox_QB'])) {
            echo html_writer::script("
                (document.getElementById('id_checkbox_QB')).checked='checked';
                (document.getElementById('id_checkbox_QB')).value=1;
            ");
        }

        // Creating new text field.
        $textsearchinputfield = array();
        $textsearchinputfield[] = $mform->createElement('text', 'searchtext', '', ' maxlength="254" size="50"');
        $mform->addGroup($textsearchinputfield, '', $strsearchbytext);

        $mform->setType('searchtext', PARAM_TEXT);

        $this->check_if_option_isset('searchtext', $mform);
        // RADIO BUTTONS.
        // Radio buttons for searching on creator or modifier names.
        $radiobuttonsnameoption = array();
        $radiobuttonsnameoption[] = $mform->createElement('radio', 'format', '', $strauthor, 'author', array(''));
        $radiobuttonsnameoption[] = $mform->createElement('radio', 'format', '', $strlastmodifiedby, 'modifiedby', array(''));
        $radiobuttonsnameoption[] = $mform->createElement('html', '<div class="w-100" ></div>');

        // Radio buttons for searching on username, firstname or lastname.
        $radiobuttonsnametype = array();
        $radiobuttonsnametype[] = $mform->createElement('radio', 'format_name', '', $strusername, 'username', array(''));
        $radiobuttonsnametype[] = $mform->createElement('radio', 'format_name', '', $strfirstname, 'firstname', array(''));
        $radiobuttonsnametype[] = $mform->createElement('radio', 'format_name', '', $strlastname, 'lastname', array(''));
        $radiobuttonsnametype[] = $mform->createElement('html', '<div class="w-100" ><br></div>');

        // Radio buttons for searching on question name.
        $questiontext = array();
        $questiontext[] = $mform->createElement('radio', 'format', '', $strquestiontext, 'questiontext', array(''));

        $mergedarrayofradiooptions = array_merge($radiobuttonsnameoption, $radiobuttonsnametype, $questiontext);
        $mform->addGroup($mergedarrayofradiooptions,  "formatchoices",  $strapplysearch, '<div style="top:10px" ></div>', false);

        $this->check_if_option_isset('format_name', $mform);
        // Updating the attributes of the form elements depending on the search.
        if (!isset($_GET['format'])) {
            $mform->setDefault('format', "");
            $mform->setDefault('format_name', "");
        } else {
            if (isset($_GET['checkbox_QB'])) {
                $mform->setDefault('format', $_GET['format']);
            }
        }

        // CALENDARS.
        // Creationdate.
        $creationdate = array();
        $creationdate[] = $mform->createElement('radio', 'format', '', $strfrom, 'creation', '');
        $creationdate[] = $mform->createElement('date_selector', 'searchcreatedate', '', '', $this->searchcreatedate, '');
        $creationdate[] = $mform->createElement('checkbox', 'checkbox_creation', '', $strto, 'creation_checked', '');
        $creationdate[] = $mform->createElement('date_selector', 'searchcreatedate2', '', '',  $this->searchcreatedate2, '');

        // Modificationdate.
        $mofificationdate = array();
        $mofificationdate[] = $mform->createElement('radio', 'format', '', $strfrom, 'modified', '');
        $mofificationdate[] = $mform->createElement('date_selector',
        'searchmodifieddate', '', $strfrom, $this->searchmodifieddate, '');
        $mofificationdate[] = $mform->createElement('checkbox', 'checkbox_modified', '', $strto, 'modified_checked', '');
        $mofificationdate[] = $mform->createElement('date_selector',
        'searchmodifieddate2', '', $strto, $this->searchmodifieddate2, '');

        if (!isset($_GET['format'])) {
            $mform->setDefault('format', "");
        } else {

            if ($_GET['format'] == 'creation') {
                if (isset($_GET['checkbox_creation'])) {
                    $mform->setDefault('checkbox_creation', $_GET['checkbox_creation']);
                } else {
                    $mform->setDefault('checkbox_creation', "");
                }
            } else if ($_GET['format'] == 'modified') {
                if (isset($_GET['checkbox_modified'])) {
                    $mform->setDefault('checkbox_modified', $_GET['checkbox_modified']);
                } else {
                    $mform->setDefault('checkbox_modified', "");
                }
            }
        }

        $this->check_if_option_isset('searchcreatedate', $mform);
        $this->check_if_option_isset('searchcreatedate2', $mform);
        $this->check_if_option_isset('searchmodifieddate', $mform);
        $this->check_if_option_isset('searchmodifieddate2', $mform);
        $mform->addGroup($creationdate,  "formatchoices_date",
        $strcreationdate, '<div style="padding: 5px" ></div>', false);
        $mform->addGroup($mofificationdate,  "formatchoices_dates",
        $strmodificationdate, '<div style="padding: 5px" ></div>', false);
        $mform->addElement('submit', 'submitbutton', $strsubmitbuttontext);

        $this->buttons_actions_and_requirements();
        // Unsetting all search options when checkbox for the search in the question bank is unset.
        if (!isset($_GET['checkbox_QB'])) {
            $mform->setDefault('searchtext', '');
            $mform->setDefault('format', "");
            $mform->setDefault('format_name', "");
            $mform->setDefault('checkbox_creation', "");
            $mform->setDefault('checkbox_modified', "");

            $this->where = '';
            echo html_writer::script("
                (document.getElementById('id_checkbox_QB')).checked='';
                (document.getElementById('id_checkbox_QB')).value=0;
            ");

            $mform->updateElementAttr('submitbutton', array('disabled' => 'disabled', 'style' => " opacity: 0.6;"));
        }

        echo  '<br>';
        echo  '<br>';

        return $mform->display();
    }

    // SQL QUERIES.
    /**
     * Searching for creator, modifier or question name in the Question Bank.
     */
    private function init() {

        global $DB;
        $this->where = '(' . $DB->sql_like('questiontext', ':searchtext1', false) . ' OR ' .
            $DB->sql_like('q.name', ':searchtext2', false) . " )";

        $this->params['searchtext1'] = '%' . $DB->sql_like_escape($this->searchtext) . '%';
        $this->params['searchtext2'] = $this->params['searchtext1'];

        if (isset($_GET['format'])) {
            if ($_GET['format'] == "questiontext") {
                $this->where .= " OR ( q.id IN (SELECT question FROM {question_answers} qa WHERE " .
                    $DB->sql_like('answer', ':searchtext3', false) . ') )';
                $this->params['searchtext3'] = $this->params['searchtext1'];
                $_GET['format_name'] = '';
            } else {

                if ($_GET['format'] == "author" && isset($_GET['format_name'])) {
                    $this->where .= " OR ( q.createdby IN (SELECT u.id FROM {user} u WHERE " .
                        $DB->sql_like($_GET['format_name'], ':searchtext3', false)  . ') )';
                    $this->params['searchtext3'] = $this->params['searchtext1'];
                } else if ($_GET['format'] == "modifiedby" && isset($_GET['format_name'])) {
                    $this->where .= " OR ( q.modifiedby IN (SELECT u.id FROM {user} u WHERE " .
                        $DB->sql_like($_GET['format_name'], ':searchtext3', false) . ') )';
                    $this->params['searchtext3'] = $this->params['searchtext1'];
                }
            }
        }
    }

    /**
     * Searching for the creation- or modification - date.
     */
    private function initdate() {
        global $CFG;
        global $DB;

        $this->searchcreatedate = $this->date_formatter(implode("-", $_GET['searchcreatedate']));
        $this->params['searchtext4'] = $this->searchcreatedate;

        $this->searchmodifieddate = $this->date_formatter(implode("-", $_GET['searchmodifieddate']));
        $this->params['searchtext5'] = $DB->sql_like_escape($this->searchmodifieddate);

        $strerrormessagedate = get_string('errormessagedate', 'local_questionfinder');
        if (!($this->searchcreatedate && $this->searchmodifieddate)) {
            echo "<script>
                    setTimeout(function(){ alert(' . $strerrormessagedate . ') }, 500)
                  </script>";
            return;
        }

        if (strval(($CFG->dbtype)) == "pgsql") {
            if (isset($_GET['searchcreatedate']) && $_GET['format'] == 'creation') {

                $this->where .= "  SELECT to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd') IN
                 (SELECT to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd') FROM {question} q WHERE " .
                $DB->sql_like("to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd')", ':searchcreatedate', false)  . ')';

                $this->params['searchcreatedate'] = $this->params['searchtext4'];
            } else if (isset($_GET['searchmodifieddate']) && $_GET['format'] == 'modified') {

                $this->where .= " SELECT to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd') IN
                 (SELECT to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd') FROM {question} q WHERE " .
                $DB->sql_like("to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd')", ':searchmodifieddate', false)  . ' )';

                $this->params['searchmodifieddate'] = $this->params['searchtext5'];
            }
        } else {
            if (isset($_GET['searchcreatedate'])  && $_GET['format'] == 'creation') {
                $this->where .= "FROM_UNIXTIME(q.timecreated, '%Y-%m-%d') = '" . $this->params['searchtext4'] ."'";
                $this->params['searchcreatedate'] = $this->params['searchtext4'];
            } else if (isset($_GET['searchmodifieddate']) && $_GET['format'] == 'modified') {
                $this->where .= "FROM_UNIXTIME(q.timemodified, '%Y-%m-%d') = '" . $this->params['searchtext5'] ."'";
                $this->params['searchmodifieddate'] = $this->params['searchtext5'];
            }
        }
    }

    /**
     * Searhcing for daterange for creation- or modification - date.
     */
    private function initdaterange() {
        global $CFG;
        global $DB;

        $this->searchcreatedate = $this->date_formatter(implode("-", $_GET['searchcreatedate']));
        $this->params['searchtext4'] = $DB->sql_like_escape($this->searchcreatedate);
        $this->searchcreatedate2 = $this->date_formatter(implode("-", $_GET['searchcreatedate2']));
        $this->params['searchtext6'] = $DB->sql_like_escape($this->searchcreatedate2);

        $this->searchmodifieddate = $this->date_formatter(implode("-", $_GET['searchmodifieddate']));
        $this->params['searchtext5'] = $DB->sql_like_escape($this->searchmodifieddate);
        $this->searchmodifieddate2 = $this->date_formatter(implode("-", $_GET['searchmodifieddate2']));
        $this->params['searchtext7'] = $DB->sql_like_escape($this->searchmodifieddate2);

        if (strval(($CFG->dbtype)) == "pgsql") {
            if (isset($_GET['checkbox_creation'])) {

                $this->where .= " SELECT to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd')
                 IN ( SELECT to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd') FROM {question} q ) WHERE
                 to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd') >= '" . $this->params["searchtext4"] .
                "' AND to_char(to_timestamp(q.timecreated)::date,'yyyy-mm-dd') <= '" . $this->params["searchtext6"] . "' ";

                $this->params['searchcreatedate'] = $this->params['searchtext4'];
                $this->params['searchcreatedate2'] = $this->params['searchtext6'];
            } else if (isset($_GET['checkbox_modified'])) {

                $this->where .= "  SELECT to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd')
                 IN (SELECT to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd') FROM {question} q ) WHERE
                 to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd') >= '" . $this->params["searchtext5"] .
                "' AND to_char(to_timestamp(q.timemodified)::date,'yyyy-mm-dd') <= '" . $this->params['searchtext7'] . "'";

                $this->params['searchmodifieddate'] = $this->params['searchtext5'];
                $this->params['searchmodifieddate2'] = $this->params['searchtext7'];
            }
        } else {

            if (isset($_GET['checkbox_creation'])) {
                $this->searchmodifieddate = '';
                $this->searchmodifieddate2 = '';
                $this->where .= "FROM_UNIXTIME(q.timecreated, '%Y-%m-%d') >= '" . $this->params["searchtext4"] .
                "' AND FROM_UNIXTIME(q.timecreated, '%Y-%m-%d') <= '" . $this->params["searchtext6"] . "'";

                $this->params['searchcreatedate'] = $this->params['searchtext4'];
                $this->params['searchcreatedate2'] = $this->params['searchtext6'];
            } else if (isset($_GET['checkbox_modified'])) {

                $this->where .= "FROM_UNIXTIME(q.timemodified, '%Y-%m-%d') >= '" . $this->params["searchtext5"]  .
                "' AND FROM_UNIXTIME(q.timemodified, '%Y-%m-%d') <= '" . $this->params["searchtext7"]  . "'";

                $this->params['searchmodifieddate'] = $this->params['searchtext5'];
                $this->params['searchmodifieddate2'] = $this->params['searchtext7'];
            }
        }
    }
    /**
     * Change the fomat of the date from 'dd-mm-yyyy to yyyy-mm-dd'.
     * @param string $thisdate of the date_selector.
     */
    public function date_formatter($thisdate) {
        $tmpvalue = $thisdate;
        for ($i = 0; $i < strlen($thisdate); $i++) {
            if ($tmpvalue[$i] == '-') {
                if ($i < 2) {
                    $tmpvalue = "0" . $thisdate;
                } else if ($i > 2 && $i < 5) {
                    $tmpvalue = substr_replace($tmpvalue, "0", $i - 1, 0);
                }
            }
        }
        $day = substr($tmpvalue, 0, 2);
        $month = substr($tmpvalue, -7, -5);
        $year = substr($tmpvalue, -4);
        $thisdate = $year . "-" . $month . "-" . $day;

        return $thisdate;
    }

    /**
     * Checking if form option has a value.
     * @param string $element the name of the element to be used.
     * @param MoodleQuickForm $mform the form used for the search in the question bank.
     */
    public function check_if_option_isset($element, $mform) {
        if (!isset($_GET[$element])) {

            $mform->setDefault($element, '');
        } else {

            $mform->setDefault($element, $_GET[$element]);
        }
    }
    /**
     * Creates interactive useage of all buttons in the form
     */
    public function buttons_actions_and_requirements() {
        echo html_writer::script("
        	window.addEventListener('load', function(event) {
                (document.getElementById('id_submitbutton')).addEventListener('click', function(e){
                    for (a in document.getElementsByName('format')){
                        if(document.getElementsByName('format')[a].checked){
                            if(document.getElementsByName('format')[a].value == 'creation'
                            || document.getElementsByName('format')[a].value == 'modified'
                            || document.getElementsByName('format')[a].value == 'questiontext' ){

                                for (name in document.getElementsByName('format_name')){
                                    if((document.getElementsByName('format_name')[name]).required){
                                        (document.getElementsByName('format_name')[name]).required='';
                                    }
                                }
                                if(document.getElementsByName('format')[a].value != 'questiontext' ){
                                    document.getElementById('id_searchtext').required = '';
                                }
                            }
                        }
                    }
                })
                for (a in document.getElementsByName('format')){
                    if((document.getElementsByName('format')[a]).value == 'creation'
                        || (document.getElementsByName('format')[a]).value == 'modified'
                        || (document.getElementsByName('format')[a]).value == 'questiontext' ){
                            (document.getElementsByName('format')[a]).addEventListener('click', function(e){
                                for (name in document.getElementsByName('format_name')){
                                    if((document.getElementsByName('format_name')[name]).checked){
                                        (document.getElementsByName('format_name')[name]).checked='';
                                    }
                                }
                            })
                            if ((document.getElementsByName('format')[a]).value == 'creation') {
                                (document.getElementsByName('format')[a]).addEventListener('click', function(e) {
                                    (document.getElementById('id_checkbox_modified')).value = '0';
                                    (document.getElementById('id_checkbox_modified')).checked = '';
                                })
                            }else if ((document.getElementsByName('format')[a]).value == 'modified') {
                                (document.getElementsByName('format')[a]).addEventListener('click', function(e) {
                                    (document.getElementById('id_checkbox_creation')).value = '0';
                                    (document.getElementById('id_checkbox_creation')).checked = '';
                                })
                            }else if ((document.getElementsByName('format')[a]).value == 'questiontext') {
                                (document.getElementsByName('format')[a]).addEventListener('click', function(e) {
                                    (document.getElementById('id_checkbox_modified')).value = '0';
                                    (document.getElementById('id_checkbox_modified')).checked = '';
                                    (document.getElementById('id_checkbox_creation')).value = '0';
                                    (document.getElementById('id_checkbox_creation')).checked = '';
                                })
                            }
                    } else if (document.getElementsByName('format')[a].value == 'author'
                        || document.getElementsByName('format')[a].value == 'modifiedby'
                        || document.getElementsByName('format')[a].value == 'questiontext' ) {
                        (document.getElementsByName('format')[a]).addEventListener('click', function(e) {
                            document.getElementById('id_searchtext').required = 'required';
                            (document.getElementById('id_checkbox_modified')).value = '0';
                            (document.getElementById('id_checkbox_modified')).checked = '';
                            (document.getElementById('id_checkbox_creation')).value = '0';
                            (document.getElementById('id_checkbox_creation')).checked = '';
                        })
                        if(document.getElementsByName('format')[a].value != 'questiontext') {
                            for (name in document.getElementsByName('format_name')) {
                                (document.getElementsByName('format_name')[name]).required = 'required';
                                console.log();
                                if ((document.getElementsByName('format_name')[name]).value) {
                                    (document.getElementsByName('format_name')[name]).addEventListener('click', function(e) {
                                        (document.getElementById('id_checkbox_modified')).value = '0';
                                        (document.getElementById('id_checkbox_modified')).checked = '';
                                        (document.getElementById('id_checkbox_creation')).value = '0';
                                        (document.getElementById('id_checkbox_creation')).checked = '';
                                    })
                                }
                            }
                        }
                    }
                }
            })
        ");
    }
}
