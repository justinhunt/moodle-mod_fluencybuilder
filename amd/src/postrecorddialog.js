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

            var thecontrols = '<div class="hide">';
            thecontrols += '<div class="mod_fluencybuilder_dialogcontentbox">';
            thecontrols +=  '<button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_me_play">' + 'me play' + '</button>';
            thecontrols += '<button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_me_ok" disabled>' + 'me ok' + '</button>';
            thecontrols += ' <button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_me_ng" disabled>' + 'me ng' + '</button>';
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

        register_events: function(){
            var ip =this.instanceprops;

            //set the submission player src to the ot\rigin
            ip.controls.me_play.click(function() {
                var recorder_play_button = $('#' + ip.holderid + '  .poodll_play-recording_fluencybuilder');
                recorder_play_button.click();
            });

            //when the mode player finishes show our dialog
            var model_player = $('#' + ip.holderid + '  .poodll_modelplayer_fluencybuilder');
            model_player.on('ended',function(){
                    ip.controls.the_dialog.dialog({
                        resizable: false,
                        height: "auto",
                        width: 400,
                        modal: true,
                        buttons: {
                            "Next": function() {
                                $( this ).dialog( "close" );
                                for(var i=1; i < ip.itemcount+1;i++){
                                    if(ip.currentitem == ip.itemcount){
                                        window.location.replace(M.cfg.wwwroot + '/mod/fluencybuilder/view.php?id=' + ip.cmid);
                                    }
                                    if(ip.currentitem+1==i){
                                        $('#' + 'mod_fluencybuilder_dplaceholder_' + i).show();
                                    }else{
                                        $('#' + 'mod_fluencybuilder_dplaceholder_' + i).hide();
                                    }
                                }
                            },
                            Cancel: function() {
                                $( this ).dialog( "close" );
                            }
                        }
                    });
            });
        },


        should_be_checked: function(filename){
            //check this is an unconverted recording that we need to track
            //log.debug('mediaduration: ' + this.mediaduration);
            //log.debug('placeholderduration: ' + this.placeholderduration);

            //if any of these numbers is not numeric we kill it
            if(!$.isNumeric(this.placeholderduration)){return false;}
            if(!$.isNumeric(this.mediaduration)){return false;}
            //if the two numbers are equivalent to one decimal place we credit it
            //firefox calcs mp3 size diff to chrome, but they seem same to 1 place ... now anyway
            if( parseFloat(this.mediaduration).toFixed(1) !=  parseFloat(this.placeholderduration).toFixed(1)){
                return false;
            }
            //this is a bogus check.
            //later we only want to check filenames that look like poodll recorded ones
            if(filename==''){
                return false;
            }
            return true;
        },

        check_updates: function(filename,checktype){
            //checktype:firstpass - if have a task then we keep checking till there is no task
            //then we know its finished. Those checks are the 'secondpass'

            //check this is a recording that we need to track
            if(checktype=='firstpass' && !this.should_be_checked(filename)){
                return;
            }

            //set up our ajax request
            var xhr = new XMLHttpRequest();
            var that = this;
            
            //set up our handler for the response
            xhr.onreadystatechange = function(e){
                if(this.readyState===4){
                    if(xhr.status==200){
                        log.debug('ok we got a mediarefresh response');
                        //get a yes or forgetit or tryagain
                        var payload = xhr.responseText;
                        var payloadobject = JSON.parse(payload);
                        if(payloadobject){
                            switch(payloadobject.code) {

                                case 'mediaready':
                                    that.alertconverted();
                                    break;
                                case 'stillwaiting':
                                        setTimeout(function(){that.check_updates(filename,'secondpass')}, 15000);
                                    break;
                                case 'notask':
                                    if(checktype=='secondpass'){
                                        that.alertconverted();
                                    }
                                    break;
                                case 'notloggedin':
                                default:
                                    //just stop trying in this case
                                    //the task is long ago processed or its not a rec. or something
                            }
                        }
                     }else{
                        log.debug('Not 200 response:' + xhr.status);
                    }
                }
            };

            //log.debug(params);
            var params = "filename=" + filename;
            xhr.open("POST",M.cfg.wwwroot + '/filter/poodll/ajaxmediaquery.php', true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader("Cache-Control", "no-cache");
            xhr.send(params);
        }
    };//end of returned object
});//total end