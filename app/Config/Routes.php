<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->get('checkTokenExpiry', "User/UserController::checkTokenExpiry", ['filter' => 'authFilter']);
$routes->post("superadminlogin", "SuperAdmin::super_admin_login");
$routes->resource('accessfeatures', ['controller' => 'AccessFeatures']);
$routes->resource('featureaction', ['controller' => 'FeatureAction']);

$routes->get('sendTestWBMessage', "FeatureAction::sendTestWBMessage");
$routes->resource('ServiceRem/ServiceReminderController', ['filter' => 'authFilter']);
$routes->resource('WbWebhookController');
$routes->resource('Whatsapp/WhatsappChatController');
$routes->post("getWhatsappCustomers", "Whatsapp/WhatsappChatController::getWhatsappCustomers", ['filter' => 'authFilter']);
$routes->post("getWhatsappCustomerMessages", "Whatsapp/WhatsappChatController::getWhatsappCustomerMessages", ['filter' => 'authFilter']);
$routes->post("sendMessageToCustomer", "Whatsapp/WhatsappChatController::sendMessageToCustomer", ['filter' => 'authFilter']);
$routes->post("sendNewCustomerMessage", "Whatsapp/WhatsappChatController::sendNewCustomerMessage", ['filter' => 'authFilter']);
$routes->post("sendMessageWithMedia", "Whatsapp/WhatsappChatController::sendMessageWithMedia", ['filter' => 'authFilter']);
$routes->post("sendWhatsappDocument", "Whatsapp/WhatsappChatController::sendWhatsappDocument", ['filter' => 'authFilter']);
$routes->post("updateCustomerCategory", "Whatsapp/WhatsappChatController::updateCustomerCategory", ['filter' => 'authFilter']);
$routes->post("sendAppointmentMessage", "Whatsapp/WhatsappChatController::sendAppointmentMessage", ['filter' => 'authFilter']);
$routes->post("blockContactFromWhatsapp", "Whatsapp/WhatsappChatController::blockContactFromWhatsapp", ['filter' => 'authFilter']);
$routes->post("deleteCustomerMessage", "Whatsapp/WhatsappChatController::deleteCustomerMessage", ['filter' => 'authFilter']);
$routes->post("sendNewCustomerCampaignMessage", "Whatsapp/WhatsappChatController::sendNewCustomerCampaignMessage", ['filter' => 'authFilter']);
$routes->post("sendNewEngagementMessage", "Whatsapp/WhatsappChatController::sendNewEngagementMessage", ['filter' => 'authFilter']);
$routes->post("createLeadFromWhatsapp", "WbWebhookController::createLeadFromWhatsapp");
$routes->post("uploadServiceRemainderList", "ServiceRem/ServiceReminderController::uploadServiceRemainderList", ['filter' => 'authFilter']);
$routes->post('serviceReminderCustomerList', "ServiceRem/ServiceReminderController::serviceReminderCustomerList", ['filter' => 'authFilter']);
$routes->post('sendServiceReminders', "ServiceRem/ServiceReminderController::sendServiceReminders", ['filter' => 'authFilter']);

$routes->resource('User/UserroleController', ['filter' => 'authFilter']);
$routes->resource('User/UserController', ['filter' => 'authFilter']);
$routes->post('User/UserController/changeuserstatus', "User/UserController::changeuserstatus", ['filter' => 'authFilter']);
$routes->get('getSpecialUsers', "User/UserController::getSpecialUsers", ['filter' => 'authFilter']);
$routes->resource('User/UserGroupController', ['filter' => 'authFilter']);
$routes->post('Customer/Customerdatafetch/customer_create', "Customer/CustomerDataFetch::customer_create", ['filter' => 'authFilter']);
$routes->post('customer/customerdatafetch/customer_vehicles', "Customer/CustomerDataFetch::customer_vehicles", ['filter' => 'authFilter']);
$routes->post('customer/customerdatafetch/customer_jobcards', "Customer/CustomerDataFetch::customer_jobcards", ['filter' => 'authFilter']);
$routes->get('getyeastarconfigdatas', 'Commonutils/GetConfigDatas::getYeastarKeys');
$routes->get('getPermittedIps', 'Commonutils/GetConfigDatas::getPermittedIps', ['filter' => 'authFilter']);
$routes->post('permittedIPSync', 'Commonutils/GetConfigDatas::PermittedIpSync', ['filter' => 'authFilter']);
// $routes->resource('userrole', ['controller' => 'UserRole']);
// $routes->resource('user', ['controller' => 'User']);verifyOTP
$routes->post("verifyOTP", "Login::verifyOTP");
$routes->post("userlogin", "Login::user_login");
$routes->resource('Calllogs/CustomerCalls', ['filter' => 'authFilter']);
$routes->resource('Leads/LeadSource', ['filter' => 'authFilter']);
$routes->resource('Leads/LeadStatus', ['filter' => 'authFilter']);
$routes->resource('Leads/Lead', ['filter' => 'authFilter']);
$routes->resource('Leads/CallPurpose', ['filter' => 'authFilter']);
$routes->resource('Leads/PreferLanguage', ['filter' => 'authFilter']);
$routes->post('Leads/Lead/leadupdate', "Leads/Lead::leadupdate", ['filter' => 'authFilter']);
$routes->post('whatsappLeadUpdate', "Leads/Lead::whatsappleadupdate", ['filter' => 'authFilter']);
$routes->resource('Leads/LeadTask', ['filter' => 'authFilter']);
$routes->resource('Leads/LeadReminder', ['filter' => 'authFilter']);
$routes->resource('Leads/LeadDocument', ['filter' => 'authFilter']);
$routes->resource('Leads/LeadActivity', ['filter' => 'authFilter']);
$routes->post('Leads/LeadActivity/getActivity', "Leads/LeadActivity::getActivity", ['filter' => 'authFilter']);
$routes->post('Leads/LeadTask/taskStatusChange', "Leads/LeadTask::taskStatusChange", ['filter' => 'authFilter']);
//$routes->post('leads/leadreminder/reminderStatusChange', "leads/leadreminder::reminderStatusChange");
$routes->post('Leads/LeadDocument/attachDoc', "Leads/LeadDocument::attachDoc", ['filter' => 'authFilter']);
$routes->post('callpopup/ViewDetail/viewDetailFromPopup', "callpopup/ViewDetail::viewDetailFromPopup", ['filter' => 'authFilter']);
$routes->post('callpopup/ViewDetail/JobCardDetail', "callpopup/ViewDetail::JobCardDetail", ['filter' => 'authFilter']);
$routes->post('callpopup/ViewDetail/leadlistByCustomer', "callpopup/ViewDetail::leadlistByCust", ['filter' => 'authFilter']);
$routes->get('callpopup/ViewDetail/getAlmJobcards', "callpopup/ViewDetail::JobCardList", ['filter' => 'authFilter']);
$routes->post('Customer/LeadToCust/getCustomerType', "Customer/LeadToCust::getCustomerType", ['filter' => 'authFilter']);
$routes->post('Customer/LeadToCust/getCountry', "Customer/LeadToCust::getCountry", ['filter' => 'authFilter']);

$routes->resource('Customer/CustomerMaster', ['filter' => 'authFilter']);
$routes->resource('Quotes/Campaign', ['filter' => 'authFilter']);
$routes->resource('Quotes/Quotation', ['filter' => 'authFilter']);
$routes->resource('Quotes/SplQuotation', ['filter' => 'authFilter']);
$routes->get('commonQuoteDetails', "Quotes/Quotation::commonQuoteDetails", ['filter' => 'authFilter']);
$routes->post('Quotes/Quotation/quoteByLead', "Quotes/Quotation::quoteByLead", ['filter' => 'authFilter']);
$routes->resource('Quotes/Brand', ['filter' => 'authFilter']);
$routes->post('createQuoteVersion', "Quotes/Quotation::createQuoteVersion", ['filter' => 'authFilter']);
$routes->post('updateQuoteVersion', "Quotes/Quotation::updateQuoteVersion", ['filter' => 'authFilter']);
$routes->post('getQuoteVersions', "Quotes/Quotation::getQuoteVersions", ['filter' => 'authFilter']);
$routes->post('getQuoteVersionDetails', "Quotes/Quotation::getQuoteVersionDetails", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/closeLead', "Leads/Lead::closeLead", ['filter' => 'authFilter']);

$routes->get('getDashDatas', "User/UserController::userDash", ['filter' => 'authFilter']);

$routes->resource('permittedIP', ['controller' => 'PermittedIP']);

$routes->get('verifyLogin', "User/UserController::verifyLogin", ['filter' => 'authFilter']);
$routes->get('call_count', "User/UserController::call_count", ['filter' => 'authFilter']);

$routes->post('callpopup/ViewDetail/call_logs', "callpopup/ViewDetail::call_logs", ['filter' => 'authFilter']);

$routes->post('callpopup/getCustomerDetails', "callpopup/ViewDetail::getCustomerDetails", ['filter' => 'authFilter']);

$routes->resource('UserLog', ['controller' => 'UserLog'], ['filter' => 'authFilter']);

$routes->resource('CommonNumber', ['controller' => 'CommonNumber'], ['filter' => 'authFilter']);
$routes->post('checkNumber', "CommonNumber::checkNumber");
$routes->resource('User/TrustedGroup', ['filter' => 'authFilter']);

$routes->get("getVerificationNumber", "User/UserController::getVerificationNumber", ['filter' => 'authFilter']);

$routes->get("userLogFilter", "UserLog::userLogFilter", ['filter' => 'authFilter']);

$routes->resource('User/UserNotification', ['filter' => 'authFilter']);

$routes->post("changeUserNotiStatus", "User/UserNotification::changeUserNotiStatus", ['filter' => 'authFilter']);

$routes->get("notificationCount", "User/UserNotification::notificationCount", ['filter' => 'authFilter']);

$routes->post("updateFCMToken", "User/UserNotification::updateFCMToken", ['filter' => 'authFilter']);

$routes->resource('TrunkList', ['filter' => 'authFilter']);

$routes->get("getDept", "TrunkList::getDept", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/checkLeadAvail', "Leads/Lead::checkLeadAvail", ['filter' => 'authFilter']);

$routes->post('callpopup/ViewDetail/CallInfo', "callpopup/ViewDetail::CallInfo", ['filter' => 'authFilter']);

$routes->post('Customer/CustomerMaster/customerExist', "Customer/CustomerMaster::customerExist", ['filter' => 'authFilter']);

// $routes->post('checkNumberInfo', "CommonNumber::checkNumberInfo");

$routes->resource('Settings/CommonSettings', ['filter' => 'authFilter']);

$routes->post("addWorkingTime", "Settings/CommonSettings::addWorkingTime", ['filter' => 'authFilter']);

$routes->post("changeLandlineStatus", "Settings/CommonSettings::changeLandlineStatus", ['filter' => 'authFilter']);

$routes->post("getPSFWhatsappReport", "Settings/CommonSettings::getPSFWhatsappReport", ['filter' => 'authFilter']);

$routes->get("getCommonSettings", "Settings/CommonSettings::getCommonSettings", ['filter' => 'authFilter']);

$routes->post("addVerificationNumber", "Settings/CommonSettings::addVerificationNumber", ['filter' => 'authFilter']);

$routes->post('callpopup/ViewDetail/getPendingCall', "callpopup/ViewDetail::getPendingCall", ['filter' => 'authFilter']);

$routes->post('dashboardCards', "User/UserController::dashboardCards", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/updateLeadCloseTime', "Leads/Lead::updateLeadCloseTime", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/modalleadupdate', "Leads/Lead::modalleadupdate", ['filter' => 'authFilter']);



$routes->resource('User/UserWorktime', ['filter' => 'authFilter']);

$routes->resource('User/CallAssignList', ['filter' => 'authFilter']);

$routes->post("UserCallAssignList", "User/CallAssignList::UserCallAssignList", ['filter' => 'authFilter']);

$routes->resource('Customer/LostCustomer', ['filter' => 'authFilter']);

$routes->post('Customer/LostCustomer/uploadExcel', "Customer/LostCustomer::uploadExcel", ['filter' => 'authFilter']);

$routes->post('Customer/LostCustomer/assignLostCustomer', "Customer/LostCustomer::assignLostCustomer", ['filter' => 'authFilter']);

$routes->get("LcAssignedDateList", "Customer/LostCustomer::assigned_date_list", ['filter' => 'authFilter']);



$routes->post('Customer/LostCustomer/assigned_lc_list', "Customer/LostCustomer::assigned_lc_list", ['filter' => 'authFilter']);

$routes->get("UploadFileList", "Customer/LostCustomer::uploadFileList", ['filter' => 'authFilter']);

$routes->get("yeastarAccessTokenUpdate", "Settings/CommonSettings::yeastarAccessTokenUpdate", ['filter' => 'authFilter']);

$routes->get("assigned_list", "Customer/LostCustomer::assigned_list", ['filter' => 'authFilter']);


$routes->get("getAssignedDateList", "User/CallAssignList::getAssignedDateList", ['filter' => 'authFilter']);

$routes->post('Customer/CustomerMaster/customerExistReport', "Customer/CustomerMaster::customerExistReport", ['filter' => 'authFilter']);

$routes->post('Customer/CustomerMaster/customerStatusReport', "Customer/CustomerMaster::customerStatusReport", ['filter' => 'authFilter']);

$routes->post('customerJobStatusReport', "Customer/CustomerMaster::customerJobStatusReport", ['filter' => 'authFilter']);

$routes->post('TrunkList/FeatureListByDept', "TrunkList::FeatureListByDept", ['filter' => 'authFilter']);

$routes->post('User/CallAssignList/addMisscallNote', "User/CallAssignList::addMisscallNote", ['filter' => 'authFilter']);

$routes->post('User/CallAssignList/getMisscallDetails', "User/CallAssignList::getMisscallDetails", ['filter' => 'authFilter']);

$routes->get("ViewUserLog", "UserLog::ViewUserLog", ['filter' => 'authFilter']);

$routes->post('Customer/LostCustomer/disableLcFile', "Customer/LostCustomer::disableLcFile", ['filter' => 'authFilter']);

$routes->resource('Report/InboundCallReport', ['filter' => 'authFilter']);


$routes->post('InboundReport', "Report/InboundCallReport::InboundReport", ['filter' => 'authFilter']);

$routes->resource('User/UserDetail', ['filter' => 'authFilter']);

$routes->post('User/UserDetail/custDetails', "User/UserDetail::custDetails", ['filter' => 'authFilter']);

$routes->post('User/UserDetail/leadDetail', "User/UserDetail::leadDetail", ['filter' => 'authFilter']);

$routes->post('User/UserDetail/getCustomerDetail', "User/UserDetail::getCustomerDetail", ['filter' => 'authFilter']);

$routes->post('User/UserDetail/getReportChartData', "User/UserDetail::getReportChartData", ['filter' => 'authFilter']);

$routes->post('best', "User/UserController::best", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/modalleadupdateibound', "Leads/Lead::modalleadupdateinbound", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/LeadByPurpose', "Leads/Lead::LeadByPurpose", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/AssignLead', "Leads/Lead::AssignLead", ['filter' => 'authFilter']);

$routes->post('NotUpdatedLead', "Leads/Lead::NotUpdatedLead", ['filter' => 'authFilter']);

$routes->post('User/StaffDash/dashCounts', "User/StaffDash::dashCounts", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/modalleadupdateoutbound', "Leads/Lead::modalleadupdateoutbound", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/getAppointmentLeads', "Leads/Lead::getAppointmentLeads", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/updateAppointLead', "Leads/Lead::updateAppointLead", ['filter' => 'authFilter']);

$routes->post('Leads/Lead/leadPendingCount', "Leads/Lead::leadPendingCount", ['filter' => 'authFilter']);

$routes->post('Customer/LostCustomer/AddLcNote', "Customer/LostCustomer::AddLcNote", ['filter' => 'authFilter']);

$routes->post('User/UserController/performance', "User/UserController::performance", ['filter' => 'authFilter']);

$routes->post('User/UserWorktime/getWorkTimeByDay', "User/UserWorktime::getWorkTimeByDay", ['filter' => 'authFilter']);

$routes->post('customerConvertReport', "Customer/CustomerMaster::customerConvertReport", ['filter' => 'authFilter']);

$routes->post('User/UserController/changeUserPassword', "User/UserController::changeUserPassword", ['filter' => 'authFilter']);

$routes->post('Customer/LostCustomer/updateLCReport', "Customer/LostCustomer::updateLCReport", ['filter' => 'authFilter']);

$routes->post('Customer/CustomerMaster/leadExistReport', "Customer/CustomerMaster::leadExistReport", ['filter' => 'authFilter']);

$routes->post("addBufferTime", "Settings/CommonSettings::addBufferTime", ['filter' => 'authFilter']);

$routes->post("addHoliday", "Settings/CommonSettings::addHoliday", ['filter' => 'authFilter']);

$routes->post("getHolidays", "Settings/CommonSettings::getHolidays", ['filter' => 'authFilter']);

$routes->post("deleteHoliday", "Settings/CommonSettings::deleteHoliday", ['filter' => 'authFilter']);

$routes->post("addWorkBufferTime", "Settings/CommonSettings::addWorkBufferTime", ['filter' => 'authFilter']);

$routes->post("getHoliday_report", "Settings/CommonSettings::getHoliday_report", ['filter' => 'authFilter']);
$routes->resource('InboundCall/InboundCallReportController', ['filter' => 'authFilter']);
$routes->resource('TargetSettings/TargetSettingsController', ['filter' => 'authFilter']);
$routes->post('getCallsData', "InboundCall/InboundCallReportController::getCallsData", ['filter' => 'authFilter']);
$routes->post('getCustomerWithoutCallsData', "InboundCall/InboundCallReportController::getCustomerWithoutCallsData", ['filter' => 'authFilter']);
$routes->post('InboundCall/InboundCallReportController/getcustomerdata', "InboundCall/InboundCallReportController::getcustomerdata", ['filter' => 'authFilter']);
$routes->post('InboundCall/InboundCallReportController/getcustomerleads', "InboundCall/InboundCallReportController::getcustomerleads", ['filter' => 'authFilter']);
$routes->post('CustomerConversion/CustomerConversionReportController/getcustomerdatas', "Customer/CustomerConversionReportController::getcustomerdatas", ['filter' => 'authFilter']);
$routes->post('CustomerConversion/CustomerConversionReportController/getexistingcustomerdata', "Customer/CustomerConversionReportController::getexistingcustomerdata", ['filter' => 'authFilter']);
$routes->post('CustomerConversion/CustomerConversionReportController/getexistingcustomer', "Customer/CustomerConversionReportController::getexistingcustomer", ['filter' => 'authFilter']);
$routes->post('CustomerConversion/CustomerConversionReportController/getcustomerconvert', "Customer/CustomerConversionReportController::getcustomerconvert", ['filter' => 'authFilter']);
$routes->post('CustomerConversion/CustomerConversionReportController/getPreviouscustomer', "Customer/CustomerConversionReportController::getPreviouscustomer", ['filter' => 'authFilter']);
$routes->post('CustomerConversion/CustomerConversionReportController/getPreviouscustomerJobcard', "Customer/CustomerConversionReportController::getPreviouscustomerJobcard", ['filter' => 'authFilter']);
$routes->post('getcustomerinfo', "InboundCall/InboundCallReportController::getcustomerinfo", ['filter' => 'authFilter']);
$routes->post('getMissedCustomerInfo', "InboundCall/InboundCallReportController::getMissedCustomerInfo", ['filter' => 'authFilter']);
$routes->post('getcallleadlog', "InboundCall/InboundCallReportController::getcallleadlog", ['filter' => 'authFilter']);
$routes->post('customercatlist', "Customer/CustomerMaster::customercatlist", ['filter' => 'authFilter']);
$routes->post('customercatdata', "Customer/CustomerMaster::customercatdata", ['filter' => 'authFilter']);
$routes->post('getcustomerdataanalysis', "InboundCall/InboundCallReportController::getcustomerdataanalysis", ['filter' => 'authFilter']);
$routes->post('gettarget_details', "TargetSettings/TargetSettingsController::gettarget_details", ['filter' => 'authFilter']);
$routes->post('getUserAssignedLostCustomers', "Customer/LostCustomer::getUserAssignedLostCustomers", ['filter' => 'authFilter']);
$routes->post('getCallReports', "Leads/Lead::getCallReports", ['filter' => 'authFilter']);
$routes->post('getdisatisfiedcust', "Dissatisfied/DissatisfiedController::getdisatisfiedcust", ['filter' => 'authFilter']);
$routes->post('getLeadQuote', "Leads/Lead::getLeadQuote", ['filter' => 'authFilter']);
$routes->post('leaadQuoteAppoint', "Leads/Lead::leaadQuoteAppoint", ['filter' => 'authFilter']);
$routes->post('leadQuoteUpdate', "Leads/Lead::leadQuoteUpdate", ['filter' => 'authFilter']);
$routes->post('getdissatisfiedcustbyid', "Dissatisfied/DissatisfiedController::getdissatisfiedcustbyid", ['filter' => 'authFilter']);
$routes->post('disatisfiedUpdate', "Dissatisfied/DissatisfiedController::disatisfiedUpdate", ['filter' => 'authFilter']);
$routes->get('getQuoteDetails', "Leads/Lead::getQuoteDetails", ['filter' => 'authFilter']);
$routes->get('getJobNumbers', "Leads/Lead::getJobNumbers", ['filter' => 'authFilter']);
$routes->post('Leads/Lead/createQuotation', "Leads/Lead::createQuotation", ['filter' => 'authFilter']);
$routes->post('getDashCounts', "User/StaffDash::getDashCounts", ['filter' => 'authFilter']);
$routes->post('getleadstocust', "User/StaffDash::leadstocust", ['filter' => 'authFilter']);
$routes->post('getAllLeads', "User/StaffDash::getAllLeads", ['filter' => 'authFilter']);
$routes->post('getInvoiceDetails', "Settings/CommonSettings::getNMInvoiceDetails", ['filter' => 'authFilter']);
$routes->post("updatePartsMargin", "Settings/CommonSettings::updatePartsMargin", ['filter' => 'authFilter']);
$routes->post("createSpareInvoice", "Settings/CommonSettings::createSpareInvoice", ['filter' => 'authFilter']);
$routes->get("getAlmInvoiceComman", "Settings/CommonSettings::getAlmInvoiceComman", ['filter' => 'authFilter']);
$routes->post("getJobCardsAgeData", "Settings/CommonSettings::getJobCardsAgeData", ['filter' => 'authFilter']);
$routes->post('getStaffPerformance', "Customer/LostCustomer::getLostCustomerTypewise", ['filter' => 'authFilter']);
$routes->post("saveSubStatus", "Settings/CommonSettings::saveSubStatus", ['filter' => 'authFilter']);
$routes->get("getSubStatus", "Settings/CommonSettings::getAllSubStatus", ['filter' => 'authFilter']);
$routes->post("getWIPTaskStatus", "Settings/CommonSettings::getWIPTaskStatus", ['filter' => 'authFilter']);
$routes->post("updateJobSubStatus", "Settings/CommonSettings::updateJobSubStatus", ['filter' => 'authFilter']);
$routes->post("getJobStusChangeHistory", "Settings/CommonSettings::getJobStusChangeHistory", ['filter' => 'authFilter']);
$routes->post("getAllJobCardStatus", "Settings/CommonSettings::getAllJobCardStatus", ['filter' => 'authFilter']);
$routes->post("getCustomerVehicles", "Settings/CommonSettings::getCustomerVehicles", ['filter' => 'authFilter']);
$routes->get("sendAnniversaryMessage", "Settings/CommonSettings::sendAnniversaryMessage", ['filter' => 'authFilter']);

$routes->post("getSpareInvoices", "Settings/CommonSettings::getSpareInvoices", ['filter' => 'authFilter']);
$routes->post("getSpareInvoiceById", "Settings/CommonSettings::getSpareInvoiceById", ['filter' => 'authFilter']);
$routes->post("getQuotations", "Leads/Lead::getQuotations", ['filter' => 'authFilter']);
$routes->get("getSparePartsMargin", "Settings/CommonSettings::getSparePartsMargin", ['filter' => 'authFilter']);
$routes->post("saveSparePartsMargin", "Settings/CommonSettings::saveSparePartsMargin", ['filter' => 'authFilter']);
$routes->post("getCustomerJobcards", "Settings/CommonSettings::getCustomerJobcards", ['filter' => 'authFilter']);
$routes->post("UpdateSpareInvoiceById", "Settings/CommonSettings::UpdateSpareInvoiceById", ['filter' => 'authFilter']);
$routes->post("createSupplierDetails", "Settings/CommonSettings::createSupplierDetails", ['filter' => 'authFilter']);
$routes->get("getSupplierDetails", "Settings/CommonSettings::getSupplierDetails", ['filter' => 'authFilter']);
$routes->post("updateSupplierDetails", "Settings/CommonSettings::updateSupplierDetails", ['filter' => 'authFilter']);
$routes->post("deleteSupplier", "Settings/CommonSettings::deleteSupplier", ['filter' => 'authFilter']);
$routes->get("getSparePartsDesandPart", "Settings/CommonSettings::getSparePartsDesandPart", ['filter' => 'authFilter']);
$routes->post("getTotalInvoices", "Settings/CommonSettings::getTotalInvoices", ['filter' => 'authFilter']);
$routes->get("getAdminApprovalInvoices", "Settings/CommonSettings::getAdminApprovalInvoices", ['filter' => 'authFilter']);
$routes->post("updateSpareInvoice", "Settings/CommonSettings::updateSpareInvoice", ['filter' => 'authFilter']);
$routes->post('closeDissatisfiedcust', "Dissatisfied/DissatisfiedController::closeDissatisfiedcust", ['filter' => 'authFilter']);
$routes->post('getLeadActivityLog', "Leads/Lead::getLeadActivityLog", ['filter' => 'authFilter']);
$routes->post('leadUpdate', "Leads/Lead::leadUpdateById", ['filter' => 'authFilter']);
$routes->post('getMonthlyDissatisfied', "User/StaffDash::getMonthlyDissatisfied", ['filter' => 'authFilter']);
$routes->post('fetchAllLeads', "Leads/Lead::fetchAllLeads", ['filter' => 'authFilter']);
$routes->post("getNMInvoiceList", "Settings/CommonSettings::getNMInvoiceList", ['filter' => 'authFilter']);
$routes->get("getNMInvoicePostedList", "Settings/CommonSettings::getNMInvoicePostedList", ['filter' => 'authFilter']);
$routes->post('deleteSparePartsMargin', "Settings/CommonSettings::deleteSparePartsMargin", ['filter' => 'authFilter']);
$routes->post('getSaRating', "User/StaffDash::getSaRating", ['filter' => 'authFilter']);
$routes->resource('SpareParts/SparePartsController', ['filter' => 'authFilter']);
$routes->resource('Labour/LabourController', ['filter' => 'authFilter']);
$routes->get("getSpareCategory", "SpareParts/SparePartsController::getSpareCategory", ['filter' => 'authFilter']);
$routes->resource('Service/ServiceController', ['filter' => 'authFilter']);
$routes->post('getVinGroups', "Service/ServiceController::getVinGroups", ['filter' => 'authFilter']);
$routes->post('getVehicleVariants', "Service/ServiceController::getVehicleVariants", ['filter' => 'authFilter']);
$routes->post('saveServices', "Service/ServiceController::saveServices", ['filter' => 'authFilter']);
$routes->get('getAllServices', "Service/ServiceController::getAllServices", ['filter' => 'authFilter']);
$routes->post('getServiceDetails', "Service/ServiceController::getServiceDetails", ['filter' => 'authFilter']);
$routes->post('updateServices', "Service/ServiceController::updateServices", ['filter' => 'authFilter']);
$routes->post('deleteService', "Service/ServiceController::deleteService", ['filter' => 'authFilter']);
$routes->post('Quotes/Quotation/fetchAllQuote', "Quotes/Quotation::fetchAllQuote", ['filter' => 'authFilter']);
$routes->post('Quotes/SplQuotation/getSplQuotesList', "Quotes/SplQuotation::getSplQuotesList", ['filter' => 'authFilter']);
$routes->post('getCampaignEnquiry', "Leads/CampaignController::getCampaignEnquiry", ['filter' => 'authFilter']);
$routes->post('getLeadCampaignDetails', "Leads/CampaignController::getLeadCampaignDetails", ['filter' => 'authFilter']);
$routes->post('updateLeadCampEnq', "Leads/CampaignController::updateLeadCampEnq", ['filter' => 'authFilter']);
$routes->post('saveUserRoleMargin', "Settings/CommonSettings::saveUserRoleMargin", ['filter' => 'authFilter']);
$routes->get("getUserRoleMargin", "Settings/CommonSettings::getUserRoleMargin", ['filter' => 'authFilter']);
$routes->post('deleteUserRoleMargin', "Settings/CommonSettings::deleteUserRoleMargin", ['filter' => 'authFilter']);
$routes->post('getUserRoleMarginLimit', "Settings/CommonSettings::getUserRoleMarginLimit", ['filter' => 'authFilter']);
$routes->post("getAllJobCards", "Settings/CommonSettings::getAllJobCards", ['filter' => 'authFilter']);
$routes->post('setQuotesTermsFlag', "Quotes/Quotation::setQuotesTermsFlag", ['filter' => 'authFilter']);
$routes->get('getPreloadDatas', "Leads/Lead::getPreloadDatas", ['filter' => 'authFilter']);
$routes->post('getLeadCallLog', "Leads/Lead::LeadCallLog", ['filter' => 'authFilter']);
$routes->post('getAppointmentCalls', "Leads/Appointment::getAppointmentCalls", ['filter' => 'authFilter']);
$routes->post('getAppointmentDetails', "Leads/Appointment::getAppointmentDetails", ['filter' => 'authFilter']);
$routes->post('updateAppointmentDetails', "Leads/Appointment::updateAppointmentDetails", ['filter' => 'authFilter']);
$routes->resource('Leads/Appointment', ['filter' => 'authFilter']);
$routes->post('Leads/Appointment/isExistingCustOrNot', "Leads/Appointment::isExistingCustOrNot", ['filter' => 'authFilter']);
$routes->post('fetchInvoicedCustomers', "Leads/Lead::fetchInvoicedCustomers", ['filter' => 'authFilter']);
$routes->post('getAppointmentReports', "Leads/Appointment::getAppointmentReports", ['filter' => 'authFilter']);
$routes->post('get_Customer_details_for_modal', "InboundCall/InboundCallReportController::getCustomerDetailsfromPhoneNum", ['filter' => 'authFilter']);
$routes->post('Last7DaysAppointments', "Leads/Appointment::Last7DaysAppointments", ['filter' => 'authFilter']);
$routes->resource('SocialMediaCampaign/SocialMediaCampaignController', ['filter' => 'authFilter']);
$routes->get('socialMediaCampaignsource', "SocialMediaCampaign/SocialMediaCampaignController::socialMediaCampaignsource", ['filter' => 'authFilter']);
$routes->post('getSocialMediaCampaigns', "SocialMediaCampaign/SocialMediaCampaignController::getSocialMediaCampaigns", ['filter' => 'authFilter']);
$routes->post('getSocialMediaCampaignDetails', "SocialMediaCampaign/SocialMediaCampaignController::getSocialMediaCampaignDetails", ['filter' => 'authFilter']);
$routes->post('changeCampaignStatus', "SocialMediaCampaign/SocialMediaCampaignController::changeCampaignStatus", ['filter' => 'authFilter']);
$routes->post('checkSocialMediaCampaign', "SocialMediaCampaign/SocialMediaCampaignController::checkSocialMediaCampaign", ['filter' => 'authFilter']);
$routes->post('socialMediaCampaignDelete', "SocialMediaCampaign/SocialMediaCampaignController::socialMediaCampaignDelete", ['filter' => 'authFilter']);
$routes->post('getActiveSocialMediaCampaigns', "SocialMediaCampaign/SocialMediaCampaignController::getActiveSocialMediaCampaigns", ['filter' => 'authFilter']);
$routes->post('updateAppointmentRegNo', "Leads/Appointment::updateAppointmentRegNo", ['filter' => 'authFilter']);
$routes->post('socialMediaCampaignDetailsFetch',"SocialMediaCampaign/SocialMediaCampaignController::socialMediaCampaignDetailsfetch", ['filter' => 'authFilter']);
$routes->post('getLatestJobCard',"Leads/Appointment::getLatestJobCard", ['filter' => 'authFilter']);
$routes->post('getWhatsappLeadsList', "Leads/Lead::getWhatsappLeadsList", ['filter' => 'authFilter']);
$routes->post('getQuoteLogs', "Quotes/Quotation::getQuoteLogs", ['filter' => 'authFilter']);
$routes->post('forwardWhatsappMessage', "Whatsapp/WhatsappChatController::forwardWhatsappMessage", ['filter' => 'authFilter']);
$routes->post('replyMessageToCustomer', "Whatsapp/WhatsappChatController::replyMessageToCustomer", ['filter' => 'authFilter']);
$routes->get("getWhatsappCustomersCounts", "Whatsapp/WhatsappChatController::getWhatsappCustomersCounts", ['filter' => 'authFilter']);
$routes->post("getWhatsappCustomersByTime", "Whatsapp/WhatsappChatController::getWhatsappCustomersChatsByTime", ['filter' => 'authFilter']);
$routes->get("getTemporaryLostWhatsappCustomers", "Whatsapp/WhatsappChatController::getTemporaryLostWhatsappCustomers", ['filter' => 'authFilter']);
$routes->get("getWhatsappCustomerCategorizeCounts", "Whatsapp/WhatsappChatController::getWhatsappCustomerCategorizeCounts", ['filter' => 'authFilter']);
$routes->post("getWhatsappCustomerCategorize", "Whatsapp/WhatsappChatController::getWhatsappCustomerCategorize", ['filter' => 'authFilter']);
$routes->post('sendLocationToCustomer', "Whatsapp/WhatsappChatController::sendLocationToCustomer", ['filter' => 'authFilter']);
$routes->post('forwardLocationToCustomer', "Whatsapp/WhatsappChatController::forwardLocationToCustomer", ['filter' => 'authFilter']);
$routes->post('forwardMessageWithMedia', "Whatsapp/WhatsappChatController::forwardMessageWithMedia", ['filter' => 'authFilter']);
$routes->post('forwardMessageWithAudio', "Whatsapp/WhatsappChatController::forwardMessageWithAudio", ['filter' => 'authFilter']);
$routes->get('getFollowUpAlertTime', "Whatsapp/WhatsappChatController::getFollowUpAlertTime", ['filter' => 'authFilter']);
$routes->post('updateWhatsAppMessageExpiration', "Whatsapp/WhatsappChatController::updateWhatsAppMessageExpiration", ['filter' => 'authFilter']);
$routes->post('addStaffToWhatsapp', "Whatsapp/WhatsappChatController::addStaffToWhatsapp", ['filter' => 'authFilter']);
$routes->post('deleteWhatsappAssignedStaff', "Whatsapp/WhatsappChatController::deleteWhatsappAssignedStaff", ['filter' => 'authFilter']);
$routes->get('getUnreadMessages', "Whatsapp/WhatsappChatController::getUnreadMessages", ['filter' => 'authFilter']);
$routes->get('checkFollowUpOverdue', "Whatsapp/WhatsappChatController::checkFollowUpOverdue", ['filter' => 'authFilter']);
$routes->post('sendLocationMessage', "Whatsapp/WhatsappChatController::sendLocationMessage", ['filter' => 'authFilter']);
$routes->post('sendAppointmentRemainderMessage', "Whatsapp/WhatsappChatController::sendAppointmentRemainderMessage", ['filter' => 'authFilter']);
$routes->post('sendCustomerReEngMessage', "Whatsapp/WhatsappChatController::sendCustomerReEngMessage", ['filter' => 'authFilter']);
$routes->post('whatsappMessageExpiredFollowupLogs', "Whatsapp/WhatsappChatController::whatsappMessageExpiredFollowupLogs", ['filter' => 'authFilter']);
$routes->get('getWhatsappLeadReOpenHours', "Whatsapp/WhatsappChatController::getWhatsappLeadReOpenHours", ['filter' => 'authFilter']);
$routes->post('updateWhatsappLeadReOpenHours', "Whatsapp/WhatsappChatController::updateWhatsappLeadReOpenHours", ['filter' => 'authFilter']);
$routes->post('getCustomerAnalysisReport', "Customer/CustomerConversionReportController::getCustomerAnalysisReport", ['filter' => 'authFilter']);
$routes->post('sendCampaignMessage', "Whatsapp/WhatsappChatController::sendCampaignMessage", ['filter' => 'authFilter']);
$routes->post('sendNewCustomerCampaignNewMessage', "Whatsapp/WhatsappChatController::sendNewCustomerCampaignNewMessage", ['filter' => 'authFilter']);
$routes->post('searchWhatsappCustomer', "Whatsapp/WhatsappChatController::searchWhatsappCustomer", ['filter' => 'authFilter']);
$routes->get('getWhatsappCustomersFollowups', "Whatsapp/WhatsappChatController::getWhatsappCustomersFollowups", ['filter' => 'authFilter']);
$routes->get('sendSMCWithDays', "Customer/CustomerReEngageCampaignController::sendSMCWithDays", ['filter' => 'authFilter']);
$routes->post('fetchAllFollowUpCustomers', "Customer/CustomerReEngageCampaignController::fetchAllFollowUpCustomers", ['filter' => 'authFilter']);
$routes->get("getServiceRemainderDays", "Settings/CommonSettings::getServiceRemainderDays", ['filter' => 'authFilter']);
$routes->post('updateServiceRemainderDays', "Settings/CommonSettings::updateServiceRemainderDays", ['filter' => 'authFilter']);
$routes->post('getSRCReport', "Customer/CustomerReEngageCampaignController::getSRCReport", ['filter' => 'authFilter']);
$routes->post('getAppointmentCustomersFromSRC', "Customer/CustomerReEngageCampaignController::getAppointmentCustomersFromSRC", ['filter' => 'authFilter']);
$routes->post('updateWhatsappAutoMessageHours', "Whatsapp/WhatsappChatController::updateWhatsappAutoMessageHours", ['filter' => 'authFilter']);


























//yeaStar routes
$routes->post("getCDRReport", "YeaStar/YeaStarController::getCDRDetails", ['filter' => 'authFilter']);
$routes->post("getCDRReportByNumber", "YeaStar/YeaStarController::getCDRDetailsByNumber", ['filter' => 'authFilter']);
$routes->post("getCDRInboundByNumberlist", "YeaStar/YeaStarController::getCDRInboundByNumberlist", ['filter' => 'authFilter']);
$routes->post("getCDRByNumberlist", "YeaStar/YeaStarController::getCDRByNumberlist", ['filter' => 'authFilter']);
$routes->post("getCDRInboundByNumberlistByMonth", "YeaStar/YeaStarController::getCDRInboundByNumberlistByMonth", ['filter' => 'authFilter']);
$routes->post("getOutboundCalls", "YeaStar/YeaStarController::getOutboundCalls", ['filter' => 'authFilter']);
$routes->resource('Leads/LeadSource', ['filter' => 'authFilter']);
//psf routes 
$routes->resource('PSFModule/PSFController', ['filter' => 'authFilter']);
$routes->resource('PSFModule/PSFCREAdminController', ['filter' => 'authFilter']);
$routes->post('get_CREPSFrecord_info', "PSFModule/PSFCREAdminController::get_CREPSFrecord_info", ['filter' => 'authFilter']);
$routes->get('crmDailyPSFRoutineUpdate', "PSFModule/PSFController::crmDailyPSFRoutineUpdate", ['filter' => 'authFilter']);
$routes->get('get_creDailyPSFCalls', "PSFModule/PSFCREAdminController::get_creDailyPSFCalls", ['filter' => 'authFilter']);
$routes->get('crmDailyPSFUpdateProcess', "PSFModule/PSFController::crmDailyPSFUpdate", ['filter' => 'authFilter']);
$routes->get('get_crmDailyPSFCalls', "PSFModule/PSFController::get_crmDailyPSFCalls", ['filter' => 'authFilter']);
$routes->get('get_PSFresponseMaster', "PSFModule/PSFController::get_PSFresponseMaster", ['filter' => 'authFilter']);
$routes->post('get_PSFreasonMaster', "PSFModule/PSFController::get_PSFreasonMaster", ['filter' => 'authFilter']);
$routes->post('get_crmDailyUserPSFCalls', "PSFModule/PSFController::get_crmDailyUserPSFCalls", ['filter' => 'authFilter']);
$routes->post('get_PSFrecord_info', "PSFModule/PSFController::get_PSFrecord_info", ['filter' => 'authFilter']);
$routes->post('get_psfReport', "PSFModule/PSFController::get_psfReport", ['filter' => 'authFilter']);
$routes->post('get_psfReport_cre', "PSFModule/PSFController::get_psfReport_cre", ['filter' => 'authFilter']);
$routes->post('get_userPsfReport', "PSFModule/PSFController::get_userPsfReport", ['filter' => 'authFilter']);
$routes->post('get_expiredandissatisfied', "PSFModule/PSFController::get_expiredandissatisfied", ['filter' => 'authFilter']);
$routes->post('get_expiredandissatisfiedcre', "PSFModule/PSFController::get_expiredandissatisfiedcre", ['filter' => 'authFilter']);
$routes->post('get_specificCallData', "PSFModule/PSFController::get_specificCallData", ['filter' => 'authFilter']);
$routes->resource('PSFModule/CREQuestionMasterController', ['filter' => 'authFilter']);
$routes->post("updaetMaxPSFDays", "Settings/CommonSettings::updaetMaxPSFDays", ['filter' => 'authFilter']);
$routes->get("retrivePSFSettingsData", "Settings/CommonSettings::retrivePSFSettingsData", ['filter' => 'authFilter']);
$routes->post("assignPSFStaff", "Settings/CommonSettings::assignPSFStaff", ['filter' => 'authFilter']);
$routes->post("removePSFStaff", "Settings/CommonSettings::removePSFStaff", ['filter' => 'authFilter']);
$routes->post("updatePSFMethod", "Settings/CommonSettings::updatePSFMethod", ['filter' => 'authFilter']);
$routes->post('get_userPsfReportsa', "PSFModule/PSFController::get_userPsfReportsa", ['filter' => 'authFilter']);
$routes->post('get_psfReportsa', "PSFModule/PSFController::get_psfReportsa", ['filter' => 'authFilter']);
$routes->post('getPSFTodayCallsData', "PSFModule/PSFController::get_psfTodayCalls", ['filter' => 'authFilter']);
$routes->post('customerdata', "PSFModule/PSFController::get_customerdata", ['filter' => 'authFilter']);
$routes->post('get3rdDayPsfCallData', "PSFModule/PSFController::get3rdDayPsfCallData", ['filter' => 'authFilter']);
$routes->post('get7thDayPsfCallData', "PSFModule/PSFController::get7thDayPsfCallData", ['filter' => 'authFilter']);
$routes->post('Leads/MobileLead', 'Leads\MobileLead::create');
$routes->post('Leads/ExistCalllog', 'Leads\MobileLead::existcalllog');
$routes->post('Leads/checkLeadstatus', 'Leads\MobileLead::checkleadstatus');




//new 08122023
$routes->post('userlogs', "UserLog::userlogs", ['filter' => 'authFilter']);


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
