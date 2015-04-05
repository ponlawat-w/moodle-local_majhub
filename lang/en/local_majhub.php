<?php // $Id: local_majhub.php 230 2013-03-01 08:48:24Z malu $

$string['pluginname'] = 'MAJ Community Hub';

$string['leaderboard'] = 'Leader Board';
$string['searchcriteria'] = 'Search Criteria';
$string['searchresults'] = 'Search Results';

$string['mostdownloaded'] = 'Most Downloaded';
$string['mostreviewed'] = 'Most Reviewed';
$string['toprated'] = 'Top Rated';
$string['latest'] = 'Latest';

$string['optionalfields'] = 'Optional fields';
$string['keywords'] = 'Any Keywords';
$string['title'] = 'Title';
$string['contributor'] = 'Contributor';
$string['uploadedat'] = 'Uploaded';
$string['filesize'] = 'File size';
$string['version'] = 'Version';
$string['demourl'] = 'Demo site';

$string['preview'] = 'Preview';
$string['download'] = 'Download';
$string['demosite'] = 'Demo site';

$string['sortby:newest'] = 'Newest';
$string['sortby:oldest'] = 'Oldest';
$string['sortby:title'] = 'Title';
$string['sortby:contributor'] = 'Contributor';
$string['sortby:rating'] = 'Rating';

$string['coursewaresperpage'] = 'Coursewares per page';
$string['searchforcoursewares'] = 'Search for coursewares';
$string['showoptionalcriteria'] = 'Show optional criteria';
$string['hideoptionalcriteria'] = 'Hide optional criteria';

$string['previewthiscourseware'] = 'Preview this courseware';
$string['downloadthiscourseware'] = 'Download this courseware';
$string['visitauthorsdemosite'] = 'Visit author\'s demo site';
$string['editcoursewaremetadata'] = 'Edit courseware metadata';
$string['previewcourseisnotready'] = 'Generating Preview Course may take more then 10 minutes. Please visit later.';

$string['noresult'] = 'No result';

$string['costspoints'] = 'Costs {$a} points';
$string['youhavepoints'] = 'You have {$a} points';
$string['howtogetpoints'] = 'How to get points';
$string['howtogetpoints.desc'] = '<ul>
<li>Upload more courses<br />
    + {$a->pointsforuploading} per course upload</li>
<li>Review more courses<br />
    + {$a->pointsforreviewing} per review</li>
<li>Request Bonus<br />
    (= Send email to admin)</li>
</ul>';

$string['review'] = 'Review';
$string['rating'] = 'Rating';
$string['moderator'] = 'Moderator';
$string['overallrating'] = 'Overall';
$string['latestreviews'] = 'Latest {$a->latest} of {$a->total} reviews';
$string['reviewinletters'] = 'Review in {$a} letters or more to get points';

$string['give'] = 'Give';

$string['settings/frontpage'] = 'Front page settings';
$string['settings/metafields'] = 'Meta field definitions';
$string['settings/pointsystem'] = 'Point system settings';
$string['settings/restore'] = 'Course restore settings';

$string['coursewaresperpageoptions'] = 'Choices for coursewares per page';
$string['coursewaresperpagedefault'] = 'Default coursewares per page';

$string['pointacquisitions'] = 'Point acquisitions';
$string['pointsforregistration'] = 'Registration bonus points';
$string['pointsforuploading'] = 'Points for uploading';
$string['pointsforreviewing'] = 'Points for reviewing';
$string['pointsforquality'] = 'Quality bonus points';
$string['pointsforpopularity'] = 'Popularity bonus points';
$string['countforpopularity'] = 'Number of downloads to get popularity bonus';
$string['lengthforreviewing'] = 'Minimum comment length to get reviewing points';

$string['pointconsumptions'] = 'Point consumptions';
$string['pointsfordownloading'] = 'Downloading cost';

$string['fieldtype'] = 'Type';
$string['fieldtype:text'] = 'Text';
$string['fieldtype:radio'] = 'Radio button';
$string['fieldtype:check'] = 'Checkbox';
$string['attributes'] = 'Attributes';
$string['attributes:required'] = 'Required';
$string['attributes:optional'] = 'Optional';
$string['options'] = 'Options';

$string['confirm:payfordownload'] = 'Do you want to pay {$a} points for downloading this courseware?';

$string['confirm:metafield:delete'] = 'Are you sure you really want to delete this meta field?';
$string['confirm:metafield:delete:warning'] = 'WARNING !!
The associated meta data which had been entered by users will be unlinked permanently.
They will never be recovered even if you redefine a same-name meta field.';

$string['error:accessdenied'] = 'Access denied
(If you have multiple accounts on this Hub server, log in as the account you entered in your Hub Client.)';
$string['error:missingcourseware'] = 'Courseware #{$a} is missing';
$string['error:youdonthaveenoughpoints'] = 'Sorry, you don\'t have enough points.';
$string['error:coursecannotbepreviewed'] = 'This course is not available for preview.';
$string['error:metafield:emptyname'] = 'Name cannot be empty';
$stirng['error:metafield:emptyoptions'] = 'Options cannot be empty if type is not Text';
$string['error:metafield:duplicatename'] = 'Name is already in use';
$string['error:metafield:duplicateoption'] = 'Duplicate option cannot be set';

$string['maxrestorablebackupsize'] ='Maximum backup file size for restoration';
$string['minrestorableversion'] ='Minimum required Moodle version';
$string['moodleversion'] ='Moodle version';

$string['potentialcontributors'] ='Potential Contributors';
$string['selectnewcontributor'] ='Select from the list of users, to change the course contributor';

$string['site'] ='Original Site';
$string['sitecourseid'] ='Original Course ID';
$string['resync'] ='Resync (CAREFULLY!!)';

$string['settings/assign'] ='Assign Points';
$string['settings/reports'] ='Reports';
$string['givepoints'] ='Give Points';
$string['removepoints'] ='Remove Points';
$string['allocatepoints'] ='Points';
$string['reason'] ='Reason';
$string['giveresult'] ='Assigned {$a->points} points each to {$a->users} users.';
$string['removeresult'] ='Removed {$a->points} points each from {$a->users} users.';
$string['profileresult'] ='Set selected profile field to {$a->updatedvalue} for {$a->users} users.'; 
$string['setprofilefield'] ='Set user profile field'; 
$string['profilefield'] ='User profile field'; 
$string['profilefieldvalue'] ='new value'; 
$string['pointsheader'] ='Points Actions'; 
$string['profileheader'] ='Profile Actions';

$string['settings/managecourses'] ='Manage Courses';
$string['originalversion'] ='Original Version';
//reports
$string['returntoreports'] ='Return to Reports';
$string['allusers'] ='All Users';
$string['allusersreport'] ='All Users Report';
$string['nodataavailable'] ='No Data Available';
$string['reports'] ='MAJHub Reports';
$string['reporttitle'] ='Report Title {$a}';
$string['exportcsv'] ='Export to CSV';
$string['exportexcel'] ='Export to Excel(csv)';
$string['exportpdf'] ='Export to PDF';
$string['selectanother'] ='Back to Course';
$string['fullname'] ='Full Name';
$string['username'] ='Username';
$string['email'] ='Email';
$string['lastaccess'] ='Last Access';
$string['points'] ='Points';
$string['pointsreport'] ='Points Report';
$string['neveraccessed'] ='Never';
$string['registration'] ='Regn';
$string['upload'] ='Upload';
$string['review'] ='Rev.';
$string['popularity'] ='Pop.';
$string['quality'] ='Qual.';
$string['user'] ='User';
$string['download'] ='Downld';
$string['total'] ='Total';
$string['firstname'] ='firstname';
$string['lastname'] ='lastname';
$string['idnumber'] ='idnumber';
$string['language'] ='language';
$string['majmember'] ='majmember';
$string['mailchimp'] ='mailchimp';
$string['mailchimpreport'] ='Mailchimp Import Report';

$string['fullname'] ='Full Name';
$string['id'] ='ID';
$string['startdate'] ='Restore Start';
$string['uploaddate'] ='Upload Date';
$string['action'] ='Action';
$string['unrestored'] ='Unrestored';
$string['resetrestore'] ='Reset Restore';
$string['unrestoredreport'] ='Unrestored Report';
$string['neverstarted'] ='never';
$string['coursewareupdated'] ='Courseware Updated Successfully';