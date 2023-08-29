<?php 
		
 		require_once('../../main.inc.php');
		require_once(DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php');
		require_once('./class/html.formdepart.class.php');
 		require_once('class/cgldepart.class.php');
		$ID = $_GET["ID"];		
	
$w= new CglDepart($db);	
$num =  $w->InfoVentilNego($ID);
if ($num) {
	$rep =  $w->tva_assuj. '?';
		
	$rep .=   $w->ventilation_vente.  '?';	
	$rep .=     price2num($w->cost_trainer) .  '?';
	$rep .=    price2num($w->cost_trip ).  '?';
	$rep .=   dol_print_date($w->date_nego, '%d/%m/%y');	
}	
		echo $rep;
		
?>
