Fluency Builder

Notes: 2016/11/14
kanrisha/[literally]-[Latindog]

booted  and ran
created an activity
removed refs to nodejs in fetch session links in renderer

on instance page refs to activitymode/partnermode and session need to be planned next

2017/01/16
removed partnermode from settigns mod_form
left mode in because it seems like something the meaning of which might change but which could be needed
To do this just remove it from the form, and then go to phpmyadmin and remove it there too
In lib.php we simply add and remove without altering formdata

disabled grading
to re-enable
uncomment components in mod_form.php
uncomment lines at top of lib.php
uncomment in update_Grade_item() call in lib.php : fluencybuilder_add_instance AND fluencybuilder_update_instance
uncomment fluencybuilder_update_grades() call in update_Grade_item lib.php : fluencybuilder_update_instance 
comment/remove the return call in lib.php: fluencybuilder_reset_gradebook
uncomment the reset_user_grades in lib.php: fluencybuilder_reset_userdata

removed nodejs logic from locallib.php (for managing server) because we do not need it

Justin Hunt
poodllsupport@gmail.com
