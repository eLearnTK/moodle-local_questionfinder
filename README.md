# questionfinder
Update of original Question search module for Moodle - Copyright (C) 2014 Ray Morris, this version (1.1.0) adds several search options to the original functionality extended by Tobias Kutzner (Moodle Moot DACH 2019: Improve questionbank with better search options for question Tobias Kutzner, Katja Neubehler, Gerhard Schwed), Pedro Rojas Copyright (C) 2019

## Description (Original question search module for Moodle - Copyright (C) 2014 Ray Morris):
Adds text search to the question bank. You can find questions and answers which contain specific words and phrases. Some have used it to find "all of the above" answers by searching for that phrase.  

The wildcard % can be used within a search. For example, chocolate%recipe will find:

chocolate cake recipe

chocolate pie recipe

chocolate and peanut butter bar recipe

## Changelog:
Version (1.1.0) adds several search options to the original functionality (Extended by Tobias Kutzner (Moodle Moot DACH 2019: Improve questionbank with better search options for question Tobias Kutzner, Katja Neubehler, Gerhard Schwed), Pedro Rojas):

- Search by Author
- Search by Last Modified by
- Search by Creation Date (single date or range)
- Search by Modification Date (single date or range)

## How to Use:
Navigate to the Question Bank search tab on your Moodle server and choose a search mode:

### Search by Author, Question Text or Last Modified by:
1) Write the text you want to find in the "Search by" text input
2) Click on the respective checkbox depending on the type of search (**Author**, **Question text** or **Last modified by**)

### Search by Creation Date or Modification Date:
1) Select the desired date on the first (leftmost) calendar for a single date search, or the starting and finishing date for a ranged search on either **Creation Date** or **Modification Date**

## Developer Documentation:
The plugin uses the existent core_question/bank/search/condition moodle functionality.
### UI:
The plugin elements are created by making use of the **html_writer** and creating several inputs:
- 1 Texbox
- 3 Checkboxes
- 4 Calendars
### SQL Queries:
The plugin uses the default SQL Moodle database by adding extra conditions to the existant search queries. The queries are spread in two functions **init()**, **initdate()** and **initdaterange()**. The first adds the conditions for **question text** (question title or description), **author** and **last modifier**; the second adds conditions for **single date** **creation date** and **modification date** and the last adds conditions for **a range** of **creation dates** and **modification dates**.
 
## Moodle plugin link:
[https://github.com/eLearnTK/moodle_local_questionfinder](https://moodle.org/plugins/moodle_local_questionfinder)

Maintaned by: **Ray Morris**

Updated by: **Tobias Kutzner, Pedro Rojas**
