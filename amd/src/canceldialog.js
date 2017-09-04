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

            var thecontrols = '<div class="hide">';
            thecontrols += '<div class="mod_fluencybuilder_cancelbox">';
            thecontrols +=  'banana wa wa';//M.util.get_string('recui_play', 'mod_fluencybuilder') ;
            thecontrols += '</div>';//end of dialog div
            thecontrols += '</div>';//end of hide div
            thecontrols +=  '<button type="button" class="mod_fluencybuilder_dbutton mod_fluencybuilder_cancelbutton">' + 'cancel' + '</button>';
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

                ip.controls.the_dialog.dialog({
                    resizable: false,
                    height: "auto",
                    width: 400,
                    modal: true,
                    buttons: {
                        "Really Cancel?": function() {
                            $( this ).dialog( "close" );
                        },
                        Cancel: function() {
                            $( this ).dialog( "close" );
                        }
                    }
                });

            });


        }
    };//end of returned object
});//total end
