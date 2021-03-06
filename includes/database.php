<?
//A list of database schema updates for each version
$cg_database_version = 3;
$cg_database_updates = array();

//Version 0 to 1 -------------------------------------------------------------------------------------------------
$cg_database_updates[0][] = <<<CGDB0001
 CREATE TABLE IF NOT EXISTS `dbversion` (
  `version` int(11) NOT NULL DEFAULT '0' COMMENT 'The current version.',
  `changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When it was applied.',
  PRIMARY KEY (`version`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Track the database schema.'
CGDB0001;
$cg_database_updates[0][] = <<<CGDB0002
 INSERT INTO `dbversion` ( `version` ) VALUES ( '1' )
CGDB0002;
//----------------------------------------------------------------------------------------------------------------

//Version 1 to 2 -------------------------------------------------------------------------------------------------
$cg_database_updates[1][] = <<<CGDB0003
 ALTER TABLE `prefs` ADD `cartinriver` TINYINT NOT NULL DEFAULT '0' COMMENT 'Show cartulized articles in a modal.'
CGDB0003;
$cg_database_updates[1][] = <<<CGDB0004
 INSERT INTO `dbversion` ( `version` ) VALUES ( '2' )
CGDB0004;
//----------------------------------------------------------------------------------------------------------------

//Version 2 to 3 -------------------------------------------------------------------------------------------------
$cg_database_updates[2][] = <<<CGDB0005
 ALTER TABLE `flags` ADD PRIMARY KEY ( `name` )
CGDB0005;
$cg_database_updates[2][] = <<<CGDB0006
 INSERT INTO `dbversion` ( `version` ) VALUES ( '3' )
CGDB0006;
//----------------------------------------------------------------------------------------------------------------
?>



<?
// ----- Database utility functions

//_______________________________________________________________________________________
//Check if the given user id actually exists in the system
function get_database_version()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get the database version number
  $stmt = "SELECT version FROM $table_dbversion ORDER BY version DESC LIMIT 1";
  if( ($sql=$dbh->prepare($stmt)) === FALSE ) {
    loggit(3,"Error preparing to query database version.");
    return(FALSE);
  }
  if( $sql->execute() === FALSE ) {
    loggit(3,"Error executing query for database version.");
    return(FALSE);
  }
  $sql->store_result() or print(mysql_error());
  if($sql->num_rows() != 1) {
    $sql->close() or print(mysql_error());
    loggit(3,"Too many, or not enough, records returned for database version.");
    return(FALSE);
  }
  $sql->bind_result($cdbversion) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());


  loggit(3,"Database version: [$cdbversion]");
  return( $cdbversion );
}


//_______________________________________________________________________________________
//Apply updates to the database to bring it to the current version
function apply_all_database_updates()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  global $cg_database_version;
  global $cg_database_updates;

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Get the current database version
  $error = FALSE;
  $dbversion = get_database_version();
  if( $dbversion == FALSE ) {
    $dbversion = 0;
  }


  //We execute a loop, applying all of the updates from the
  //current database version up to the newest
  $rounds = 0;
  while( $dbversion < $cg_database_version ) {
      loggit(3, "DATABASE UPGRADE: Applying update [$dbversion]");

      //Execute the queries in this update
      $stmt = $cg_database_updates[$dbversion];

      $i = 0;
      if( $dbh->multi_query(implode(';', $stmt)) ) {
        do {
          $i++;
        } while ($dbh->next_result());
      }
      if ($dbh->errno) {
        loggit(3, "DATABASE UPGRADE ERROR ON [$i]: ".print_r($dbh->error, TRUE));
        $dbh->close() or print(mysql_error());
        return(FALSE);
      }

      //Check where we're at now
      $dbversion = get_database_version();
      if( $dbversion == FALSE ) {
        loggit(3,"The last database update: [$dbversion] did not apply correctly.");
        $dbh->close() or print(mysql_error());
        return(FALSE);
      }

      if( $dbversion == $cg_database_version ) {
        loggit(3,"Database is current at version: [$dbversion].");
        $dbh->close() or print(mysql_error());
        return(TRUE);
      } else {
        loggit(3,"Database now at version: [$dbversion].");
      }
  }


  //Close connection and bail
  $dbh->close() or print(mysql_error());
  return(FALSE);
}



?>
