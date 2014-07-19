===============================================================================
License
===============================================================================
/*
    Free Weekly Report Tool
    Copyright (C) 2014 Namrata Powar <namrata.pawar10@gmail.com> and
    Yogi P <yogi@vadactro.org.in>
    Version 0.1

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
===============================================================================
Introduction
===============================================================================
Its very annoying for Managers to collect weekly status reports of team members
through email and then storing then in Word/Text. Searching for specific report
from old reports is much more pain.

Free Weekly Report Tool is a solution for such problems with following exciting
features implemented.

1. It is web based tool and can be accessed through any decent web browser.
   All the data is stored in database such as Mysql.
2. Installation is simple. Please read following INSTALL section for details.
3. User can save and/or submit their weekly report of current and last week.
4. Every successful submit of report would send a notification to user's
   manager.
5. Users can view their specific week's report or all the reports submitted so
   far. Managers can view his weekly reports  or of his team members.
6. Admin user can add new user, block any existing user and reset passwords of
   any user. Non-admin can change his/her password.

===============================================================================
INSTALL
===============================================================================
To use this FWRT perform following
a)  Mysql user creation and grant permissions 

i.	create user wrt_user identified by 'somepassword';
ii.	create db wrt_db;
iii.	GRANT ALL ON wrt_db.* to 'wrt_user'@'localhost' identified by 'somepassword';

b) login to mysql using wrt_user
i.	mysql -uwrt_user -psomepassword wrt_db
ii.	CREATE TABLE `login` (
	  `Name` varchar(40) NOT NULL,
	  `User` varchar(20) NOT NULL,
	  `password` varchar(50) NOT NULL,
	  `Email` varchar(50) NOT NULL,
	  `Website` varchar(20) DEFAULT NULL,
	  `Gender` varchar(20) NOT NULL,
	  `Manager` varchar(20) NOT NULL,
	  `admin` int(11) DEFAULT NULL,
	  `status` int(11) DEFAULT NULL,
	  PRIMARY KEY (`User`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;
iii. 	CREATE TABLE `week_report` (
	  `Task` varchar(200) NOT NULL,
	  `Percent` int(3) NOT NULL,
	  `Week` varchar(20) DEFAULT NULL,
	  `User` varchar(20) NOT NULL,
	  KEY `User` (`User`),
	  CONSTRAINT `week_report_ibfk_1` FOREIGN KEY (`User`) REFERENCES `login` (`User`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;
iv. 	Generate password using 'echo -n "password" | md5sum'
v. 	Add first admin entry to login db manually

	insert into login values ('First Last', 'userid', '6321acfbf468d15a8w9ab7693c92ee07', 'id@email.com','','','userid', 1, 1);

	Note that for the users that do not have managers, use their own userid as managers.
vi.	Add database credential at sql_con() in index.php.
