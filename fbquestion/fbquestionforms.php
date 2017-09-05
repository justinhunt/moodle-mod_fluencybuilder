<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Forms for fluencybuilder Activity
 *
 * @package    mod_fluencybuilder
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/fluencybuilder/lib.php');

/**
 * Abstract class that item type's inherit from.
 *
 * This is the abstract class that add item type forms must extend.
 *
 * @abstract
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class fluencybuilder_add_item_form_base extends moodleform {

    /**
     * This is used to identify this itemtype.
     * @var string
     */
    public $type;

    /**
     * The simple string that describes the item type e.g. audioitem, textitem
     * @var string
     */
    public $typestring;

	
    /**
     * An array of options used in the htmleditor
     * @var array
     */
    protected $editoroptions = array();

    /**
     * An array of options used in the htmleditor
     * @var array
     */
    protected $timetarget = 10;

	/**
     * An array of options used in the filemanager
     * @var array
     */
    protected $filemanageroptions = array();
	
	
    /**
     * True if this is a standard item of false if it does something special.
     * items are standard items
     * @var bool
     */
    protected $standard = true;

    /**
     * Each item type can and should override this to add any custom elements to
     * the basic form that they want
     */
    public function custom_definition() {}

    /**
     * Used to determine if this is a standard item or a special item
     * @return bool
     */
    public final function is_standard() {
        return (bool)$this->standard;
    }

    /**
     * Add the required basic elements to the form.
     *
     * This method adds the basic elements to the form including title and contents
     * and then calls custom_definition();
     */
    public final function definition() {
        $mform = $this->_form;
        $this->editoroptions = $this->_customdata['editoroptions'];
		$this->filemanageroptions = $this->_customdata['filemanageroptions'];
        $this->timetarget = $this->_customdata['timetarget'];

	
        $mform->addElement('header', 'typeheading', get_string('createaitem', 'fluencybuilder', get_string($this->typestring, 'fluencybuilder')));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        if ($this->standard === true) {
            $mform->addElement('hidden', 'type');
            $mform->setType('type', PARAM_INT);
			
			$mform->addElement('hidden', 'itemorder');
            $mform->setType('itemorder', PARAM_INT);

            $mform->addElement('text', 'name', get_string('itemtitle', 'fluencybuilder'), array('size'=>70));
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', get_string('required'), 'required', null, 'client');

            $mform->addElement('editor', MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION . '_editor', get_string('itemcontents', 'fluencybuilder'), array('rows'=>'4', 'columns'=>'80'), $this->editoroptions);
            $mform->setType(MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION . '_editor', PARAM_RAW);
            $mform->addRule(MOD_FLUENCYBUILDER_FBQUESTION_TEXTQUESTION . '_editor', get_string('required'), 'required', null, 'client');
        }
		//visibility
		$mform->addElement('selectyesno', 'visible', get_string('visible'));

		//time target
		$duration_options = array('defaultunit'=>1,'optional'=>0);
		$mform->addElement('duration', 'timetarget', get_string('timetarget','fluencybuilder'),$duration_options);
		$mform->setDefault('timetarget',$this->timetarget);

        $this->custom_definition();
		
		

		//add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('saveitem', 'fluencybuilder'));

    }

    protected final function add_audio_upload($name, $count=-1, $label = null, $required = false) {
		if($count>-1){
			$name = $name . $count ;
		}
		
		$this->_form->addElement('filemanager',
                           $name,
                           $label,
                           null,
						   $this->filemanageroptions
                           );
		
	}

	protected final function add_audio_prompt_upload($label = null, $required = false) {
		return $this->add_audio_upload(MOD_FLUENCYBUILDER_FBQUESTION_AUDIOPROMPT,-1,$label,$required);
	}

    protected final function add_audio_model_upload($label = null, $required = false) {
        return $this->add_audio_upload(MOD_FLUENCYBUILDER_FBQUESTION_AUDIOMODEL,-1,$label,$required);
    }

	protected final function add_picture_upload($name, $count=-1, $label = null, $required = false) {
		if($count>-1){
			$name = $name . $count ;
		}
		
		$this->_form->addElement('filemanager',
                           $name,
                           $label,
                           null,
						   $this->filemanageroptions
                           );
		
	}

	protected final function add_picture_item_upload($label = null, $required = false) {
		return $this->add_picture_upload(MOD_FLUENCYBUILDER_FBQUESTION_PICTUREPROMPT,-1,$label,$required);
	}


    /**
     * Convenience function: Adds an response editor
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @return void
     */
    protected final function add_response($count, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('response', 'fluencybuilder');
        }
        $this->_form->addElement('editor', 'response_editor['.$count.']', $label, array('rows'=>'4', 'columns'=>'80'), array('noclean'=>true));
        $this->_form->setDefault('response_editor['.$count.']', array('text'=>'', 'format'=>FORMAT_MOODLE));
        if ($required) {
            $this->_form->addRule('response_editor['.$count.']', get_string('required'), 'required', null, 'client');
        }
    }

    /**
     * A function that gets called upon init of this object by the calling script.
     *
     * This can be used to process an immediate action if required. Currently it
     * is only used in special cases by non-standard item types.
     *
     * @return bool
     */
    public function construction_override($itemid,  $fluencybuilder) {
        return true;
    }
}

//this is the standard form for creating an item with a text only prompt
class fluencybuilder_add_item_form_textprompt extends fluencybuilder_add_item_form_base {

    public $type = 'textchoice';
    public $typestring = 'textchoice';

    public function custom_definition() {

		
		$this->add_audio_prompt_upload(get_string('audioitemfile','fluencybuilder'));
		
    }
}


//this is the standard form for creating an item with an audio only prompt
class fluencybuilder_add_item_form_audioprompt extends fluencybuilder_add_item_form_base {

    public $type = 'audiochoice';
    public $typestring = 'audiochoice';

    public function custom_definition() {
	
		$this->add_audio_prompt_upload(get_string('addaudiopromptfile','fluencybuilder'));

        $this->add_audio_model_upload(get_string('addaudiomodelfile','fluencybuilder'));
    }
}

//this is the standard form for creating an item with a picture prompt
class fluencybuilder_add_item_form_pictureprompt extends fluencybuilder_add_item_form_base {

    public $type = 'picturechoice';
    public $typestring = 'picturechoice';

    public function custom_definition() {
	
		$this->add_picture_item_upload(get_string('pictureitemfile','fluencybuilder'));

    }
}