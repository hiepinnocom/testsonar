{*/*<!--
/*********************************************************************************
 * The content of this file is subject to the Reports 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ********************************************************************************/
-->*/*}

{* //ITS4YOU-CR SlOl 11. 5. 2016 11:54:45 *}
<style type="text/css">
.conditionFilterDiv{
    background: #EEEFF2;
    padding-left:5px;
    padding-top:5px;
    padding-right:5px;
    margin-bottom: 10px;
    border: 0.1px solid rgba(0, 0, 0, 0.1);
    border-radius: 5px;
}
</style>
{* //ITS4YOU-END *}

<link rel="stylesheet" type="text/css" media="all" href="modules/ITS4YouReports/classes/Reports4YouDefault.css">
{*
<script type="text/javascript" src="modules/ITS4YouReports/highcharts/js/highcharts.js"></script>
<script type="text/javascript" src="modules/ITS4YouReports/highcharts/js/modules/funnel.js"></script>
<script type="text/javascript" src="modules/ITS4YouReports/highcharts/js/modules/exporting.js"></script>
*}
<script type="text/javascript" 
  src="modules/ITS4YouReports/highcharts/js/angular.js">
</script>

<script type="text/javascript" 
  src="modules/ITS4YouReports/highcharts/js/highcharts-ng.js">
</script> 

<script type="text/javascript" 
  src="modules/ITS4YouReports/highcharts/js/rgbcolor.js">
</script> 

<script type="text/javascript" 
  src="modules/ITS4YouReports/highcharts/js/StackBlur.js">
</script>

<script type="text/javascript" 
  src="modules/ITS4YouReports/highcharts/js/canvg.js">
</script> 

{strip}
    <div id="toggleButton" class="toggleButton" title="{vtranslate('LBL_LEFT_PANEL_SHOW_HIDE', 'Vtiger')}">
        <i id="tButtonImage" class="{if $LEFTPANELHIDE neq '1'}icon-chevron-left{else}icon-chevron-right{/if}"></i>
    </div>
    <div class="container-fluid no-print">
        <div class="row-fluid reportsDetailHeader">
            <input type="hidden" name="date_filters" data-value='{ZEND_JSON::encode($DATE_FILTERS)}' />
            <input type="hidden" name="report_filename" id="report_filename" value="" />
            
            <input type="hidden" name="export_pdf_format" id="export_pdf_format" value="" />
            <input type="hidden" name="pdf_file_name" id="pdf_file_name" value="" />
            <input type="hidden" name="ch_image_name" id="ch_image_name" value="" />
             
            <form id="detailView" onSubmit="return false;"  method="POST">
                <input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
                <input type="hidden" name="advft_criteria" id="advft_criteria" value='' />
                <input type="hidden" name="advft_criteria_groups" id="advft_criteria_groups" value='' />
                <input type="hidden" name="groupft_criteria" id="groupft_criteria" value='' />
                <input type="hidden" name="reload" id="reload" value='' />
                <input type="hidden" name="currentMode" id="currentMode" value='generate' />
                
                <input type="hidden" name="reporttype" id="reporttype" value='{$REPORTTYPE}' />
                
                <br>
                <div class="reportHeader row-fluid">
                    <div class="span3">
                        <div class="btn-toolbar">
                            {if $REPORT_MODEL->isEditable() eq true}
                                <div class="btn-group">
                                    <button onclick='window.location.href="{$REPORT_MODEL->getEditViewUrl()}"' type="button" class="cursorPointer btn">
                                        <strong>{vtranslate('LBL_CUSTOMIZE',$MODULE)}</strong>&nbsp;
                                        <i class="icon-pencil"></i>
                                    </button>
                                </div>
                                <div class="btn-group">
                                    <button onclick='window.location.href="{$REPORT_MODEL->getDuplicateRecordUrl()}"' type="button" class="cursorPointer btn">
                                        <strong>{vtranslate('LBL_DUPLICATE',$MODULE)}</strong>
                                    </button>
                                </div>
                            {/if}
                        </div>
                    </div>
                    <div class='span5 textAlignCenter'>
                        <h3>{$REPORT_MODEL->getName()}</h3>
                        <div id="noOfRecords">{vtranslate('LBL_NO_OF_RECORDS',$MODULE)} <span id="countValue">{$COUNT}</span>
    						{if $COUNT > 1000}
    							<span class="redColor" id="moreRecordsText"> ({vtranslate('LBL_MORE_RECORDS_TXT',$MODULE)})</span>
    						{else}
    							<span class="redColor hide" id="moreRecordsText"> ({vtranslate('LBL_MORE_RECORDS_TXT',$MODULE)})</span>
                        {/if}
                        </div>
                        <div id='activate_pdfmaker' style="display:block;">
                            {if $PDFMakerActive !== true}
                                {vtranslate('Please_Install_PDFMaker',$MODULE)}
                            {/if}
                            {if $IS_TEST_WRITE_ABLE !== true}
                                {vtranslate('Test_Not_WriteAble',$MODULE)}
                            {/if}
                        </div>
                    </div>
                    <div class='span4'>
                        <span class="pull-right">
                            <div class="btn-toolbar">
                                {foreach item=DETAILVIEW_LINK from=$DETAILVIEW_LINKS}
                                    {assign var=LINKNAME value=$DETAILVIEW_LINK->getLabel()}
                                    <div class="btn-group">
                                        <button class="btn reportActions" name="{$LINKNAME}" data-href="{$DETAILVIEW_LINK->getUrl()}" {if $DETAILVIEW_LINK->get('id')!=""}id="{$DETAILVIEW_LINK->get('id')}"{/if} {if $DETAILVIEW_LINK->get('style')!=""}style="{$DETAILVIEW_LINK->get('style')}"{/if} {if $DETAILVIEW_LINK->get('onClick')!=""}onClick="{$DETAILVIEW_LINK->get('onClick')}"{/if} >
                                            <strong>{$LINKNAME}</strong>
                                        </button>
                                    </div>
                                {/foreach}
                            </div>
                        </span>
                    </div>
                </div>
    			<br>
                <div class="row-fluid">
                    <input type="hidden" id="recordId" value="{$RECORD_ID}" />
                    <input type="hidden" id="widgetReports4YouId" value="{$RECORD_ID}" />
                    {if $REPORTTYPE!="custom_report"}
                        {assign var=RECORD_STRUCTURE value=array()}
                        {assign var=PRIMARY_MODULE_LABEL value=vtranslate($PRIMARY_MODULE, $PRIMARY_MODULE)}
                        {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$PRIMARY_MODULE_RECORD_STRUCTURE}
                            {assign var=PRIMARY_MODULE_BLOCK_LABEL value=vtranslate($BLOCK_LABEL, $PRIMARY_MODULE)}
                            {assign var=key value="$PRIMARY_MODULE_LABEL $PRIMARY_MODULE_BLOCK_LABEL"}
                            {if $LINEITEM_FIELD_IN_CALCULATION eq false && $BLOCK_LABEL eq 'LBL_ITEM_DETAILS'}
                                {* dont show the line item fields block when Inventory fields are selected for calculations *}
                            {else}
                                {$RECORD_STRUCTURE[$key] = $BLOCK_FIELDS}
                            {/if}
                        {/foreach}
                        {foreach key=MODULE_LABEL item=SECONDARY_MODULE_RECORD_STRUCTURE from=$SECONDARY_MODULE_RECORD_STRUCTURES}
                            {assign var=SECONDARY_MODULE_LABEL value=vtranslate($MODULE_LABEL, $MODULE_LABEL)}
                            {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$SECONDARY_MODULE_RECORD_STRUCTURE}
                                {assign var=SECONDARY_MODULE_BLOCK_LABEL value=vtranslate($BLOCK_LABEL, $MODULE_LABEL)}
                                {assign var=key value="$SECONDARY_MODULE_LABEL $SECONDARY_MODULE_BLOCK_LABEL"}
                                {$RECORD_STRUCTURE[$key] = $BLOCK_FIELDS}
                            {/foreach}
                        {/foreach}
                        {*{include file='AdvanceFilter.tpl'|@vtemplate_path RECORD_STRUCTURE=$RECORD_STRUCTURE ADVANCE_CRITERIA=$SELECTED_ADVANCED_FILTER_FIELDS COLUMNNAME_API=getReportFilterColumnName}*}
                {include file='modules/ITS4YouReports/AdvanceFilter.tpl'}
                {* ADVANCE FILTER START *}
                <div class="allConditionContainer conditionGroup contentsBackground well">
                    {include file='modules/ITS4YouReports/FiltersCriteria.tpl'}
                </div>
                {* ADVANCE FILTER END *}
                                {/if}


                {* PARAM https://redmine.innocom.vn/issues/15 *}

                {assign var=REPORT_INNOPARAM value=$REPORT_MODEL->report->reportinformations["innoparam"]}
                    {if !empty($REPORT_INNOPARAM)}
                    <table class="table table-bordered table-report">
                        <thead>
                            <tr class="blockHeader">
                                <th style="vertical-align: middle;">
                                     {vtranslate('Param name',$MODULE)}                             
                                </th>
                                <th style="vertical-align: middle;">
                                     {vtranslate('Param value',$MODULE)}                             
                                </th>
                            </tr>
                        </thead>
                        <tbody> 
                            {foreach from=$REPORT_INNOPARAM item=PARAM}
                            <tr>
                                
                                <td>
                                    {$PARAM.param_name}
                                </td>
                                    {if !empty($smarty.request.{$PARAM.param_name})}
                                        {assign var=SHOWVALUE value=$smarty.request.{$PARAM.param_name}}
                                    {else}
                                        {assign var=SHOWVALUE value={$PARAM.defaultvalue}}
                                    {/if}  
                                <td>
                                     <div class="filterContainer">
                                        <div style="display:block" >     
                                            <!-- https://redmine.innocom.vn/issues/252 -->
                                            {if $PARAM.type eq "user"} 
                                                <select class="chzn-select {$ASSIGNED_USER_ID}" name="{$PARAM.param_name}">
                                                    {foreach key=OWNER_ID item=OWNER_NAME from=$ACCESSIBLE_USER}
                                                            <option   value="{$OWNER_ID}" data-pick}istvalue= '{$OWNER_NAME}' {if $SHOWVALUE eq $OWNER_ID} selected {/if}>
                                                            {$OWNER_NAME}
                                                            </option>
                                                    {/foreach}  
                                                </select>
                                            <!-- https://redmine.innocom.vn/issues/252 END -->
                                            {else}                                                
                                                &nbsp;<input type='text' value="{$SHOWVALUE}" name="{$PARAM.param_name}">
                                                
                                            {/if}                        
                                        </div>                                           
                                    </div>                                    
                                </td>
                            {/foreach}
                            </tr>
                        </tbody>
                    </table>
                    {/if}

                {* PARAMS https://redmine.innocom.vn/issues/15 END *}


                    <div class="row-fluid">
                        <div class="textAlignCenter">
                            <button class="btn generateReport" data-mode="generate" value="{vtranslate('LBL_GENERATE_NOW',$MODULE)}"/>
                                <strong>{vtranslate('LBL_GENERATE_NOW',$MODULE)}</strong>
                            </button>&nbsp;
                            {if $REPORTTYPE!="custom_report" && $REPORT_MODEL->isEditable() eq true}
    				<button class="btn btn-success generateReport" data-mode="save" value="{vtranslate('LBL_SAVE',$MODULE)}"/>
    	                            <strong>{vtranslate('LBL_SAVE',$MODULE)}</strong>
    	                        </button>
    												{/if}
                            {* //ITS4YOU-CR SlOl | 14.4.2015 11:48  *}
                            {if $checkDashboardWidget != "" && $checkDashboardWidget != "Exist"}
                                <button class="btn addWidget" data-mode="addwidget" value="{vtranslate('LBL_ADD_WIDGET',$MODULE)}"/>
                                    <strong>{vtranslate('LBL_ADD_WIDGET',$MODULE)}</strong>
                                </button>
                            {/if}
                            {* //ITS4YOU-END 14.4.2015 11:48  *}
                        </div>
                    </div>
    				<br>
                </div>
           </form>
        </div>
    </div>

{/strip}
<form method="post" action="IndexAjax" target="_blank">
    <input type="hidden" name="module" value="ITS4YouReports"/>  
    <input type="hidden" name="action" value="IndexAjax"/>  
    <input type="hidden" name="mode" value="ExportXLS"/>  
    <input type="hidden" name="filename" value="Test.xls"/>  
    <input type="hidden" name="report_html" id="report_html" value=""/>
</form>
<form method="post" action="index.php" name="GeneratePDF" id="GeneratePDF" target="_blank">
    <input type="hidden" name="module" value="ITS4YouReports"/>  
    <input type="hidden" name="action" value="GeneratePDF"/>  
    <input type="hidden" name="form_export_pdf_format" id="form_export_pdf_format" value=""/>  
    <input type="hidden" name="form_filename" id="form_filename" value=""/>
    <input type="hidden" name="form_report_name" id="form_report_name" value=""/>  
    <input type="hidden" name="form_report_html" id="form_report_html" value=""/>
    <input type="hidden" name="form_report_totals" id="form_report_totals" value=""/>
    <input type="hidden" name="form_chart_canvas" id="form_chart_canvas" value=""/>
</form>
<form method="post" action="index.php" name="GenerateXLS" id="GenerateXLS" target="_blank">
    <input type="hidden" name="module" value="ITS4YouReports"/>  
    <input type="hidden" name="view" value="ExportReport"/> 
    <input type="hidden" name="mode" value="GetXLS"/> 
    <input type="hidden" name="record" value="{$RECORD_ID}"/> 
</form>
