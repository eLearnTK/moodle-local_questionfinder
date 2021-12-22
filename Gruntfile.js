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
/* jshint node: true, browser: false */
/* eslint-env node */

/**
 *
 * @package    local_questionfinder
 * @copyright  2013 Ray Morris
 * @copyright  2019 onwards Tobias Kutzner <Tobias.Kutzner@b-tu.de>
 * @copyright  2020 onwards Pedro Rojas
 * @copyright  2020 onwards Eleonora Kostova <Eleonora.Kostova@b-tu.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 "use strict";

 module.exports = function (grunt) {
     // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
     require("grunt-load-gruntfile")(grunt);
     grunt.loadGruntfile("../../Gruntfile.js");
 };
