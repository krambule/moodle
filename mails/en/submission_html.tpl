<b>Course:</b> [<%%COURSE_SHORT%%>] <%%COURSENAME%%><br/>
<b>Tracker:</b> <%%TRACKERNAME%%><br/>
<hr/>
<p><b>New bug entry:</b> <%%ISSUE%%> / <%%SUMMARY%%><br/>
<b>By:</b> <%%BY%%></p>
<hr/>
<b>Description : </b><%%DESCRIPTION%%>
<hr/>
<p>
<form method="get" action="<%%COMMENTURL%%>">

<input type='hidden' name='what' value='doaddcomment'/><br/>
<input type='hidden' name='screen'  value='viewanissue'/><br/>
<input type='hidden' name='issueid'  value='<%%ISSUEID%%>'/><br/>
<input type='hidden' name='id'  value='<%%ID%%>'/><br/>
<input type='hidden' name='commentformat'  value='1'/><br/>
<input type='hidden' name='save'  value='Continue'/><br/>

<label for='commenttext'>Add a comment to the ticket</label>
<input type='text' size = 20 id='commenttext' name='comment'/><br/>
<input type="submit" value="Submit"/><br/>
</p>
<p>
<a href="<%%ISSUEURL%%>">See the issue record</a><br/>
<a href="<%%CCURL%%>">Register for watches on this record</a><br/>
<a href="<%%TICKETASSIGN%%>">Assign this ticket to yourself</a></p>