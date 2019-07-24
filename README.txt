English Central Activity for Moodle >= 2.9

===============================================
OVERVIEW
===============================================

This module allows you to embed videos from EnglishCentral.com within activities in a Moodle course.

In an EnglishCentral activity, students interact with a selected set of videos and work toward the Watch, Learn and Study goals set by the teacher.

===============================================
LICENSE
===============================================

This plugin is distributed under the terms of the General Public License
(see http://www.gnu.org/licenses/gpl.txt for details)

This software is provided "AS IS" without a warranty of any kind.

===============================================
CREDITS
===============================================

This module was funded by EnglishCentral and developed by Gordon Bateson. It is based on the "EnglishCentral activity module for Moodle 2.0" by Justin Hunt. It includes numerous suggestions and code tips from Alan Schwartz and EC staff, with particular thanks to Jet F. and Jon M.

===============================================
To INSTALL the EnglishCentral module
===============================================

    ----------------
    Using GIT
    ----------------

    1. Clone this plugin to your server

       cd /PATH/TO/MOODLE
       git clone -q https://github.com/gbateson/moodle-mod_englishcentral mod/englishcentral

    2. Add this plugin to the GIT exclude file

       cd /PATH/TO/MOODLE
       echo '/mod/englishcentral/' >> '.git/info/exclude'

      (continue with step 3 below)

    ----------------
    Using ZIP
    ----------------

    1. download the zip file from one of the following locations

        * https://github.com/gbateson/moodle-mod_englishcentral/archive/master.zip
        * https://bateson.kanazawa-gu.ac.jp/moodle/zip/plugins_mod_englishcentral.zip

    2. Unzip the zip file - if necessary renaming the resulting folder to "englishcentral".
       Then upload, or move, the "englishcentral" folder into the "mod" folder on
       your Moodle >= 2.9 site, to create a new folder at "mod/englishcentral"

       (continue with step 3 below)

    ----------------
    Using GIT or ZIP
    ----------------

    3. Log in to Moodle as administrator to initiate the install/update

       If the install/update does not begin automatically, you can initiate it
       manually by navigating to the following Moodle administration page:

          Settings -> Site administration -> Notifications

    4. At the end of the installation process, the plugin configuration settings will be displayed. These are explained below. They can be completed at this point, or later, by visiting the plugin settings page.

    ----------------
    Troubleshooting
    ----------------

    If you have a white screen when trying to view your Moodle site
    after having installed this plugin, then you should remove the
    plugin folder, enable Moodle debugging, and try the install again.

    With Moodle debugging enabled you should get a somewhat meaningful
    message about what the problem is.

    The most common issues with installing this plugin are:

    (a) the "englishcentral" folder is put in the wrong place
        SOLUTION: make sure the folder is at "mod/englishcentral"
                  under your main Moodle folder, and that the file
                  "mod/englishcentral/version.php" exists

    (b) permissions are set incorrectly on the "mod/englishcentral" folder
        SOLUTION: set the permissions to be the same as those of other folders
                  within the "mod" folder

    (c) the PHP cache is old
        SOLUTION: refresh the cache, for example by restarting the web server,
                  or the PHP accelerator, or both

=================================================
To UPDATE the EnglishCentral module
=================================================

    ----------------
    Using GIT
    ----------------

    1. Get the latest version of this plugin

       cd /PATH/TO/MOODLE/mod/englishcentral
       git pull

    2. Log in to Moodle as administrator to initiate the update

    ----------------
    Using ZIP
    ----------------

    Repeat steps 1 - 4 of the ZIP install procedure (see above)

=================================================
to CONFIGURE the EnglishCentral module
=================================================

The settings for the EnglishCentral module can be found at:

    [Moodle Site]/Site Administration -> Plugins -> Activity Modules -> English Central

These values for these settings can be obtained from EnglishCentral by filling out the form on the following page:

    https://poodll.com/englishcentral-demo-request/

=================================================
to Add an EnglishCentral Activity to a Course
=================================================

    1. Login to Moodle, and navigate to a course page.

    2. Enable "Edit mode" on course page.

    3. Locate the course topic/week where you wish to add the Hot Potatoes exercise.

    4. Select "EnglishCentral activity" from "Add an activity" menu.

    5. Specify and name for the activity, and if necessary, a description.

    6. If required, specify the dates on which the activity opens and closes, and the dates from when, and until when, the videos are viewable.

    7. Define study goals in terms of the following:
       (a) the number of videos to Watch
       (b) the number of words to Learn
       (c) the number of lines to Speak.

    8. Review other settings.

    9. Click the "Save and display" button at bottom of page.

=================================================
to Add videos to an EnglishCentral activity
=================================================

    1. Navigate to an EnglishCentral activity in a Moodle course where you are a teacher.

    2. Enter one or more search terms in the search box, and click the "Search" button.

    3. Click the large plus-sign, "+", next to any videos that you wish to add to this activity.

=================================================
to View videos in an EnglishCentral activity
=================================================

    1. Navigate to an EnglishCentral activity in a Moodle course

    2. Chose a video you wish to watch and click the thumbnail of that video.

    3. After watching the video, try the activities to learn words and speak lines.

    4. Continue watching videos, learning words and speaking lines until you have reached all the study goals and achieved a total score of 100%.
