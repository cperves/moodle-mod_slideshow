<?php
    /// Library of functions and constants for module slideshow
    //
    /**
     * List of features supported in slideshow module
     * @param string $feature FEATURE_xx constant for requested feature
     * @return mixed True if module supports feature, false if not, null if doesn't know
     */
    function slideshow_supports($feature) {
        switch($feature) {
            case FEATURE_GROUPS:                  return false;
            case FEATURE_GROUPINGS:               return false;
            case FEATURE_GROUPMEMBERSONLY:        return false;
            case FEATURE_MOD_INTRO:               return false;
            case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
            case FEATURE_GRADE_HAS_GRADE:         return false;
            case FEATURE_GRADE_OUTCOMES:          return false;
			case FEATURE_BACKUP_MOODLE2:          return true;

            default: return null;
        }
    }
    if (!isset($CFG->slideshow_maxwidth)) {
    // Default maximum size for images (px)
        set_config('slideshow_maxwidth','640');
    }
    if (!isset($CFG->slideshow_maxheight)) {
    // Default maximum size for images (px)
        set_config('slideshow_maxheight','480');
    }
    if (!isset($CFG->slideshow_securepix)) {
    // disable right-click and direct link
        set_config('slideshow_securepix','0');
    }

    function slideshow_add_instance($data, $mform) {
        global $DB;

        $cmid        = $data->coursemodule;
        $draftitemid = $data->location;
        $data->timemodified = time();
        $data->id = $DB->insert_record('slideshow', $data);
        // we need to use context now, so we need to make sure all needed info is already in db
        $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
        $context = get_context_instance(CONTEXT_MODULE, $cmid);

        if ($draftitemid) {
            file_save_draft_area_files($draftitemid, $context->id, 'mod_slideshow', 'content', 0, array('subdirs'=>true));
        }

        return $data->id;
    }

	function slideshow_update_instance($data, $mform) {
        global $CFG, $DB;
        $cmid        = $data->coursemodule;
        $draftitemid = $data->location;

        $data->timemodified = time();
        $data->id = $data->instance;
        $DB->update_record("slideshow", $data);

        $context = get_context_instance(CONTEXT_MODULE, $cmid);
        if ($draftitemid = file_get_submitted_draft_itemid('location')) {
            file_save_draft_area_files($draftitemid, $context->id, 'mod_slideshow', 'content', 0, array('subdirs'=>true));
        }
        return true;
    }

	function slideshow_delete_instance($id) {
	global $DB;
        if (! $slideshow = $DB->get_record("slideshow", array("id" => $id))) {
            return false;
        }
        $result = true;
        # Delete any dependent records here #
        $module_id = $DB->get_record("modules",array("name" => "slideshow"));
        $instance_id = $DB->get_record("course_modules",array("instance" => $id, "module" => $module_id->id));
        if (! $DB->delete_records("slideshow_captions", array("slideshow" => $instance_id->id))) {
            $result = false;
        } else {
            if (! $DB->delete_records("slideshow", array("id" => $slideshow->id))) {
                $result = false;
            }
        }
        return $result;
    }

    function slideshow_user_outline($course, $user, $mod, $slideshow) {
    /// Return a small object with summary information about what a
    /// user has done with a given particular instance of this module
    /// Used for user activity reports.
    /// $return->time = the time they did it
    /// $return->info = a short text description
        return $return;
    }
    function slideshow_user_complete($course, $user, $mod, $slideshow) {
    /// Print a detailed representation of what a  user has done with
    /// a given particular instance of this module, for user activity reports.
        return true;
    }
    function slideshow_print_recent_activity($course, $isteacher, $timestart) {
    /// Given a course and a time, this module should find recent activity
    /// that has occurred in slideshow activities and print it out.
    /// Return true if there was output, or false is there was none.
        return false;  //  True if anything was printed, otherwise false
    }
    function slideshow_cron () {
    /// Function to be run periodically according to the moodle cron
    /// This function searches for things that need to be done, such
    /// as sending out mail, toggling flags etc ...
        return true;
    }
    function slideshow_grades($slideshowid) {
    /// Must return an array of grades for a given instance of this module,
    /// indexed by user.  It also returns a maximum allowed grade.
    ///
    ///    $return->grades = array of grades;
    ///    $return->maxgrade = maximum allowed grade;
    ///
    ///    return $return;
       return NULL;
    }
    function slideshow_get_participants($slideshowid) {
    //Must return an array of user records (all data) who are participants
    //for a given instance of slideshow. Must include every user involved
    //in the instance, independient of his role (student, teacher, admin...)
    //See other modules as example.
        return false;
    }
    function slideshow_scale_used ($slideshowid,$scaleid) {
    //This function returns if a scale is being used by one slideshow
    //it it has support for grading and scales. Commented code should be
    //modified if necessary. See forum, glossary or journal modules
    //as reference.
        $return = false;
        return $return;
    }
    //////////////////////////////////////////////////////////////////////////////////////
    /// Any other slideshow functions go here.  Each of them must have a name that
    /// starts with slideshow_
    
    function slideshow_display_thumbs($filearray){
        global $baseurl,$slideshow_thumbdir,$showdir,$cm,$img_num;
        $this_img = 0;
        $thumb_html_arr = array();
        foreach ($filearray as $filename) {
            if ($this_img == $img_num){
                $bstyle = 'border:3px solid #369';
            } else {
                $bstyle = 'border:2px solid white';
            }
            echo "<a href=\"?id=".($cm->id).'&img_num='.$this_img.'">'; 
            echo '<img src="'.$baseurl.'thumb_'.$filename.'" alt="'.$filename.'" title="'.$filename.'" style="'.$bstyle.'">';
            echo '</a> ';
            $this_img++;
        }
    }
    
    function slideshow_filetidy ($filename){
        return substr($filename, 0, -strlen(strrchr($filename, '.')));
        //return $filename;
    }
    
    function slideshow_caption_array($id,$image) {
        global $DB;
        $captions = array();
        if($caption = $DB->get_record_select('slideshow_captions', 'slideshow = '. $id . ' AND image = "'.$image.'"')) {
            $captions['image'] = $image;
            $captions['title'] = $caption->title;
            $captions['caption'] = $caption->caption;
        } else {
            $captions['image'] = $image;
            $captions['title'] = '';
            $captions['caption'] = '';
		}
        return ($captions);
    }
    
    function slideshow_write_captions($captions,$slideshow){
	global $DB;
        $DB->delete_records('slideshow_captions', array('slideshow' => $slideshow->id));
        for ($i=1;$i<$captions->imagenum;$i++) {
            $newcaption->slideshow = $slideshow->id;
            $newcaption->image = $captions->{"image".$i};
            $newcaption->title = $captions->{"title".$i};
			if ($slideshow->htmlcaptions) {
				$newcaption->caption = $captions->{"caption".$i}['text'];
			} else {
				$newcaption->caption = $captions->{"caption".$i};
			}
            if (!$newcaption->id = $DB->insert_record('slideshow_captions', $newcaption)) {
                print_error('Could not insert caption');
            }
        }
    }

    function slideshow_secure_script ($securitylevel){
        if ($securitylevel){
            echo'<script language=JavaScript>
                <!--
                var message="";
                function clickIE() {if (document.all) {(message);return false;}}
                function clickNS(e) {if 
                (document.layers||(document.getElementById&&!document.all)) {
                if (e.which==2||e.which==3) {(message);return false;}}}
                if (document.layers) 
                {document.captureEvents(Event.MOUSEDOWN);document.onmousedown=clickNS;}
                else{document.onmouseup=clickNS;document.oncontextmenu=clickIE;}
                document.oncontextmenu=new Function("return false")
                --> 
                </script>';
        } 
    }
	// Called from $CFG->wwwroot/pluginfile.php
	// See also test version (mod/slideshow/pluginfile.php) if problems here :-(
	function slideshow_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
		$fs = get_file_storage();
		$relativepath = implode('/', $args);
		$fullpath = "/$context->id/mod_slideshow/$filearea/$relativepath";
		if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
			send_file_not_found();
		}
		send_stored_file($file,86400,0,false,$file->get_filename(),false);
		die;
	}
?>