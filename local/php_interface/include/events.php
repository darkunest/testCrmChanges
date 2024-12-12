<?php
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler("crm", 'OnBeforeCrmCompanyUpdate', "OnBeforeCrmCompanyUpdateHandler");
$eventManager->addEventHandler("crm", 'OnBeforeCrmDealUpdate', "OnBeforeCrmDealUpdateHandler");
$eventManager->addEventHandler("crm", 'OnBeforeCrmLeadUpdate', "OnBeforeCrmLeadUpdateHandler");


function OnBeforeCrmCompanyUpdateHandler(&$fields) {
    OnBeforeCrmItemUpdateHandler($fields, 'CRM_COMPANY');
}
function OnBeforeCrmDealUpdateHandler(&$fields) {
    OnBeforeCrmItemUpdateHandler($fields, 'CRM_DEAL');
}
function OnBeforeCrmLeadUpdateHandler(&$fields) {
    OnBeforeCrmItemUpdateHandler($fields, 'CRM_LEAD');
}

function OnBeforeCrmItemUpdateHandler(&$fields, $entityName) {
    switch ($entityName) {
        case 'CRM_COMPANY':
            $className = \CCrmOwnerType::Company;
            break;
        case 'CRM_DEAL':
            $className = \CCrmOwnerType::Deal;
            break;
        default:
            $className = \CCrmOwnerType::Lead;
            break;
    }

    $changeLog = $fields;
    $modifiedBy = $fields['MODIFY_BY_ID'];
    $elementId = $fields['ID'];
    foreach (['MODIFY_BY_ID', 'ID', '~DATE_MODIFY'] as $fieldName) {
        unset($changeLog[$fieldName]);
    }

    $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($className);
    $item = $factory->getItem($fields['ID']);
    $prev = $item->getCompatibleData();

    foreach ($changeLog as $code => $value) {
        if (!(isset($prev[$code]) && $prev[$code] != $value)) {
            unset($changeLog[$code]);
        }
    }

    if (empty($changeLog)) {
        return;
    }

    $data = [
        'UF_USER_ID' => $modifiedBy,
        'UF_ENTITY_ID' => $entityName,
        'UF_ELEMENT_ID' => $elementId,
        'UF_CHANGE_LOG' => serialize($changeLog),
        'UF_DATE' => new DateTime()
    ];

    $entity = HL\HighloadBlockTable::compileEntity(CRM_UPDATE_LOG_HLBLOCK_ID);
    $entityDataClass = $entity->getDataClass();

    $entityDataClass::add($data);
}

function RemoveLogAgent() {
    Loader::includeModule('highloadblock');

    $entity = HL\HighloadBlockTable::compileEntity(CRM_UPDATE_LOG_HLBLOCK_ID);
    $entityDataClass = $entity->getDataClass();

    $dateObj = new DateTime;
    $date = $dateObj->add("-1 month");
    $res = $entityDataClass::getList(['filter' => ['<UF_DATE' => $date]]);

    $deleteIds = [];
    while ($item = $res->fetch()) {
        $deleteIds[] = $item['ID'];
    }

    foreach ($deleteIds as $deleteId) {
        $entityDataClass::delete($deleteId);
    }

    return "RemoveLogAgent();";
}