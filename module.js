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
        this.gY = Y;
        this.opts = opts;
        this.playerdiv = opts['playerdiv'];
        this.resultsdiv = opts['resultsdiv'];
        this.videoid = opts['videoid'];
        this.consumerkey= opts['consumerkey'];
        this.sdktoken = opts['sdktoken'];
        this.resultsmode = opts['resultsmode'];


        //default to show in div, but could be in lightbox
        var usecontainer = this.playerdiv;
        var pdiv = Y.one('#' + this.playerdiv);
        var rdiv = Y.one('#' + this.resultsdiv);
        //no player div is loaded when the user os out of attempts, so we exit in this case
        if(!pdiv){
            return;
        }

            pdiv.addClass('englishcentral_showdiv');
            rdiv.addClass('englishcentral_hidediv');

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
    	if(!this.opts['speaklitemode'] && results.linesRecorded>0){
    		completionrate = results.linesRecorded /results.linesTotal;
    	}
    	txt += '<b>' + M.util.get_string('compositescore','englishcentral') + ':  </b>' +Math.round(completionrate * (results.sessionScore * 100)) + '%<br />';
    	

    	/*
		for (i in results){
			txt+= i +': '+results[i]+'<br />';
		}
		*/
		console.log(results);
		this.showresponse(txt);
		var thebutton = this.gY.one('#mod_englishcentral_startfinish_button');
		this.showresultsdiv(true);
		thebutton.set('innerHTML','Try Again');
	},
	
	handleerror: function(message) {
		//console.log(message);
		this.showresponse("ERRORMSG <br />" + message);
    },
	
	startfinish: function(){
		var thebutton = this.gY.one('#mod_englishcentral_startfinish_button');
        var theplayerdiv = this.gY.one('#' + this.playerdiv);
		if(thebutton.get('innerHTML') =='Start' || thebutton.get('innerHTML') =='Try Again'){
            theplayerdiv.set('innerHTML','');
            ECSDK.loadWidget("player", {
                partnerKey: this.consumerkey,
                dialogId: this.videoid,
                container: this.playerdiv,
                partnerSdkToken: this.sdktoken
            });

			thebutton.set('innerHTML','Finish');
			this.showresultsdiv(false);
		}else{
		    var actiondata= {dialogID: this.videoid, sdkToken: this.sdktoken};
		    this.ajaxpost('dialogprogress',actiondata);
		}
	
	},
	
	showresultsdiv: function(showresults){
		var pdiv = Y.one('#' + this.playerdiv);
		var rdiv = Y.one('#' + this.resultsdiv);
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

    showresponse: function(showtext){
    	var resultscontainer = this.gY.one('#' + this.resultsdiv);
		if((typeof showtext) != 'string'){
			showtext = JSON.stringify(showtext);
		}
		resultscontainer.setContent(showtext);
    },
	
	    // Define a function to handle the AJAX response.
    ajaxresult: function(id,o,args) {
    	var id = id; // Transaction ID.
        var returndata = o.responseText; // Response data.
        var Y = M.mod_englishcentral.playerhelper.gY;
    	var result = Y.JSON.parse(returndata);
        if(result.success){
        	location.reload(true);
        }
    },
    

	
	ajaxpost: function(action,actiondata){
    	var Y = this.gY;
		var opts = this.opts;
		
		//bail if we are in preview mode
		//if(opts['preview']){return;}

    	var uri  = 'ajaxhelper.php?id=' +  opts['cmid'] +
            '&ecaction=' +  action +
            '&actiondata=' + encodeURIComponent(JSON.stringify(actiondata))+
            '&sesskey=' + M.cfg.sesskey;
        //we should only declare this callback once. but actually it blocks
		Y.on('io:complete', this.ajaxresult, Y,null);
		Y.io(uri);
		return;
    },
	
	
};
