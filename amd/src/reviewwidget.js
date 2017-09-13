/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','filter_poodll/utils_amd'], function($, jqui, log, utils) {

    "use strict"; // jshint ;_;

    log.debug('Post Record Review Widget: initialising');

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
            dd.instanceprops.itemholderid = 'mod_fluencybuilder_dplaceholder_' + props.currentitem;
            dd.instanceprops.holderid = 'mod_fluencybuilder_rwplaceholder_' + props.currentitem;
            dd.instanceprops.recorderid = 'mod_fluencybuilder_rwplaceholder_' + props.currentitem;
            dd.insert_html();
            dd.register_events();

        },

        insert_html: function(){

            var ip =this.instanceprops;
            var element = $('#' + ip.holderid);
            if (element.length ==0){return;}
            var title = M.util.get_string('recui_howwasit', 'mod_fluencybuilder');
            var thecontrols = '<div class="mod_fluencybuilder_reviewwidgetbox" title="' + title + '">';
            thecontrols +=  '<button type="button" class="mod_fluencybuilder_rwbutton mod_fluencybuilder_question_play"><i class="fa fa-play" aria-hidden="true"></i></button>';
            thecontrols +=  '<button type="button" class="mod_fluencybuilder_rwbutton mod_fluencybuilder_model_play"><i class="fa fa-play" aria-hidden="true"></i></button>';
            thecontrols +=  '<button type="button" class="mod_fluencybuilder_rwbutton mod_fluencybuilder_me_play"><i class="fa fa-play" aria-hidden="true"></i></button>';
            thecontrols += '<button type="button" class="mod_fluencybuilder_rwbutton mod_fluencybuilder_me_ok"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i></button>';
            thecontrols += ' <button type="button" class="mod_fluencybuilder_rwbutton mod_fluencybuilder_me_ng"><i class="fa fa-thumbs-o-down" aria-hidden="true"></i></button>';
            thecontrols += ' <span class="mod_fluencybuilder_rw_comment">blablah</span>';
            thecontrols += '</div>';//end of widgetbox div

            $(element).prepend(thecontrols);

            var controls ={
                the_box:  $('#' + ip.holderid + '  .mod_fluencybuilder_reviewwidgetbox'),
                question_play: $('#' + ip.holderid + '  .mod_fluencybuilder_question_play'),
                model_play: $('#' + ip.holderid + '  .mod_fluencybuilder_model_play'),
                me_play: $('#' + ip.holderid + '  .mod_fluencybuilder_me_play'),
                me_ok: $('#' + ip.holderid + '  .mod_fluencybuilder_me_ok'),
                me_ng: $('#' + ip.holderid + '  .mod_fluencybuilder_me_ng')
            };
            ip.controls = controls;
        },

        register_events: function() {
            var ip = this.instanceprops;

            //play back from the original recorder using our button
            ip.controls.me_play.click(function () {
                var recorder_play_button = $('#' + ip.itemholderid + '  .poodll_play-recording_fluencybuilder');
                recorder_play_button.click();
            });

            // Here we are connecting our buttons to the audio players already on the page
            // we need to do some dumb settimeout here because of javascript load race condtions YUKKY
            //another way to do this would be to use the model and question audio urls which we know and have a single
            //player for all the audio on the review page, just swapping the src.
            var model_player = $('#' + ip.holderid + '  .poodll_modelplayer_fluencybuilder');
            var question_player = $('#' + ip.itemholderid + '  .poodll_itemplayer_fluencybuilder');
            if (model_player.length < 1) {
                var interval_handle = setInterval(function(){
                    model_player = $('#' + ip.itemholderid + '  .poodll_modelplayer_fluencybuilder');
                    question_player = $('#' + ip.itemholderid + '  .poodll_itemplayer_fluencybuilder');
                    if(model_player.length >0){
                        clearInterval(interval_handle);
                        //add event handling here
                        ip.controls.model_play.click(function () {model_player.click();});
                        ip.controls.question_play.click(function () {question_player.click();});
                    }
                }, 500);
            }else{
                //add event handling here
                ip.controls.model_play.click(function () {model_player.click();});
                ip.controls.question_play.click(function () {question_player.click();});
            }
        }
    };//end of returned object
});//total end
