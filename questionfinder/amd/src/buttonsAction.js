// This file is part of Moodle - http://moodle.org/.
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
 *
 * @module     local_qustionfinder/buttonsAction
 * @class      buttonsAction
 * @package    local_qustionfinder
 * @copyright  2013 Ray Morris
 * @copyright  2019 onwards Tobias Kutzner <Tobias.Kutzner@b-tu.de>
 * @copyright  2020 onwards Pedro Rojas
 * @copyright  2020 onwards Eleonora Kostova <Eleonora.Kostova@b-tu.de>
 * @copyright  based on 2012 work by Felipe Carasso (http://carassonet.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function () {
    return /** @alias module:local_qustionfinder/buttonsAction */ {
        /**
         * Implements buttons' reaction.
         *
         * @method buttons_actions_and_requirements
         */
        buttons_actions_and_requirements: function () {
            document.getElementById("id_submitbutton").addEventListener("click", function (e) {
                for (let a in document.getElementsByName("format")) {
                    if (document.getElementsByName("format")[a].checked) {
                        if (document.getElementsByName("format")[a].value == "creation" ||
                          document.getElementsByName("format")[a].value == "modified" ||
                          document.getElementsByName("format")[a].value == "questiontext" ||
                          document.getElementsByName("format")[a].value == "metadatacreation") {
                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].required) {
                                    document.getElementsByName("format_name")[name].required = "";
                                }
                            }
                            if (document.getElementsByName("format")[a].value != "questiontext") {
                                document.getElementById("id_searchtext").required = "";
                            } else {
                                document.getElementById("id_searchtext").required = "required";
                            }
                        } else if (document.getElementsByName("format")[a].value != "author" &&
                            document.getElementsByName("format")[a].value != "modifiedby") {
                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].required) {
                                    document.getElementsByName("format_name")[name].required = "";
                                }
                            }

                            document.getElementById("id_searchtext").required = "required";
                        } else if (document.getElementsByName("format")[a].value == "author" ||
                            document.getElementsByName("format")[a].value == "modifiedby") {
                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].value) {
                                    document.getElementsByName("format_name")[name].required = "required";
                                }
                            }
                            document.getElementById("id_searchtext").required = "required";
                        }
                    }
                }
            });

            for (let a in document.getElementsByName("format")) {
                if (document.getElementsByName("format")[a].value) {
                    if (document.getElementsByName("format")[a].value == "creation" ||
                        document.getElementsByName("format")[a].value == "modified" ||
                        document.getElementsByName("format")[a].value == "questiontext") {
                        document.getElementsByName("format")[a].addEventListener("click", function (e) {
                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].checked) {
                                    document.getElementsByName("format_name")[name].checked = "";
                                }
                            }
                        });
                        if (document.getElementsByName("format")[a].value == "creation") {
                            document.getElementsByName("format")[a].addEventListener("click", function (e) {
                                document.getElementById("id_checkbox_modified").value = "0";
                                document.getElementById("id_checkbox_modified").checked = "";
                                document.getElementById("id_checkbox_metadatacreation").value = "0";
                                document.getElementById("id_checkbox_metadatacreation").checked = "";
                            });
                        } else if (document.getElementsByName("format")[a].value == "modified") {
                            document.getElementsByName("format")[a].addEventListener("click", function (e) {
                                document.getElementById("id_checkbox_creation").value = "0";
                                document.getElementById("id_checkbox_creation").checked = "";
                                document.getElementById("id_checkbox_metadatacreation").value = "0";
                                document.getElementById("id_checkbox_metadatacreation").checked = "";
                            });
                        } else if (document.getElementsByName("format")[a].value == "questiontext") {
                            document.getElementsByName("format")[a].addEventListener("click", function (e) {
                                document.getElementById("id_searchtext").required = "required";
                                document.getElementById("id_checkbox_modified").value = "0";
                                document.getElementById("id_checkbox_modified").checked = "";
                                document.getElementById("id_checkbox_creation").value = "0";
                                document.getElementById("id_checkbox_creation").checked = "";
                                document.getElementById("id_checkbox_metadatacreation").value = "0";
                                document.getElementById("id_checkbox_metadatacreation").checked = "";
                            });
                        }
                    } else if (document.getElementsByName("format")[a].value == "author" ||
                        document.getElementsByName("format")[a].value == "modifiedby" ||
                        document.getElementsByName("format")[a].value == "questiontext") {
                        document.getElementsByName("format")[a].addEventListener("click", function (e) {
                            document.getElementById("id_searchtext").required = "required";
                            document.getElementById("id_checkbox_modified").value = "0";
                            document.getElementById("id_checkbox_modified").checked = "";
                            document.getElementById("id_checkbox_creation").value = "0";
                            document.getElementById("id_checkbox_creation").checked = "";
                            document.getElementById("id_checkbox_metadatacreation").value = "0";
                            document.getElementById("id_checkbox_metadatacreation").checked = "";
                        });
                        if (document.getElementsByName("format")[a].value != "questiontext") {
                            document.getElementById("id_searchtext").required = "required";
                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].value) {
                                    document.getElementsByName("format_name")[name].required = "required";
                                }
                            }

                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].value) {
                                    document.getElementsByName("format_name")[name].addEventListener("click", function (e) {
                                        document.getElementById("id_checkbox_modified").value = "0";
                                        document.getElementById("id_checkbox_modified").checked = "";
                                        document.getElementById("id_checkbox_creation").value = "0";
                                        document.getElementById("id_checkbox_creation").checked = "";
                                        document.getElementById("id_checkbox_metadatacreation").value = "0";
                                        document.getElementById("id_checkbox_metadatacreation").checked = "";
                                    });
                                }
                            }
                        } else {
                            for (let name in document.getElementsByName("format_name")) {
                                if (document.getElementsByName("format_name")[name].value) {
                                    document.getElementsByName("format_name")[name].required = "required";
                                }
                            }
                        }
                    } else {
                        if (document.getElementsByName("format")[a].value) {
                            if (document.getElementsByName("format")[a].checked) {
                                for (let name in document.getElementsByName("format_name")) {
                                    if (document.getElementsByName("format_name")[name].checked) {
                                        document.getElementsByName("format_name")[name].checked = "";
                                    }
                                    if (document.getElementsByName("format_name")[name].value) {
                                        document.getElementsByName("format_name")[name].required = "";
                                    }
                                }

                                if (document.getElementsByName("format")[a].value != "metadatacreation") {
                                    document.getElementById("id_searchtext").required = "required";
                                    document.getElementById("id_checkbox_metadatacreation").value = "0";
                                    document.getElementById("id_checkbox_metadatacreation").checked = "";
                                } else {
                                    document.getElementById("id_searchtext").required = "";
                                }

                                  document.getElementById("id_checkbox_modified").value = "0";
                                  document.getElementById("id_checkbox_modified").checked = "";
                                  document.getElementById("id_checkbox_creation").value = "0";
                                  document.getElementById("id_checkbox_creation").checked = "";
                            }
                            let form_value = document.getElementsByName("format")[a].value;
                            document.getElementsByName("format")[a].addEventListener("click", function (e) {
                                for (let name in document.getElementsByName("format_name")) {
                                    if (document.getElementsByName("format_name")[name].checked) {
                                        document.getElementsByName("format_name")[name].checked = "";
                                    }
                                    if (document.getElementsByName("format_name")[name].value) {
                                        document.getElementsByName("format_name")[name].required = "";
                                    }
                                }
                                if (form_value == "metadatacreation") {
                                    document.getElementById("id_searchtext").required = "";
                                } else {
                                    document.getElementById("id_checkbox_metadatacreation").value = "0";
                                    document.getElementById("id_checkbox_metadatacreation").checked = "";
                                    document.getElementById("id_searchtext").required = "required";
                                }

                                document.getElementById("id_checkbox_modified").value = "0";
                                document.getElementById("id_checkbox_modified").checked = "";
                                document.getElementById("id_checkbox_creation").value = "0";
                                document.getElementById("id_checkbox_creation").checked = "";
                            });
                        }
                    }
                }
            }
        },
        /**
         * Disables the option of pages per page.
         *
         * @method hidepagging
         */
        hidepagging: function () {
            if (document.getElementsByClassName("paging")[0]) {
                document.getElementsByClassName("paging")[0].style.display = "none";
            }
        },
        /**
         * Redirect user to a specific page.
         *
         * @method replacelocationurl
         */
        replacelocationurl: function (url) {
            location.replace(url);
        },
        /**
         * Selects checkbox.
         *
         * @method checkboxactivitychecked
         */
        checkboxactivitychecked: function () {
            document.getElementById("id_checkbox_QB").checked = "checked";
        },
        /**
         * Unselets checkbox.
         *
         * @method checkboxactivityunchecked
         */
        checkboxactivityunchecked: function () {
            document.getElementById("id_checkbox_QB").checked = "";
        },
    };
});
