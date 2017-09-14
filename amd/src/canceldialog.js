/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','filter_poodll/utils_amd'], function($, jqui, log, utils) {

    "use strict"; // jshint ;_;

    log.debug('Post Record Dialog: initialising');

    return {

        instanceprops: null,


        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){

            this.instanceprops = props;
            this.insert_html();
            this.register_events();

        },

        insert_html: function(){

            var ip =this.instanceprops;
            var element = $('#' + ip.holderid);
            if (element.length ==0){return;}
            var title = M.util.get_string('cancelui_cancelactivity', 'mod_fluencybuilder');

            var thecontrols = '<div class="hide">';
            thecontrols += '<div class="mod_fluencybuilder_cancelbox" title="' + title + '">';
            thecontrols +=  M.util.get_string('cancelui_reallycancel', 'mod_fluencybuilder') ;
            thecontrols += '</div>';//end of dialog div
            thecontrols += '</div>';//end of hide div
            $(element).prepend(thecontrols);

            var controls ={
                the_dialog:  $('#' + ip.holderid + '  .mod_fluencybuilder_cancelbox'),
                cancelbutton:  $('#' + ip.holderid + '  .mod_fluencybuilder_cancelbutton'),
            };
            ip.controls = controls;
        },

        register_events: function(){
            var ip =this.instanceprops;


            ip.controls.cancelbutton.click(function(){
                var quittext = M.util.get_string('cancelui_iwantquit', 'mod_fluencybuilder');
                var noquittext = M.util.get_string('cancelui_inoquit', 'mod_fluencybuilder');
                var buttons ={};
                buttons[quittext] =  function() {
                    window.location.replace(M.cfg.wwwroot + '/mod/fluencybuilder/view.php?id=' + ip.cmid);
                };
                buttons[noquittext] =  function() { $( this ).dialog( "close" );};

                ip.controls.the_dialog.dialog({
                    resizable: false,
                    height: "auto",
                    width: 400,
                    modal: true,
                    buttons: buttons
                });

            });


        }
    };//end of returned object
});//total end
