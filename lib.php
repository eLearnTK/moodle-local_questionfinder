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
 * @package    local_questionfinder
 * @copyright  2013 Ray Morris
 * @copyright  2019 onwards Tobias Kutzner <Tobias.Kutzner@b-tu.de>
 * @copyright  2020 onwards Pedro Rojas
 * @copyright  2020 onwards Eleonora Kostova <Eleonora.Kostova@b-tu.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Provide an array of search condition classes this plugin implements.
 *
 * @param \stdClass $caller
 * @return core_question\bank\search\condition[]
 */
function local_questionfinder_get_question_bank_search_conditions($caller) {
    return array(new local_questionfinder_question_bank_search_condition($caller));
}

/**
 * Helper class for filtering/searching questions.
 *
 * See also {@link question_bank_view::init_search_conditions()}.
 * @copyright 2013 Ray Morris
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_questionfinder_question_bank_search_condition  extends core_question\bank\search\condition {
    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /** @var array query param used in where. */
    protected $params;

    /**
     * Contructor.
     */
    public function __construct($caller) {
        global $PAGE;

        $this->serverurl = optional_param('REQUEST_URI', $_SERVER['REQUEST_URI'], PARAM_TEXT);
        $this->format = optional_param('format', '', PARAM_TEXT);
        $this->formatname = optional_param('format_name', '', PARAM_TEXT);
        $this->checkbox = optional_param('checkbox_QB', '', PARAM_BOOL);
        $this->checkboxcreation = optional_param('checkbox_creation', '', PARAM_BOOL);
        $this->checkboxmodified = optional_param('checkbox_modified', '', PARAM_BOOL);
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

        // Setting the number of items on a page on 1000 and hiding the option for 20 items.
        if (!$this->checkbox) {
            if ((strpos($this->serverurl, "&qperpage=1000"))) {
                $this->serverurl = str_replace("&qperpage=1000", "", $this->serverurl);

                $PAGE->requires->js_call_amd(
                    "local_questionfinder/buttonsAction",
                    "replacelocationurl",
                    array("url" => $this->serverurl)
                );
            }
            $arguments = file_get_contents('php://input');
            $requests = json_decode($arguments, true);
            if (!empty($requests)) {
                $args = $requests[0]['args']['args'][0];
                $querystring = preg_replace('/^\?/', '', $args['value']);
                $params = [];
                parse_str($querystring, $params);
                if (!empty($params['checkbox_QB'])) {
                    $this->checkbox = $params['checkbox_QB'];
                }
                if (!empty($params['searchtext'])) {
                    $this->searchtext = $params['searchtext'];
                }
                if (!empty($params['format'])) {
                    $this->format = $params['format'];
                }
                if (!empty($params['format_name'])) {
                    $this->formatname = $params['format_name'];
                }
                if (!empty($params['checkbox_creation'])) {
                    $this->checkboxcreation = $params['checkbox_creation'];
                }
                if (!empty($params['checkbox_modified'])) {
                    $this->checkboxmodified = $params['checkbox_modified'];
                }
                if (!empty($params['searchcreatedate'])) {
                    $this->searchcreatedate = $params['searchcreatedate'];
                }
                if (!empty($params['searchcreatedate2'])) {
                    $this->searchcreatedate2 = $params['searchcreatedate2'];
                }
                if (!empty($params['searchmodifieddate'])) {
                    $this->searchmodifieddate = $params['searchmodifieddate'];
                }
                if (!empty($params['searchmodifieddate2'])) {
                    $this->searchmodifieddate2 = $params['searchmodifieddate2'];
                }
            }
        } else {
            if (!(strpos($this->serverurl, "&qperpage=1000"))) {
                if ((strpos($this->serverurl, "&qperpage=20"))) {
                    $this->serverurl = str_replace("&qperpage=20", "&qperpage=1000", $this->serverurl);
                } else {
                    $this->serverurl .= ("&qperpage=1000");
                }
                if ((strpos($this->serverurl, "&qpage="))) {
                    $strtoreplace = substr($this->serverurl, strpos($this->serverurl, "&qpage="), strlen("&qpage= "));
                    $this->serverurl = str_replace($strtoreplace, "", $this->serverurl);
                }
                $PAGE->requires->js_call_amd(
                    "local_questionfinder/buttonsAction",
                    "replacelocationurl",
                    array("url" => $this->serverurl)
                );
            }

            $PAGE->requires->js_call_amd("local_questionfinder/buttonsAction", "hidepagging", array());
        }

        if (($this->format)) {
            if ($this->format != 'creation' && $this->format != 'modified') {
                $this->init();
            } else {

                if (($this->checkboxcreation) || ($this->checkboxmodified)) {
                    if (
                        !empty(json_encode($this->searchcreatedate)) && !empty(json_encode($this->searchcreatedate2)) ||
                        !empty(json_encode($this->searchmodifieddate)) && !empty(json_encode($this->searchmodifieddate2))
                    ) {

                        $this->initdaterange();
                    }
                } else if (
                    !empty(json_encode($this->searchcreatedate)) || !empty(json_encode($this->searchmodifieddate))
                ) {

                    $this->initdate();
                }
            }
        }
    }

    /**
     * Return an SQL fragment to be ANDed into the WHERE clause to filter which questions are shown.
     * @return string SQL fragment. Must use named parameters.
     */
    public function where() {
        return $this->where;
    }

    /**
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return $this->params;
    }

    /**
     * Display GUI for selecting criteria for searching/filtering questions.
     *
     * @return string HTML form fragment
     */
    public function display_options_adv() {
        global $DB, $PAGE;
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
        $strsubmitbuttontext = get_string('submitbuttontext', 'local_questionfinder');

        // Form new.
        $strapplysearchto = get_string('applysearch', 'local_questionfinder');
        $id = optional_param('id', false, PARAM_INT);
        $questions = $DB->get_record('question', array('id' => $id));
        require_login();
        echo "<hr />";

        // Creating a checkbox for activating the search in the question bank.
        $attr = array(
            'type' => 'checkbox', 'name' => 'checkbox_QB', 'id' => 'id_checkbox_QB',
            'class' => 'searchoptions mr-1',  'value' => 1
        );
        echo html_writer::empty_tag('input', $attr);
        echo html_writer::label($strsearchinquestionbank, 'checkbox_QB');

        // New Form for the advanced search.
        $mform = new MoodleQuickForm('mform_advanced', 'post', '', null, true);

        $this->check_if_option_isset('checkbox_QB', $mform, $this->checkbox);

        if ($this->checkbox) {
            $PAGE->requires->js_call_amd(
                "local_questionfinder/buttonsAction",
                "checkboxactivitychecked",
                array()
            );
        }

        // Creating new text field.
        $textsearchinputfield = array();
        $textsearchinputfield[] = $mform->createElement('text', 'searchtext', '', ' maxlength="254" size="50"');
        $mform->addGroup($textsearchinputfield, '', $strsearchbytext);

        $mform->setType('searchtext', PARAM_TEXT);

        $this->check_if_option_isset('searchtext', $mform, $this->searchtext);
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
        $mform->addGroup($mergedarrayofradiooptions,  "formatchoices",  $strapplysearchto, '<div style="top:10px" ></div>', false);

        $this->check_if_option_isset('format_name', $mform, $this->formatname);
        // Updating the attributes of the form elements depending on the search.
        if (!($this->format)) {
            $mform->setDefault('format', "");
            $mform->setDefault('format_name', "");
        } else {
            if (($this->checkbox)) {
                $mform->setDefault('format', $this->format);
            }
        }

        // CALENDARS.
        // Date search text.
        $datesearchtext = array();
        $datesearchtext[] = $mform->createElement('html', '<div class="w-100" ><br></div>');
        $mform->addGroup(
            $datesearchtext,
            "",
            $strsearchbydate,
            '<div style="padding: 5px" ></div>',
            false
        );

        // Creationdate.
        $creationdate = array();
        $creationdate[] = $mform->createElement('radio', 'format', '', $strfrom, 'creation', '');
        $creationdate[] = $mform->createElement('date_selector', 'searchcreatedate', '', '', $this->searchcreatedate, '');
        $creationdate[] = $mform->createElement('checkbox', 'checkbox_creation', '', $strto, '', '');
        $creationdate[] = $mform->createElement('date_selector', 'searchcreatedate2', '', '',  $this->searchcreatedate2, '');

        // Modificationdate.
        $mofificationdate = array();
        $mofificationdate[] = $mform->createElement('radio', 'format', '', $strfrom, 'modified', '');
        $mofificationdate[] = $mform->createElement(
            'date_selector',
            'searchmodifieddate',
            '',
            $strfrom,
            $this->searchmodifieddate,
            ''
        );
        $mofificationdate[] = $mform->createElement('checkbox', 'checkbox_modified', '', $strto, '', '');
        $mofificationdate[] = $mform->createElement(
            'date_selector',
            'searchmodifieddate2',
            '',
            $strto,
            $this->searchmodifieddate2,
            ''
        );

        if (!($this->format)) {
            $mform->setDefault('format', "");
        } else {

            if ($this->format == 'creation') {
                if (($this->checkboxcreation)) {
                    $mform->setDefault('checkbox_creation', $this->checkboxcreation);
                } else {
                    $mform->setDefault('checkbox_creation', "");
                }
            } else if ($this->format == 'modified') {
                if (($this->checkboxmodified)) {
                    $mform->setDefault('checkbox_modified', $this->checkboxmodified);
                } else {
                    $mform->setDefault('checkbox_modified', "");
                }
            }
        }

        $this->check_if_option_isset('searchcreatedate', $mform, $this->searchcreatedate);
        $this->check_if_option_isset('searchcreatedate2', $mform, $this->searchcreatedate2);
        $this->check_if_option_isset('searchmodifieddate', $mform, $this->searchmodifieddate);
        $this->check_if_option_isset('searchmodifieddate2', $mform, $this->searchmodifieddate2);

        $mform->addGroup(
            $creationdate,
            "formatchoices_date",
            $strcreationdate,
            '<div style="padding: 5px" ></div>',
            false
        );
        $mform->addGroup(
            $mofificationdate,
            "formatchoices_dates",
            $strmodificationdate,
            '<div style="padding: 5px" ></div>',
            false
        );
        $mform->addElement('submit', 'submitbutton', $strsubmitbuttontext);

        $PAGE->requires->js_call_amd("local_questionfinder/buttonsAction", "buttonsActions", array());

        // Unsetting all search options when checkbox for the search in the question bank is unset.
        if (!($this->checkbox)) {
            $mform->setDefault('searchtext', '');
            $mform->setDefault('format', "");
            $mform->setDefault('format_name', "");
            $mform->setDefault('checkbox_creation', "");
            $mform->setDefault('checkbox_modified', "");

            $this->where = '';

            $PAGE->requires->js_call_amd(
                "local_questionfinder/buttonsAction",
                "checkboxactivityunchecked",
                array()
            );

            $mform->updateElementAttr('submitbutton', array('disabled' => 'disabled', 'style' => " opacity: 0.6;"));
            $PAGE->requires->js_call_amd("local_questionfinder/buttonsAction", "disablesearchbuttons", array());
        }

        echo  '<br/>';
        echo  '<br/>';

        return $mform->display();
    }

    // SQL QUERIES.

    /**
     * Search questions in the DB by name.
     */
    private function init() {

        global $DB;
        $this->where = '(' . $DB->sql_like('questiontext', ':searchtext1', false) . ' OR ' .
            $DB->sql_like('q.name', ':searchtext2', false) . " )";

        $this->params['searchtext1'] = '%' . $DB->sql_like_escape($this->searchtext) . '%';
        $this->params['searchtext2'] = $this->params['searchtext1'];

        if (($this->format)) {
            if (($this->formatname)) {
                if ($this->format == "author") {
                    $this->where .= " OR ( q.createdby IN (SELECT u.id FROM {user} u WHERE " .
                        $DB->sql_like($this->formatname, ':searchtext3', false)  . ') )';
                    $this->params['searchtext3'] = $this->params['searchtext1'];
                } else if ($this->format == "modifiedby") {
                    $this->where .= " OR ( q.modifiedby IN (SELECT u.id FROM {user} u WHERE " .
                        $DB->sql_like($this->formatname, ':searchtext3', false) . ') )';
                    $this->params['searchtext3'] = $this->params['searchtext1'];
                }
            } else {   // QUESTIONTEXTFIELD.
                if ($this->format == "questiontext") {
                    $this->where .= " OR ( q.id IN (SELECT question FROM {question_answers} qa WHERE " .
                        $DB->sql_like('answer', ':searchtext3', false) . ') )';
                    $this->params['searchtext3'] = $this->params['searchtext1'];
                    $this->formatname = '';
                }
            }
        }
    }

    /**
     * Search questions in the DB by date.
     */
    private function initdate() {

        $searchcreatedatetmp = $this->date_formatter(implode("-", $this->searchcreatedate));
        $this->params['searchtext4'] = $searchcreatedatetmp;

        $searchmodifieddatetmp = $this->date_formatter(implode("-", $this->searchmodifieddate));
        $this->params['searchtext5'] = $searchmodifieddatetmp;

        $strerrormessagedate = get_string('errormessagedate', 'local_questionfinder');
        if (!($this->searchcreatedate && $this->searchmodifieddate)) {
            echo "<script>
                    setTimeout(function(){ alert(' . $strerrormessagedate . ') }, 500)
                  </script>";
            return;
        }

        if (($this->searchcreatedate) && $this->format == 'creation') {

            $time1 = strtotime($searchcreatedatetmp);
            $time2 = strtotime($searchcreatedatetmp . "+1day");

            $this->where .= "SELECT q.timecreated IN ( SELECT q.timecreated
            FROM {question} q WHERE timecreated >= $time1 AND timecreated < $time2)";

            $this->params['searchcreatedate'] = $this->params['searchtext4'];
        } else if (($this->searchmodifieddate) && $this->format == 'modified') {

            $time1 = strtotime($searchmodifieddatetmp);
            $time2 = strtotime($searchmodifieddatetmp . "+1day");

            $this->where .= "SELECT q.timemodified IN ( SELECT q.timemodified
            FROM {question} q WHERE timemodified >= $time1 AND timemodified < $time2)";

            $this->params['searchmodifieddate'] = $this->params['searchtext5'];
        }
    }

    /**
     * Search questions in the DB by date range.
     */
    private function initdaterange() {

        $searchcreatedatetmp = $this->date_formatter(implode("-", $this->searchcreatedate));
        $this->params['searchtext4'] = $searchcreatedatetmp;
        $searchcreatedatetmp2 = $this->date_formatter(implode("-",  $this->searchcreatedate2));
        $this->params['searchtext6'] = $searchcreatedatetmp2;

        $searchmodifieddatetmp = $this->date_formatter(implode("-", $this->searchmodifieddate));
        $this->params['searchtext5'] = $searchmodifieddatetmp;
        $searchmodifieddatetmp2 = $this->date_formatter(implode("-", $this->searchmodifieddate2));
        $this->params['searchtext7'] = $searchmodifieddatetmp2;

        if (($this->checkboxcreation)) {

            $time1 = strtotime($searchcreatedatetmp);
            $time2 = strtotime($searchcreatedatetmp2 . "+1day");

            $this->where .= "SELECT q.timecreated IN ( SELECT q.timecreated
            FROM {question} q WHERE timecreated >= $time1 AND timecreated < $time2)";

            $this->params['searchcreatedate'] = $this->params['searchtext4'];
            $this->params['searchcreatedate2'] = $this->params['searchtext6'];
        } else if (($this->checkboxmodified)) {

            $time1 = strtotime($searchmodifieddatetmp);
            $time2 = strtotime($searchmodifieddatetmp2 . "+1day");

            $this->where .= "SELECT q.timemodified IN ( SELECT q.timemodified
            FROM {question} q WHERE timemodified >= $time1 AND timemodified < $time2)";

            $this->params['searchmodifieddate'] = $this->params['searchtext5'];
            $this->params['searchmodifieddate2'] = $this->params['searchtext7'];
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
     * @param mixed $elem the form element.
     */
    public function check_if_option_isset($element, $mform, $elem) {
        if (!$elem) {
            $mform->setDefault($element, '');
        } else {
            $mform->setDefault($element, $elem);
        }
    }
}
