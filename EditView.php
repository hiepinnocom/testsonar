<?php

/*+********************************************************************************
 * The content of this file is subject to the Reports 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ********************************************************************************/


vimport('~~/modules/ITS4YouReports/ScheduledReports4You.php');

class ITS4YouReports_EditView_Model extends Vtiger_Base_Model {

        public static function ReportParams(Vtiger_Request $request, $viewer) {
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $mode = $request->get('mode');
            
            $adb = PearDatabase::getInstance();
            $current_user = Users_Record_Model::getCurrentUserModel(); 

            $viewer->assign("MODULE",$moduleName);
            
            $record = $request->get('record');
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            $viewer->assign("RECORDID",$record);
            
            $report_params = $reportModel->report->reportinformations["innoparam"];

            $viewer->assign("REPORT_INNOPARAM",$report_params);


            return $viewer->view('ReportParams.tpl', $moduleName, true);

        }
        
        public static function ReportsStep1(Vtiger_Request $request, $viewer) {
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $R_Data = $request->getAll();
            $viewer->assign("MODULE",$moduleName);
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            
            if ($request->has("reportname")) {
                $reportname = $request->get("reportname");
            }else{
                $reportname = $reportModel->getName();
            }
            $viewer->assign("REPORTNAME",$reportname);
            
            if ($request->has("reportdesc")) {
                $reportdesc = $request->get("reportdesc");
            }else{
                $reportdesc = $reportModel->getDesc();
            }
            $viewer->assign("REPORTDESC",$reportdesc);
            
            $viewer->assign("REP_MODULE",$reportModel->getPrimaryModule());

            $viewer->assign("PRIMARYMODULES",$reportModel->getPrimaryModules());
            
            $viewer->assign("REP_FOLDERS",$reportModel->getReportFolders());
            
            return $viewer->view('ReportsStep1.tpl', $moduleName, true);
        }
	
	public static function ReportGrouping(Vtiger_Request $request, $viewer) {
//error_reporting(63);ini_set("display_errors",1);
//global $adb;$adb->setDebug(true);
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $R_Data = $request->getAll();
            $viewer->assign("MODULE",$moduleName);
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            if ($request->has("primarymodule") && !$request->isEmpty("primarymodule")) {
                    $primary_moduleid = $request->get("primarymodule");
                    $primary_module = vtlib_getModuleNameById($primary_moduleid);
            }else{
                    $primary_module = $reportModel->getPrimaryModule();
                    $primary_moduleid = $reportModel->getPrimaryModuleId();
            }
            
            // $primarymodule = $reportModel->getPrimaryModule();
            // $primarymoduleid = $reportModel->getPrimaryModuleId();
            $reportModel->module_list = $reportModel->report->module_list;
            $primary_df_arr = getPrimaryTLStdFilter($primary_module,$this);
            $date_options = array();
            if(!empty($primary_df_arr)){
                foreach ($primary_df_arr as $val_arr){
                    foreach ($val_arr as $val_dtls){
                        $date_options[] = $val_dtls["value"];
                    }
                }
            }
            $date_options_json = Zend_JSON::encode($date_options);
            $viewer->assign("date_options_json", $date_options_json);

            $timelinecolumn = $reportModel->getTimeLineColumnHTML();
            $viewer->assign("timelinecolumn",$timelinecolumn);
            $Report_Informations = array();
            $secondarymodule = '';
            $secondarymodules =Array();

            if($record != ""){
                $Report_Informations = $reportModel->getReportInformations();
                if ($request->has('summaries_limit') && !$request->isEmpty('summaries_limit')) {
                        $summaries_limit = $request->get("summaries_limit");
                }else{
                    $summaries_limit = $Report_Informations["summaries_limit"];
                }
            }else{
                $summaries_limit = "20";
            }
            $viewer->assign("SUMMARIES_LIMIT",$summaries_limit);
            
            if($primary_module!=""){
                $reportModel->getPriModuleColumnsList($primary_module);
                foreach ($reportModel->report->related_modules[$primary_module] as $key => $secmodid){
                    $rp = $reportModel->report->getSecModuleColumnsList($secmodid["id"]);
                    if(!in_array($secmodid["id"],$reportModel->report->relatedmodulesarray)){
                        $reportModel->report->relatedmodulesarray[] = $secmodid["id"];
                    }
                }
            }
            
            for($tc_i=1;$tc_i<4;$tc_i++){
                $timelinecol = $selected_timeline_column = "";
                if ($request->has("group$tc_i") && !$request->isEmpty("group$tc_i")) {
                        $group = $request->get("group$tc_i");
                            $selected_timeline_column = $request->get("timeline_column$tc_i");
                }else{
                    $group = $Report_Informations["Group$tc_i"];
                    $selected_timeline_column = $Report_Informations["timeline_columnstr$tc_i"];
                }
                if(isset($selected_timeline_column) && !in_array($selected_timeline_column,array("","none","@vlv@"))){
                    $timelinecol = $reportModel->getTimeLineColumnHTML($tc_i,  $selected_timeline_column);
                    $viewer->assign("timelinecolumn".$tc_i."_html",$timelinecol);
                }
                $RG_BLOCK = getPrimaryColumns_GroupingHTML($primary_module,$group,$reportModel->report);
                
                if(!empty($reportModel->report->relatedmodulesarray)){
                    foreach($reportModel->report->relatedmodulesarray as $secmodid){
                        $secmodule_arr = explode("x", $secmodid);
                        $module_id = $secmodule_arr[0];
                        $field_id = (isset($secmodule_arr[1]) && $secmodule_arr[1] != "" ? $secmodule_arr[1] : "");
                        if($field_id!="MIF"){
                            // getSecondaryColumns_GroupingHTML($moduleid, $selected = "", $ogReport = "") -> return $shtml;
                            $RG_BLOCK .= getSecondaryColumns_GroupingHTML($secmodid,$group,$reportModel->report);
                        }
                    }
                }
                // ITS4YOU-UP SlOl |24.8.2015 11:09 
                
                // ITS4YOU-END

                $viewer->assign("RG_BLOCK$tc_i",$RG_BLOCK);
                if($tc_i>1){
                    if ($request->has("timeline_type$tc_i") && !$request->isEmpty("timeline_type$tc_i")) {
                            $timeline_type = $request->get("timeline_type$tc_i");
                    }else{
                        $timeline_type = $Report_Informations["timeline_type$tc_i"];
                    }
                    $viewer->assign("timeline_type$tc_i",$timeline_type);
                }
            }
//ITS4YouReports::sshow($RG_BLOCK);

            for($sci=1;$sci<4;$sci++){
                if ($request->has("sort$sci") && !$request->isEmpty("sort$sci")) {
                        $sortorder = $request->get("sort$sci");
                }else{
                    $sortorder = $Report_Informations["Sort".$sci];
                }
                
                $sa = $sd = "";
                if($sortorder == "Descending") {
                    $sd = " selected='selected' ";
                } elseif($sortorder == "Ascending") {
                    $ss = " selected='selected' ";
                }
                /*
                $shtml =  '<input type="radio" id="Sort'.$sci.'a" name="Sort'.$sci.'" value="Ascending" '.$sa.'>'.vtranslate('Ascending').' &nbsp; 
                          <input type="radio" id="Sort'.$sci.'d" name="Sort'.$sci.'" value="Descending" '.$sd.'>'.vtranslate('Descending');
                */
                $shtml = '<select id="Sort'.$sci.'" name="Sort'.$sci.'" class="txtBox" style="float:left;width:8em;margin:auto;" >
                    <option value="Ascending" '.$sa.' >'.vtranslate('Ascending',$moduleName).'</option>
                    <option value="Descending" '.$sd.' >'.vtranslate('Descending',$moduleName).'</option>
                </select>';
                
                $viewer->assign("ASCDESC".$sci,$shtml);
            }

            $module_id = $primary_moduleid;
            $modulename_prefix="";
            $module_array["module"]=$primary_module;
            $module_array["id"]=$module_id;
            $selectedmodule = $module_array["id"];

            $modulename = $module_array["module"];
            $modulename_lbl = getTranslatedString($modulename,$modulename);

            $availModules[$module_array["id"]] = $modulename_lbl;
            $modulename_id=$module_array["id"];
            if(isset($selectedmodule)){
                $secondarymodule_arr = $reportModel->getReportRelatedModules($module_array["id"]);
                $reportModel->getSecModuleColumnsList($selectedmodule);
                $RG_BLOCK4 = sgetSummariesHTMLOptions($module_array["id"],$module_id);
                $available_modules[]=array("id"=>$module_id,"name"=>$modulename_lbl,"checked"=>"checked");
                foreach ($secondarymodule_arr as $key=>$value) {
                    $exploded_mid = explode("x", $value["id"]);
                    if(strtolower($exploded_mid[1])!="mif"){
                        if (!$reportModel->report->in_multiarray($value["id"], $available_modules, "id")){
                            $available_modules[] = array("id"=>$value["id"],"name"=>"- ".$value["name"],"checked"=>"");
                        }
                    }
                }

                $viewer->assign("RG_BLOCK4",$RG_BLOCK4);
            }

            $viewer->assign("SummariesModules",$available_modules);
            $SumOptions = sgetSummariesOptions($selectedmodule);

            if(empty($SumOptions)){
                $SumOptions = getTranslatedString("NO_SUMMARIES_COLUMNS",$currentModule);
            }
            
            $SPSumOptions[$module_array["id"]][$module_array["id"]] = $SumOptions;
            $viewer->assign("SUMOPTIONS",$SPSumOptions);

            if ($request->has("selectedSummariesString")) {
                $selectedSummariesString = $request->get("selectedSummariesString");
                //$selectedSummariesString = str_replace("@AMPKO@", "&", $selectedSummariesString);
                $selectedSummariesString = str_replace("&", "@AMPKO@", $selectedSummariesString);
                $selectedSummariesArr = explode(";", $selectedSummariesString);
                $summaries_orderby = vtlib_purify($request->get("summaries_orderby"));
                $RG_BLOCK6 = sgetSelectedSummariesHTMLOptions($selectedSummariesArr,$summaries_orderby);
            }else{
                if(!empty($Report_Informations["summaries_columns"])){
                    foreach ($Report_Informations["summaries_columns"] as $key=>$summaries_columns_arr) {
                            $selectedSummariesArr[] = $summaries_columns_arr["columnname"];
                    }
                    if($selectedSummariesString!="")
                        $selectedSummariesString = implode(";", $selectedSummariesString);
                }
                $summaries_orderby = "";
                if(isset($Report_Informations["summaries_orderby_columns"][0]) && $Report_Informations["summaries_orderby_columns"][0]!=""){
                    $summaries_orderby = $Report_Informations["summaries_orderby_columns"][0];
                }
                $RG_BLOCK6 = sgetSelectedSummariesHTMLOptions($selectedSummariesArr,$summaries_orderby);
            }

            // sum_group_columns for group filters start
            $sm_arr = sgetSelectedSummariesOptions($selectedSummariesArr);
            $sm_str = "";
            if(!empty($sm_arr)){
                    foreach ($sm_arr as $key=>$opt_arr) {
                            if($sm_str!=""){
                                    $sm_str .= "(|@!@|)";
                            }
                            $sm_str .= $opt_arr["value"]."(|@|)".$opt_arr["text"];
                    }
            }
            $viewer->assign("sum_group_columns",$sm_str);
            // sum_group_columns for group filters end
            $viewer->assign("selectedSummariesString",$selectedSummariesString);
            $viewer->assign("RG_BLOCK6",$RG_BLOCK6);

            $RG_BLOCKx2 = array();
            $all_fields_str = "";
            foreach ($SPSumOptions AS $module_key => $SumOptions)
            {
                    $RG_BLOCKx2 = "";
                    $r_modulename = vtlib_getModuleNameById($module_key);
                    $r_modulename_lbl = getTranslatedString($r_modulename,$r_modulename); 

                    foreach ($SumOptions as $SumOptions_key=>$SumOptions_value) {
                            if(is_array($SumOptions_value)){
                                    foreach ($SumOptions_value AS $optgroup => $optionsdata)
                                    {
                                            if ($RG_BLOCKx2 != "")
                                                    $RG_BLOCKx2 .= "(|@!@|)";
                                            $RG_BLOCKx2 .= $optgroup;
                                            $RG_BLOCKx2 .= "(|@|)";

                                            $RG_BLOCKx2 .= Zend_JSON::encode($optionsdata);
                                    }
                            }else{
                                    $RG_BLOCKx2 .= $SumOptions_value;
                                    $RG_BLOCKx2 .= "(|@|)";
                                    $optionsdata[] = array("value"=>"none","text"=>getTranslatedString("LBL_NONE",$currentModule));
                                    $RG_BLOCKx2 .= Zend_JSON::encode($optionsdata);
                            }
                            $all_fields_str .= $module_key."(!#_ID@ID_#!)".$r_modulename_lbl."(!#_ID@ID_#!)".$RG_BLOCKx2;
                    }
            }
            $viewer->assign("ALL_FIELDS_STRING",$all_fields_str);
            // ITS4YOU-END 5. 3. 2014 14:50:47  SUMMARIES END

            if ($request->has("summaries_orderby") && !$request->isEmpty("summaries_orderby")) {
                    $summaries_orderby = $request->get("summaries_orderby");
                    $summaries_orderby_type = $request->get("summaries_orderby_type");
            }elseif(isset($Report_Informations["summaries_orderby_columns"]) && !empty($Report_Informations["summaries_orderby_columns"])){
                    $summaries_orderby = $Report_Informations["summaries_orderby_columns"][0]["column"];
                    $summaries_orderby_type = $Report_Informations["summaries_orderby_columns"][0]["type"];
            }else{
                    $summaries_orderby = "none";
                    $summaries_orderby_type = "ASC";
            }
            $viewer->assign("summaries_orderby",$summaries_orderby);
            $viewer->assign("summaries_orderby_type",$summaries_orderby_type);

            return $viewer->view('ReportGrouping.tpl', $moduleName, true);
        }
        
        public static function ReportColumns(Vtiger_Request $request, $viewer) {
                
                $adb = PearDatabase::getInstance();
                $moduleName = $request->getModule();

                $R_Data = $request->getAll();
                $record = $request->get('record');
                
                $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
                $primary_module = $reportModel->getPrimaryModule();
                $primary_moduleid = $reportModel->getPrimaryModuleId();
                
                $sortorder = "ASC";
                if($record!=''){
                    $BLOCK1 = getPrimaryColumnsHTML($primary_module);
                    
                    $related_modules = $reportModel->getReportRelatedModules($primary_moduleid);
                    
                    $selectedColumnsArray = $reportModel->getSelectedColumnListArray($record);
                    
                    $BLOCK2 = $reportModel->getSelectedColumnsList($selectedColumnsArray);
                    $viewer->assign("BLOCK1",$BLOCK1);
                    $viewer->assign("BLOCK2",$BLOCK2);
                    
                    // ITS4YOU-UP SlOl 13. 5. 2016 7:29:00 - sortcolsequence
                    $sreportsortsql = "SELECT sortcolsequence, columnname, sortorder FROM  its4you_reports4you_sortcol WHERE reportid =? AND sortcolid = 4 ORDER BY sortcolsequence ASC";
                    $result_sort = $adb->pquery($sreportsortsql, array($record));
                    $num_rows = $adb->num_rows($result_sort);      

                    $BLOCKS3 = $BLOCKS_ORDER3 = array();
                    $scolrow_n = $sc_i = 1;
                    if ($num_rows > 0)      
                    {
                        $sarray = $reportModel->getSelectedColumnListArray($record);
                        $BLOCK3 = $reportModel->getSelectedColumnsList($sarray);
                        while ($sc_row = $adb->fetchByAssoc($result_sort)){
                            $BLOCKS3[$sc_i] = $reportModel->getSelectedColumnsList($sarray,$sc_row["columnname"]);
                            $BLOCKS_ORDER3[$sc_i] = $sc_row["sortorder"];
                            $sc_i++;
                        }
                        $scolrow_n = count($BLOCKS3);
                    }
                    else
                    {
                        $BLOCK3 = $BLOCK2;
                    }
                    $viewer->assign("BLOCK3",$BLOCK3);
                    $viewer->assign("BLOCKS3",$BLOCKS3);
                    $viewer->assign("BLOCKS_ORDER3",$BLOCKS_ORDER3);
                    $viewer->assign("scolrow_n",$scolrow_n);
                    // ITS4YOU-END

                    $BLOCK4 = "";
                    //$BLOCK4 = getSecondaryColumnsHTML($this->relatedmodulesstring,$this);
                    //$relatedmodulesstring = $reportModel->getRelatedModulesString();
                    //$BLOCK4 = $reportModel->getSecondaryColumnsHTML($relatedmodulesstring);
                    $viewer->assign("BLOCK4",$BLOCK4);
                    $columns_limit = $reportModel->report->reportinformations["columns_limit"];
                }else
                {
                    $BLOCK1 = getPrimaryColumnsHTML($primary_module);
                    if(!empty($related_modules[$primary_module])) {
                        foreach($related_modules[$primary_module] as $key=>$value){
                            $BLOCK1 .= $reportModel->getSecondaryColumnsHTML($R_Data["secondarymodule_".$value]);
                        }
                    }
                    $viewer->assign("BLOCK1",$BLOCK1);

                    $columns_limit = "20";
                }
                $viewer->assign("COLUMNS_LIMIT",$columns_limit);

/*
                if($sortorder != "DESC")
                {
                    $shtml =  '<input type="radio" name="SortOrderColumn" value="ASC" checked>'.  vtranslate('Ascending',$moduleName).' &nbsp; 
                                <input type="radio" name="SortOrderColumn" value="DESC">'.vtranslate('Descending',$moduleName);
                }else
                {
                    $shtml =  '<input type="radio" name="SortOrderColumn" value="ASC">'.vtranslate('Ascending',$moduleName).' &nbsp; 
                                <input type="radio" name="SortOrderColumn" value="DESC" checked>'.vtranslate('Descending',$moduleName);
                }
                $viewer->assign("COLUMNASCDESC",$shtml);
*/

                $timelinecolumns .=  '<input type="radio" name="TimeLineColumn" value="DAYS" checked>'.vtranslate('TL_DAYS').' ';
                $timelinecolumns .=  '<input type="radio" name="TimeLineColumn" value="WEEK" >'.vtranslate('TL_WEEKS').' ';
                $timelinecolumns .=  '<input type="radio" name="TimeLineColumn" value="MONTH" >'.vtranslate('TL_MONTHS').' ';
                $timelinecolumns .=  '<input type="radio" name="TimeLineColumn" value="YEAR" >'.vtranslate('TL_YEARS').' ';
                $timelinecolumns .=  '<input type="radio" name="TimeLineColumn" value="QUARTER" >'.vtranslate('TL_QUARTERS').' ';
                $viewer->assign("TIMELINE_FIELDS",$timelinecolumns);

                // ITS4YOU-CR SlOl  19. 2. 2014 16:30:20 
                $SPSumOptions = $availModules = array();
                $RC_BLOCK0 = "";

                $viewer->assign("availModules",$availModules);
                $viewer->assign("ALL_FIELDS_STRING",$RC_BLOCK0);
                // ITS4YOU-END 19. 2. 2014 16:30:23
                //$viewer->assign("MODULE",$currentModule);
                
                return $viewer->view('ReportColumns.tpl', $moduleName, true);
        }
        
        
        public static function ReportColumnsTotal(Vtiger_Request $request, $viewer) {
                $adb = PearDatabase::getInstance();
                $moduleName = $request->getModule();

                $R_Data = $request->getAll();
                $record = $request->get('record');

                $viewer->assign("MODULE",$moduleName);
                
                $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
                $Report_Informations = $reportModel->getReportInformations();
                
                $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP6_INFO",$moduleName);
                $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);

                $Objects = array();

                $curl_array = array();
                if(isset($R_Data["curl"])) {
                    $curl = $R_Data["curl"];
                    $curl_array = explode('$_@_$', $curl );
                    $selectedColumnsString = str_replace("@AMPKO@", "&", $R_Data["selectedColumnsStr"]);
                    $R_Objects = explode("<_@!@_>", $selectedColumnsString);
                } else {
                    $curl_array = $reportModel->getSelectedColumnsToTotal($record);
                    $curl = implode('$_@_$', $curl_array);
                    $selectedColumnsString = str_replace("@AMPKO@", "&", $Report_Informations["selectedColumnsString"]);
					$default_charset = vglobal("default_charset");
                    $R_Objects = explode(";", html_entity_decode($selectedColumnsString, ENT_QUOTES, $default_charset));
                }
                $viewer->assign("CURL",$curl);

                $Objects = sgetNewColumnstoTotalHTMLScript($R_Objects);
                $reportModel->setColumnsSummary($Objects);
                
                $BLOCK1 = $reportModel->sgetNewColumntoTotalSelected($record,$R_Objects,$curl_array);

                $viewer->assign("RECORDID",$record);
                $viewer->assign("BLOCK1",$BLOCK1);

                $viewer->assign("display_over",$Report_Informations["display_over"]);
                $viewer->assign("display_under",$Report_Informations["display_under"]);

                //added to avoid displaying "No data avaiable to total" when using related modules in report.
                $rows_count = 0;
                $rows_count = count($BLOCK1);
                $viewer->assign("ROWS_COUNT",$rows_count);
                
                $viewer->assign("cc_populated",$request->get('cc_populated'));
                if($record=="" && $request->has("cc_populated")!=true){
                    $viewer->assign("ACT_MODE",$request->get('mode'));
                    $columns_options = ITS4YouReports::getColumnsOptions($request);
                    $viewer->assign("COLUMNS_OPTIONS",$columns_options);
                }
                
                return $viewer->view('ReportColumnsTotal.tpl', $moduleName, true);
        }
        // ITS4YOU-CR SlOl 16. 5. 2016 13:35:01 
        public static function ReportCustomCalculations(Vtiger_Request $request, $viewer) {
            $moduleName = $request->getModule();
            
            $record = $request->get('record');
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            
            $columns_options = ITS4YouReports::getColumnsOptions($request);
            $viewer->assign("COLUMNS_OPTIONS",$columns_options);
            
            $custom_calculations = $reportModel->report->reportinformations["cc_array"];
            $viewer->assign("CUSTOM_CALCULATIONS",$custom_calculations);
            
            return $viewer->view('ReportCustomCalculations.tpl', $moduleName, true);
        }
        // ITS4YOU-END
        
        public static function ReportLabels(Vtiger_Request $request, $viewer) {
                $adb = PearDatabase::getInstance();
                $moduleName = $request->getModule();

                $R_Data = $request->getAll();
                $record = $request->get('record');
                
                $viewer->assign("MODULE",$moduleName);

                // ITS4YOU-CR SlOl 10. 9. 2013 16:13:47
                $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP7_INFO", $moduleName);
                $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);
                // ITS4YOU-END 10. 9. 2013 16:13:50 

                $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
                $Report_Informations = $reportModel->getReportInformations();

                $Objects = array();

                $selected_columns_array = $selectedSummaries_array = $curl_array = array();

                // selected labels from url
                $default_charset = vglobal("default_charset");
                $lbl_url_string = html_entity_decode($R_Data["lblurl"], ENT_QUOTES, $default_charset);
                $lbl_url_string = str_replace("@AMPKO@", "&", $lbl_url_string);
                if ($lbl_url_string != "") {
                    $lbl_url_arr = explode('$_@_$', $lbl_url_string);
                    foreach ($lbl_url_arr as $key => $lbl_value) {
                        if (strpos($lbl_value, 'hidden_') === false) {
                            if (strpos($lbl_value, '_SC_lLbLl_') !== false) {
                                $temp = explode('_SC_lLbLl_', $lbl_value);
                                $temp_lbls = explode('_lLGbGLl_', $temp[1]);
                                $lbl_key = $temp_lbls[0];
                                $lbl_value = $temp_lbls[1];
                                $lbl_url_selected["SC"][$lbl_key] = $lbl_value;
                            }
                            if (strpos($lbl_value, '_SM_lLbLl_') !== false) {
                                $temp = explode('_SM_lLbLl_', $lbl_value);
                                $temp_lbls = explode('_lLGbGLl_', $temp[1]);
                                $lbl_key = $temp_lbls[0];
                                $lbl_value = $temp_lbls[1];
                                $lbl_url_selected["SM"][$lbl_key] = $lbl_value;
                            }

                            if (strpos($lbl_value, '_CT_lLbLl_') !== false) {
                                $temp = explode('_CT_lLbLl_', $lbl_value);
                                $temp_lbls = explode('_lLGbGLl_', $temp[1]);
                                $lbl_key = $temp_lbls[0];
                                $lbl_value = $temp_lbls[1];
                                $lbl_url_selected["CT"][$lbl_key] = $lbl_value;
                            }
                        }
                    }
                }
                // COLUMNS labeltype SC
                if (isset($R_Data["selectedColumnsStr"])) {
                    $selectedColumnsString = html_entity_decode($R_Data["selectedColumnsStr"], ENT_QUOTES, $default_charset);
                    $selectedColumnsString = str_replace("@AMPKO@", "&", $selectedColumnsString);
                    $selected_columns_array = explode("<_@!@_>", $selectedColumnsString);
                } else {
                    $selectedColumnsString = html_entity_decode($Report_Informations["selectedColumnsString"], ENT_QUOTES, $default_charset);
                    $selected_columns_array = explode(";", $selectedColumnsString);
                }

                for ($gi=1;$gi<4;$gi++) {
                	if ($request->has("group$gi") && !$request->isEmpty("group$gi")) {
                		$group_col = $request->get("group$gi");
                		if($group_col!=""){
                			$selected_columns_array[] = $group_col;
                		}
                	}
                }
//ITS4YouReports::sshow($lbl_url_selected);

                $labels_html["SC"] = $reportModel->report->getLabelsHTML($selected_columns_array, "SC", $lbl_url_selected);
                // SUMMARIES labeltype SM
                if (isset($R_Data["selectedSummariesString"])) {
                    $selectedColumnsString = trim($R_Data["selectedSummariesString"], ";");
                    $selectedColumnsString = str_replace("@AMPKO@", "&", $selectedColumnsString);
                    $selectedSummaries_array = explode(";", $selectedColumnsString);
                } else {
                    if(isset($Report_Informations["summaries_columns"])){
                        foreach ($Report_Informations["summaries_columns"] as $key => $sum_arr) {
                            $selectedSummaries_array[] = $sum_arr["columnname"];
                        }
                    }
                }
                $labels_html["SM"] = $reportModel->report->getLabelsHTML($selectedSummaries_array, "SM", $lbl_url_selected);

                $viewer->assign("labels_html", $labels_html);

                $viewer->assign("LABELS", $curl);

                $viewer->assign("RECORDID", $record);

                $viewer->assign("display_over", $Report_Informations["display_over"]);
                $viewer->assign("display_under", $Report_Informations["display_under"]);

                //added to avoid displaying "No data avaiable to total" when using related modules in report.
                $rows_count = count($labels_html);
                foreach ($labels_html as $key => $labels_type_arr) {
                    $rows_count += count($labels_type_arr);
                }
                $viewer->assign("ROWS_COUNT", $rows_count);

                return $viewer->view('ReportLabels.tpl', $moduleName, true);
        }
        
        public static function ReportFilters(Vtiger_Request $request, $viewer) {
            
            require_once('modules/ITS4YouReports/FilterUtils.php');
            
            $adb = PearDatabase::getInstance();
            $moduleName = $request->getModule();

            $R_Data = $request->getAll();
            $record = $request->get('record');

            $viewer->assign("MODULE",$moduleName);
            
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            $Report_Informations = $reportModel->getReportInformations();
            
            $primary_module = $reportModel->getPrimaryModule();
            $primary_moduleid = $reportModel->getPrimaryModuleId();
            
            $current_user = Users_Record_Model::getCurrentUserModel();
            
            $viewer->assign("DATEFORMAT",$current_user->date_format);
            $viewer->assign("JS_DATEFORMAT",parse_calendardate(vtranslate('NTC_DATE_FORMAT')));

            // ITS4YOU-CR SlOl 10. 9. 2013 16:13:47
            $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP7_INFO",$moduleName);
            $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);
            // ITS4YOU-END 10. 9. 2013 16:13:50 

            $BLOCK1 = "<option selected value='Not Accessible'>".vtranslate('LBL_NOT_ACCESSIBLE')."</option>";

            $user_privileges_path = 'user_privileges/user_privileges_' . $current_user->id . '.php';
            if(file_exists($user_privileges_path)) {
                require($user_privileges_path);
            }
            
            $related_modules = $reportModel->getReportRelatedModulesList();
            $advft_criteria = array();
             
            if($record!="") {
                $reportModel->getSelectedStandardCriteria($reportid);
                
                $stdselectedcolumn = $reportModel->getSTDSelectedColumn();
                $relatedmodulesstring = $reportModel->getRelatedModulesString();
                
                $BLOCK1 .= getITSPrimaryStdFilterHTML($primary_module,$stdselectedcolumn);
                $BLOCK1 .= getITSSecondaryStdFilterHTML($relatedmodulesstring,$stdselectedcolumn);

                //added to fix the ticket #5117

                $selectedcolumnvalue = '"'. $stdselectedcolumn . '"';
                if (!$is_admin && isset($stdselectedcolumn) && strpos($BLOCK1, $selectedcolumnvalue) === false)
                        $viewer->assign("BLOCK1_STD",$BLOCK1);

                $stdselectedfilter = $reportModel->getSTDSelectedFilter();
               

                $startdate = $reportModel->getStartDate();
                $enddate = $reportModel->getEndDate();
                
                if($startdate != "")
                    $viewer->assign("STARTDATE_STD",getValidDisplayDate($startdate));
                
                if($enddate != "")    
                    $viewer->assign("ENDDATE_STD",getValidDisplayDate($enddate));

                $reportModel->getGroupFilterList($reportid); 
                $reportModel->getAdvancedFilterList($reportid);
                $advft_criteria = $reportModel->getSelectedAdvancedFilter($reportid);
                
            } else {
                $primary_module = $R_Data["reportmodule"];

                $BLOCK1 .= getITSPrimaryStdFilterHTML($primary_module);
                if(!empty($related_modules[$primary_module])) {
                        foreach($related_modules[$primary_module] as $key=>$value){
                            $BLOCK1 .= getITSSecondaryStdFilterHTML($R_Data["secondarymodule_".$value]);
                        }
                }
                $viewer->assign("BLOCK1_STD",$BLOCK1);

                $stdselectedfilter = "";
            }

            $BLOCKCRITERIA = $reportModel->getSelectedStdFilterCriteria($stdselectedfilter);
            $viewer->assign("BLOCKCRITERIA_STD",$BLOCKCRITERIA);
            
            $BLOCKJS = $reportModel->getCriteriaJS();
            $viewer->assign("BLOCKJS_STD",$BLOCKJS);
            
            ///AdvancedFilter.php

            $summaries_criteria = $reportModel->getSummariesCriteria();
            
            $viewer->assign("CRITERIA_GROUPS",$advft_criteria);
            $viewer->assign("EMPTY_CRITERIA_GROUPS",empty($advft_criteria));
            $viewer->assign("SUMMARIES_CRITERIA",$summaries_criteria);
            /*
            if(isset($R_Data["mode"]) && $R_Data["mode"]!=""){
                $mode = vtlib_purify($R_Data["mode"]);
            }else{
                $mode = "generate";
            }
            */
            if ($record!="") {
                $viewer->assign('MODE', 'edit');
            } else {
            $viewer->assign('MODE', 'create');
            }
            
            $FILTER_OPTION = getAdvCriteriaHTML();
            $viewer->assign("FOPTION",$FILTER_OPTION);
            $secondarymodule = '';
            $secondarymodules =Array();

            if(!empty($related_modules[$primary_module])) {
                    foreach($related_modules[$primary_module] as $key=>$value){
                            if(isset($R_Data["secondarymodule_".$value]))$secondarymodules []= $R_Data["secondarymodule_".$value];
                    }
            }

            $reportModel->getPriModuleColumnsList($primary_module);
            if(!empty($related_modules[$primary_module])) {
                    foreach($related_modules[$primary_module] as $key=>$value){
                            $secondarymodules[]= $value["id"];
                    }
                    $secondary_modules_str = implode(":",$secondarymodules);
            }
            $reportModel->getSecModuleColumnsList($secondary_modules_str);
            
            if($mode!="ChangeSteps"){
                $Options = getPrimaryColumns($Options,$reportModel->report->primarymodule,true,$reportModel->report);
                
                $secondarymodules =Array();
                if(!empty($reportModel->report->related_modules[$reportModel->report->primarymodule])) {
                    foreach($reportModel->report->related_modules[$reportModel->report->primarymodule] as $key=>$value){
                        $exploded_mid = explode("x", $value["id"]);
                        if(strtolower($exploded_mid[1])!="mif"){
                            $secondarymodules[]= $value["id"];
                        }
                    }
                }
                $secondarymodules_str = implode(":", $secondarymodules);
                $Options_sec = getSecondaryColumns(array(),$secondarymodules_str,$reportModel->report);
                
                foreach ($Options_sec as $moduleid=>$sec_options) {
                    $Options = array_merge($Options, $sec_options);
                }
                
                // ITS4YOU-CR SlOl 16. 9. 2015 10:49:04 OTHER COLUMNS
                if (isset($R_Data["selectedColumnsStr"]) && $R_Data["selectedColumnsStr"] != "") {
                    $selectedColumnsStr = $R_Data["selectedColumnsStr"];
                    $selectedColumnsStringDecoded = html_entity_decode($selectedColumnsStr, ENT_QUOTES, $default_charset);
                    $selectedColumns_arr = explode("<_@!@_>", $selectedColumnsStringDecoded);
                } else {
                    $selectedColumnsStr = $reportModel->report->reportinformations["selectedColumnsString"];
                    // $selectedColumnsStringDecoded = html_entity_decode($selectedColumnsStr, ENT_QUOTES, $default_charset);
                    $selectedColumnsStringDecoded = $selectedColumnsStr;
                    $selectedColumns_arr = explode(";", $selectedColumnsStringDecoded);
                }
                if(!empty($selectedColumns_arr)){
                    $opt_label = vtranslate("LBL_Filter_SelectedColumnsGroup", "ITS4YouReports");
                    foreach ($selectedColumns_arr as $sc_key => $sc_col_str) {
                        if ($sc_col_str != "") {
                            $in_options = false;
                            foreach ($Options as $opt_group => $opt_array) {
                                if ($reportModel->report->in_multiarray($sc_col_str, $opt_array, "value") === true) {
                                    $in_options = true;
                                    continue;
                                }
                            }
                            if ($in_options) {
                                continue;
                            } else {
                                $Options[$opt_label][] = array("value" => $sc_col_str, "text" => $reportModel->report->getColumnStr_Label($sc_col_str));
                            }
                        }
                    }
                }
                // ITS4YOU-END 
                
                foreach ($Options AS $optgroup => $optionsdata)
                {
                        if ($COLUMNS_BLOCK_JSON != "")
                                $COLUMNS_BLOCK_JSON .= "(|@!@|)";
                        $COLUMNS_BLOCK_JSON .= $optgroup;
                        $COLUMNS_BLOCK_JSON .= "(|@|)";
                        $COLUMNS_BLOCK_JSON .= Zend_JSON::encode($optionsdata);
                }
                $viewer->assign("COLUMNS_BLOCK_JSON",$COLUMNS_BLOCK_JSON);
                
                $adv_sel_fields = $reportModel->getAdvSelFields();
                
                $sel_fields = Zend_Json::encode($adv_sel_fields);
                $viewer->assign("SEL_FIELDS",$sel_fields);
           
                $default_charset = vglobal("default_charset");
                $std_filter_columns = $reportModel->getStdFilterColumns();
                $std_filter_columns_js = implode("<%jsstdjs%>", $std_filter_columns);
                $std_filter_columns_js = html_entity_decode($std_filter_columns_js, ENT_QUOTES, $default_charset);

                $viewer->assign("std_filter_columns", $std_filter_columns_js);
                
                $Date_Filter_Values = $reportModel->getDateFilterValues();
                
                // ITS4YOU-UP SlOl 19. 11. 2015 10:46:35
                foreach($Date_Filter_Values as $std_opt => $std_val){
                    $Date_Filter_Values[$std_opt] = vtranslate($std_val,$reportModel->report->currentModule);
                }
                // ITS4YOU-END
                $std_filter_criteria = Zend_Json::encode($Date_Filter_Values);
                $viewer->assign("std_filter_criteria", $std_filter_criteria);
            }
            $rel_fields = $reportModel->getAdvRelFields();
            $viewer->assign("REL_FIELDS",Zend_Json::encode($rel_fields));                
            /*NEWS*/
//error_reporting(63);ini_set("display_errors",1);
            $primary_module = $reportModel->report->primarymodule;
            $primary_moduleid = $reportModel->report->primarymoduleid;
        
            // NEW ADVANCE FILTERS START
            $reportModel->report->getGroupFilterList($reportModel->report->record); 
            $reportModel->report->getAdvancedFilterList($reportModel->report->record);
            $reportModel->report->getSummariesFilterList($reportModel->report->record);

            $sel_fields = Zend_Json::encode($reportModel->report->adv_sel_fields);
            $viewer->assign("SEL_FIELDS", $sel_fields);
            if (isset($_REQUEST["reload"])) {
                $criteria_groups = $reportModel->report->getRequestCriteria($sel_fields);
            }else{
                $criteria_groups = $reportModel->report->advft_criteria;
            }
            
            $viewer->assign("CRITERIA_GROUPS",$criteria_groups);
            $viewer->assign("EMPTY_CRITERIA_GROUPS",empty($criteria_groups));

            $viewer->assign("SUMMARIES_CRITERIA",$reportModel->report->summaries_criteria);
            $FILTER_OPTION = getAdvCriteriaHTML();
            $viewer->assign("FOPTION",$FILTER_OPTION);

            $COLUMNS_BLOCK_JSON = $reportModel->report->getAdvanceFilterOptionsJSON($primary_module);
            $viewer->assign("COLUMNS_BLOCK", $COLUMNS_BLOCK);
            if($mode!="ajax"){
                //echo "<div class='none' style='display:none;' id='filter_columns'>" . $COLUMNS_BLOCK_JSON . "</div>";
                //echo "<input type='hidden' name='filter_columns' id='filter_columns' value='".$COLUMNS_BLOCK_JSON."' />";
                $viewer->assign("filter_columns",$COLUMNS_BLOCK_JSON);
                $sel_fields = Zend_Json::encode($reportModel->report->adv_sel_fields);
                $viewer->assign("SEL_FIELDS",$sel_fields);
                global $default_charset;
                $std_filter_columns = $reportModel->report->getStdFilterColumns();
                $std_filter_columns_js = implode("<%jsstdjs%>", $std_filter_columns);
                $std_filter_columns_js = html_entity_decode($std_filter_columns_js, ENT_QUOTES, $default_charset);
                $viewer->assign("std_filter_columns", $std_filter_columns_js);
                // ITS4YOU-UP SlOl 19. 11. 2015 10:46:35
                $Date_Filter_Values = $reportModel->report->Date_Filter_Values;
                foreach($Date_Filter_Values as $std_opt => $std_val){
                    $Date_Filter_Values[$std_opt] = vtranslate($std_val,$reportModel->report->currentModule);
                }
                // ITS4YOU-END
                $std_filter_criteria = Zend_Json::encode($Date_Filter_Values);
                $viewer->assign("std_filter_criteria", $std_filter_criteria);
            }
            $rel_fields = $reportModel->report->adv_rel_fields;
            $rel_fields = Zend_Json::encode($rel_fields);
            $rel_fields = str_replace("'", "\'", $rel_fields);
            $viewer->assign("REL_FIELDS",$rel_fields);
            // NEW ADVANCE FILTERS END

            $BLOCKJS = $reportModel->getCriteriaJS();
            $viewer->assign("BLOCKJS_STD",$BLOCKJS);
            /*NEWE*/
            
            // ITS4YOU-CR SlOl 23. 11. 2015 14:42:35
            $viewer->assign("current_mk_time",mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            global $current_user;
            $viewer->assign("user_date_format",$current_user->date_format);
            // ITS4YOU-CR SlOl 9. 6. 2016 7:04:45
            $viewer->assign("fld_date_options",Zend_Json::encode(ITS4YouReports::$fld_date_options));
            // ITS4YOU-END 
            
            return $viewer->view('ReportFilters.tpl', $moduleName, true);
        }
        
        public static function ReportFiltersAjax(Vtiger_Request $request) {
            
            $BLOCK_R = $BLOCK1 = $BLOCK2 = '';

            $Options = array();
            $secondarymodule = '';
            $secondarymodules =Array();

            $record = $request->get('record');
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);

            $primary_moduleid = $request->get("primarymodule");
            $primary_module = vtlib_getModuleNameById($primary_moduleid);
            
            //$reportModel->report->init_list_for_module($primary_module);
            $related_modules = $reportModel->getReportRelatedModulesList();
            
            if(!empty($related_modules[$primary_module])) {
                    foreach($related_modules[$primary_module] as $key=>$value){
                        $exploded_mid = explode("x", $value["id"]);
                        if(strtolower($exploded_mid[1])!="mif"){
                            $secondarymodules[]= $value["id"];
                        }
                    }
            }
            $Options = $reportModel->getPrimaryColumns($Options,$primary_module,true);
            // $Options = array_merge(array(vtranslate("LBL_NONE")=>array("0"=>array("value"=>"","text"=>vtranslate("LBL_NONE"),))), $p_options); 
            
            $secondarymodules_str = implode(":", $secondarymodules);

            $reportModel->getSecModuleColumnsList($secondarymodules_str);
            $Options_sec = $reportModel->getSecondaryColumns(array(),$secondarymodules_str);

            foreach ($Options_sec as $moduleid=>$sec_options) {
                    $Options = array_merge($Options, $sec_options);
            }
            
            // ITS4YOU-CR SlOl 16. 9. 2015 10:49:04 OTHER COLUMNS
            if ($request->has("selectedColumnsStr") && $request->get("selectedColumnsStr") != "") {
                $selectedColumnsStr = $request->get("selectedColumnsStr");
                $selectedColumnsStringDecoded = html_entity_decode($selectedColumnsStr, ENT_QUOTES, $default_charset);
                $selectedColumns_arr = explode("<_@!@_>", $selectedColumnsStringDecoded);
            } else {
                $selectedColumnsStr = $reportModel->report->reportinformations["selectedColumnsString"];
                $selectedColumnsStringDecoded = html_entity_decode($selectedColumnsStr, ENT_QUOTES, $default_charset);
                $selectedColumns_arr = explode(";", $selectedColumnsStringDecoded);
            }
            if(!empty($selectedColumns_arr)){
                $opt_label = vtranslate("LBL_Filter_SelectedColumnsGroup", "ITS4YouReports");
                foreach ($selectedColumns_arr as $sc_key => $sc_col_str) {
                    if ($sc_col_str != "") {
                        $in_options = false;
                        foreach ($Options as $opt_group => $opt_array) {
                            if ($reportModel->report->in_multiarray($sc_col_str, $opt_array, "value") === true) {
                                $in_options = true;
                                continue;
                            }
                        }
                        if ($in_options) {
                            continue;
                        } else {
                            $Options[$opt_label][] = array("value" => $sc_col_str, "text" => $reportModel->report->getColumnStr_Label($sc_col_str));
                        }
                    }
                }
            }
            // ITS4YOU-END 
            
            foreach ($Options AS $optgroup => $optionsdata)
            {
                    if ($BLOCK1 != "")
                            $BLOCK1 .= "(|@!@|)";
                    $BLOCK1 .= $optgroup;
                    $BLOCK1 .= "(|@|)";
                    $BLOCK1 .= Zend_JSON::encode($optionsdata);
            }
            $BLOCK_R .= $BLOCK1;

            // ITS4YOU-CR SlOl 21. 3. 2014 10:20:17 summaries columns for frouping filters start
            $selectedSummariesString = $request->get("selectedSummariesString");
            $selectedSummariesArr = explode(";", $selectedSummariesString);
            $sm_arr = sgetSelectedSummariesOptions($selectedSummariesArr);
            $sm_str = "";
            foreach ($sm_arr as $key=>$opt_arr) {
                    if($sm_str!=""){
                            $sm_str .= "(|@!@|)";
                    }
                    $sm_str .= $opt_arr["value"]."(|@|)".$opt_arr["text"];
            }
            $BLOCK_S = $sm_str;
            $BLOCK_R .= "__BLOCKS__".$BLOCK_S;
            
            $Report_Informations = $reportModel->getReportInformations();
            if(isset($Report_Informations["advft_criteria"]) && $Report_Informations["advft_criteria"]!=""){
                $advft_criteria = $Report_Informations["advft_criteria"];
            }else{
                $advft_criteria = "";
            }
            $BLOCK_R .= "__ADVFTCRI__".Zend_JSON::encode($advft_criteria);
            
            $adv_sel_fields = $reportModel->getAdvSelFields();
            $sel_fields = Zend_Json::encode($adv_sel_fields);
            $BLOCK_R .= "__ADVFTCRI__".$sel_fields;
            
            $default_charset = vglobal("default_charset");
            $std_filter_columns = $reportModel->getStdFilterColumns();
            $std_filter_columns_js = implode("<%jsstdjs%>", $std_filter_columns);
            $std_filter_columns_js = html_entity_decode($std_filter_columns_js, ENT_QUOTES, $default_charset);
            $BLOCK_R .= "__ADVFTCRI__".$std_filter_columns_js;

            return $BLOCK_R;
    }
    
    public static function ReportSharing(Vtiger_Request $request, $viewer) {

            $moduleName = $request->getModule();
            $record = $request->get('record');
            $R_Data = $request->getAll();
            $viewer->assign("MODULE",$moduleName);

            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);

            $primary_module = $reportModel->getPrimaryModule();
            $primary_moduleid = $reportModel->getPrimaryModuleId();

            $current_user = Users_Record_Model::getCurrentUserModel();        
            
            //$Report_Informations = $reportModel->getReportInformations();
            $Report_Informations = $reportModel->report->reportinformations;

            $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP8_INFO",$moduleName);
            $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);

            //require_once('include/utils/GetUserGroups.php');

            // ITS4YOU-UP SlOl 26. 4. 2013 9:47:59
            $template_owners = get_user_array(false);
            if(isset($Report_Informations["owner"]) && $Report_Informations["owner"]!=""){
                $selected_owner = $Report_Informations["owner"];
            }else{
                $selected_owner = $current_user->id;
            }
            $viewer->assign("TEMPLATE_OWNERS", $template_owners);
            $owner = (isset($R_Data['template_owner'])&&$R_Data['template_owner']!='')?$R_Data['template_owner']:$selected_owner;
            $viewer->assign("TEMPLATE_OWNER", $owner);

            $sharing_types = Array("public"=>vtranslate("PUBLIC_FILTER", $moduleName),
                                   "private"=>vtranslate("PRIVATE_FILTER", $moduleName),
                                   "share"=>vtranslate("SHARE_FILTER", $moduleName));
            $viewer->assign("SHARINGTYPES", $sharing_types);

            if($request->get('reporttype')=="custom_report"){
                $sharingtype = "private";
            }else{
                $sharingtype = "public";
            }
            if(isset($R_Data['sharing']) && $R_Data['sharing']!=''){
                $sharingtype = $R_Data['sharing'];
            }elseif(isset($Report_Informations["sharing"]) && $Report_Informations["sharing"]!=""){
                $sharingtype = $Report_Informations["sharing"];
            }

            $viewer->assign("SHARINGTYPE", $sharingtype);

            //$cmod = return_specified_module_language($current_language, "Settings");
            //$viewer->assign("CMOD", $cmod);

            $sharingMemberArray = array();
            if(isset($R_Data['sharingSelectedColumns'])&&$R_Data['sharingSelectedColumns']!=''){
                $sharingMemberArray = explode("|", trim($R_Data['sharingSelectedColumns'],"|"));
            }elseif(isset($Report_Informations["members_array"]) && !empty($Report_Informations["members_array"])){
                $sharingMemberArray = $Report_Informations["members_array"];
            }

            $sharingMemberArray = array_unique($sharingMemberArray);
            if(count($sharingMemberArray) > 0)
            {
                $outputMemberArr = array();
                foreach($sharingMemberArray as $setype=>$shareIdArr)
                {
                    $shareIdArr = explode("::", $shareIdArr);
                    $shareIdArray = array();
                    $shareIdArray[$shareIdArr[0]] = $shareIdArr[1];
                    foreach($shareIdArray as $shareType=>$shareId)
                    {
                        switch($shareType)
                        {
                            case "groups":
                                $groupArray = getGroupName($shareId);
                                $memberName=$groupArray[0];
                                $memberDisplay="Group::";
                            break;
                            case "roles":
                                $memberName=getRoleName($shareId);
                                $memberDisplay="Roles::";
                            break;
                            case "rs":
                                $memberName=getRoleName($shareId);
                                $memberDisplay="RoleAndSubordinates::";
                            break;
                            case "users":
                                $memberName=getUserFullName($shareId);
                                $memberDisplay="User::";
                            break;
                        }
                        $outputMemberArr[] = $shareType."::".$shareId;
                        $outputMemberArr[] = $memberDisplay.$memberName;
                    }
                }
                $viewer->assign("MEMBER", array_chunk($outputMemberArr,2));
            }
            // ITS4YOU-END

//Constructing the Role Array
            $roleDetails = getAllRoleDetails();
            $i = 0;
            $roleIdStr = "";
            $roleNameStr = "";
            $userIdStr = "";
            $userNameStr = "";
            $grpIdStr = "";
            $grpNameStr = "";

            foreach ($roleDetails as $roleId => $roleInfo) {
                if ($i != 0) {
                    if ($i != 1) {
                        $roleIdStr .= ", ";
                        $roleNameStr .= ", ";
                    }
                    $roleName = $roleInfo[0];
                    $roleIdStr .= "'" . $roleId . "'";
                    $roleNameStr .= "'" . addslashes(decode_html($roleName)) . "'";
                }
                $i++;
            }
//Constructing the User Array
            $l = 0;
            $userDetails = getAllUserName();
            foreach ($userDetails as $userId => $userInfo) {
                if ($l != 0) {
                    $userIdStr .= ", ";
                    $userNameStr .= ", ";
                }
                $userIdStr .= "'" . $userId . "'";
                $userNameStr .= "'" . $userInfo . "'";
                $l++;
            }
//Constructing the Group Array
            $parentGroupArray = array();

            $m = 0;
            $grpDetails = getAllGroupName();
            foreach ($grpDetails as $grpId => $grpName) {
                if (!in_array($grpId, $parentGroupArray)) {
                    if ($m != 0) {
                        $grpIdStr .= ", ";
                        $grpNameStr .= ", ";
                    }
                    $grpIdStr .= "'" . $grpId . "'";
                    $grpNameStr .= "'" . addslashes(decode_html($grpName)) . "'";
                    $m++;
                }
            }
            $viewer->assign("ROLEIDSTR", $roleIdStr);
            $viewer->assign("ROLENAMESTR", $roleNameStr);
            $viewer->assign("USERIDSTR", $userIdStr);
            $viewer->assign("USERNAMESTR", $userNameStr);
            $viewer->assign("GROUPIDSTR", $grpIdStr);
            $viewer->assign("GROUPNAMESTR", $grpNameStr);

            $visiblecriteria = $reportModel->getVisibleCriteria();
            $viewer->assign("VISIBLECRITERIA", $visiblecriteria);

            return $viewer->view('ReportSharing.tpl', $moduleName, true);
    }
    
    public static function ReportScheduler(Vtiger_Request $request, $viewer) {
                            
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $mode = $request->get('mode');
            
            $adb = PearDatabase::getInstance();
            $current_user = Users_Record_Model::getCurrentUserModel(); 

            $viewer->assign('CURRENT_USER', $current_user); 

            $viewer->assign("MODULE",$moduleName);
            
            $record = $request->get('record');
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            
            /* SCHEDULE REPORTS START */
            $availableUsersHTML = ITS4YouScheduledReport::getAvailableUsersHTML();
            $availableGroupsHTML = ITS4YouScheduledReport::getAvailableGroupsHTML();
            $availableRolesHTML = ITS4YouScheduledReport::getAvailableRolesHTML();
            $availableRolesAndSubHTML = ITS4YouScheduledReport::getAvailableRolesAndSubordinatesHTML();

            $viewer->assign("AVAILABLE_USERS", $availableUsersHTML);
            $viewer->assign("AVAILABLE_GROUPS", $availableGroupsHTML);
            $viewer->assign("AVAILABLE_ROLES", $availableRolesHTML);
            $viewer->assign("AVAILABLE_ROLESANDSUB", $availableRolesAndSubHTML);

            $scheduledReport = new ITS4YouScheduledReport($adb, $current_user, $record);

            if($mode=="ChangeSteps"){
                    $scheduledReport->getReportScheduleInfo();
                    $is_scheduled = $request->get('isReportScheduled');
                    $report_format = $request->get('scheduledReportFormat');
                    $selectedRecipientsHTML = $scheduledReport->getSelectedRecipientsHTML();
            }else{
                    $scheduledReport->getReportScheduleInfo();
                    $is_scheduled = $scheduledReport->isScheduled;
                    $report_format = explode(";", $scheduledReport->scheduledFormat);
                    $selectedRecipientsHTML = $scheduledReport->getSelectedRecipientsHTML();
            }
            $viewer->assign('IS_SCHEDULED', $is_scheduled);
            foreach($report_format as $sh_format){
                $viewer->assign("REPORT_FORMAT_".strtoupper($sh_format), true);
            }

            $viewer->assign("SELECTED_RECIPIENTS", $selectedRecipientsHTML);
            
            $result = $adb->pquery('SELECT generate_subject, generate_text, generate_other, schedule_all_records FROM  its4you_reports4you_scheduled_reports WHERE reportid=?', array($record));
            $generateInfo = array();
            $generate_subject = $generate_text = $generate_other = "";
            if($adb->num_rows($result) > 0) {
                $generateInfo = $adb->raw_query_result_rowdata($result, 0);
                $generate_subject = $generateInfo["generate_subject"];
                $generate_text = trim($generateInfo["generate_text"]);
                $generate_other = $generateInfo["generate_other"];
                $schedule_all_records = $generateInfo["schedule_all_records"];
            }
            $viewer->assign("generate_subject",$generate_subject);
            $viewer->assign("generate_text",$generate_text);
            $viewer->assign("generate_other",$generate_other);
            $viewer->assign("schedule_all_records",$schedule_all_records);
            
            $selectedForOptions = ITS4YouReports::getSchedulerGenerateFor($record);
            $viewer->assign("selectedForOptions",$selectedForOptions);

            $viewer->assign("schtypeid",$scheduledReport->scheduledInterval['scheduletype']);
            $viewer->assign("schtime",$scheduledReport->scheduledInterval['time']);
            $viewer->assign("schday",$scheduledReport->scheduledInterval['date']);
            $viewer->assign("schweek",$scheduledReport->scheduledInterval['day']);
            $viewer->assign("schmonth",$scheduledReport->scheduledInterval['month']);
            /* SCHEDULE REPORTS END */

            $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP9_INFO",$moduleName);
            $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);

            if($mode=="ChangeSteps"){
                    $tpl_name = "ReportSchedulerContent.tpl";
            }else{
                    $tpl_name = "ReportScheduler.tpl";
            }
            
            // ITS4YOU-CR SlOl 1. 4. 2016 14:16:03
            $primary_module = $reportModel->getPrimaryModule();
            $generateForOptions = ITS4YouReports_EditView_Model::getGenerateForOptionsArray($reportModel,$primary_module);
            $viewer->assign('generateForOptions', $generateForOptions);
            // ITS4YOU-END 
            
            return $viewer->view($tpl_name, $moduleName, true);
    }
    
    public static function ReportGraphs(Vtiger_Request $request, $viewer) {
            
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $R_Data = $request->getAll();
           
            $viewer->assign("MODULE",$moduleName);
            
            $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP12_INFO",$moduleName);
            $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);
            // ITS4YOU-END 10. 9. 2013 16:13:50 
            
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            $Report_Informations = $reportModel->getReportInformations();
            if(!empty($Report_Informations["charts"])){
                $charts_array = $Report_Informations["charts"];
            }
            
            if(isset($R_Data["chartType1"])){
                for($chi=1;$chi<4;$chi++){
                	if(isset($R_Data["chartType$chi"])){
                        $charts_array[$chi]['charttype'] = $R_Data["chartType$chi"];
                        $charts_array[$chi]['dataseries'] = $R_Data["data_series$chi"];
                    }
                }
            }
            
            if(isset($R_Data["chart_type"]) && $R_Data["chart_type"]!="" && $R_Data["chart_type"]!="none"){
                $selected_chart_type = $R_Data["chart_type"];
            }else{
                $selected_chart_type = $Report_Informations["charts"]["charttype"];
            }
            $viewer->assign("IMAGE_PATH",$chart_type);
            if(isset($R_Data["data_series"]) && $R_Data["data_series"]!=""){
                $selected_data_series = $R_Data["data_series"];
            }else{
                $selected_data_series = $Report_Informations["charts"];
            }
            if(isset($R_Data["charttitle"]) && $R_Data["charttitle"]!=""){
                $selected_charttitle = $R_Data["charttitle"];
            }else{
                $selected_charttitle = $Report_Informations["charts"][1]["charttitle"];
            }
            $viewer->assign("charttitle",$selected_charttitle);

            $chart_type["horizontal"]=array("value"=>vtranslate("LBL_CHART_horizontal",$moduleName),"selected"=>($selected_chart_type=="horizontal"?"selected":""));
            $chart_type["horizontalstacked"] = array("value" => vtranslate("LBL_CHART_horizontalstacked", $moduleName), "selected" => ($selected_chart_type == "horizontalstacked" ? "selected" : ""));
            $chart_type["vertical"]=array("value"=>vtranslate("LBL_CHART_vertical",$moduleName),"selected"=>($selected_chart_type=="vertical"?"selected":""));
            $chart_type["verticalstacked"] = array("value" => vtranslate("LBL_CHART_verticalstacked", $moduleName), "selected" => ($selected_chart_type == "verticalstacked" ? "selected" : ""));
            $chart_type["linechart"]=array("value"=>vtranslate("LBL_CHART_linechart",$moduleName),"selected"=>($selected_chart_type=="linechart"?"selected":""));
            $chart_type["pie"]=array("value"=>vtranslate("LBL_CHART_pie",$moduleName),"selected"=>($selected_chart_type=="pie"?"selected":""));
            //$chart_type["pie3d"]=array("value"=>vtranslate("LBL_CHART_pie3D",$moduleName),"selected"=>($selected_chart_type=="pie3d"?"selected":""));
            $chart_type["funnel"]=array("value"=>vtranslate("LBL_CHART_funnel",$moduleName),"selected"=>($selected_chart_type=="funnel"?"selected":""));
            $viewer->assign("CHART_TYPE",$chart_type);
            // column
            // bar
            // line
            // pie
            // funnel

            // selected labels from url
            if(isset($R_Data["lblurl"])){
                $lbl_url_string = html_entity_decode($R_Data["lblurl"]);
            }

            $lbl_url_string = str_replace("@AMPKO@", "&", $lbl_url_string);
            if ($lbl_url_string != "") {
                $lbl_url_arr = explode('$_@_$', $lbl_url_string);
                foreach ($lbl_url_arr as $key => $lbl_value) {
                    if (strpos($lbl_value, 'hidden_') === false) {
                        if (strpos($lbl_value, '_SC_lLbLl_') !== false) {
                            $temp = explode('_SC_lLbLl_', $lbl_value);
                            $temp_lbls = explode('_lLGbGLl_', $temp[1]);
                            $lbl_key = $temp_lbls[0];
                            $lbl_value = $temp_lbls[1];
                            $lbl_url_selected["SC"][$lbl_key] = $lbl_value;
                        }
                        if (strpos($lbl_value, '_SM_lLbLl_') !== false) {
                            $temp = explode('_SM_lLbLl_', $lbl_value);
                            $temp_lbls = explode('_lLGbGLl_', $temp[1]);
                            $lbl_key = $temp_lbls[0];
                            $lbl_value = $temp_lbls[1];
                            $lbl_url_selected["SM"][$lbl_key] = $lbl_value;
                        }

                        if (strpos($lbl_value, '_CT_lLbLl_') !== false) {
                            $temp = explode('_CT_lLbLl_', $lbl_value);
                            $temp_lbls = explode('_lLGbGLl_', $temp[1]);
                            $lbl_key = $temp_lbls[0];
                            $lbl_value = $temp_lbls[1];
                            $lbl_url_selected["CT"][$lbl_key] = $lbl_value;
                        }
                    }
                }
            }
/*
            global $current_language;
            $group_string = "";
            if(isset($R_Data["selectedSummariesString"])!=""){
                $selectedSummariesArray = explode(";", trim($R_Data["selectedSummariesString"],";"));
                
                if(!empty($selectedSummariesArray)){
                    foreach($selectedSummariesArray as $column_str){
                        if($column_str!=""){
                            $column_str = str_replace("@AMPKO@", "&", $column_str);
                            $column_lbl = $reportModel->report->getColumnStr_Label($column_str,"SM");
                            $data_series[$column_str] = array("value"=>$column_lbl,"selected"=>($column_str==$selected_data_series?"selected":""));
                        }
                        /*if($column_str!=""){
                            if(isset($lbl_url_selected["SM"][$column_str]) && $lbl_url_selected["SM"][$column_str]!=""){
                                $column_lbl = $lbl_url_selected["SM"][$column_str];
                            }else{
                                $column_str_arr = explode(":", $column_str);
                                $translate_arr = explode("_", $column_str_arr[2]);
                                $translate_module = $translate_arr[0];
                                unset($translate_arr[0]);
                                $translate_str = implode("_", $translate_arr);
                                $translate_mod_str = return_module_language($current_language, $translate_module);
                                if(isset($translate_mod_str[$translate_str])){
                                    $column_lbl = $translate_mod_str[$translate_str];
                                }else{
                                    $column_lbl = str_replace("_", " ", $translate_str);
                                }
                            }
                            $data_series[$column_str] = array("value"=>$column_lbl,"selected"=>($column_str==$selected_data_series?"selected":""));
                        }* /
                    }
                }
            }
*/
/*
            if(empty($data_series) && $selected_data_series!="" && in_array($selected_data_series, $selectedSummariesArray)){
                $column_lbl = $reportModel->getColumnStr_Label($selected_data_series,"SM");
                $data_series[$selected_data_series] = array("value"=>$column_lbl,"selected"=>"selected");
            }*/
//            $viewer->assign("DATA_SERIES",$data_series);

            // NEW WAY
            $viewer->assign("CHARTS_ARRAY",$charts_array);
            
            if ($request->has("selectedSummariesString")) {
                $selectedSummariesString = $request->get("selectedSummariesString");
                $selectedSummariesString = str_replace("@AMPKO@", "&", $selectedSummariesString);
                $r_selectedSummariesArr = explode(";", $selectedSummariesString);
                foreach ($r_selectedSummariesArr as $key=>$summaries_col_str) {
                    if($summaries_col_str!=""){
                        $selectedSummariesArr[] = array("value"=>$summaries_col_str,"label"=>$reportModel->report->getColumnStr_Label($summaries_col_str,"SM"));
                    }
                }
            }else{
                if(!empty($Report_Informations["summaries_columns"])){
                    foreach ($Report_Informations["summaries_columns"] as $key=>$summaries_columns_arr) {
                        $selectedSummariesArr[] = array("value"=>$summaries_columns_arr["columnname"],"label"=>$reportModel->report->getColumnStr_Label($summaries_columns_arr["columnname"],"SM"));
                    }
                }
            }
            if(empty($selectedSummariesArr)){
                $primarymodule = $reportModel->report->primarymodule;
                $crmid_count_str = "vtiger_crmentity:crmid:" . $primarymodule . "_COUNT Records:" . $primarymodule . "_count:V";
                $selectedSummariesArr[] = array("value"=>$crmid_count_str,"label"=>$reportModel->getColumnStr_Label($crmid_count_str,"SM"));
            }
            $viewer->assign("selected_summaries",$selectedSummariesArr);
            
            $group_string = "";
            
            if(isset($R_Data["group1"])){
                for($gi=1;$gi<3;$gi++){
                    if(isset($R_Data["group".$gi]) && $R_Data["group".$gi]!="none"){
                        if($group_string!=""){
                            $group_string .= " - ";
                        }
                        $group_string .= $reportModel->getColumnStr_Label($R_Data["group".$gi]);
                        $x_group["group".$gi]['value'] = $group_string;
                        if("group".$gi == $R_Data["x_group"]){
                            $x_group["group".$gi]['selected'] = " selected='selected' ";
                        }
                    }
                }
            }else{
                for($gi=1;$gi<3;$gi++){
                    if(isset($Report_Informations["Group".$gi]) && $Report_Informations["Group".$gi]!="none"){
                        if($group_string!=""){
                            $group_string .= " - ";
                        }
                        $group_string .= $reportModel->getColumnStr_Label($Report_Informations["Group".$gi]);
                        // $x_group[$Report_Informations["Group".$gi]]['value'] = $group_string;
                        $x_group["group".$gi]['value'] = $group_string;
                        if("group".$gi == $Report_Informations["charts"][1]["x_group"]){
                            $x_group["group".$gi]['selected'] = " selected='selected' ";
                        }
                    }
                }
            }
            
            $viewer->assign("X_GROUP",$x_group);
            $viewer->assign("X_GROUP_COUNT",count($x_group));
                
            return $viewer->view("ReportGraphs.tpl", $moduleName, true);
    }
    
    // ITS4YOU-CR SlOl 18. 3. 2016 10:41:34 
    public static function ReportDashboards(Vtiger_Request $request, $viewer) {
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $R_Data = $request->getAll();
           
        $viewer->assign("MODULE",$moduleName);
        
        $LBL_INFORMATIONS_4YOU = vtranslate("LBL_STEP13INFO",$moduleName);
        $viewer->assign("LBL_INFORMATIONS_4YOU", $LBL_INFORMATIONS_4YOU);
        // ITS4YOU-END 10. 9. 2013 16:13:50 
        
        $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
        if ($request->has("primarymodule") && !$request->isEmpty("primarymodule")) {
            $primary_moduleid = $request->get("primarymodule");
            $primary_module = vtlib_getModuleNameById($primary_moduleid);
        }else{
            $primary_module = $reportModel->getPrimaryModule();
            $primary_moduleid = $reportModel->getPrimaryModuleId();
        }

        $Report_Informations = $reportModel->getReportInformations();
        
        $viewer->assign("primary_search",$Report_Informations["primary_search"]);
        
        $pSearchOptions = $Options = array();
        $Options = getPrimaryColumns($Options,$primary_module,true,$reportModel->report);
        if(!empty($Options)){
            foreach($Options as $blockName => $columnsArray){
                foreach($columnsArray as $c_arr){
                    $column_str = $c_arr['value'];
                    $sc_field_uitype = ITS4YouReports::getUITypeFromColumnStr($column_str);
                    if(in_array($sc_field_uitype,ITS4YouReports::$s_uitypes)){
                        $pSearchOptions[] = $c_arr;
                    }
                }
            }
        }
        $viewer->assign("primary_search_options",$pSearchOptions);
        
        // ITS4YOU-CR SlOl 21. 3. 2016 8:36:16
        $adb = PearDatabase::getInstance();
        $allModules = array("Home"=>vtranslate("Home"));
        
        $allModulesTemp = com_vtGetModules($adb);
        if(!empty($allModulesTemp)){
            asort($allModulesTemp);
            foreach($allModulesTemp as $mName => $mLabel){
                $allModules[$mName] = vtranslate($mLabel,$mName);
            }
        }
        $viewer->assign("allmodules",$allModules);
        $allowedModules = array();
        if($Report_Informations["allow_in_modules"]!=""){
            $allowedModules = explode(',', $Report_Informations["allow_in_modules"]);
        }else{
            $allowedModules[] = "";
        }
        $viewer->assign("allowedmodules",$allowedModules);
        // ITS4YOU-END 
        
        return $viewer->view("ReportDashboards.tpl", $moduleName, true);
    }
    // ITS4YOU-END
    
    public static function ReportCustomSql(Vtiger_Request $request, $viewer){
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $mode = $request->get('mode');
            
            $adb = PearDatabase::getInstance();
            $current_user = Users_Record_Model::getCurrentUserModel(); 

            $viewer->assign("MODULE",$moduleName);
            
            $record = $request->get('record');
            $reportModel = ITS4YouReports_Record_Model::getCleanInstance($record);
            $viewer->assign("RECORDID",$record);
            
            $report_custom_sql = ITS4YouReports::validateCustomSql($reportModel->report->reportinformations["custom_sql"]);

            $viewer->assign("REPORT_CUSTOM_SQL",$report_custom_sql);
            
            return $viewer->view("ReportCustomSQL.tpl", $moduleName, true);
    }
    
    // ITS4YOU-CR SlOl 4. 4. 2016 8:22:37
    public static function getGenerateForOptionsArray($reportModel,$primary_module=""){
        $generateForOptions = $Options = array();
        if($primary_module!=""){
            $Options = getPrimaryColumns($Options,$primary_module,true,$reportModel->report);
            
            $secondarymodules =Array();
            if(!empty($reportModel->report->related_modules[$reportModel->report->primarymodule])) {
                foreach($reportModel->report->related_modules[$reportModel->report->primarymodule] as $key=>$value){
                    $exploded_mid = explode("x", $value["id"]);
                    if(strtolower($exploded_mid[1])!="mif"){
                        $secondarymodules[]= $value["id"];
                    }
                }
            }
            $secondarymodules_str = implode(":", $secondarymodules);
            $Options_sec = getSecondaryColumns(array(),$secondarymodules_str,$reportModel->report);
            
            if(!empty($Options_sec)){
                foreach ($Options_sec as $moduleid=>$sec_options) {
                    $Options = array_merge($Options, $sec_options);
                }
            }
            
            if(!empty($Options)){
                foreach($Options as $blockName => $columnsArray){
                    foreach($columnsArray as $c_arr){
                        $column_str = $c_arr['value'];
                        $sc_field_uitype = ITS4YouReports::getUITypeFromColumnStr($column_str);
                        if(in_array($sc_field_uitype,ITS4YouReports::$s_users_uitypes)){
                            list($sc_tablename, $sc_columnname, $sc_modulestr) = explode(':', $column_str);
                            list($sc_module) = explode('_', $sc_modulestr);
                            if (vtlib_isModuleActive($sc_module) && ((isPermitted($sc_module, 'DetailView') == 'yes'))){
                                $generateForOptions[$sc_module][] = $c_arr;
                            }
                        }
                    }
                }
            }
        }
        return $generateForOptions;
    }
    // ITS4YOU-END
}
