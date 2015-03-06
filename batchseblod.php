<?php
$oldId=$pk; //use oldID otherwise it can create confuseness
//echo "Old content id".$oldId;
//echo '<br/>New content ID:'.$newId."<br/>";;
// No direct access
defined('_JEXEC') or die;
/*
 * $pk=old id
 * $newId=new id
 */


//STEP 1 GET cckID
if (!$table->load($oldId))
			{
				if ($error = $table->getError())
				{
					// Fatal error
					$this->setError($error);
					return false;
				}
				else
				{
					// Not fatal error
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $oldId));
					continue;
				}
}


$pattern="#::cck::(.*)::/cck::#iU";
preg_match_all($pattern,$table->introtext,$matches);
$cckID=(int)$matches[1][0];
//echo "CCK ID:".$cckID;

$db =& JFactory::getDBO();
$db		= $this->getDbo();

//DUPLICATE $cckID to new ID in cck_core
//
//get MAX ID
$query="SELECT Max(#__cck_core.id) as maxid FROM #__cck_core";
$db->setQuery($query);
$row = $db->loadAssoc();
$nextcckID=$row['maxid']+1;
//echo 'next cck core ID '.$nextcckID."<br/>";

//get old PK
$query="SELECT pk FROM #__cck_core WHERE #__cck_core.id=".$cckID;
$db->setQuery($query);
$row = $db->loadAssoc();
$oldPK=$row['pk'];


$query="DROP TEMPORARY TABLE IF EXISTS tmptable ;";
$db->setQuery($query);
$result = $db->query();
//echo str_replace('#_', 'erx', $query);
$query="CREATE TEMPORARY TABLE tmptable SELECT * FROM #__cck_core WHERE id =".$cckID.";";
$db->setQuery($query);
$result = $db->query();
//echo str_replace('#_', 'erx', $query);
//
$query="UPDATE tmptable SET id = ".$nextcckID.",
pk=".$newId." WHERE id = ".$cckID.";";
$db->setQuery($query);
$result = $db->query();
//echo str_replace('#_', 'erx', $query);

$query="INSERT INTO #__cck_core SELECT * FROM tmptable WHERE id = ".$nextcckID.";";
$db->setQuery($query);
$result = $db->query();

//echo "<br/>";
//echo str_replace('#_', 'erx', $query);
//echo "<br/>";

//STEP 3 GET IMPACT TABLE BY cckID
// Get a database object

 

		$db		= $this->getDbo();
		$query = "SELECT DISTINCT
core.pk AS cckID,
cck_fields.storage_table AS cckName,
cck_fields.storage_table AS ccktable
FROM
#__cck_core AS core
Left Join #__cck_core_types AS types ON types.name = core.cck
Left Join #__cck_core_type_field AS type_field ON type_field.typeid = types.id
Left Join #__cck_core_fields AS cck_fields ON cck_fields.id = type_field.fieldid
WHERE
core.id =  ".$cckID." AND
cck_fields.storage_table <>  '#__content' AND
cck_fields.storage_table<>''";
	
	//	echo str_replace('#_', 'erx', $query);		
$db->setQuery($query);
$row = $db->loadAssocList();
//echo "<br/>PROCESSING TABLE<br/>";

foreach($row as $loop=>$data){
	//TIME TO COPY $data['ccktable'];
	//echo "TABLE ".$data['ccktable']," <br/>";
	if(strrpos($data['ccktable'],'store_item' )>0||strrpos($data['ccktable'], 'store_form')>0){
		// "FOUND STORE_ITEM";
			$query="DROP TEMPORARY TABLE IF EXISTS tmptable ;";
			$db->setQuery($query);
			$result = $db->query();
			//echo $query;
			$query="CREATE TEMPORARY TABLE tmptable SELECT * FROM ".$data['ccktable']." WHERE id =".$oldPK.";";
			$db->setQuery($query);
			$result = $db->query();
			//echo $query;
			$query="UPDATE tmptable SET id = ".$newId." WHERE id = ".$oldPK.";";
			$db->setQuery($query);
			$result = $db->query();
			//echo $query;
			$query="INSERT INTO ".$data['ccktable']." SELECT * FROM tmptable WHERE id = ".$newId.";";
			$db->setQuery($query);
			$result = $db->query();
			//echo $query."<br/> <br/>";
	}else{

			die('no match');
		
	}
}


//new IntroText
if (!$table->load($newId))
			{
				if ($error = $table->getError())
				{
					// Fatal error
					$this->setError($error);
					return false;
				}
				else
				{
					// Not fatal error
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
}
$table->introtext=str_replace('::cck::'.$cckID.'::/cck::', '::cck::'.$nextcckID.'::/cck::', $table->introtext);
if (!$table->store())
{
				$this->setError($table->getError());
				return false;
}
//die();