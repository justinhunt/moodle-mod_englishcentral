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
$string['modulename'] = 'Poodll EnglishCentral';
$string['modulenameplural'] = 'Poodll EnglishCentral Activities';
$string['modulename_help'] = 'Use the EnglishCentral module to allow your students to use EnglishCentral videos within Moodle Courses';
$string['pluginadministration'] = 'EnglishCentral Administration';
$string['pluginname'] = 'Poodll EnglishCentral';

// capabilities
$string['englishcentral:addinstance'] = 'Add a new EnglishCentral activity';
$string['englishcentral:manage'] = 'Manage an EnglishCentral activity';
$string['englishcentral:manageattempts'] = 'Manage attempts at an EnglishCentral activity';
$string['englishcentral:submit'] = 'Submit data to an EnglishCentral activity';
$string['englishcentral:view'] = 'View an EnglishCentral activity';
$string['englishcentral:viewreports'] = 'View reports for an English Central activity';
$string['englishcentral:viewdevelopertools'] = 'View developer tools for an English Central activity';

// completion
$string['completiongoals'] = 'Require study goals';
$string['completionmingrade'] = 'Require minimum grade';
$string['completionpass'] = 'Require passing grade';
$string['completiondetail:mingrade'] = 'Minimum grade: {$a}';
$string['completiondetail:pass'] = 'Achieve pass grade';
$string['completiondetail:goals'] = 'Complete the watch,learn and speak goals';

// Activity settings
$string['consumerkey'] = 'Consumer Key';
$string['consumerkeydefault'] = 'YOUR CONSUMER KEY';
$string['consumerkeyexplain'] = 'Your consumer key is available from {$a}';
$string['consumersecret'] = 'Consumer Secret';
$string['consumersecretdefault'] = 'YOUR CONSUMER SECRET';
$string['consumersecretexplain'] = 'Your consumer secret is available from {$a}';
$string['developmentmode'] = 'Development mode';
$string['developmentmodeexplain'] = 'On development sites, this setting should be enabled. On production sites, it should be disabled.';
$string['encryptedsecret'] = 'Encrypted Secret';
$string['encryptedsecretdefault'] = 'YOUR ENCRYPTED SECRET';
$string['encryptedsecretexplain'] = 'Your encrypted secret is available from {$a}';
$string['partnerid'] = 'Partner ID';
$string['partneriddefault'] = 'YOUR PARTNER ID';
$string['partneridexplain'] = 'Your partnerid is available from {$a}';

$string['achieved'] = 'achieved';
$string['chatgoal_help'] = 'The target number of chat questions to discuss.';
$string['chatgoal'] = 'Chat';
$string['chatgoalunits'] = 'questions';
$string['chatquestions'] = '{$a} questions';
$string['goals_help'] = 'Define goals for Watch, Learn, Speak, Chat and Study time';
$string['goals'] = 'Goals';
$string['learngoal_help'] = 'The target number of unique words to learn.';
$string['learngoal'] = 'Learn';
$string['learngoalunits'] = 'words';
$string['learnwords'] = '{$a} words';
$string['goalperiod_help'] = 'The time period and the day or date by which these goals should be achieved.';
$string['goalperiod'] = 'Goal period';
$string['speakgoal_help'] = 'The target number of unique lines to speak.';
$string['speakgoal'] = 'Speak';
$string['speakgoalunits'] = 'lines';
$string['speaklines'] = '{$a} lines';
$string['studygoal_help'] = 'The target number of minutes/hours to study.

The study time is calculated using the following formula:

(number of videos watched) x 6 minutes
+ (number of words learned) x 1 minute
+ (number of lines spoken) x 1 minute
+ (number of chat questions) x 4 minutes';
$string['studygoal'] = 'Study time';
$string['studygoalunits'] = 'minutes';
$string['totalgoal_help'] = 'The total progress as a percentage. it is calculated as using the following formula:

**(number of items studied) / (total study goal)**

* **number of items studied**:
(number of videos watched) +
(number of words learned) +
(number of lines spoken) +
(number of chat questions discussed)
* **total study goal**:
(watch goal) + (learn goal) + (speak goal) + (chat goal)';
$string['totalgoal'] = 'Total';
$string['watchgoal_help'] = 'The target number of videos to watch.';
$string['watchgoal'] = 'Watch';
$string['watchgoalunits'] = 'videos';
$string['watchvideos'] = '{$a} videos';
$string['yourprogress'] = 'Your progress';

$string['from'] = 'From';
$string['until'] = 'Until';

$string['activityname_help'] = 'This is the content of the help tooltip associated with the englishcentralname field. Markdown syntax is supported.';
$string['activityname'] = 'Activity Name';

$string['activityopen'] = 'Activity opens';
$string['activityopen_help'] = 'Students can access this activity starting from this date and time. Before this date and time, the activity will be closed.';
$string['activityclose'] = 'Activity closes';
$string['activityclose_help'] = 'Students can to access this activity up until the date and time specified here. After this date and time, the activity will be closed.';
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

$string['noenglishcentrals'] = 'Sorry, there are no EnglishCentral activities in this course.';
$string['notavailable'] = 'Sorry, this activity is not currently avialable to you.';
$string['notviewable'] = 'Sorry, the videos for this actiity are not currently available to you.';

$string['editvideos'] = 'Edit videos';
$string['novideos'] = 'There are no videos to watch at the moment.';
$string['beginner'] = 'Beginner';
$string['intermediate'] = 'Intermediate';
$string['advanced'] = 'Advanced';
$string['levelx'] = 'Level {$a}';
$string['level'] = 'Level';

$string['addthisvideo'] = 'Add this video';
$string['addvideo'] = 'Add video';
$string['addvideohelp'] = 'To add a video to this page, click this icon and do a video search.';
$string['confirmremovevideo'] = 'Do you really want to remove this video?';
$string['copyright'] = 'Source';
$string['description'] = 'Description';
$string['goals'] = 'Goals';
$string['noconnection'] = 'WARNING: Your Moodle site cannot currently connect to the EnglishCentral server.';
$string['removevideo'] = 'Remove video';
$string['removevideohelp'] = 'To remove a video from this page, drag it to this icon.';
$string['searchterm'] = 'Search terms';
$string['topics'] = 'Topics';
$string['transcript'] = 'Transcript';
$string['xitemsfound'] = '{$a} items found';
$string['videosearch'] = 'Video search';
$string['videosearchhelp'] = 'Please type one or more search terms into the "Video search" text box.';
$string['videosearchprompt'] = 'Enter search terms here';
$string['videodetails'] = 'EnglishCentral video details';
$string['duration1'] = '<b>Short</b> (up to 1 minute)';
$string['duration2'] = '<b>Medium</b> (1 to 3 minutes)';
$string['duration3'] = '<b>Long</b> (over 3 minutes)';

$string['supporttitle'] = 'Request partnerID from EnglishCentral.com';
$string['supportconfirm'] = 'The following information will be sent to Poodll.com (English Central demo request) to request a partner ID and access keys:';
$string['supportsubject'] = 'Request for access to EC module for Moodle';
$string['supportmessage'] = 'Please could you contact me regarding a partner ID and keys to use the EC module on my Moodle site.';

$string['updatinggrades'] = 'Updating EnglishCentral grades';

$string['noprogressreport'] = 'Sorry, but there is no progress to report.';

// Display settings
$string['showdetails'] = 'Show link to details';
$string['showdetails_help'] = 'If this option is selected, a link to details of each video will be shown. The details include a transcript, vocabulary information, comprehension questions and discusion questions.';
$string['showduration'] = 'Show duration';
$string['showduration_help'] = 'If this option is selected, the duration of each video will be shown.';
$string['showlevelnumber'] = 'Show level number';
$string['showlevelnumber_help'] = 'If this option is selected, the difficulty level of each video will be displayed as a number.';
$string['showleveltext'] = 'Show level description';
$string['showleveltext_help'] = 'If this option is selected, the difficulty level of each video will be displayed as text.';
$string['showtostudentsonly'] = 'Yes, show to students only';
$string['showtoteachersandstudents'] = 'Yes, show to teachers and students';
$string['showtoteachersonly'] = 'Yes, show to teachers only';

// Deprecated strings (listed in "mod/englishcentral/lang/en/deprecated.txt").
$string['englishcentral'] = 'English Central';
$string['englishcentralfieldset'] = 'Custom example fieldset';
$string['englishcentralsettings'] = 'Enter English Central Video Title and ID';
$string['hiddenchallengemode'] = 'Hidden challenge';
$string['learnmode'] = 'Learn mode';
$string['lightboxmode'] = 'Lightbox mode';
$string['playersettings'] = 'Player settings';
$string['simpleui'] = 'Simple UI';
$string['speaklitemode'] = 'SpeakLite mode';
$string['speakmode'] = 'Speak mode';
$string['videoid'] = 'Video ID';
$string['videotitle'] = 'Video Title';
$string['watchmode'] = 'Watch mode';

// Tabs
$string['overview'] = 'Overview';
$string['overview_help'] = 'In an EnglishCentral activity, students interact with a selected set of videos and work toward the Watch, Learn and Study goals set by the teacher.

To set up the activity, the teacher first defines the following study goals:

* the number of videos to Watch
* the number of words to Learn
* the number of lines to Speak
* the number of Chat questions to discuss

The teacher then searches the EnglishCentral video library and selects which videos should be added to this EnglishCentral activity.

When the students view the activity, they first choose one or more of the videos they would like to watch. After watching a video, they can choose which words from the video they would like to learn, and which lines they wish to practice speaking. Finally, they can confirm their understanding and chat about the video with an AI ChatBot using the discussion questions as prompts.

As each student studies, the dials on their scoreboard are updated to give feedback about their progress towards the Watch, Learn, Speak and Chat goals.';
$string['preview'] = 'Preview';
$string['previewenglishcentral'] = 'Preview English Central';
$string['view'] = 'View';

// reports
$string['activetime'] = 'Active Time';
$string['allattempts'] = 'Attempts Manager';
$string['allusers'] = 'All Users (most recent attempt)';
$string['allusersreport'] = 'All Users Report';
$string['attemptdetails'] = 'Attempt Details';
$string['attemptdetailsheader'] = '{$a->name} attempt details for {$a->username} on {$a->date}';
$string['attemptsmanager'] = 'Attempts Manager';
$string['badcount'] = 'Bad Count';
$string['completed'] = 'Completed';
$string['compositescore'] = 'Overall Score';
$string['confirmattemptdelete'] = 'Are you sure you want to delete this attempt?';
$string['confirmattemptdeleteall'] = 'Are you sure you want to delete ALL attempts?';
$string['confirmattemptdeletealltitle'] = 'Delete ALL attempts?';
$string['confirmattemptdeletetitle'] = 'Delete attempt?';
$string['date'] = 'Date';
$string['defaultsettings'] = 'Default Settings';
$string['delete'] = 'Delete';
$string['deleteallattempts'] = 'Delete All Attempts';
$string['deleteattempt'] = 'delete';
$string['details'] = 'Details';
$string['email'] = 'Email';
$string['exceededattempts'] = 'You have completed the maximum {$a} attempts.';
$string['exportcsv'] = 'Export to CSV';
$string['exportexcel'] = 'Export to Excel(csv)';
$string['exportpdf'] = 'Export to PDF';
$string['finish'] = 'Finish';
$string['fullname'] = 'Full Name';
$string['goodcount'] = 'Good Count';
$string['gradeaverage'] = 'average score of all attempts';
$string['gradehighest'] = 'highest scoring attempt';
$string['gradelatest'] = 'score of latest attempt';
$string['gradelowest'] = 'lowest scoring attempt';
$string['gradenone'] = 'No grade';
$string['gradeoptions'] = 'Grade Options';
$string['item'] = 'Item';
$string['lastaccess'] = 'Last Access';
$string['linesrecorded'] = 'Lines recorded';
$string['lineswatched'] = 'Lines watched';
$string['maxattempts'] = 'Maximum attempts';
$string['myattempts'] = 'My Attempts';
$string['nodataavailable'] = 'No data available';
$string['phoneme'] = 'Phoneme';
$string['phonemes'] = 'Phonemes';
$string['phonemesheader'] = '{$a->englishcentralname} Phonemes for {$a->username} attempt on {$a->attemptdate}';
$string['playerversion'] = 'Player version';
$string['playerversiondefault'] = 'JSDK3';
$string['playerversionexplain'] = 'Generally you should use the most recent version available, but in some circumstances, you may wish to select an older version.';
$string['points'] = 'Points';
$string['reattempt'] = 'Try Again';
$string['reports'] = 'Reports';
$string['reporttitle'] = 'Report Title {$a}';
$string['selectanother'] = 'Back to Course';
$string['sessionactivetime'] = 'Session active time';
$string['sessiongrade'] = 'Session grade';
$string['sessionresults'] = 'Session results';
$string['sessionscore'] = 'Average score';
$string['sessionscore'] = 'Session Score';
$string['start'] = 'Start';
$string['status'] = 'Status';
$string['total'] = 'Total';
$string['totalactivetime'] = 'Total active time';
$string['unlimited'] = 'unlimited';
$string['username'] = 'Username';
$string['value'] = 'Value';
$string['viewreport'] = 'Details';
$string['viewreports'] = 'View reports';

// privacy api strings
$string['privacy:metadata:attempttable'] = 'The table in which the user\'s English Central attempt data is stored.';
$string['privacy:metadata:attemptid'] = 'The unique identifier of a user\'s English Central activity attempt.';
$string['privacy:metadata:ecid'] = 'The unique identifier of an English Central activity instance.';
$string['privacy:metadata:userid'] = 'The user id for the English Central attempt';
$string['privacy:metadata:videoid'] = 'The id of the video for the current attempt';
$string['privacy:metadata:watchcomplete'] = 'The percentage of the watch session completed';
$string['privacy:metadata:watchtotal'] = 'The watch session total items';
$string['privacy:metadata:watchcount'] = 'The watch session items watched';
$string['privacy:metadata:watchlineids'] = 'The watch session item line ids';
$string['privacy:metadata:learntotal'] = 'The learn session total items';
$string['privacy:metadata:learncount'] = 'The learn session items done';
$string['privacy:metadata:learnwordids'] = 'The ids of the learnt words';
$string['privacy:metadata:speakcomplete'] = 'The percentage of the speak session completed';
$string['privacy:metadata:speaktotal'] = 'The speak session total items';
$string['privacy:metadata:speakcount'] = 'The speak session items watched';
$string['privacy:metadata:speaklineids'] = 'The speak session item line ids';
$string['privacy:metadata:totalpoints'] = 'The total points for the session';
$string['privacy:metadata:sessiongrade'] = 'The session grade';
$string['privacy:metadata:sessionscore'] = 'The session score';
$string['privacy:metadata:activetime'] = 'The time active on session';
$string['privacy:metadata:totaltime'] = 'The total session time';
$string['privacy:metadata:timecompleted'] = 'The time the activity was completed';
$string['privacy:metadata:timecreated'] = 'The time the session was created';
$string['privacy:metadata:status'] = 'The activity status';
$string['privacy:metadata:englishcentralcom:accountid'] = 'The English Central plugin matches the Moodle userid with a unique English Central account id. The account id is stored at englishcentral.com.';
$string['privacy:metadata:englishcentralcom'] = 'The English Central plugin stores session data against the Moodle users English Central account id.';

$string['accountlookup'] = 'Account Lookup';
$string['accountid'] = 'EnglishCentral ID';
$string['lookupinstructions'] = 'Select a user and press "Search" to see that user\'s EnglishCentral account id.';
$string['lookupresults'] = 'The EnglishCentral account id for {$a->fullname} is: <big><b>{$a->accountid}</b></big>';
$string['lookupemptyresult'] = 'No EnglishCentral account id was found for user: {$a->fullname}.';

// CloudPoodll auth related strings
$string['displaysubs'] = '{$a->subscriptionname} : expires {$a->expiredate}';
$string['noapiuser'] = 'No Poodll API user entered. Please subscribe or take a free trial at <a href="https://poodll.com/plugin-poodll-englishcentral" target="EC">https://poodll.com</a>';
$string['noapisecret'] = 'No Poodll API secret entered. Please subscribe or take a free trial at <a href="https://poodll.com/plugin-poodll-englishcentral" target="EC">https://poodll.com</a>';
$string['credentialsinvalid'] = 'The Poodll API user and secret entered could not be used to get access. Please check them.  Contact <a href="https://poodll.com/contact" target="EC">Poodll support </a>if there is a problem.';
$string['appauthorised'] = 'Poodll EnglishCentral is authorised for this site.';
$string['appnotauthorised'] = 'Poodll EnglishCentral is NOT authorised for this site. Is your site URL registered and your subscription current?';
$string['refreshtoken'] = 'Refresh license information';
$string['notokenincache'] = 'Refresh to see license information. Contact <a href="https://poodll.com/contact" target="EC">Poodll support </a>if there is a problem.';
$string['poodllapiuser'] = 'Poodll API user';
$string['poodllapiuser_details'] = '';
$string['poodllapisecret'] = 'Poodll API Secret';

// These errors are displayed on activity page
$string['nocredentials'] = 'Poodll API user and secret not entered. Please enter them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member" target="EC">Poodll.com.</a>';
$string['novalidcredentials'] = 'Poodll API user and secret were rejected and could not gain access. Please check them on <a href="{$a}">the settings page.</a> You can get them from <a href="https://poodll.com/member" target="EC">Poodll.com.</a>';
$string['nosubscriptions'] = 'There is no current subscription for this site/plugin.';
$string['subscriptionhasnocreds'] = 'The Poodll EnglishCentral subscription is expired or not yet set up. Contact <a href="https://poodll.com/contact" target="EC">Poodll support</a>';

$string['advancedsection'] = 'Advanced Settings';
$string['advancedsection_details'] = 'The settings from here should usually be left untouched. You may be directed by Poodll support to use them in some cases.';

$string['forceembedlayout'] = 'Force embedded layout';
$string['forceembedlayout_details'] = 'Force embedded window.';

$string['enablesetuptab'] = 'Enable setup tab';
$string['enablesetuptab_details'] = 'Show a tab containing the activity instance settings to admins. This setting appears to be just for developers. It will also force the "embed" page layout.';

$string['setup'] = 'Setup';
$string['view'] = 'View';
$string['reports'] = 'Reports';
$string['report'] = 'Reports';

$string['freetrial'] = 'Get Cloud Poodll API Credentials and a Free Trial';
$string['freetrial_desc'] = 'A dialog should appear that allows you to register for a free trial with Poodll. After registering you should login to the members dashboard to get your API user and secret. And to register your site URL.';
$string['memberdashboard'] = 'Member Dashboard';
$string['memberdashboard_desc'] = "";
$string['fillcredentials'] = 'Set API user and secret with existing credentials';
$string['progressdials'] = 'Progress Dials Location';
$string['progressdials_details'] = '';
$string['progressdials_top'] = 'Top (above player)';
$string['progressdials_bottom'] = 'Bottom (Below player)';

$string['chatmode'] = 'Enable Chat Mode';
$string['chatmode_details'] = 'If enabled on your account, chat mode will become available in the EnglishCentral player.';
$string['progressupdated'] = 'Progress Updated Event';
$string['noitemsfound'] = 'No videos found. Please try a different search.';
$string['developertools'] = "Developer Tools";
/* Reports */
$string['reportsmenutoptext'] = "Review attempts on Poodll EnglishCentral activities using the reports below.";
$string['showingattempt'] = 'Showing attempt for: {$a}';
$string['basicreport'] = 'Basic Report';
$string['returntoreports'] = 'Return to Reports Menu';
$string['returntogradinghome'] = 'Return to Grading Top';
$string['exportexcel'] = 'Export to CSV';

$string['studentid'] = "St. No.";
$string['studentname'] = "Student Name";
$string['activityname'] = "RA. Name.";
$string['errorcount'] = "No. errors";
$string['activitywords'] = "No. Words in Passage";
$string['readingtime'] = "Read Time (secs)";
$string['oralreadingscore'] = "Oral Reading Score";
$string['oralreadingscore_p'] = 'Oral Reading Score(%)';
$string['reportsmenutoptext'] = "Review attempts on EnglishCentral activities using the reports below.";
$string['attempts_explanation'] = "A summary of EnglishCentral attempts per user in this activity.";

$string['customfont'] = "Custom font";
$string['customfont_help'] = "A font name that will override site default for this passage when displayed. Must be exact in spelling and case. eg Andika or Comic Sans MS";
$string['advancedheader'] = "Advanced";

$string['missedwords'] = "Missed Words";
$string['missedwordsheading'] = "Missed Words";
$string['missedwordsreport'] = "Missed Words";
$string['missedwords_explanation'] = "The top error words in the most recent attempts";
$string['missed_count'] = "Missed Count";
$string['rank'] = "Rank";
$string['nodataavailable'] = 'No Data Available Yet';
$string['datatables_info'] = "Showing _START_ to _END_ of _TOTAL_ entries";
$string['datatables_infoempty'] = "Showing 0 to 0 of 0 entries";
$string['datatables_infofiltered'] = "(filtered from _MAX_ total entries)";
$string['datatables_infothousands'] = ",";
$string['datatables_lengthmenu'] = "Show _MENU_ entries";
$string['datatables_search'] = "Search:";
$string['datatables_zerorecords'] = "No matching records found";
$string['datatables_paginate_first'] = "First";
$string['datatables_paginate_last'] = "Last";
$string['datatables_paginate_next'] = "Next";
$string['datatables_paginate_previous'] = "Previous";
$string['datatables_emptytable'] = "No data available in table";
$string['datatables_aria_sortascending'] = "activate to sort column ascending";
$string['datatables_aria_sortdescending'] = "activate to sort column descending";
$string['attemptsperpage'] = "Attempts to show per page: ";
$string['attemptsreport'] = 'Attempts Report';
$string['attemptssummaryreport'] = 'Attempts Summary Report';
$string['myattemptssummary'] = 'Attempts Summary ({$a} attempts)';
$string['summaryexplainer'] = 'The table below shows your average and your highest scores for this activity.';
$string['id'] = 'ID';
$string['name'] = 'Name';
$string['timecreated'] = 'Time Created';
$string['basicheading'] = 'Basic Report';
$string['attemptsheading'] = 'Attempts Report: {$a}';
$string['userattemptsheading'] = 'Attempts by User: {$a->username} on Activity: {$a->activityname}';
$string['attemptssummaryheading'] = 'Attempts Summary Report';
$string['gradingheading'] = 'Grading latest attempts for each user.';
$string['machinegradingheading'] = 'Machine evaluated latest attempt for each user.';
$string['gradingbyuserheading'] = 'Grading all attempts for: {$a}';
$string['deletenow'] = '';
$string['watch'] = "Watch";
$string['learn'] = "Learn";
$string['speak'] = "Speak";
$string['chat'] = "Chat";
$string['total'] = "Total";
$string['watch_p'] = "Watch(%)";
$string['learn_p'] = "Learn(%)";
$string['speak_p'] = "Speak(%)";
$string['chat_p'] = "Chat(%)";
$string['total_p'] = "Total(%)";
$string['attempts'] = "Attempts";
$string['firstattempt'] = "First Attempt";
$string['videoname'] = "Video";
$string['activetime'] = "Active Time";
$string['totaltime'] = "Total Time";
$string['timecompleted'] = "Time Completed";
$string['videoperformancereport'] = 'Video Performance Report';
$string['videoperformanceheading'] = 'Video Performance on Activity: {$a}';
$string['videoperformance_explanation'] = 'Information on how videos were viewed and practiced with in this activity.';
$string['totalwatches'] = "Total Watches";
$string['averagelearn'] = "Average Learn";
$string['averagespeak'] = "Average Speak";
$string['averagechat'] = "Average Chat";
$string['courseattemptsreport'] = 'Course Attempts Report';
$string['courseattemptsheading'] = 'Course Attempts Report: {$a}';
$string['courseattempts_explanation'] = "A summary of EnglishCentral attempts per user in this course.";
$string['usercourseattemptsreport'] = 'User Course Attempts Report';
$string['usercourseattempts_explanation'] = "All a users attempts on EnglishCentral activities within this course";
$string['attemptssummaryreport'] = 'Attempts Summary Report';
$string['attemptssummaryreport_explanation'] = 'A summary of attempts by users on thiis EnglishCentral activity.';
$string['usercourseattemptsheading'] = 'Attempts by User: {$a->username} in course: {$a->coursename}';
$string['activities'] = "Activities";
$string['graphicalattempts_explanation'] = 'A summary of EnglishCentral attempts per user in this activity [Graphical]';
$string['deletedvideo'] = "Removed Video";
$string['updateallgrades'] = "Update All Grades";
$string['updateallgrades_details'] = "<div>Update all grades in gradebook for this activity. Maybe useful if you changed goals.</div>";
$string['generateattemptdata'] = "Generate Attempt Data";
$string['generateattemptdata_details'] = "<div>Generate random attempts from the last attempt in the table, 1 for each enrolled user</div>";
$string['updategradesconfirm'] = "This will update all the user grades in this activity. Are you sure?";
$string['generateattemptsconfirm'] = "This will generate a fake attempt in this activity for each enrolled user. Are you sure?";
$string['dayslimit'] = "Days to show";
$string['nodayslimit'] = 'Show all data';
$string['xdayslimit'] = 'Show last {$a} days';
$string['table'] = 'Table';
$string['chart'] = 'Chart';
$string['combi'] = 'Combi';
$string['table_explanation'] = 'Report in Table format';
$string['chart_explanation'] = 'Report in Chart format';
$string['combi_explanation'] = 'Report in Table and Chart format';
$string['firstname'] = 'First Name';
$string['lastname'] = 'Last Name';
$string['activityname'] = 'Activity Name';
$string['reportstable'] = "Reports Style";
$string['reportstable_details'] = "Ajax tables are faster to use and can sort data. Paged tables are harder to navigate with, better with a lot of data.";
$string['reporttableajax'] = "Ajax Tables";
$string['reporttablepaged'] = "Paged Tables";
$string['difficulty'] = "Video Level";
$string['cloudpoodllserver'] = 'Cloud Poodll Server';
$string['cloudpoodllserver_details'] = 'The server to use for Cloud Poodll. Only change this if Poodll has provided a different one.';

