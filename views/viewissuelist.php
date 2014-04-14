<?php

/**
* @package mod-tracker
* @category mod
* @author Clifford Thamm, Valery Fremaux > 1.8
* @date 02/12/2007
*
* Print Bug List
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from view.php in mod/tracker
}

include_once $CFG->libdir.'/tablelib.php';

$STATUSKEYS = array(POSTED => get_string('posted', 'tracker'), 
                    OPEN => get_string('open', 'tracker'), 
                    RESOLVING => get_string('resolving', 'tracker'), 
                    WAITING => get_string('waiting', 'tracker'), 
                    TESTING => get_string('testing', 'tracker'), 
                    VALIDATED => get_string('validated', 'tracker'), 
                    PUBLISHED => get_string('published', 'tracker'), 
                    RESOLVED => get_string('resolved', 'tracker'), 
                    ABANDONNED => get_string('abandonned', 'tracker'),
                    TRANSFERED => get_string('transfered', 'tracker'));

/// get search engine related information
// fields can come from a stored query,or from the current query in the user's client environement cookie
if (!isset($fields)){
    $fields = tracker_extractsearchcookies();
}
if (!empty($fields)){
    $searchqueries = tracker_constructsearchqueries($tracker->id, $fields);
}

$limit = 20;
$page = optional_param('page', 1, PARAM_INT);

if ($page <= 0){
    $page = 1;
}

if (isset($searchqueries)){
    /* SEARCH DEBUG 
    $strsql = str_replace("\n", "<br/>", $searchqueries->count);
    $strsql = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $strsql);
    echo "<div align=\"left\"> <b>count using :</b> ".$strsql." <br/>";
    $strsql = str_replace("\n", "<br/>", $searchqueries->search);
    $strsql = str_replace("\t", "&nbsp;&nbsp;&nbsp;", $strsql);
    echo " <b>search using :</b> ".str_replace("\n", "<br/>", $strsql)." <br/></div>";
    */
    $sql = $searchqueries->search;
    $numrecords = $DB->count_records_sql($searchqueries->count);
} else {
    // check we display only resolved tickets or working
    if ($resolved){
        $resolvedclause = " AND
           (status = ".RESOLVED." OR
           status = ".ABANDONNED.")
        ";
    } else {
        $resolvedclause = " AND
           status <> ".RESOLVED." AND
           status <> ".ABANDONNED."
        ";
    }

    $sql = "
        SELECT 
            i.id, 
            i.summary, 
            i.datereported, 
            i.reportedby, 
			i.urgency,
            i.assignedto, 
            i.status,
            i.resolutionpriority,
            u.firstname firstname, 
            u.lastname lastname,
            COUNT(ic.issueid) watches
        FROM 
            {user} u,
            {tracker_issue} i
        LEFT JOIN
            {tracker_issuecc} ic 
        ON
            ic.issueid = i.id
        WHERE 
            i.reportedby = u.id AND 
            i.trackerid = {$tracker->id}
            $resolvedclause
        GROUP BY 
            i.id, 
            i.summary, 
            i.datereported, 
            i.reportedby, 
			i.urgency,
            i.assignedto, 
            i.status,
            i.resolutionpriority,		
            u.firstname, 
            u.lastname
    ";

    $sqlcount = "
        SELECT 
            COUNT(*)
        FROM 
            {tracker_issue} i, 
            {user} u
        WHERE 
            i.reportedby = u.id AND 
            i.trackerid = {$tracker->id}
            $resolvedclause
    ";
    $numrecords = $DB->count_records_sql($sqlcount);
}



/// display list of issues
?>
<center>
<table border="1" width="100%">
<?php
if (isset($searchqueries)){
?>
    <tr>
        <td colspan="2">
            <?php print_string('searchresults', 'tracker') ?>: <?php echo $numrecords ?> <br/>
        </td>
        <td colspan="2" align="right">
                <a href="view.php?id=<?php p($cm->id) ?>&amp;what=clearsearch"><?php print_string('clearsearch', 'tracker') ?></a>
        </td>
    </tr>
<?php
}
?>      
</table>
</center>
<form name="manageform" action="view.php" method="post">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="what" value="updatelist" />
<?php       

/// define table object
$prioritystr = get_string('priority', 'tracker');
$issuenumberstr = get_string('issuenumber', 'tracker');
$summarystr = get_string('summary', 'tracker');
$datereportedstr = get_string('datereported', 'tracker');
$reportedbystr = get_string('reportedby', 'tracker');
$assignedtostr = get_string('assignedto', 'tracker');
$statusstr = get_string('status', 'tracker');
$urgencystr = get_string('urgency', 'tracker');   // String to represent urgency header - BRAEDEN BODILY
$watchesstr = get_string('watches', 'tracker');
$actionstr = '';
if ($resolved){			// Added column header for urgency when viewing tickets - BRAEDEN BODILY
    if(!empty($tracker->parent)){
        $transferstr = get_string('transfer', 'tracker');
        $tablecolumns = array('id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'urgency', 'watches', 'transfered', 'action');
        $tableheaders = array("<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reportedbystr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$urgencystr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'urgency', 'watches', 'action');
        $tableheaders = array("<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reportedbystr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$urgencystr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    }
} else {
    if(!empty($tracker->parent)){
        $transferstr = get_string('transfer', 'tracker');
        $tablecolumns = array('resolutionpriority', 'id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'urgency', 'watches', 'transfered', 'action');
        $tableheaders = array("<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reportedbystr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$urgencystr</b>", "<b>$watchesstr</b>", "<b>$transferstr</b>", "<b>$actionstr</b>");
    } else {
        $tablecolumns = array('resolutionpriority', 'id', 'summary', 'datereported', 'reportedby', 'assignedto', 'status', 'urgency', 'watches', 'action');
        $tableheaders = array("<b>$prioritystr</b>", "<b>$issuenumberstr</b>", "<b>$summarystr</b>", "<b>$datereportedstr</b>", "<b>$reportedbystr</b>", "<b>$assignedtostr</b>", "<b>$statusstr</b>", "<b>$urgencystr</b>", "<b>$watchesstr</b>", "<b>$actionstr</b>");
    }
}

$table = new flexible_table('mod-tracker-issuelist');
$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);

$table->define_baseurl($CFG->wwwroot.'/mod/tracker/view.php?id='.$cm->id.'&view='.$view.'&screen='.$screen);

$table->sortable(true, 'resolutionpriority', SORT_ASC); //sorted by priority by default
$table->collapsible(true);
$table->initialbars(true);

// allow column hiding
// $table->column_suppress('reportedby');
// $table->column_suppress('watches');

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'issues');
$table->set_attribute('class', 'issuelist');
$table->set_attribute('width', '100%');

$table->column_class('resolutionpriority', 'list_priority');
$table->column_class('id', 'list_issuenumber');
$table->column_class('summary', 'list_summary');
$table->column_class('datereported', 'timelabel');
$table->column_class('reportedby', 'list_reportedby');
$table->column_class('assignedto', 'list_assignedto');
$table->column_class('urgency', 'list_urgency');  // Column to display urgency for each ticket - BRAEDEN BODILY
$table->column_class('watches', 'list_watches');
$table->column_class('status', 'list_status');
$table->column_class('action', 'list_action');
if (!empty($tracker->parent)){
    $table->column_class('transfered', 'list_transfered');
}

$table->setup();


/// get extra query parameters from flexible_table behaviour
$where = $table->get_sql_where();
$sort = $table->get_sql_sort();
$table->pagesize($limit, $numrecords);

if (!empty($sort)){
    $sql .= " ORDER BY $sort";
} else {
    $sql .= " ORDER BY resolutionpriority ASC";
}

/// set list length limits
/*
if ($limit > $numrecords){
    $offset = 0;
} else {
    $offset = $limit * ($page - 1);
}
$sql = $sql . ' LIMIT ' . $limit . ',' . $offset;
*/
//

$issues = $DB->get_records_sql($sql, null, $table->get_page_start(), $table->get_page_size());

$maxpriority = $DB->get_field_select('tracker_issue', 'MAX(resolutionpriority)', " trackerid = $tracker->id GROUP BY trackerid ");

if (!empty($issues)){
    /// product data for table
    foreach ($issues as $issue){
        $issuenumber = "<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}\">{$tracker->ticketprefix}{$issue->id}</a>";
        $summary = "<a href=\"view.php?id={$cm->id}&amp;view=view&amp;screen=viewanissue&amp;issueid={$issue->id}\">".format_string($issue->summary).'</a>';
        $datereported = date('Y/m/d h:i', $issue->datereported);
        $user = $DB->get_record('user', array('id' => $issue->reportedby));
        $reportedby = fullname($user);
		$urgency = $issue->urgency;  //  A function to get urgency out of database - BRAEDEN BODILY
        $user = $DB->get_record('user', array('id' => $issue->assignedto));
        if (has_capability('mod/tracker:manage', $context)){ // managers can assign bugs
            $status = html_writer::select($STATUSKEYS, "status{$issue->id}", $issue->status, array('' => 'choose'), array('onchange' => "document.forms['manageform'].schanged{$issue->id}.value = 1;")). "<input type=\"hidden\" name=\"schanged{$issue->id}\" value=\"0\" />";
            $developers = tracker_getdevelopers($context);
            $developersmenu = array();
            foreach($developers as $developer){
                $developersmenu[$developer->id] = fullname($developer);
            }
            $assignedto = html_writer::select($developersmenu, "assignedto{$issue->id}", $issue->assignedto, array('' => get_string('unassigned', 'tracker')), array('onchange' => "document.forms['manageform'].changed{$issue->id}.value = 1;")) . "<input type=\"hidden\" name=\"changed{$issue->id}\" value=\"0\" />";
        } elseif (has_capability('mod/tracker:resolve', $context)){ // resolvers can give a bug back to managers
            $status = html_writer::select($STATUSKEYS, "status{$issue->id}", $issue->status, array('' => 'choose'), array('onchange' => "document.forms['manageform'].schanged{$issue->id}.value = 1;")) . "<input type=\"hidden\" name=\"schanged{$issue->id}\" value=\"0\" />";
            $managers = tracker_getadministrators($context);
            foreach($managers as $manager){
                $managersmenu[$manager->id] = fullname($manager);
            }
            $managersmenu[$USER->id] = fullname($USER);
            $assignedto = html_writer($developersmenu, "assignedto{$issue->id}", $issue->assignedto, array('' => get_string('unassigned', 'tracker')), array('onchange' => "document.forms['manageform'].changed{$issue->id}.value = 1;")) . "<input type=\"hidden\" name=\"changed{$issue->id}\" value=\"0\" />";
        } else {
            $status = $STATUSKEYS[0 + $issue->status]; 
            $assignedto = fullname($user);
        }
        $status = '<div class="status_'.$STATUSCODES[$issue->status].'" style="width: 110%; height: 105%; text-align:center">'.$status.'</div>';
        $hassolution = $issue->status == RESOLVED && !empty($issue->resolution);
        $solution = ($hassolution) ? "<img src=\"{$CFG->wwwroot}/mod/tracker/pix/solution.gif\" height=\"15\" alt=\"".get_string('hassolution','tracker')."\" />" : '' ;
        $actions = '';
        if (has_capability('mod/tracker:manage', $context) || has_capability('mod/tracker:resolve', $context)){
            $actions = "<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&screen=editanissue\" title=\"".get_string('update')."\" ><img src=\"".$OUTPUT->pix_url('t/edit', 'core')."\" border=\"0\" /></a>";
        }
        if (has_capability('mod/tracker:manage', $context)){
            $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&what=delete\" title=\"".get_string('delete')."\" ><img src=\"".$OUTPUT->pix_url('t/delete', 'core')."\" border=\"0\" /></a>";
        }
        if (!$DB->get_record('tracker_issuecc', array('userid' => $USER->id, 'issueid' => $issue->id))){
	        $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;view=profile&amp;screen={$screen}&amp;issueid={$issue->id}&what=register\" title=\"".get_string('register', 'tracker')."\" ><img src=\"".$OUTPUT->pix_url('register', 'mod_tracker')."\" border=\"0\" /></a>";
	    }
        if (preg_match('/^resolutionpriority/', $sort) && has_capability('mod/tracker:managepriority', $context)){
            if ($issue->resolutionpriority < $maxpriority){
                $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&what=raisetotop\" title=\"".get_string('raisetotop', 'tracker')."\" ><img src=\"".$OUTPUT->pix_url('totop', 'mod_tracker')."\" border=\"0\" /></a>";
                $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&what=raisepriority\" title=\"".get_string('raisepriority', 'tracker')."\" ><img src=\"".$OUTPUT->pix_url('up', 'mod_tracker')."\" border=\"0\" /></a>";
            } else {
                $actions .= "&nbsp;<img src=\"".$OUTPUT->pix_url('up_shadow', 'mod_tracker')."\" border=\"0\" />";
                $actions .= "&nbsp;<img src=\"".$OUTPUT->pix_url('totop_shadow', 'mod_tracker')."\" border=\"0\" />";
            }
            if ($issue->resolutionpriority > 1){
                $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&what=lowerpriority\" title=\"".get_string('lowerpriority', 'tracker')."\" ><img src=\"".$OUTPUT->pix_url('down', 'mod_tracker')."\" border=\"0\" /></a>";
                $actions .= "&nbsp;<a href=\"view.php?id={$cm->id}&amp;issueid={$issue->id}&what=lowertobottom\" title=\"".get_string('lowertobottom', 'tracker')."\" ><img src=\"".$OUTPUT->pix_url('tobottom', 'mod_tracker')."\" border=\"0\" /></a>";
            } else {
                $actions .= "&nbsp;<img src=\"".$OUTPUT->pix_url('down_shadow', 'mod_tracker')."\" border=\"0\" />";
                $actions .= "&nbsp;<img src=\"".$OUTPUT->pix_url('tobottom_shadow', 'mod_tracker')."\" border=\"0\" />";
            }
        }
        if ($resolved){  //  A column to display the urgency
            if (!empty($tracker->parent)) {
                $transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '' ;
                $dataset = array($issuenumber, $summary.' '.$solution, $datereported, $reportedby, $assignedto, $status, $urgency, 0 + $issue->watches, $transfer, $actions);
            } else {
                $dataset = array($issuenumber, $summary.' '.$solution, $datereported, $reportedby, $assignedto, $status, $urgency, 0 + $issue->watches, $actions);
            }
        } else {
            if (!empty($tracker->parent)) {
                $transfer = ($issue->status == TRANSFERED) ? tracker_print_transfer_link($tracker, $issue) : '' ;
                $dataset = array($maxpriority - $issue->resolutionpriority + 1, $issuenumber, $summary.' '.$solution, $datereported, $reportedby, $assignedto, $status, $urgency, 0 + $issue->watches, $transfer, $actions);
            } else {
                $dataset = array($maxpriority - $issue->resolutionpriority + 1, $issuenumber, $summary.' '.$solution, $datereported, $reportedby, $assignedto, $status, $urgency, 0 + $issue->watches, $actions);
            }
        }
        $table->add_data($dataset);     
    }
    $table->print_html();
    echo '<br/>';
} else {
    if (!$resolved){
    	echo '<br/>';
    	echo '<br/>';
        notice(get_string('noissuesreported', 'tracker'), "view.php?id=$cm->id"); 
    } else {
    	echo '<br/>';
    	echo '<br/>';
        notice(get_string('noissuesresolved', 'tracker'), "view.php?id=$cm->id"); 
    }
}

if (has_capability('mod/tracker:manage', $context) || has_capability('mod/tracker:resolve', $context)){
?>
<center>
    <p><input type="submit" name="go_btn" value="<?php print_string('savechanges') ?>" /></p>
</center>
</form>
<?php

$nohtmleditorneeded = true;
}
?>
