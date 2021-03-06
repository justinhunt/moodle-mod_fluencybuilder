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
 * Forms for CRUDMODULE Activity
 *
 * @package    mod_CRUDMODULE
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/CRUDMODULE/lib.php');

/**
 * Abstract class that item type's inherit from.
 *
 * This is the abstract class that add item type forms must extend.
 *
 * @abstract
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class CRUDMODULE_add_item_form_base extends moodleform {

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
     * An array of options used in the filemanager
     * @var array
     */
    protected $audiofilemanageroptions = array();
	
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
		$this->audiofilemanageroptions = $this->_customdata['audiofilemanageroptions'];
	
        $mform->addElement('header', 'typeheading', get_string('createaitem', 'CRUDMODULE', get_string($this->typestring, 'CRUDMODULE')));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);

        if ($this->standard === true) {
            $mform->addElement('hidden', 'type');
            $mform->setType('type', PARAM_INT);
			
			$mform->addElement('hidden', 'order');
            $mform->setType('order', PARAM_INT);

            $mform->addElement('text', 'name', get_string('itemtitle', 'CRUDMODULE'), array('size'=>70));
            $mform->setType('name', PARAM_TEXT);
            $mform->addRule('name', get_string('required'), 'required', null, 'client');

            $mform->addElement('editor', MOD_CRUDMODULE_CRUD_TEXTQUESTION . '_editor', get_string('itemcontents', 'CRUDMODULE'), null, $this->editoroptions);
            $mform->setType(MOD_CRUDMODULE_CRUD_TEXTQUESTION . '_editor', PARAM_RAW);
            $mform->addRule(MOD_CRUDMODULE_CRUD_TEXTQUESTION . '_editor', get_string('required'), 'required', null, 'client');
        }
		$mform->addElement('selectyesno', 'visible', get_string('visible'));
		
        $this->custom_definition();
		
		

		//add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('saveitem', 'CRUDMODULE'));

    }



    /**
     * Convenience function: Adds a score input element
     *
     * @param string $name
     * @param string|null $label
     * @param mixed $value The default value
     */
    protected final function add_score($name, $label=null, $value=null) {
        if ($label === null) {
            $label = get_string("score", "CRUDMODULE");
        }

        if (is_int($name)) {
            $name = "score[$name]";
        }
        $this->_form->addElement('text', $name, $label, array('size'=>5));
        $this->_form->setType($name, PARAM_INT);
        if ($value !== null) {
            $this->_form->setDefault($name, $value);
        }
        $this->_form->addHelpButton($name, 'score', 'CRUDMODULE');

        // Score is only used for custom scoring. Disable the element when not in use to stop some confusion.
        if (!$this->_customdata['CRUDMODULE']->custom) {
            $this->_form->freeze($name);
        }
    }
	
    protected final function add_audio_upload($name, $count=-1, $label = null, $required = false) {
		if($count>-1){
			$name = $name . $count ;
		}
		
		$this->_form->addElement('filemanager',
                           $name,
                           $label,
                           null,
						   $this->audiofilemanageroptions
                           );
		
	}

	protected final function add_audio_item_upload($label = null, $required = false) {
		return $this->add_audio_upload(MOD_CRUDMODULE_CRUD_AUDIOQUESTION,-1,$label,$required);
	}
	protected final function add_audio_answer_upload($count,$label = null, $required = false) {
		return $this->add_audio_upload(MOD_CRUDMODULE_CRUD_AUDIOANSWER,$count,$label,$required);
	}	
	
	
    /**
     * Convenience function: Adds an answer editor
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @param bool $required
     * @return void
     */
    protected final function add_answer($count, $label = null, $required = false) {
        if ($label === null) {
            $label = get_string('answer', 'CRUDMODULE');
        }
        $this->_form->addElement('editor', MOD_CRUDMODULE_CRUD_TEXTANSWER . $count . '_editor', $label, array('rows'=>'4', 'columns'=>'80'), array('noclean'=>true));
        $this->_form->setDefault(MOD_CRUDMODULE_CRUD_TEXTANSWER . $count . '_editor', array('text'=>'', 'format'=>FORMAT_MOODLE));
        if ($required) {
            $this->_form->addRule(MOD_CRUDMODULE_CRUD_TEXTANSWER . $count . '_editor', get_string('required'), 'required', null, 'client');
        }
    }

	  /**
     * Convenience function: Adds correct/incorrect attribute
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @return void
     */
    protected final function add_shuffleanswers($label = null) {
        if ($label === null) {
            $label = get_string('shuffleanswers', 'CRUDMODULE');
        }
        $this->_form->addElement('selectyesno', MOD_CRUDMODULE_CRUD_SHUFFLEANSWERS, $label);
        $this->_form->setDefault(MOD_CRUDMODULE_CRUD_SHUFFLEANSWERS, 0);
    }
	 
	 /**
     * Convenience function: Adds correct/incorrect attribute
     *
     * @param int $count The count of the element to add
     * @param string $label, null means default
     * @return void
     */
    protected final function add_correctanswer($count, $label = null) {
        if ($label === null) {
            $label = get_string('iscorrectlabel', 'CRUDMODULE');
        }
        $this->_form->addElement('radio', MOD_CRUDMODULE_CRUD_CORRECTANSWER, $label,'',$count);
        $this->_form->setDefault(MOD_CRUDMODULE_CRUD_CORRECTANSWER, 1);
    }
    
   	 /**
     * Convenience function: Adds layout hint. How many answers in a row
     *
     * @param string $label, null means default
     * @return void
     */
    protected final function add_answersinrow( $label = null) {
	
		$this->_form->addElement('hidden', MOD_CRUDMODULE_CRUD_ANSWERSINROW,0);
        $this->_form->setType(MOD_CRUDMODULE_CRUD_ANSWERSINROW, PARAM_INT);
		return;
		/*
        if ($label === null) {
            $label = get_string('answersinrow', 'CRUDMODULE');
        }
        $buttonoptions = array();
        for($i=1;$i<=MOD_CRUDMODULE_CRUD_MAXANSWERS;$i++){
        	$buttonoptions[$i]=$i;
        }
        $this->_form->addElement('select', MOD_CRUDMODULE_CRUD_ANSWERSINROW, $label,$buttonoptions);
        $this->_form->setDefault(MOD_CRUDMODULE_CRUD_ANSWERSINROW, 2);
        $this->_form->setType(MOD_CRUDMODULE_CRUD_ANSWERSINROW, PARAM_INT);
		*/
    }
    
     /**
     * Convenience function: Adds layout hint. Width of a single answer
     *
     * @param string $label, null means default
     * @return void
     */
    protected final function add_answerwidth( $label = null) {
        if ($label === null) {
            $label = get_string('answerwidth', 'CRUDMODULE');
        }
		$buttonoptions = array();
		$buttonoptions['0']=get_string('shorttextanswer','CRUDMODULE');
		$buttonoptions['1']=get_string('mediumtextanswer','CRUDMODULE');
		$buttonoptions['2']=get_string('longtextanswer','CRUDMODULE');
        $this->_form->addElement('select', MOD_CRUDMODULE_CRUD_ANSWERWIDTH, $label,$buttonoptions);
        $this->_form->setDefault(MOD_CRUDMODULE_CRUD_ANSWERWIDTH, 0);
        $this->_form->setType(MOD_CRUDMODULE_CRUD_ANSWERWIDTH, PARAM_INT);
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
            $label = get_string('response', 'CRUDMODULE');
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
    public function construction_override($itemid,  $CRUDMODULE) {
        return true;
    }
}

//this is the standard form for creating a multi choice item
class CRUDMODULE_add_item_form_textchoice extends CRUDMODULE_add_item_form_base {

    public $type = 'textchoice';
    public $typestring = 'textchoice';

    public function custom_definition() {

		
		$this->add_audio_item_upload(get_string('audioitemfile','CRUDMODULE'));
		$this->add_shuffleanswers();
		$this->add_answersinrow();
		$this->add_answerwidth();
		
        for ($i = 1; $i <= MOD_CRUDMODULE_CRUD_MAXANSWERS; $i++) {
            $this->_form->addElement('header', 'answertitle'.$i, get_string('answer').' '. $i);
            $required = $i==1;
            $this->add_answer($i, null, $required);
			$this->add_correctanswer($i);
			$this->_form->setExpanded('answertitle'.$i);

        }
    }
}


//this is the standard form for creating a multi choice item
class CRUDMODULE_add_item_form_audiochoice extends CRUDMODULE_add_item_form_base {

    public $type = 'audiochoice';
    public $typestring = 'audiochoice';

    public function custom_definition() {
	
		$this->add_audio_item_upload(get_string('audioitemfile','CRUDMODULE'));
		$this->add_shuffleanswers();

        for ($i = 1; $i <= MOD_CRUDMODULE_CRUD_MAXANSWERS; $i++) {
            $this->_form->addElement('header', 'answertitle'.$i, get_string('answer').' '. $i);
            $required = $i==1;
            $this->add_audio_answer_upload($i, null, $required);
			$this->add_correctanswer($i);
			$this->_form->setExpanded('answertitle'.$i);
        }
    }
}