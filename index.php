<?php
/*
    Free Weekly Report Tool
    Copyright (C) 2014 Namrata Powar, Yogi P

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/*
 * Change Database username, password and database name
 */
function sql_con() {
	$_SESSION['con'] = mysql_connect("localhost", "db_user", "db_userpass") or die(mysql_error());
	mysql_select_db("wrt_db") or die(mysql_error());
}

/*
 * This function gets starting day of the week.
 * $today: is any day of the week.
 * Return: returns first day of that week.
 */
function get_monday($today)
{
	$day = date('N', $today);
	/* Go back ($day - 1) back to get Monday */
	$monday = date('m/d/y', $today - (($day - 1) * 24 * 60 * 60));
	return $monday;
}

/*
 * This function gets ending day of the week.
 * $today: is any day of the week.
 * Return: returns lastday of that week.
 */
function get_sunday($today)
{
	$day = date('N', $today);
	/* 7 is Sunday. So go front 7 - $today to get Sunday */
	$sunday = date('m/d/y', $today + ((7 - $day) * 24 * 60 * 60));
	return $sunday;
}

/*
 * This function get time $count previous/next week
 * $count : if positive then will return next $count week
	  : if negative then will return previous $count week
 */
function get_week($count)
{
	return (time() + ($count * 7 * 24 * 60 * 60));
}


function make_safe($variable) {
	sql_con();
    $variable = mysql_real_escape_string(trim($variable));
    return $variable;
}

/* This function processes login form
 * $user :- input username 
 * $password :- input password
 * Returns :- 0 for success, 1 for failure
 */
function process_login($user, $password)
{
	error_reporting(E_ALL);
	ini_set('display_errors', True);

	$hash = md5($password);
	
	sql_con();

	$result1 = mysql_query("SELECT Password, Name, admin from login where User='$user' and status = 1");

	$row = mysql_fetch_array($result1);

	$result2 = strcmp($hash,$row['Password']);

	if($result2 != 0) {
		return -1;
	} else {
		if ($row['admin'] == 1)
			$_SESSION['admin'] = $row['admin'];
		return 0;
	}
}


/*
 * This function creates new form
 * week - is current or last week
 * rows - number of rows
 */
function show_create_form($week, $rows)
{
	$i = 0;

	/* Make sure that data from the database
	 * is filled in by default for only once.
	 * Next time it will be fetched from post
	 */
	if (!isset($_POST['task0'])) {
		$monday = get_monday(get_week($week));
		$Name = $_SESSION['Name'];
		
		sql_con();

		$num_rows = 0;

		$query = "SELECT Task,Percent,Project FROM week_report where User='$Name'and Week='$monday'";
		$result = mysql_query($query) or die (mysql_error());
		$num_rows = mysql_num_rows($result);

		while($rows1= mysql_fetch_array($result)){
			$proj[$i] = $rows1['Project'];
			$task[$i] = $rows1['Task'];
			$percent[$i] = $rows1['Percent'];
			$i++;
		}

		$rows = ($num_rows + $rows);
	} else {

		for ($i; $i < $rows; $i++) {
			$proj[$i] = make_safe($_POST['proj'.$i]);
			$task[$i] = make_safe($_POST['task'.$i]);
			$percent[$i] = make_safe($_POST['percent'.$i]);
		}
	}
	print '
	<form name="weekly report"method="post" action="?method=1">
	<table border=1>
	<th><tr><td>Sr.No.</td><td>Project</td><td> Task </td><td> Percent </td></tr></th>';

	if ($_SESSION['err']) {
		print '<tr><td align="middle" colspan=4><font color="red">'.$_SESSION['err'].', it is '.$_SESSION['total'].'%,<br>Report not submitted.</font></td>';
		unset($_SESSION['err']);
		unset($_SESSION['total']);
	}

	for ($i = 0; $i < $rows; $i++) {
		print '<tr> <td>'.($i+1).'</td><td><input type="text" name="proj'.$i.'" value="'.$proj[$i].'"></td>
			<td><textarea name="task'.$i.'" rows="4" cols="40">'. $task[$i].'</textarea> </td>
			<td><input type="text" name="percent'.$i.'" max=100 min=0 size=3 value="'.$percent[$i].'"></td> </tr>';
	}

	/* Note here about the input type hidden used to send how many rows
	   are currently being used.
	*/
	print '
	<tr> <td></td><td><input type="hidden" name="rows" value="'.$rows.'"> </td>
	<tr> <td></td><td><input type="hidden" name="create" value="create"> </td>
	<tr> <td></td><td><input type="hidden" name="week" value="'.$week.'"> </td>
	<td><input type="submit" name="submit" value="Add a Row"> </td> </tr>
	<tr><td><input type="submit" name="save" value="save"> </td>
	<td><input type="submit" name="submit" value="submit"> </td> </tr>
	</table>
	</form>';
	print '<hr><a href="?method=1">Back</a>';
}

/*
 * This function prints names of all the subordinates
 * $admin will have different values based on the caller
 *		0 - to view reports of others
 *		1 - to reset password of others
 *		2 - Adding new user
 */
function print_user_select($manager, $admin, $default)
{
	if ($admin == 1)
		$query = "select User from login where User != '".$_SESSION['Name']."'";
	else if ($admin == 2)
		$query = "select User from login";
	else
		$query = "select User from login where manager = '".$_SESSION['Name']."'";
	sql_con();
	$result = mysql_query($query) or die (mysql_error());	
	$num_rows = mysql_num_rows($result);

	print '<select name="subuser">';

	for ($i = 0; $i < $num_rows; $i++) {
		$array = mysql_fetch_array($result);

		if (($admin == 2) && ($array['User'] == $default)) {
			print '<option selected ="selected"';
		} else {
			print '<option ';
		}

		print 'value="'.$array['User'].'">';
		print $array['User'].'</option>';
	}
	if ($admin == 0) {
		print '<option selected="selected" value="All"> All Users </option>';
	} else if ($admin == 2) {
		if ($default == 0 || $default == "None")
			print '<option selected ="selected"';
		else
			print '<option ';

		print '<value="None">None</option>';
	} else {
		print '<option selected ="selected"';
		print '<value="None">None</option>';
	}
	print '</select>';
}
/*
 * This function print week
 * limit : # of old weeks
 */
function print_week_select($limit)
{
/*	print '<select name="week" onchange="this.form.submit();">'; */
	print '<select name="week">';
	/* Previous $limit weeks and current week */
	for ($j = -$limit; $j < 1; $j++) {
		/* This will make current week as default week */
		if ($j == 0)
			print '<option selected="selected" value="'.$j.'">';
		else
			print '<option value="'.$j.'">';
		/*eg.,  03/24/14 - 03/30/14 */
		print get_monday(get_week($j)).'-'.get_sunday(get_week($j)).'</option>';
	}
	if ($limit != 1)
		print '<option value="'.$j.'"> All Weeks </option>';
	print '</select>';

}

/*
 * to replace all linebreaks to <br/>
 */
function nl2br2($string) {
	$string = str_replace(array("\\r\\n", "\\r", "\\n"), "<br />", $string);
	return $string;
}

/*
 * to replace all linebreaks to <br/>
 */
function nl2br1($string) {
	$string = str_replace(array("\r\n", "\r", "\n"), "<br />", $string);
	return $string;
}
/*
 * this code prints the table of selected week
 * week : week
 */
function print_reports($week, $user)
{
	sql_con();
	
	echo '<table border=1><th>Sr.No.</th><th>Project</th><th width=400>Task</th><th>Percent</th><th>Week</th>';

	if ($week == 1) {
		if ($user == 'All')
			$query = "select * from week_report where user in
				  (select user from login where manager = '".$_SESSION['Name']."'
				   and user != manager) order by week_report.user";
		else
			$query = "SELECT * FROM week_report where User='$user'";
	} else {
		$monday = get_monday(get_week($week));
		if ($user == 'All')
			$query = "select * from week_report where Week = '$monday' and user in
				  (select user from login where manager = '".$_SESSION['Name']."'
				   and user != manager) order by week_report.user";
		else 
			$query = "SELECT * FROM week_report where Week = '$monday' and User='$user'";
	}

	$result = mysql_query($query) or die (mysql_error());
	$no_of_rows = mysql_num_rows($result);
	$tmp_user = "";
	$i=1;
	while($row = mysql_fetch_array($result, MYSQL_BOTH) and $i <= $no_of_rows) {
		if ($tmp_user != $row["User"] && $user == 'All') {
			$tmp_user = $row["User"];
			printf ('<tr><td colspan="3">'.$tmp_user.'</td>');
		}
		$str = $row["Task"];
		print '<tr><td>'.$i.'</td><td>'.$row["Project"].'</td><td>'.nl2br1(nl2br2($str)).'</td><td>'.$row["Percent"].'</td><td>'.$row["Week"].'</tr>';
		$i++;
	}

	echo'</table>';


	mysql_close(con);
}

/*
 * This functions inserts data into the database.
 * It is first deleted and then reinserted
 */
function save($showlog)
{
	$rows = make_safe($_POST['rows']);
	$opt = make_safe($_POST["week"]);
	$monday= get_monday(get_week($opt));
	$method = make_safe($_POST['method']);
	$Name=$_SESSION['Name'];
	for ($i = 0; $i < $rows; $i++) {
		$proj[$i] = make_safe($_POST['proj'.$i]);
        	$task[$i] = make_safe($_POST['task'.$i]);
        	$percent[$i] = make_safe($_POST['percent'.$i]);
	}

	sql_con();

	$query="DELETE from week_report where User='".$Name."' and Week='".$monday."'";
	mysql_query($query) or die (mysql_error());	
	$j=0;
	for ($i = 0; $i < $rows; $i++) {
		if ($task[$i]!= NULL && $percent[$i] != NULL) {
			$query = "INSERT INTO week_report (Project,Task,Percent,Week,User)
				  VALUES('".$proj[$i]."','".$task[$i]."','".$percent[$i]."','".$monday."','".$Name."')";
			mysql_query($query) or die (mysql_error());
			$j++;
		}
	}
	if ($j) {
		if ($showlog)
			print "Saving ".$j." records.<br>";
	} else {
		echo "Please enter data";
		$method = 1;
	}
	//print '<hr><a href="?method='.$method.'">Back</a>';
}

/*
 * This functions would eventially send notification to
 * user's manager if sum total of % is not 100
 */
function submit()
{
	$opt = make_safe($_POST["week"]);
	$monday= get_monday(get_week($opt));
	$method = make_safe($_POST['method']);
	$Name=$_SESSION['Name'];

	sql_con();

	$query = "select sum(percent) as Total from week_report where user = '".$Name."' and Week='".$monday."'";
	$result = mysql_query($query) or die (mysql_error());

	$result_arr = mysql_fetch_array($result);

	$total = $result_arr['Total'];

	if ($total == 100) {
		print "Submitting report notification to your manager";
		print '<hr><a href="?method='.$method.'">Back</a>';
		email();
		mysql_close($_SESSION['con']);
		exit;
	} else {
		$_SESSION['err'] = "Sum total of percentage is not 100%";
		$_SESSION['total'] = $total;
		$method = 1;
	}
}

/*
 * Function to print login form
 */
function print_login_form($user, $password, $userErr, $passErr)
{
	print '
	<form method="post" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'">
	<table>
	<tr><td> Username:</td>
	    <td> <input type="text" name="user" value="'.$user.'"> </td>
	    <td> <span class="error"> '.$userErr.'</span> </td> </tr>
	<tr><td>Password:</td>
	    <td><input type="password" name="password" value="'.$password.'"></td>
	    <td> <span class="error"> '.$passErr.'</span></td></tr>
	<tr><td></td>
	    <td><input type="submit" name="submit" value="login"></td>
	    <td></td></tr>
	</table>
	</form>';
print '</body> </html>';
}

/*
 * Function to add new user
 */
function print_add_new_user($realname, $newname, $password, $password1,
			    $userErr, $passErr, $manager, $email, $debug)
{
	print ' <form method="post" action="?method=6"> <table>';

	if ($debug) {
		print '<tr><td></td><td><span class="error">'.$debug.'</span> </td></tr>';
		$debug = 0;
	}

	print ' <tr><td> Real Name:</td>
	    <td> <input type="text" name="realname" value="'.$realname.'"> </td>

	<tr><td> Username:</td>
	    <td> <input type="text" name="user" value="'.$newname.'"> </td>
	    <td> <span class="error"> '.$userErr.'</span> </td> </tr>

	<tr><td>Password:</td>
	    <td><input type="password" name="password" value="'.$password.'"></td>
	    <td> <span class="error"> '.$passErr.'</span></td></tr>

	<tr><td>Retype:</td>
	    <td><input type="password" name="password1" value="'.$password1.'"></td>
	    <td> <span class="error"> '.$passErr.'</span></td></tr>

	<tr><td>Manager:</td><td>';

	 print_user_select($_SESSION['Name'], 2, $manager);
	 print '</td></tr>';

	 print ' <tr><td></td>
		 <tr><td>Email:</td>
		 <td><input type="email" name="email" value="'.$email.'"></td>

	    <td><input type="submit" name="register" value="register"></td>
	    <td></td></tr>
	</table>
	</form>';
	print '<hr><a href="?method=0">Back</a>';
print '</body> </html>';
}

/*
 * Function to process new user
 */
function process_new_user($realname, $newname, $password, $password1,
		 $manager, $email)
{
	/* Check if new username is valid */
	sql_con();
	$query = "select count(User) as count from login where User = '".$newname."'";
	$result = mysql_query($query) or die (mysql_error());
	$result_arr = mysql_fetch_array($result);
	$count = $result_arr['count'];
	if ($count > 0) {
		/* Username clashed */
		$err = "Username already present, use different one";

		return $err;
	}

	$query = "select count(User) as count from login where Email = '".$email."'";
	$result = mysql_query($query) or die (mysql_error());
	$result_arr = mysql_fetch_array($result);
	$count = $result_arr['count'];
	if ($count > 0) {
		/* Email address clashed */
		$err = "Email address already registered, use different one";
		return $err;
	}

	/* Check if passwords matches */
	if (strcmp($password, $password1) != 0) {
		$err = "Passwords do not match";

		return $err;
	}

	$hash = md5($password);

	$query = "insert into login values ('".$realname."', '".$newname."', '".$hash."', '".$email."', '', '', '".$manager."', 0)";

	mysql_query($query) or die (mysql_error());
}

/*
 * Function to print reset form
 */
 function print_reset_form()
 {
	 print '<br> <form action ="?method=5" method="post"><br>
		 <table border=0>
		 <tr><td>Current Password:</td><td><input type="password" name="password1"></td></tr>
		 <tr><td>New Password:</td><td><input type="password" name="password2"></td> </tr>
		 <tr><td>Retype New Password:</td><td><input type="password" name="password3"></td> </tr>
		 <tr><td></td><td><input type="submit" name="reset" value="submit"></td></tr>
		 </table>
		 </form>';
	 print '<hr><a href="?method=0">Back</a>';
 }

/*
 * Function to block user
 */
 function block_user_admin($subuser, $status)
 {
	$status -= 1;
	$query = "update login set status = ".$status." where User ='".$subuser."'";
	$result = mysql_query($query) or die (mysql_error());

	if ($status)
		print "$subuser unblocked";
	else
		print "$subuser blocked";

	print '<hr><a href="?method=0">Back</a>';
 }
/*
 * Function to print form for block a user
 */
 function print_block_form_admin()
 {
	 print '<p>Block a specific user</p><hr> ';
	 print ' <form action ="?method=7" method="post"><br>
		 <table border=0>
		 <tr><td>User </td><td>';
	 print_user_select($_SESSION['Name'], 1, 0);
	 print '</td><td>
	 <input type="radio" name="status" value="1">block
	 <input type="radio" checked="checked" name="status" value="2">unblock
	 </td>
	 <td><input type="submit" name="block" value="submit"></td></tr>
		 </table>
		 </form>';
	 print '<hr><a href="?method=0">Back</a>';
 }

/*
 * Function to print reset form  for admin
 */
 function print_reset_form_admin()
 {
	 print '<p>Reset password for specific user</p><hr> ';
	 print '<br> <form action ="?method=8" method="post"><br>
		 <table border=0>
		 <tr><td>User Name:</td><td>';
	 print_user_select($_SESSION['Name'], 1, 0);
	 print '</td></tr>';
	 print '<tr><td>New Password:</td><td><input type="password" name="password2"></td> </tr>';
	 print '<tr><td>Retype New Password:</td><td><input type="password" name="password3"></td> </tr>
		 <tr><td></td><td><input type="submit" name="reset" value="submit"></td></tr>
		 </table>
		 </form>';
	 print '<hr><a href="?method=0">Back</a>';

 }

/*
 * function to reset password
 * user : User name
 * cpass : Current Password
 * npass : new Password
 * n1pass : renter Password
 */

function reset_password($user, $cpass, $npass, $n1pass, $admin)
{
	$hash = md5($cpass);
	if (strcmp($npass, $n1pass) == 0) {
		sql_con();
		if (!$admin) {
			$query = "SELECT Password from login where User='".$user."'";
			$result = mysql_query($query) or die(mysql_error());
			$array = mysql_fetch_array($result);
		}
		if ($admin || (strcmp($hash, $array['Password']) == 0)) {
			mysql_query("update login set Password ='".md5($npass)."' where User='".$user."'")
				or die(mysql_error());
			echo"Password changed";
		} else {
			echo"Enter correct password";
			print '<form action ="?method=5" method="post"><br>
			Current Password:<input type="password" name="password1"><br>
			New Password:<td><input type="password" name="password2"><br>
			Retype New Password:<input type="password" name="password3"><br>
			<input type="submit" name="reset" value="submit">
			</form>';
		}
	} else {
		echo"Enter same 'new passwords'";

		if ($admin)
			print_reset_form_admin();
		else
			print_reset_form();
	}
}

function email() {

        $query1 = "select Manager from login where User='".$_SESSION['Name']."'";
        $result1 = mysql_query($query1) or die (mysql_error());
        $array1= mysql_fetch_array($result1);
        $manager = $array1['Manager'];

        $query2 = "select Email from login where User='$manager'";
        $result2 = mysql_query($query2) or die (mysql_error());
        $array2= mysql_fetch_array($result2);
        $to = $array2['Email'];
        $subject = "Weekly report notification";
        $message = "Dear User,<br><br>".$_SESSION['Name']."\r\t has submitted a weekly report.<br><br>".
                    "Note that this mail has been auto-generated.<br>".
                    "Please do not reply.<br><br>".
                    "Thanks<br>".
                    "WRT Team";

	$headers = "From: Weekly Report Tool<no-reply@vadactro.org.in>\r\n".
                   "MIME-Version: 1.0" . "\r\n" .
                   "Content-type: text/html; charset=UTF-8" . "\r\n";

        mail($to, $subject, $message, $headers);
}

session_start();
$rows = make_safe($_POST['rows']);
if (!$rows)
	$rows = 1;

$submit = make_safe($_POST['submit']);
$save = make_safe($_POST['save']);


if ($submit  == "Add a Row") { 
	$rows++;
} else if ($submit == "submit") {
	/* Save first before submitting */
	save(0);
	/* Make sure sum of all percentage of selected
	 * weeks activity is 100
	 */
       	submit();
} else if ($submit == "login") {
	$user = make_safe($_POST['user']);
	$password = make_safe($_POST['password']);
	$userErr = $passErr = "";

	if (process_login($user, $password) == 0) {
		/* Store session */
		$_SESSION['Name'] = $user;
	} else {
		$userErr = 'Either username or password is incorrect';
		/* Show login form again */
	}
} else if ($save == "save") {
	save(1);
} else if ($submit == "register") {
	print "O I  am her";
	$userErr = $passErr = "";
}

if (isset($_GET['method'])) 
	$method = make_safe($_GET['method']);
else
	$method = 0;

if (isset($_GET['logout'])) 
	$logout = make_safe($_GET['logout']);
else
	$logout = 0;

if ($logout == 1) {
	unset($_SESSION['Name']);
	unset($_SESSION['admin']);
	session_destroy();
}

print '<html> <body> <h3>Weekly Report Tool</h3><hr>';

if(isset($_SESSION['Name'])){
	switch ($method) {
	case 0:
		echo 'Welcome '.$_SESSION['Name'].'<br> ';
		break;
	case 1:
		echo '['.$_SESSION['Name'].'] Create new report<br>';
		break;
	case 2:
		echo '['.$_SESSION['Name'].'] View reports<br>';
		break;
	case 3:
		echo '['.$_SESSION['Name'].'] Manage reports<br>';
		break;
	case 4:
		echo '['.$_SESSION['Name'].'] View License<br>';
		break;
	case 5:
		echo '['.$_SESSION['Name'].'] Reset Password<br>';
		break;
	}
	echo '<a href="?logout=1">Logout</a><hr>';
} else {
	print_login_form($user, $password, $userErr, $passErr);
	mysql_close($_SESSION['con']);
	exit;
} 

switch ($method)
{
/* Display create/show/edit */
case 0:

	print '<li><a href="?method=1">Create new report</a></li>
	<li><a href="?method=2">View old report/reports</a></li>';
	$query = "select User from login where manager = '".$_SESSION['Name']."'";
	sql_con();
	$result = mysql_query($query) or die (mysql_error());	
	$num_rows = mysql_num_rows($result);

	if ($num_rows)
		print '<li><a href="?method=3">View weekly report of subordinates</a></li>';

	print '<li><a href="?method=5">Reset Password</a></li>';
	print '<li><a href="?method=4">License/Authors/Credits</a></li>';

	/* Admin tasks */
	if (isset($_SESSION['admin'])) {
		print '<hr> <h5> Admin Tasks </h5> <hr>';
		print '<li><a href="?method=6">Add New User</a></li>';
		print '<li><a href="?method=7">Block existing User</a></li>';
		print '<li><a href="?method=8">Reset Password</a></li>';
	}

	break;
/* Show form to create new report */
case 1:
	$create = make_safe($_POST['create']);
	if (!$create) {
		print ' <form name="weekly report" method="post" action="?method=1">';
		/* Create report only for current/last week */
		print_week_select(1);
		print '<input type="submit" name="create" value="create">';
		print '</form>';
		print '<hr><a href="?method=0">Back</a>';
		break;
	}
	$week = make_safe($_POST["week"]);
	show_create_form($week, $rows);

	break;
/* View old/current reports */
case 2:
	$print = make_safe($_POST['print']);
	if (!$print)
	{
		/* Display week select from last 4 weeks */
		print ' <form name="weekly report" method="post" action="?method=2">';
		print_week_select(4);
		print '<input type="submit" name="print" value="print">';
		print '</form>';
		print '<hr><a href="?method=0">Back</a>';
		break;
	}
	$week = make_safe($_POST["week"]);
	print_reports($week, $_SESSION['Name']);
	print '<hr><a href="?method='.$method.'">Back</a>';

	break;
case 3:
	/* View reports of subordinates */
	$print = make_safe($_POST['print']);
	if (!$print)
	{
		/* Display week select from last 4 weeks */
		print ' <form name="weekly report" method="post" action="?method=3">';
		print_user_select($_SESSION['Name'], 0);
		print_week_select(4);
		print '<input type="submit" name="print" value="print">';
		print '</form>';
		print '<hr><a href="?method=0">Back</a>';
		break;
	} 
	$week = make_safe($_POST["week"]);
	$subuser = make_safe($_POST["subuser"]);
	print_reports($week, $subuser);
	print '<hr><a href="?method='.$method.'">Back</a>';

	break;
case 4:
	/* License */
	print 'Free Weekly Report Tool<br><br>
    	    Copyright (C) 2014 Namrata [namrata.pawar10 at gmail.com] and<br>
    	    Yogi [yogi at vadactro.org.in] <br><br>

	    This program is free software: you can redistribute it and/or modify<br>
	    it under the terms of the GNU General Public License as published by<br>
	    the Free Software Foundation, either version 3 of the License, or<br>
	    any later version.<br><br>

	    This program is distributed in the hope that it will be useful,<br>
	    but WITHOUT ANY WARRANTY; without even the implied warranty of<br>
	    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the<br>
	    GNU General Public License for more details.<br><br>

	    You should have received a copy of the GNU General Public License<br>
	    along with this program.  If not, see http://www.gnu.org/licenses/.';
	print '<hr><a href="?">Back</a>';
	break;
	
case 5:
	/* reset password */
	$reset = make_safe($_POST['reset']);
	if(!$reset){
		print_reset_form();
		break;
	} else {
		$s1 = make_safe($_POST['password1']);
		$s2 = make_safe($_POST['password2']);
		$s3 = make_safe($_POST['password3']);
		reset_password($_SESSION['Name'], $s1, $s2, $s3, 0);
		print '<hr><a href="?">Back</a>';
	}
	break;
case 6:
	if (!$_SESSION['admin'])
		break;
	$realname = make_safe($_POST['realname']);
	$newname = make_safe($_POST['user']);
	$password = make_safe($_POST['password']);
	$password1 = make_safe($_POST['password1']);
	$manager = make_safe($_POST['subuser']);
	$email = make_safe($_POST['email']);

	if (!$realname || !$newname || !$password || !$password1 ||
	    !$manager || !$email) {
		print_add_new_user($realname, $newname,
				$password, $password1,
				$userErr, $passErr,
				$manager, $email, 0);
	} else {
		$debug = process_new_user($realname, $newname,
				$password, $password1,
				$manager, $email);

		if ($debug) {
			print_add_new_user($realname, $newname,
				$password, $password1,
				$userErr, $passErr,
				$manager, $email, $debug);
		}
	}
	/* Add New user */
	break;
case 7:
	if (!$_SESSION['admin'])
		break;

	/* Block existing User */
	$block = make_safe($_POST['block']);
	$subuser = make_safe($_POST["subuser"]);
	$status = make_safe($_POST['status']);
	if(!$block || !$subuser || !$status){
		print_block_form_admin();
	} else {
		block_user_admin($subuser, $status);
	}

	break;
case 8:
	if (!$_SESSION['admin'])
		break;

	/* Reset User Password */
	$reset = make_safe($_POST['reset']);
	$subuser = make_safe($_POST["subuser"]);
	if(!$reset || !$subuser){
		print_reset_form_admin();
	} else {
		$s2 = make_safe($_POST['password2']);
		$s3 = make_safe($_POST['password3']);
		reset_password($subuser, $s1, $s2, $s3, 1);
		print '<hr><a href="?method=0">Back</a>';
	}
	break;
}
print ' </body> </html>';
mysql_close($_SESSION['con']);
?>
