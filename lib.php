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

class local_questionfinder_question_bank_search_condition  extends core_question\bank\search\condition {
    protected $tags;
    protected $where;
    protected $params;

    public function __construct() {
        $this->searchtext = optional_param('searchtext', '', PARAM_TEXT);
        $this->searchauthor = optional_param('searchauthor', false, PARAM_BOOL);
        $this->searchanswers = optional_param('searchanswers', false, PARAM_BOOL);
        $this->searchmodified = optional_param('searchmodified', false, PARAM_BOOL);
        $this->searchcreatedate = optional_param('searchcreatedate', '', PARAM_TEXT);
        $this->searchcreatedate2 = optional_param('searchcreatedate2', '', PARAM_TEXT);
        $this->searchmodifieddate = optional_param('searchmodifieddate', '', PARAM_TEXT);
        $this->searchmodifieddate2 = optional_param('searchmodifieddate2', '', PARAM_TEXT);
        $this->blockcategory = optional_param('blockcategory', '', PARAM_TEXT);

        if (!empty($this->searchtext)) {
            $this->init();
        }
        if (
            !empty($this->searchcreatedate) && !empty($this->searchcreatedate2) ||
            !empty($this->searchmodifieddate) && !empty($this->searchmodifieddate2)
        ) {
            $this->initdaterange();
        } else if (!empty($this->searchcreatedate) || !empty($this->searchmodifieddate)) {
            $this->initdate();
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
        global $output;
        $strsearchbytext = get_string('searchbytext', 'local_questionfinder');
        $strauthor = get_string('author', 'local_questionfinder');
        $strquestiontext = get_string('questiontext', 'local_questionfinder');
        $strlastmodifiedby = get_string('lastmodifiedby', 'local_questionfinder');
        $strsearchbydate = get_string('searchbydate', 'local_questionfinder');
        $strcreationdate = get_string('creationdate', 'local_questionfinder');
        $strmodificationdate = get_string('modificationdate', 'local_questionfinder');
        $strfrom = get_string('from', 'local_questionfinder');
        $strto = get_string('to', 'local_questionfinder');

        $id = optional_param('id', false, PARAM_INT);
        $questions = $DB->get_record('question', array('id' => $id));
        require_login();
        echo "<hr />\n";

        // TEXTBOX.

        echo "<h5>" . $strsearchbytext . ":</h5>";
        echo html_writer::empty_tag('input', array(
            'name' => 'searchtext', 'id' => 'searchtext', 'class' => 'searchoptions',
            'value' => $this->searchtext
        ));
        echo "<br />\n";

        // CHECKBOXES.

        echo html_writer::empty_tag('input', array(
            'type' => 'checkbox', 'name' => 'searchauthor', 'id' => 'searchauthor',
            'class' => 'searchoptions', 'value' => 1
        ));
        echo html_writer::label($strauthor, 'searchauthor');
        echo "<br />\n";

        echo html_writer::empty_tag('input', array(
            'type' => 'checkbox', 'name' => 'searchanswers', 'id' => 'searchanswers',
            'class' => 'searchoptions', 'value' => 1
        ));
        echo html_writer::label($strquestiontext, 'searchanswers');
        echo "<br />\n";

        echo html_writer::empty_tag('input', array(
            'type' => 'checkbox', 'name' => 'searchmodified', 'id' => 'searchmodified',
            'class' => 'searchoptions', 'value' => 1
        ));
        echo html_writer::label($strlastmodifiedby, 'searchmodified');
        echo "\n";
        echo html_writer::empty_tag('input', array(
            'type' => 'checkbox', 'name' => 'blockcategory', 'id' => 'blockcategory',
            'class' => 'searchoptions', 'hidden' => true, 'value' => 1
        ));
        echo "<hr />\n";

        // CALENDARS.

        echo "<h5>" . $strsearchbydate . ":</h5>";
        echo html_writer::label($strcreationdate, 'searchcreatedate');
        echo "<br />\n";
        echo html_writer::label('' . $strfrom . ': ', 'searchcreatedate');
        echo " ";
        echo html_writer::empty_tag('input', array(
            'type' => 'date', 'name' => 'searchcreatedate', 'id' => 'searchcreatedate', 'class' => 'searchoptions',
            'value' => $this->searchcreatedate
        ));
        echo " ";
        echo html_writer::label('' . $strto . ': ', 'searchcreatedate');
        echo " ";
        echo html_writer::empty_tag('input', array(
            'type' => 'date', 'name' => 'searchcreatedate2', 'id' => 'searchcreatedate2', 'class' => 'searchoptions',
            'value' => $this->searchcreatedate2
        ));
        echo  '<br>';

        echo html_writer::label($strmodificationdate, 'searchmodifieddate');
        echo "<br />\n";
        echo html_writer::label('' . $strfrom . ': ', 'searchcreatedate');
        echo " ";
        echo html_writer::empty_tag('input', array(
            'type' => 'date', 'name' => 'searchmodifieddate', 'id' => 'searchmodifieddate', 'class' => 'searchoptions',
            'value' => $this->searchmodifieddate
        ));
        echo " ";
        echo html_writer::label('' . $strto . ': ', 'searchcreatedate');
        echo " ";
        echo html_writer::empty_tag('input', array(
            'type' => 'date', 'name' => 'searchmodifieddate2', 'id' => 'searchmodifieddate2', 'class' => 'searchoptions',
            'value' => $this->searchmodifieddate2
        ));
        echo  '<br>';
        echo "<hr />\n";
    }

    // SQL QUERIES.

    private function init() {
        $this->searchcreatedate = '';
        $this->searchcreatedate2 = '';
        $this->searchmodifieddate = '';
        $this->searchmodifieddate2 = '';
        global $DB;
        $this->where = '(' . $DB->sql_like('questiontext', ':searchtext1', false) . ' OR ' .
            $DB->sql_like('q.name', ':searchtext2', false) . ')';
        $this->params['searchtext1'] = '%' . $DB->sql_like_escape($this->searchtext) . '%';
        $this->params['searchtext2'] = $this->params['searchtext1'];

        if ($this->searchanswers || $this->blockcategory == "questiontext") {
            $this->where .= " OR ( q.id IN (SELECT question FROM {question_answers} qa WHERE " .
                $DB->sql_like('answer', ':searchtext3', false) . ') )';
            $this->params['searchtext3'] = $this->params['searchtext1'];
        }
        if ($this->searchauthor || $this->blockcategory == "author") {
            $this->where .= " OR ( q.createdby IN (SELECT u.id FROM {user} u WHERE " .
                $DB->sql_like('username', ':searchtext3', false) . ') )';
            $this->params['searchtext3'] = $this->params['searchtext1'];
        }
        if ($this->searchmodified || $this->blockcategory == "modifiedby") {
            $this->where .= " OR ( q.modifiedby IN (SELECT u.id FROM {user} u WHERE " .
                $DB->sql_like('username', ':searchtext3', false) . ') )';
            $this->params['searchtext3'] = $this->params['searchtext1'];
        }
    }

    private function initdate() {
        $strerrormessagedate = get_string('errormessagedate', 'local_questionfinder');
        global $DB;
        if ($this->searchcreatedate && $this->searchmodifieddate) {
            echo "<script>
                    setTimeout(function(){ alert(" . $strerrormessagedate . ") }, 500)
                  </script>";
            return;
        }
        if ($this->searchcreatedate) {
            $this->where .= "FROM_UNIXTIME(q.timecreated, '%Y-%m-%d') = '" . $this->searchcreatedate . "'";
            $this->params['searchcreatedate'] = $this->params['searchtext1'];
        }
        if ($this->searchmodifieddate) {
            $this->where .= "FROM_UNIXTIME(q.timemodified, '%Y-%m-%d') = '" . $this->searchmodifieddate . "'";
            $this->params['searchmodifieddate'] = $this->params['searchtext1'];
        }
    }

    private function initdaterange() {
        if ($this->searchcreatedate) {
            $this->searchmodifieddate = '';
            $this->searchmodifieddate2 = '';
            $this->where .= "FROM_UNIXTIME(q.timecreated, '%Y-%m-%d') >= '" . $this->searchcreatedate .
                "' AND FROM_UNIXTIME(q.timecreated, '%Y-%m-%d') <= '" . $this->searchcreatedate2 . "'";
            $this->params['searchcreatedate'] = $this->params['searchtext1'];
            $this->params['searchcreatedate2'] = $this->params['searchtext1'];
        }
        if ($this->searchmodifieddate) {
            $this->where .= "FROM_UNIXTIME(q.timemodified, '%Y-%m-%d') >= '" . $this->searchmodifieddate .
                "' AND FROM_UNIXTIME(q.timemodified, '%Y-%m-%d') <= '" . $this->searchmodifieddate2 . "'";
            $this->params['searchmodifieddate'] = $this->params['searchtext1'];
            $this->params['searchmodifieddate2'] = $this->params['searchtext1'];
        }
    }
}
