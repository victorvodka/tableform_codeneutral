<?php 
//Judas Gutenberg January 2006
//provides a web front end admin tool for any MySQL db
//depends on a table called tf_relation for foreign key info
//also needs admin table, permission table, and permission_type table
//This code is covered under the GNU General Public License
//info here: http://www.gnu.org/copyleft/gpl.html
//the digest is as follows: you cannot modify this code without
//publishing your source code under the same license
//contact the developer at gus@asecular.com  http://asecular.com


require('core_functions.php');
require('tableform_functions.php');
require('saveform.php');
require('serializeformfunctions.php');

//this is in case we are doing wysiwygpro, though i don't want it to bomb if the files aren't there
$errorlevel=error_reporting(0);
	include("editor_files/config.php");
	include("editor_files/editor_class.php");
error_reporting($errorlevel);

echo main();
 
function main()
{
	if(!IsExtraSecure())
	{
		die(ExtraSecureFailure());
	}
	$out="";
	$strPHP=$_SERVER['PHP_SELF'];
	$olderror=error_reporting(0);
	$mode=$_REQUEST[qpre . "mode"];
	$displaytype=$_REQUEST[qpre . "displaytype"];
	$idfield=$_REQUEST[qpre . "idfield"];
	$strBackfield=$_REQUEST[qpre . "backfield"];
	$strKosherFields=$_REQUEST[qpre . "kosherfields"];
	$strDisplayMode= $_REQUEST[qpre . "suppressmetalink"];
	//this way assumes a single PK:
	$id=$_REQUEST[$idfield];
	//new way for slurping in a PK array
	//should i really have to deescape?
	$keyserialized=deescape($_REQUEST[qpre . "ks"]);
	//echo ($keyserialized);
	if(!beginswith($keyserialized, "b:"))
	{
		$arrPK=unserialize($keyserialized);
 	}
	$clearid=$_REQUEST[qpre . "submit_clearid"];
	$intFilterID=$_REQUEST[qpre . "filterid"];
	$strTable=$_REQUEST[qpre . "table"];
 
	$strDatabase=deMoronizeDB(gracefuldecay($_REQUEST[qpre . "db"],our_db));
	$strColumn=$_REQUEST[qpre . "column"];
	
	$strDirection=$_REQUEST[qpre . "direction"];
	$strSearchField=$_REQUEST[qpre . "searchfield"];
	$strSearchString=$_REQUEST[qpre . "searchstring"];
	$intSearchType=$_REQUEST[qpre . "searchtype"];
	$strConfigBehave=$_REQUEST[qpre . "behave"];
	$intRecord=$_REQUEST[qpre . "rec"];
	$feedbackspanstag="<div class=\"feedback\">";
	//error_reporting($olderror);
	$bwlSimplifyLogin=false;
  	//if the table name begins with a "!" then we go directly to any tool written specifically for it
	
  	if (beginswith($strTable,"!"))
	{
		//echo "$$";
		$strTable=substr($strTable, 1);
		$toolpage=GenericDBLookup($strDatabase, tfpre . "browsescheme", "table_name", $strTable, "toolpage");
		
		if ($toolpage!="")
		{
			//echo "#";
				header("Location: " . qbuild( $toolpage , $strDatabase, $strTable, "view", "id", $strOurID) . "&displaytype=7");
		}
		else //if there's no tool location, assume that what was meant was a php url of a front page kinda thing
		{
			header("Location: " . $strTable . ".php");
		
		}
	}
  
  
 	if ($mode=="")
	{
		$strTable="";
	}
	if (contains($strConfigBehave,"closeclickrecycle")  && !contains($strConfigBehave,"fulllogin"))
	{
		$bwlSimplifyLogin=true;
	}
	$out=LoginDecisions($strDatabase,  $strPHP, $strUser,$bwlSimplifyLogin, $strConfigBehave);
	$intUserID=GenericDBLookup($strDatabase,  tfpre . "admin", "username", $strUser, "admin_id");
	$isSuperAdmin=IsSuperAdmin($strDatabase, $strUser);
	$isSuperUser=IsSuperUser($strDatabase, $strUser) || $isSuperAdmin;
	if($mode=="version")
	{
		$out="";
		$out.= adminbreadcrumb(false,  $strDatabase, $strPHP,  "System Version", "") ;
		//$out.= AdminNav($isSuperAdmin);
		$out.=DetermineVersion();
	}
	else
	{
	 	if ($strUser!="")
		{
		
 
			if (default_table=="default_table")
			{
				if ($strTable=="")
				{
					
					$out.=tablebrowser($strDatabase, $strPHP, $strUser);
				}
			}
			else
			{
				if ($strTable==""  && !$isSuperAdmin)
				{
					$strTable=default_table;
					if (beginswith($strTable,"!"))
					{
						$strTable=substr($strTable, 1);
						header("Location: " . $strTable . ".php");
					}
					$mode="view";

					
				}
				else if ($isSuperAdmin  && $strTable=="")
				{
				
					$out.=tablebrowser($strDatabase, $strPHP, $strUser);
				}
				else
				{
	
					
				}
			}

 
			$intAdminType= AdministerType($strDatabase, $strTable, $strUser, $id);
			
			$bwlBeginsWithTF=beginswith($strTable,  tfpre);
		 	//echo $bwlBeginsWithTF . "+" . $isSuperAdmin . "-" . $isSuperUser;
			 
			if($bwlBeginsWithTF && !$isSuperAdmin && !$isSuperUser)
			{
			 
				$intAdminType=0;
			}
			//echo $intAdminType;
			if ($intSearchType=="")
			{
				$intSearchType=0;
			}
			if ($intRecord=="")
			{
				$intRecord=0;
			}
	 
			
			if ($mode=="save"  || $mode=="create")
			{
		
				if ($intAdminType==2)
				{
					 
					//i clear id if i'm doing a "save as" type operation
					if ($clearid!="")
					{
						$mode="create";
						//clear non-composite PK
						$id="";
						//clear composite PK
						if(is_array($arrPK))
						{
							foreach ($arrPK as $k=>$v)
							{
								$arrSavePK[$k]="";
							}
						}
						if(is_array($arrPK))
						{
							$arrPK=ArraySubsetFromArray($arrPK, $_REQUEST);
						}
		 
					}
					else
					{
						if(is_array($arrPK))
						{
							$arrSavePK=$arrPK;
						}
					}
					//the following test keeps odd things from happening 
					//should these things find their way into query strings
					if($_POST[qpre . "submit_clearid"]!="" || $_POST[qpre . "submit"]!="")
					{
						$out.=$feedbackspanstag .  SaveForm($strDatabase, $strTable,  $idfield, $id, $strConfigBehave, $strUser, $arrSavePK). "</div>" ;
					}
				}
				else
				{
					$out.="You don't have permissions to edit this table.";
				}
			}
			//echo $mode . "+" . $strConfigBehave;
			//echo "--" . $id . "---";
			if ( $mode=="edit" || ($mode=="create"  &&  !contains($strConfigBehave,"complete")))
			{
				if ($intAdminType==2)
				{
					$out.=TableForm($strDatabase, $strTable,  $idfield, $id, $strPHP,  $strConfigBehave, "", $strUser, $arrPK,  $strDisplayMode, $strKosherFields);
					
				}
				elseif ($intAdminType==1)
				{
					$out.=DisplayDataForARow($strDatabase, $strTable, $idfield, $id, $strPHP);
				}
				else
				{
					$out.="You don't have permissions to view or edit this table.";
				}
			}
			if ($mode=="delete")
			{
				if ($intAdminType==2)
				{
					$out.=$feedbackspanstag . rowdelete($strDatabase, $strTable,  $idfield, $id, $strUser, $arrPK) . "</div>"; 
				}
				else
				{
					$out.="You don't have permissions to edit this table.";
				}
				if (contains($strConfigBehave,"closeclickrecycle"))
				{
					$strConfigBehave.="complete";
				}
			}
		 	else if ($mode=="superdelete")
			{
				if ($intAdminType==2)
				{
					//TotalDelete($strDatabase, $strTable, $strPK, $strPKColumn="")
					if($isSuperAdmin)
					{
						$out.=$feedbackspanstag . TotalDelete($strDatabase, $strTable, $id, $idfield) . "</div>"; 
						$mode="delete";
					}
					else
					{
						$out.="You must be a super administrator to do a relational delete.";
					}
				}
				else
				{
					$out.="You don't have permissions to edit this table.";
				}
				if (contains($strConfigBehave,"closeclickrecycle"))
				{
					$strConfigBehave.="complete";
				}
			}

			if ( $mode=="new")
			{
				if ($intAdminType==2)
				{
					 
					$out.=TableForm($strDatabase, $strTable,  "", "", $strPHP, $strConfigBehave,"", $strUser, $arrPK, $strDisplayMode, $strKosherFields);
				}
				else
				{
					$out.="You don't have permissions to edit this table.";
				}
			}
			
			if (($mode=="view"  || ($mode=="save" ) || $mode=="delete")    &&  !contains($strConfigBehave,"complete"))
			{
				 
				if ($intAdminType>0)
				{
					$strFieldConfig="cust_order|customer_name*episode|episode_name|episode_datetime|show_id";
					$strFieldConfig="project|project_title|project_desc|project_desc|project_desc|project_desc|project_desc";
					$out.=DisplayDataTable($strDatabase, $strTable, "", $strColumn, $strPHP, $strDirection, $intRecord, $strSearchString, $strSearchField, $intSearchType, 50, $strFieldConfig, 1, 5, true, false, false, "", $displaytype, $intFilterID, $intUserID, $isSuperAdmin); 
				}
				else
				{
					$out.="You don't have permissions to view this table.";
				}
			}
		}
	}
	if (function_exists(toolnav) && !$bwlSimplifyLogin )
	{
		if ($strUser!="")
		{
			$out=toolnav(!$isSuperAdmin,  $strUser) . $out;
		}
	}
	if ($strBackfield!=""  &&  contains($strConfigBehave,"complete"))
	{
		$strNameField=firstnonidcolumname($strDatabase, $strTable);
		$strNameField2 = NthNonIDColumName($strDatabase, $strTable, 2);
		$strForBackField=$strDatabase . "|" . $strTable . "|" . $idfield . "|" . $strBackfield . "|" . $strNameField . "|" . $strNameField2 . "|" . $id;
		//echo $strForBackField;
		//die();
	}
	$bwlNoajax=false;
	if(contains($strConfigBehave,"noajax"))
	{
		$bwlNoajax=true;
	}
	$out =PageHeader($strDatabase . IfAThenB($strTable, " : ") . $strTable . IfAThenB($id, " : ") . $id . IfAThenB($mode, " : ") . $mode . " : Editor", $strConfigBehave, $strForBackField, true, $bwlSimplifyLogin) .   $out . PageFooter(true, $bwlNoajax, $bwlSimplifyLogin);
	return $out;
}




?>