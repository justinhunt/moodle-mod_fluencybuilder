/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','filter_poodll/utils_amd','filter_poodll/poodll_mediarecorder'], function($, jqui, log, utils,pmr) {

    "use strict"; // jshint ;_;

    log.debug('Test controller: initialising');

    return {

        currentitemid: 0,
        currentitem: 0,
        itemcount: null,
        cmid: null,
        testdata: null,
        holderid: null,
        recorderid: null,
        playerid: null,
        startbuttonid: null,
        controls: null,
        fbrecorder: null,

        //for making multiple instances
        clone: function(){
            return $.extend(true,{},this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){
            var dd = this.clone();

            dd.testdata = props.testdata;
            dd.cmid = props.cmid;
            dd.holderid = props.widgetid + '_holder';
            dd.recorderid = props.widgetid + '_recorder';
            dd.playerid = props.widgetid + '_player';
            dd.startbuttonid = props.widgetid + '_startbutton';
            dd.itemcount = dd.testdata.length;

            dd.setup_recorder();
            dd.process_html();
            dd.register_events();
        },

        start_item: function(){
            var dd = this;

            dd.currentitem = dd.currentitem +1;
            if(dd.currentitem > dd.itemcount){
                //do ending
            }

            //get item data
            var item = dd.testdata[dd.currentitem-1];

            //to make sure we send the correct evaluation
            dd.currentitemid = item.itemid;

            //set controls with item data
            dd.controls.progress.text(item.itemprogress);
            dd.controls.header.text(item.itemheader);
            dd.controls.othertext.html(item.itemtext);

            //set recorder with item data
            var ip = dd.fbrecorder.fetch_instanceprops();
            ip.config.timelimit=item.timetarget;

            ip.config.resource = item.resourceurl;
            ip.config.resource2 = item.modelurl;

            var controlbar = ip.controlbar;
            /*
            controlbar.resourceplayer[0].src = item.resourceurl;
            controlbar.modelplayer[0].src =item.modelurl;
            */

            //commence playback
            controlbar.resourcebutton.click();

        },

        process_html: function(){

            var controls ={
                //the feedback dialog
                holder: $('#' + this.holderid),
                the_dialog:  $('#' + this.holderid + '  .mod_fluencybuilder_dialogcontentbox'),
                me_play: $('#' + this.holderid + '  .mod_fluencybuilder_me_play'),
                me_ok: $('#' + this.holderid + '  .mod_fluencybuilder_me_ok'),
                me_ng: $('#' + this.holderid + '  .mod_fluencybuilder_me_ng'),
                player: $('#' + this.playerid),
                startbutton: $('#' + this.startbuttonid),

                //the text items
                progress: $('#' + this.holderid + '  .mod_fluencybuilder_itemprogress'),
                header: $('#' + this.holderid + '  .mod_fluencybuilder_itemheader'),
                othertext: $('#' + this.holderid + '  .mod_fluencybuilder_itemothertext'),

            };

            this.controls = controls;

        },

        setup_recorder: function(){
            var params=[];
            var dingurl = M.cfg.wwwroot + '/filter/poodll/ding.mp3';

            params['widgetid'] = this.recorderid;
            params['media_timeinterval'] = 2000;
            params['media_audiomimetype'] = 'audio/webm';//or audio/wav
            params['media_videorecordertype'] = 'auto';//or mediarec or webp
            params['media_videocapturewidth'] = 320;
            params['media_videocaptureheight'] = 240;
            params['mediatype'] = 'audio';
            params['media_skin'] = 'fluencybuilder';
            params['media_skin_style'] = '';
            params['timelimit'] =5;
            params['hideupload'] =true;
            params['resource']=dingurl;//just for now we use ding ..need a blank
            params['resource2']=dingurl;//just for now we use ding ..need a blank



            var fbrecorder = pmr.embed('#' + this.recorderid,params);
            this.fbrecorder =fbrecorder;

        },

        register_events: function() {
            var dd = this;
            //ip is the internal props of the fb recorder
            var ip = dd.fbrecorder.fetch_instanceprops();

            var recorder_play_button = $('#' + dd.holderid + '  .poodll_play-recording_fluencybuilder');

            dd.controls.me_ok.click(function () {
                dd.send_evaluation('ok');
            });

            dd.controls.me_ng.click(function () {
                dd.send_evaluation('ng');
            });

            dd.controls.startbutton.click(function(){
                $(this).hide();
                //debugger;
                dd.controls.holder.show();
                dd.start_item();
            }),
            dd.controls.startbutton.removeClass('mod_fluencybuilder_startbutton_disabled'),

            //set the submission player src to the origin
            dd.controls.me_play.click(function () {
                var checkplayer = dd.controls.player[0];
                pmr.do_play_audio(ip,checkplayer);
            });

            //set the dialog popup function to show after the audio model player has ended
            var endedfunction = function () {
                //prepare next button
                var nexttext = M.util.get_string('recui_next', 'mod_fluencybuilder');
                var buttons = {};
                buttons[nexttext] = function () {

                    if (dd.currentitem == dd.itemcount) {
                        window.location.replace(M.cfg.wwwroot + '/mod/fluencybuilder/view.php?id=' + dd.cmid);
                        $(this).dialog("close");
                    }else{
                        $(this).dialog("close");
                        //do next item
                         dd.start_item();
                    }
                }

                //prepare and show dialog
                dd.controls.the_dialog.dialog({
                    dialogClass: 'mod_fluencybuilder_no-close',
                    resizable: false,
                    height: "auto",
                    width: 400,
                    modal: true,
                    buttons: buttons
                });

            };

            //when the model player finishes show our dialog
            //we need to do some dumb setInterval here because of javascript load race condtions YUKKY
            var model_player = ip.controlbar.modelplayer;
            if (model_player.length < 1) {
                var interval_handle = setInterval(function(){
                    var model_player = $('#' + dd.holderid + '  .poodll_modelplayer_fluencybuilder');
                    if(model_player.length >0){
                        clearInterval(interval_handle);
                        model_player.on('ended', endedfunction);
                    }
                }, 500);
            } else {
                model_player.on('ended', endedfunction);
            }
        },



        send_evaluation: function(evaluation){

            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;
            
            //set up our handler for the response
            xhr.onreadystatechange = function(e){
                if(this.readyState===4){
                    if(xhr.status==200){
                        log.debug('ok we got an attempt update response');
                        //get a yes or forgetit or tryagain
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
                        if(payloadobject){
                            switch(payloadobject.message) {
                                case 'noted':
                                    log.debug('attempted item evaluation accepted');
                                    if(payloadobject.attemptid != window.attemptid){
                                        window.attemptid=payloadobject.attemptid;
                                    }
                                    break;

                                case 'problem':
                                default:
                                    log.debug('attempted item evaluation failure');
                            }
                        }
                     }else{
                        log.debug('Not 200 response:' + xhr.status);
                    }
                }
            };

            //use already created attemptid if we have one
            var attemptid=0;
            if(window.attemptid) {
                attemptid = window.attemptid;
            }

            var params = "itemid=" + that.currentitemid + "&cmid=" + that.cmid + "&eval=" + evaluation + '&attemptid=' + attemptid;
            xhr.open("POST",M.cfg.wwwroot + '/mod/fluencybuilder/jsonresults.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.send(params);
        }
    };//end of returned object
});//total end
