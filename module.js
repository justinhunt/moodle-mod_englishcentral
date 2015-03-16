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
	resultsmode: null,
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
		M.mod_englishcentral.playerhelper.resultsmode = opts['resultsmode'];
		
		//default to show in div, but could be in lightbox
		var usecontainer = M.mod_englishcentral.playerhelper.playerdiv;
		var pdiv = Y.one('#' + M.mod_englishcentral.playerhelper.playerdiv);
		var rdiv = Y.one('#' + M.mod_englishcentral.playerhelper.resultsdiv);
		//no player div is loaded when the user os out of attempts, so we exit in this case
		if(!pdiv){
			return;
		}
		if(opts['lightbox']){
			usecontainer = null;
			/*
			pdiv.addClass('englishcentral_hidediv');
			rdiv.addClass('englishcentral_showdiv');
			*/
		}else{
			pdiv.addClass('englishcentral_showdiv');
			rdiv.addClass('englishcentral_hidediv');
		}
		
    	EC.init({
    		app: M.mod_englishcentral.playerhelper.appid,
    		accessToken: M.mod_englishcentral.playerhelper.accesstoken,
    		playOptions: {
    			container: usecontainer,
    			showCloseButton: true,
    			showWatchMode: opts['watchmode'],
    			showSpeakMode: opts['speakmode'],
    			showSpeakLite: opts['speaklitemode'],
    			hiddenChallenge: opts['hiddenchallengemode'],
    			showLearnMode: opts['learnmode'],
    			simpleUI: opts['simpleui'],
    			socialShareUrl: "http://www.facebook.com",
    			overrideNew: true,
    			newPlayerMode: true
    		},
    		resultFunc:	function(results){ M.mod_englishcentral.playerhelper.handleresults(results);},
    		errorMsg:	function(message){M.mod_englishcentral.playerhelper.handleerror(message);}
    	});
    	//console.log("finished init");
		//console.log("accesstoken:" + M.mod_englishcentral.playerhelper.accesstoken);
		//console.log("requesttoken:" + opts['requesttoken']);
    }, 
    
    handleresults: function(results) {
    	var txt = '<h2>' + M.util.get_string('sessionresults','englishcentral') + '</h2>';
    	txt += '<br />';
    	txt += '<b>' + M.util.get_string('sessionactivetime','englishcentral') + ':  </b>' + results.activeTime  + ' seconds<br />';
    	txt += '<b>' + M.util.get_string('totalactivetime','englishcentral') + ':  </b>' + results.totalActiveTime + ' seconds<br />';
    	txt += '<b>' + M.util.get_string('lineswatched','englishcentral') + ':  </b>' + results.linesWatched + '/' + results.linesTotal + '<br />';
    	txt += '<b>' + M.util.get_string('linesrecorded','englishcentral') + ':  </b>' + results.linesRecorded + '<br />';
    	txt += '<b>' + M.util.get_string('sessionscore','englishcentral') + ':  </b>' + Math.round(results.sessionScore * 100) + '%' + '<br />';
    	txt += '<b>' + M.util.get_string('sessiongrade','englishcentral') + ':  </b>' + results.sessionGrade + '<br />';
    	var completionrate = results.recordingComplete ? 1 : 0;
    	//this won't work in litemode because linestotal != recordablelines
    	if(!M.mod_englishcentral.playerhelper.opts['speaklitemode'] && results.linesRecorded>0){
    		completionrate = results.linesRecorded /results.linesTotal;
    	}
    	txt += '<b>' + M.util.get_string('compositescore','englishcentral') + ':  </b>' +Math.round(completionrate * (results.sessionScore * 100)) + '%<br />';
    	
    	
    	/*
		for (i in results){
			txt+= i +': '+results[i]+'<br />';
		}
		*/
		console.log(results);
		M.mod_englishcentral.playerhelper.showresponse(txt);
		if(M.mod_englishcentral.playerhelper.resultsmode=='form'){
			M.mod_englishcentral.playerhelper.formpost(results);
			//do the ui updates
			var thebutton = this.gY.one('#mod_englishcentral_startfinish_button');
			if(M.mod_englishcentral.playerhelper.opts['lightbox'] ){
				thebutton.removeClass('englishcentral_hidediv');
				thebutton.addClass('englishcentral_showdiv');
			}
			this.showresultsdiv(true);
			thebutton.set('innerHTML','Try Again');	
		}else{
			M.mod_englishcentral.playerhelper.ajaxpost(results);
		}	
    },
	
	handleerror: function(message) {
		//console.log(message);
		M.mod_englishcentral.playerhelper.showresponse("ERRORMSG <br />" + message);  
    },
	
	startfinish: function(){
		var thebutton = this.gY.one('#mod_englishcentral_startfinish_button');
		if(thebutton.get('innerHTML') =='Start' || thebutton.get('innerHTML') =='Try Again'){
			if(!EC.getReadyStatus()){return;}
			this.play();
			if(M.mod_englishcentral.playerhelper.resultsmode=='form' && M.mod_englishcentral.playerhelper.opts['lightbox'] ){
				thebutton.removeClass('englishcentral_showdiv');
				thebutton.addClass('englishcentral_hidediv');
			}else{
				thebutton.set('innerHTML','Finish');
			}
			this.showresultsdiv(false);
		}else{
			EC.getResults('M.mod_englishcentral.playerhelper.handleresults');
		}
	
	},
	
	showresultsdiv: function(showresults){
		var pdiv = Y.one('#' + M.mod_englishcentral.playerhelper.playerdiv);
		var rdiv = Y.one('#' + M.mod_englishcentral.playerhelper.resultsdiv);
		if(showresults){
			pdiv.removeClass('englishcentral_showdiv');
			pdiv.addClass('englishcentral_hidediv');
			rdiv.removeClass('englishcentral_hidediv');
			rdiv.addClass('englishcentral_showdiv');
		}else{
			rdiv.removeClass('englishcentral_showdiv');
			rdiv.addClass('englishcentral_hidediv');
			pdiv.removeClass('englishcentral_hidediv');
			pdiv.addClass('englishcentral_showdiv');
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
    
    formpost: function(resultobj){
    	var Y = M.mod_englishcentral.playerhelper.gY;
		var opts = M.mod_englishcentral.playerhelper.opts;
		for (i in resultobj){
			var elem = M.mod_englishcentral.playerhelper.gY.one('.' + 'englishcentral_' + i);
			if(elem){
				elem.set('value',resultobj[i]);
			}
		}
		return;
    },
	
	ajaxpost: function(resultobj){
    	var Y = M.mod_englishcentral.playerhelper.gY;
		var opts = M.mod_englishcentral.playerhelper.opts;
		
		//bail if we are in preview mode
		//if(opts['preview']){return;}
		
    	var uri  = 'ajaxfriend.php?id=' +  opts['cmid'] + 
				'&ecresult=' +  encodeURIComponent(JSON.stringify(resultobj)) +
    			'&sesskey=' + M.cfg.sesskey;
		//we dhoul donly declare this callback once. but actually it blocks
		Y.on('io:complete', M.mod_englishcentral.playerhelper.ajaxresult, Y,null);
		Y.io(uri);
		return;
    },
	
	
};
