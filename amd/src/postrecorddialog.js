/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','filter_poodll/utils_amd'], function($, jqui, log, utils) {

    "use strict"; // jshint ;_;

    log.debug('Post Record Dialog: initialising');

    return {

        itemid: null,
        currentitem: null,
        itemcount: null,
        cmid: null,
        instanceprops: null,


        //for making multiple instances
        clone: function(){
            return $.extend(true,{},this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){
            var dd = this.clone();

            dd.itemid = props.itemid;
            dd.cmid = props.cmid;
            dd.currentitem = props.currentitem;
            dd.itemcount = props.itemcount;
            dd.instanceprops = props;
            dd.instanceprops.holderid = 'mod_fluencybuilder_dplaceholder_' + props.currentitem;
            dd.instanceprops.recorderid = 'mod_fluencybuilder_dplaceholder_' + props.currentitem;
            dd.insert_html();
            dd.register_events();

        },

        insert_html: function(){

            var ip =this.instanceprops;
            var element = $('#' + ip.holderid);
            if (element.length ==0){return;}
            var title = M.util.get_string('recui_howwasit', 'mod_fluencybuilder');
            var thecontrols = '<div class="hide">';
            thecontrols += '<div class="mod_fluencybuilder_dialogcontentbox" title="' + title + '">';
            thecontrols +=  '<button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_me_play"><i class="fa fa-play" aria-hidden="true"></i></button>';
            thecontrols += '<button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_me_ok"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i></button>';
            thecontrols += ' <button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_me_ng"><i class="fa fa-thumbs-o-down" aria-hidden="true"></i></button>';
            thecontrols += '</div>';//end of dialog div
            thecontrols += '</div>';//end of hide div

            $(element).prepend(thecontrols);

            var controls ={
                the_dialog:  $('#' + ip.holderid + '  .mod_fluencybuilder_dialogcontentbox'),
                me_play: $('#' + ip.holderid + '  .mod_fluencybuilder_me_play'),
                me_ok: $('#' + ip.holderid + '  .mod_fluencybuilder_me_ok'),
                me_ng: $('#' + ip.holderid + '  .mod_fluencybuilder_me_ng')
            };
            ip.controls = controls;

        },

        register_events: function() {
            var ip = this.instanceprops;
            var that = this;

            ip.controls.me_ok.click(function () {
                that.send_evaluation('ok');
            });

            ip.controls.me_ng.click(function () {
                that.send_evaluation('ng');
            });

            //set the submission player src to the origin
            ip.controls.me_play.click(function () {
                var recorder_play_button = $('#' + ip.holderid + '  .poodll_play-recording_fluencybuilder');
                recorder_play_button.click();
            });

            //set the dialog popup function to show after the audio model player has ended
            var endedfunction = function () {
                //prepare next button
                var nexttext = M.util.get_string('recui_next', 'mod_fluencybuilder');
                var buttons = {};
                buttons[nexttext] = function () {
                    $(this).dialog("close");
                    for (var i = 1; i < ip.itemcount + 1; i++) {
                        if (ip.currentitem == ip.itemcount) {
                            window.location.replace(M.cfg.wwwroot + '/mod/fluencybuilder/view.php?id=' + ip.cmid);
                        }
                        if (ip.currentitem + 1 == i) {
                            $('#' + 'mod_fluencybuilder_dplaceholder_' + i).show();
                        } else {
                            $('#' + 'mod_fluencybuilder_dplaceholder_' + i).hide();
                        }
                    }
                }

                //prepare and show dialog
                ip.controls.the_dialog.dialog({
                    dialogClass: 'mod_fluencybuilder_no-close',
                    resizable: false,
                    height: "auto",
                    width: 400,
                    modal: true,
                    buttons: buttons
                });
            };

            //when the mode player finishes show our dialog
            //we need to do some dumb settimeout here because of javascript load race condtions YUKKY
            var model_player = $('#' + ip.holderid + '  .poodll_modelplayer_fluencybuilder');
            if (model_player.length < 1) {
                var interval_handle = setInterval(function(){
                    var model_player = $('#' + ip.holderid + '  .poodll_modelplayer_fluencybuilder');
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

            var params = "itemid=" + that.itemid + "&cmid=" + that.cmid + "&eval=" + evaluation + '&attemptid=' + attemptid;
            xhr.open("POST",M.cfg.wwwroot + '/mod/fluencybuilder/jsonresults.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.send(params);
        }
    };//end of returned object
});//total end
