<?php

class ITS4YouReports_Detail_View extends Vtiger_Index_View {
    protected $drp_x0b;
    protected $drp_x0c;
    public function checkPermission(Vtiger_Request $request) {
        
        $moduleName = $request->getModule();
        $moduleModel = ITS4YouReports_Module_Model::getInstance($moduleName);
        $recordId = $request->get('record');
        $reportModel = ITS4YouReports_Record_Model::getCleanInstance($recordId);
        $userModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$userModel->hasModulePermission($moduleModel->getId()) && !$reportModel->isEditable()) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
    }
    const REPORT_LIMIT = 1000;
    protected function preProcessTplName(Vtiger_Request $request) {
        
        return 'IndexViewPreProcess.tpl';
    }
    function preProcess(Vtiger_Request $request) {
        
        parent::preProcess($request);
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $page = $request->get('page');
        $detailViewModel = ITS4YouReports_DetailView_Model::getInstance($moduleName, $recordId);
        $reportModel = $detailViewModel->getRecord();
        $reportModel->setModule('ITS4YouReports');
        $PageModel = new Vtiger_Paging_Model();
        $PageModel->set('page', $page);
        $PageModel->set('limit', self::REPORT_LIMIT);
        $drp_x18 = ITS4YouReports_Record_Model::getRecordReportType($recordId);
        $this->reportData = $reportModel->getReportData($PageModel);
        $viewer->assign('REPORTTYPE', $drp_x18);
        if ($drp_x18 == "custom_report") {
            global $drp_x19;
            $isadmin = is_admin($drp_x19);
            $viewer->assign('IS_ADMIN_USER', $isadmin);
        } else {
            $drp_x1b = $reportModel->getPrimaryModule();
            $drp_x1c = $reportModel->getSecondaryModules();
            $drp_x1d = Vtiger_Module_Model::getInstance($drp_x1b);
            $drp_x1e = Users_Record_Model::getCurrentUserModel();
            $drp_x1f = Users_Privileges_Model::getInstanceById($drp_x1e->getId());
            $drp_x20 = $drp_x1f->hasModulePermission($drp_x1d->getId());
            if (!$drp_x20) {
                $viewer->assign('MODULE', $drp_x1b);
                $viewer->assign('MESSAGE', 'LBL_PERMISSION_DENIED');
                $viewer->view('OperationNotPermitted.tpl', $drp_x1b);
                exit;
            }
        }
        $drp_x21 = $detailViewModel->getDetailViewLinks();
        $viewer->assign('PRIMARY_MODULE', $drp_x1b);
        $viewer->assign('DETAILVIEW_LINKS', $drp_x21);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('RECORD_ID', $recordId);
        $viewer->assign('COUNT', count($this->reportData));
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('PDFMakerActive', $detailViewModel->exportPDFAvailable());
        $viewer->assign('IS_TEST_WRITE_ABLE', $detailViewModel->isTestWriteAble());
        $viewer->assign("DISPLAY_FILTER_HEADER", true);
        if ($request->get("currentMode") == "save") {
            $drp_x22 = $reportModel->getSelectedAdvancedFilter($recordId);
            $drp_x23 = $drp_x24 = array();
            if (!empty($drp_x22)) {
                foreach ($drp_x22 as $drp_x25 => $drp_x26) {
                    $drp_x24[$drp_x25]["groupcondition"] = $drp_x26["condition"];
                    foreach ($drp_x26["columns"] as $drp_x27) {
                        $drp_x27["groupid"] = $drp_x25;
                        $drp_x23[] = $drp_x27;
                    }
                }
            }
            $drp_x28 = false;
            $reportModel->saveAdvancedFilters($drp_x23, $drp_x24, $drp_x29, $drp_x28);
        }
        $drp_x2a = ITS4YouReports_EditView_Model::ReportFilters($request, $viewer);
        $viewer->assign("checkDashboardWidget", $reportModel->checkDashboardWidget('check'));
        // $drp_x2b = new ITS4YouReports_License_Action();
        // if (substr($drp_x2b->checkLicense(), 2, 1) == 0) {
        $accessibleUser = get_user_array(false, "ACTIVE", "", "","SalesOrder");
        $viewer->assign("ACCESSIBLE_USER", $accessibleUser);
            $viewer->view('ReportHeader.tpl', 'ITS4YouReports');
        // } else {
            // header('Location:  index.php?module=ITS4YouReports&view=List');
            // exit;
        // }
            

    }
    function process(Vtiger_Request $request) {
        
        $drp_x2c = $request->getMode();
        if (!empty($drp_x2c)) {
            $this->invokeExposedMethod($drp_x2c, $request);
            return;
        }
        echo $this->getReport($request);
    }
    function getReport(Vtiger_Request $request) {
        
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $page = $request->get('page');
        $ReportData = $this->reportData;
        
        $drp_x2e = $this->calculationFields;
        $reportModel = ITS4YouReports_Record_Model::getInstanceById($recordId);
        if (empty($ReportData)) {
            $reportModel->setModule('ITS4YouReports');
            $PageModel = new Vtiger_Paging_Model();
            $PageModel->set('page', $page);
            $PageModel->set('limit', self::REPORT_LIMIT + 1);
            $ReportData = $reportModel->getReportData($PageModel);
        }
        if (isset($ReportData[0])) {
            $ReportData = $ReportData[0];
        }
        $viewer->assign('CALCULATION_FIELDS', $drp_x2e);
        $viewer->assign('DATA', $ReportData);
        $viewer->assign('RECORD_ID', $recordId);
        $viewer->assign('PAGING_MODEL', $PageModel);
        $viewer->assign('MODULE', $moduleName);
        if (count($ReportData) > self::REPORT_LIMIT) {
            $viewer->assign('LIMIT_EXCEEDED', true);
        }
        $viewer->view('ReportContents.tpl', $moduleName);
    }
    function getHeaderScripts(Vtiger_Request $request) {
        
        $drp_x2f = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $drp_x30 = array('modules.Vtiger.resources.Detail', "modules.$moduleName.resources.Detail",);
        $drp_x31 = $this->checkAndConvertJsScripts($drp_x30);
        $drp_x2f = array_merge($drp_x2f, $drp_x31);
        return $drp_x2f;
    }
}