<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SalesOrder_Module_Model extends Inventory_Module_Model{
	//Function xử lý save
	 		/* https://redmine.innocom.vn/issues/191 */

	public function handleSaveValidation(Vtiger_Request $request)
		{

				global $APE;
				global $APEClosedDeal;			
				$redirectUrl = 'index.php?module=SalesOrder&view=Edit';

				// https://redmine.innocom.vn/issues/192
				$param = "";
				$start = false; 
				foreach ($_REQUEST as $key => $value) {
					if ($key == "record"){
						$start = true;
					}
					if ($start){
						$param .= '&'.$key.'='.$value;	
					}					
				}
				$redirectUrl .= $param;
				$validate = true;
				// https://redmine.innocom.vn/issues/192

				$info = $request->getAll();

				$quantity = $info['totalProductCount'];
				$maxDoorGift = $info['cf_1017'];
				$maxClosedDealGift = $info['cf_1019'];
				$totalDoorGift = 0;
				$totalClosedDealGift = 0;
				$total = $info['subtotal'];
				//https://redmine.innocom.vn/issues/348
				$money = new CurrencyField();
				$APECommit =  $money->convertToDBFormat($info['cf_1009']);
				//https://redmine.innocom.vn/issues/348 -END
				for ($i=1; $i <= $quantity; $i++) { 
					$pid = $info["hdnProductId".$i]; 
					$ProductsModel = Vtiger_Record_Model::getInstanceById($pid, 'Products');
					$type = $ProductsModel->get('cf_759');
					if ($type=='Door Gift') {
						$totalDoorGift = $totalDoorGift + $info["qty".$i];
					}
					if ($type=='Closed Deal Gift') {
						$totalClosedDealGift = $totalClosedDealGift + $info["qty".$i];
					}
				}

				$startTimeStamp = strtotime(date('d-m-Y'));
				$endTimeStamp = strtotime(date('d-m-Y', strtotime($info['duedate'])));
				$timeDiff = abs($endTimeStamp - $startTimeStamp);
				$numberDays = $timeDiff/86400; 
				$numberDays = intval($numberDays);

				
				//var_dump($info);
				 $totalDoorGift . "<br/>";
				 $total . "<br/>";
				 $totalClosedDealGift . "<br/>";
				 $APECommit . "<br/>";
				 $MetlifeMessage = "";
				//exit();
				if ( ($maxClosedDealGift*(float)$APEClosedDeal['value']) < $totalClosedDealGift) {
					 $MetlifeMessage = $MetlifeMessage . "Số lượng quà Closed Deal Gift lớn hơn " . $APEClosedDeal['key'] . " số hợp đồng dự kiến 1 <br/>";
					$validate = false;
				}

				if ($maxDoorGift < $totalDoorGift) {
					 $MetlifeMessage = $MetlifeMessage . "Số lượng quà DOOR GIFT lớn hơn lượng khách  <br/>";
					$validate = false;
				}
				
				if (((int)$APECommit*$APE['value']) <= $total) {
					 $MetlifeMessage =  $MetlifeMessage .  "Tổng tiền quà không được vượt quá " . $APE['key'] . "APE  <br/>";
					$validate = false;
				}

				if ($numberDays<=7) {
					 $MetlifeMessage =  $MetlifeMessage . "Quà có thể chuyển đến không kịp thời gian theo Quy trình phối hợp sự kiện giữa Sales và Marketing. Bạn cần xin quà trước ít nhất 7 ngày  <br/>";
				}
				
				Vtiger_Session::set('MetlifeError', $MetlifeMessage);
				if (!$validate){
					$recordId = $request->get('record');
					if (!empty($recordId)) {
						// https://redmine.innocom.vn/issues/192
						// $redirectUrl = sprintf('index.php?module=SalesOrder&view=Edit&record=%d', $recordId);
						$sessionKeyValidation = 'Leads_Validate_' . $recordId;
						Vtiger_Session::set($sessionKeyValidation, $validate);
						return $redirectUrl;
					} else {
						// https://redmine.innocom.vn/issues/192
						// $redirectUrl = 'index.php?module=SalesOrder&view=Edit';
						$sessionKeyValidation = 'Leads_Validate_New';
						Vtiger_Session::set($sessionKeyValidation, $validate);
						return $redirectUrl;
					}					
				}
				else{
					return '';
				}
				
				
			
		}
}
?>
