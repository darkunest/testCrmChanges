<?php

if (!defined('CRM_UPDATE_LOG_HLBLOCK_ID')) {
    $hlId = 0;
    $hlRes = HL\HighloadBlockTable::getList(['filter' => ['NAME' => 'CrmUpdateLog']]);
    if ($hl = $hlRes->fetch()) {
        $hlId = $hl['ID'];
    }
    if ($hlId) {
        define('CRM_UPDATE_LOG_HLBLOCK_ID', $hlId);
    }
}