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
 * English strings for englishcentral
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// required plugin strings
$string['modulename'] = 'English Central';
$string['modulenameplural'] = 'English Central Activities';
$string['modulename_help'] = 'Use the English Central Activity module to allow your students to use Englosh Central from within Moodle Courses';
$string['pluginadministration'] = 'English Central Module Administration';
$string['pluginname'] = 'English Central Activity';

// capabilities
$string['englishcentral:addinstance'] = 'Add a new English Central activity';
$string['englishcentral:view'] = 'View English Central activity';
$string['englishcentral:manage'] = 'Manage an English Central activity';
$string['englishcentral:manageattempts'] = 'Manage attempts at an English Central activity';

// activity settings
$string['consumerkey'] = 'Consumer Key';
$string['consumerkeydefault'] = 'YOUR CONSUMER KEY';
$string['consumerkeyexplain'] = 'Your consumer key is available from EnglishCentral.com';
$string['consumersecret'] = 'Consumer Secret';
$string['consumersecretdefault'] = 'YOUR CONSUMER SECRET';
$string['consumersecretexplain'] = 'Your consumer secret is available from EnglishCentral.com';
$string['developmentmode'] = 'Development mode';
$string['developmentmodeexplain'] = 'On development sites, this setting should be enabled. On production sites, it should be disabled.';
$string['encryptedsecret'] = 'Encrypted Secret';
$string['encryptedsecretdefault'] = 'YOUR ENCRYPTED SECRET';
$string['encryptedsecretexplain'] = 'Your encrypted secret is available from EnglishCentral.com';
$string['partnerid'] = 'Partner ID';
$string['partneriddefault'] = 'YOUR PARTNER ID';
$string['partneridexplain'] = 'Your partnerid is available from EnglishCentral.com';

$string['goals_help'] = 'Define goals for Watch, Learn, Speak and Study time';
$string['goals'] = 'Goals';
$string['learngoal_help'] = 'Specify the target number of words to learn.';
$string['learngoal'] = 'Learn';
$string['learngoalunits'] = '(words)';
$string['learnwords'] = '{$a} words';
$string['goalperiod_help'] = 'Specify the time period and the day or date by which these goals should be achieved.';
$string['goalperiod'] = 'Goal period';
$string['speakgoal_help'] = 'Specify the target number of lines to speak.';
$string['speakgoal'] = 'Speak';
$string['speakgoalunits'] = '(lines)';
$string['speaklines'] = '{$a} lines';
$string['studygoal_help'] = 'Specify the target number of minutes/hours to study.

The study time is calculated using the following formula:

(number of videos watched) x 6 minutes
+ (number of words learned) x 1 minute
+ (number of lines spoken) x 1 minute';
$string['studygoal'] = 'Study time';
$string['studygoalunits'] = '(minutes)';
$string['watchgoal_help'] = 'Specify the target number of videos to watch.';
$string['watchgoal'] = 'Watch';
$string['watchgoalunits'] = '(videos)';
$string['watchvideos'] = '{$a} videos';

$string['activityname_help'] = 'This is the content of the help tooltip associated with the englishcentralname field. Markdown syntax is supported.';
$string['activityname'] = 'Activity Name';

$string['activityopen'] = 'Activity available from';
$string['activityopen_help'] = 'Students can access this activity starting from this date and time. Before this date and time, the activity will not be available.';
$string['activityclose'] = 'Activity avialable until';
$string['activityclose_help'] = 'Students can to access this activity up until the date and time specified here. After this date and time, the activity will not be available.';
$string['videoopen'] = 'Videos viewable from';
$string['videoopen_help'] = 'Students can view and interact with videos starting from this date and time. Before this date, the videos will not be viewable.';
$string['videoclose'] = 'Videos viewable until';
$string['videoclose_help'] = 'Students can view and interact with videos until this date and time. After this date, students cannot view the videos, but they can still view their results.';

$string['consultadmin'] = 'For further help, please consult the administrator of this Moodle site.';
$string['invalidconfig'] = 'Sorry, we cannot proceed because the settings for the English Central activity module on this Moodle site are not valid:';
$string['missingconfig'] = 'Sorry, we cannot proceed because the following settings are not yet defined for the English Central activity module on this Moodle site:';
$string['updatesettings'] = 'Click on the following link to add/edit these settings: {$a}';

$string['readonlymode'] = 'Read-only mode';
$string['readonlymode_desc'] = 'This activity is currently in read-only mode. You can view the information on the first page of this EnglishCentral activity, but you cannot view any of the videos.';
$string['timeondate'] = '%l:%M %p on %b %d (%a) %Y';

$string['futureactivityopen'] = 'This activity will open at {$a}';
$string['futureactivityclose'] = 'This activity will close at {$a}';
$string['futurevideoclose'] = 'The videos will be available until {$a}';
$string['futurevideoopen'] = 'The videos will be available from {$a}';
$string['pastactivityopen'] = 'This activity opened at {$a}';
$string['pastactivityclose'] = 'This activity closed at {$a}';
$string['pastvideoclose'] = 'The videos were available until {$a}';
$string['pastvideoopen'] = 'The videos became available at {$a}';

$string['notavailable'] = 'Sorry, this activity is not currently avialable to you.';
$string['notviewable'] = 'Sorry, the videos for this actiity are not currently available to you.';

$string['editvideos'] = 'Edit videos';
$string['novideos'] = 'There are no videos to watch at the moment.';
$string['beginner'] = 'Beginner';
$string['intermediate'] = 'Intermediate';
$string['advanced'] = 'Advanced';
$string['levelx'] = 'Level {$a}';
$string['addvideos'] = 'Add videos';
$string['searchterm'] = 'Search term(s)';

// deprecated strings

$string['englishcentral'] = 'English Central';
$string['englishcentralfieldset'] = 'Custom example fieldset';
$string['englishcentralsettings'] = 'Enter English Central Video Title and ID';
$string['hiddenchallengemode'] ='Hidden challenge';
$string['learnmode'] ='Learn mode';
$string['lightboxmode'] ='Lightbox mode';
$string['playersettings'] = 'Player settings';
$string['simpleui'] ='Simple UI';
$string['speaklitemode'] ='SpeakLite mode';
$string['speakmode'] ='Speak mode';
$string['videoid'] = 'Video ID';
$string['videotitle'] = 'Video Title';
$string['watchmode'] ='Watch mode';

//tabs
$string['overview'] ='Overview';
$string['overview_help'] ='Overview Help';
$string['preview'] ='Preview';
$string['previewenglishcentral'] ='Preview English Central';
$string['view'] ='View';

//reports
$string['activetime'] ='Active Time';
$string['allattempts'] = 'Attempts Manager';
$string['allusers'] ='All Users (most recent attempt)';
$string['allusersreport'] ='All Users Report';
$string['attemptdetails'] = 'Attempt Details';
$string['attemptdetailsheader'] = '{$a->name} attempt details for {$a->username} on {$a->date}';
$string['attemptsmanager'] = 'Attempts Manager';
$string['badcount'] ='Bad Count';
$string['completed'] ='Completed';
$string['compositescore'] = 'Overall Score';
$string['confirmattemptdelete'] ='Are you sure you want to delete this attempt?';
$string['confirmattemptdeleteall'] ='Are you sure you want to delete ALL attempts?';
$string['confirmattemptdeletealltitle'] ='Delete ALL attempts?';
$string['confirmattemptdeletetitle'] ='Delete attempt?';
$string['date'] ='Date';
$string['defaultsettings'] ='Default Settings';
$string['delete'] ='Delete';
$string['deleteallattempts'] ='Delete All Attempts';
$string['deleteattempt'] ='delete';
$string['details'] ='Details';
$string['email'] ='Email';
$string['exceededattempts'] ='You have completed the maximum {$a} attempts.';
$string['exportcsv'] ='Export to CSV';
$string['exportexcel'] ='Export to Excel(csv)';
$string['exportpdf'] ='Export to PDF';
$string['finish'] ='Finish';
$string['fullname'] ='Full Name';
$string['goodcount'] ='Good Count';
$string['gradeaverage'] ='average score of all attempts';
$string['gradehighest'] ='highest scoring attempt';
$string['gradelatest'] ='score of latest attempt';
$string['gradelowest'] ='lowest scoring attempt';
$string['gradenone'] ='No grade';
$string['gradeoptions'] ='Grade Options';
$string['item'] ='Item';
$string['lastaccess'] ='Last Access';
$string['linesrecorded'] ='Lines recorded';
$string['lineswatched'] ='Lines watched';
$string['maxattempts'] ='Maximum attempts';
$string['myattempts'] ='My Attempts';
$string['nodataavailable'] ='No data available';
$string['phoneme'] ='Phoneme';
$string['phonemes'] ='Phonemes';
$string['phonemesheader'] = '{$a->englishcentralname} Phonemes for {$a->username} attempt on {$a->attemptdate}';
$string['points'] ='Points';
$string['points'] ='Points';
$string['reattempt'] ='Try Again';
$string['reports'] ='Reports';
$string['reporttitle'] ='Report Title {$a}';
$string['returntoreports'] ='Return to reports';
$string['selectanother'] ='Back to Course';
$string['sessionactivetime'] ='Session active time';
$string['sessiongrade'] ='Session grade';
$string['sessionresults'] ='Session gesults';
$string['sessionscore'] ='Average score';
$string['sessionscore'] ='Session Score';
$string['start'] ='Start';
$string['status'] ='Status';
$string['total'] ='Total';
$string['totalactivetime'] ='Total active time';
$string['unlimited'] ='unlimited';
$string['username'] ='Username';
$string['value'] ='Value';
$string['viewreport'] ='Details';
$string['viewreports'] ='View reports';
