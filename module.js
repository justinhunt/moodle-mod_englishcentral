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
 * JavaScript library for the englishcentral module.
 *
 * @package    mod
 * @subpackage englishcentral
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


M.mod_englishcentral = M.mod_englishcentral || {};

M.mod_englishcentral.playerhelper = {
	gY: null,
	playerdiv: null,
	resultsdiv: null,
	accesstoken: null,
	videoid: null,
	appid: null,
	opts: null,

	 /**
     * @param Y the YUI object
     * @param start, the timer starting time, in seconds.
     * @param preview, is this a quiz preview?
     */
    init: function(Y,opts) {
    	//console.log("entered init");
    	M.mod_englishcentral.playerhelper.gY = Y;
		M.mod_englishcentral.playerhelper.opts = opts;
    	M.mod_englishcentral.playerhelper.playerdiv = opts['playerdiv'];
    	M.mod_englishcentral.playerhelper.resultsdiv = opts['resultsdiv'];
    	M.mod_englishcentral.playerhelper.videoid = opts['videoid'];
		M.mod_englishcentral.playerhelper.appid = opts['appid'];
		M.mod_englishcentral.playerhelper.accesstoken = opts['accesstoken'];
    	EC.init({
    		app: M.mod_englishcentral.playerhelper.appid,
    		accessToken: M.mod_englishcentral.playerhelper.accesstoken,
    		playOptions: {
    			container: M.mod_englishcentral.playerhelper.playerdiv,
    			showCloseButton: true,
    			showWatchMode: opts['watchmode'],
    			showSpeakMode: opts['speakmode'],
    			showSpeakLite: opts['speaklitemode'],
    			hiddenChallenge: opts['hiddenchallengemode'],
    			showLearnMode: opts['learnmode'],
    			simpleUI: opts['simpleui'],
    			socialShareUrl: "http://www.facebook.com"
    		},
    		resultFunc:	M.mod_englishcentral.playerhelper.handleresults,
    		errorMsg:	M.mod_englishcentral.playerhelper.handleerror
    	});
    	//console.log("finished init");
		//console.log("accesstoken:" + M.mod_englishcentral.playerhelper.accesstoken);
		//console.log("requesttoken:" + opts['requesttoken']);
    }, 
    
    handleresults: function(results) {
    	var txt = 'RESULTS<br />';
		for (i in results){
			txt+= i +': '+results[i]+'<br />';
		}
		//console.log(results);
		M.mod_englishcentral.playerhelper.showresponse(txt); 
		M.mod_englishcentral.playerhelper.ajaxpost(results);		
    },
	
	handleerror: function(message) {
		//console.log(message);
		M.mod_englishcentral.playerhelper.showresponse("ERRORMSG <br />" + message);  
    },
	
	startfinish: function(){
		var thebutton = this.gY.one('#mod_englishcentral_startfinish_button');
		if(thebutton.get('innerHTML') =='Start' || thebutton.get('innerHTML') =='Try Again'){
			this.play();
			thebutton.set('innerHTML','Finish');
		}else{
			EC.getResults('M.mod_englishcentral.playerhelper.handleresults');
		}
	
	},
	
	play: function(){
		EC.play(this.videoid);
    },
    
	login: function(){
		//console.log('logginin');
		EC.login(M.mod_englishcentral.playerhelper.accesstoken,M.mod_englishcentral.playerhelper.showresponse);
    },
	
	logout: function(){
		console.log('logginout');
		EC.logout(M.mod_englishcentral.playerhelper.showresponse);
    },
    
    showresponse: function(showtext){
    //console.log('that was:' + showtext);
    	var resultscontainer = M.mod_englishcentral.playerhelper.gY.one('#' + M.mod_englishcentral.playerhelper.resultsdiv);
		if((typeof showtext) != 'string'){
			showtext = JSON.stringify(showtext);
		}
		resultscontainer.setContent(showtext);
		//console.log('that was:' + showtext);
    },
	
	    // Define a function to handle the AJAX response.
    ajaxresult: function(id,o,args) {
    	var id = id; // Transaction ID.
        var returndata = o.responseText; // Response data.
        var Y = M.mod_englishcentral.playerhelper.gY;
    	//console.log(returndata);
        var result = Y.JSON.parse(returndata);
        if(result.success){
        	location.reload(true);
			//console.log(result);
        }
    },
	
	ajaxpost: function(resultobj){
    	var Y = M.mod_englishcentral.playerhelper.gY;
		var opts = M.mod_englishcentral.playerhelper.opts;
		
		//bail if we are in preview mode
		//if(opts['preview']){return;}
		
    	var uri  = 'ajaxfriend.php?id=' +  opts['cmid'] + 
				'&ecresult=' +  JSON.stringify(resultobj) +
    			'&sesskey=' + M.cfg.sesskey;
		//we dhoul donly declare this callback once. but actually it blocks
		Y.on('io:complete', M.mod_englishcentral.playerhelper.ajaxresult, Y,null);
		Y.io(uri);
		return;
    },
	
	
};
