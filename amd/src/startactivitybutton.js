/* jshint ignore:start */
define(['jquery','jqueryui', 'core/log','filter_poodll/utils_amd'], function($, jqui, log, utils) {

    "use strict"; // jshint ;_;

    log.debug('Start activity button: initialising');

    return {

        buttonid: null,
        instanceprops: null,


        //for making multiple instances
        clone: function(){
            return $.extend(true,{},this);
        },

        //pass in config, the jquery video/audio object, and a function to be called when conversion has finshed
        init: function(props){
            this.instanceprops=props;
            this.buttonid=props.startbuttonid;
            this.register_events();

        },

        register_events: function() {
            var ip = this.instanceprops;
            var that = this;
            var startbutton = $('#' + this.buttonid);
            var firstplayercontainer = $('#' + 'mod_fluencybuilder_dplaceholder_1');
            var resource_play_button= $('#' + 'mod_fluencybuilder_dplaceholder_1 .poodll_play-resource_fluencybuilder');

            startbutton.click(function (e) {
                e.preventDefault();
                resource_play_button.click();
                firstplayercontainer.show();
                startbutton.hide();
            });

        }
   };//end of returned object
});//total end
