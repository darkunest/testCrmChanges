<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle('LOGS');

$APPLICATION->IncludeComponent(
    "test:crm.log.update.list",
    ""
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
