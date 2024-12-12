<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var CBitrixComponent $this */
/** @var array $this- >arParams */
/** @var array $this- >arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */

/** @global CMain $APPLICATION */

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;
use Bitrix\Crm\CompanyTable;
use Bitrix\Main\UserTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\DealTable;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Main\Loader;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Service;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Crm\RoleTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Iblock;

class CrmLogUpdateList extends CBitrixComponent
{

    const GRID_ID = 'crm_log_update_items';
    /**
     * Execute component
     *
     * @return void
     */
    public function executeComponent(): void
    {
        /*if ($this->isAllParametersSet()) {
            self::addToolbarButtonSettings();
            try {
                // Data for selects in layout
                $this->arResult['USER_LIST'] = $this->getUsersListForSelect();
                $this->arResult['BP_LIST'] = $this->getBPListForSelect();
                // Process permissions
                $this->arResult['CRM_ROLE_LIST'] = $this->getNeedleCrmRoles();
                $this->arResult['CRM_ROLE_RELATION_LIST'] = $this->getRolesRelationsListByRolesFromParameters();
                $this->arResult['PERMISSION'] = $this->calculateMaxCrmRolePermission();
                $this->arResult['CURRENT_USER_DEPARTMENT'] = self::getCurrentUserDepartmentsId();
            } catch (ObjectPropertyException|ArgumentException|SystemException $e) {
                ShowError($e->getMessage());
            }
        } else {
            $this->getErrorMessageListParametersNotSet();
        }
        self::addToolbarButtonBack();*/

        $this->arResult['GRID_ID'] = self::GRID_ID;

        $this->getGridOptions();
        $this->getNavObject();
        $this->getGridSort();

        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['ITEMS'] = $this->getData();

        $this->arResult['FILTER_FIELDS'] = $this->getFilterFields();

        $this->includeComponentTemplate();
    }
    // endregion

    // region toolbar
    /**
     * Add filter fields
     *
     * @return array
     */
    private function getFilterFields()
    {
        return [
            ['id' => 'UF_DATE', 'name' => 'DATE', 'type' => 'date', 'default' => true],
            ['id' => 'UF_USER_ID', 'name' => 'USER_ID', 'type' => 'dest_selector', 'default' => true, 'params' => [
                'contextCode' => 'U',
                'enableAll' => 'N',
                'enableSonetgroups' => 'N',
                'allowEmailInvitation' => 'N',
                'allowSearchEmailUsers' => 'N',
                'departmentSelectDisable' => 'Y',
                'isNumeric' => 'Y',
                'prefix' => 'U',
            ]],
        ];
    }
    /*private static function addToolbarButtonSettings(): void
    {
        $button = new Button([
            'color' => Color::LIGHT_BORDER,
            'icon' => Icon::SETTINGS,
            'menu' => [
                'items' => [
                    [
                        'text' => Loc::getMessage('BUTTON_EXPORT_EXCEL'),
                        'title' => Loc::getMessage('BUTTON_EXPORT_EXCEL'),
                        'onclick' => new JsCode(
                            'BX.Ithive.DepartmentReport.exportToExcel()'
                        ),
                        'className' => 'excel'
                    ],
                ],
            ],
        ]);
        Toolbar::addButton($button);
    }*/

    /**
     * get columns for grid
     *
     * @return array
     */
    private function getColumns()
    {
        return [
            ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
            ['id' => 'USER', 'name' => 'USER_ID', 'sort' => 'UF_USER_ID', 'default' => true],
            ['id' => 'UF_ENTITY_ID', 'name' => 'ENTITY_ID', 'default' => true],
            ['id' => 'UF_ELEMENT_ID', 'name' => 'ELEMENT_ID', 'default' => true],
            ['id' => 'UF_CHANGE_LOG', 'name' => 'CHANGE_LOG', 'default' => true],
            ['id' => 'UF_DATE', 'name' => 'DATE', 'sort' => 'UF_DATE', 'default' => true],
        ];
    }

    protected function getGridSort() {
        $this->gridSort = $this->gridOptions->GetSorting()['sort'];
    }

    protected function getGridOptions() {
        $this->gridOptions =  new Bitrix\Main\Grid\Options(self::GRID_ID);
    }

    private function getNavParams() {
        $this->navParams = $this->gridOptions->GetNavParams();
    }

    protected function getNavObject() {
        $this->getNavParams();

        $this->navObject = new Bitrix\Main\UI\PageNavigation(self::GRID_ID);
        $this->navObject->allowAllRecords(true)
            ->setPageSize($this->navParams['nPageSize'])
            ->initFromUri();

        if ($this->navObject->allRecordsShown()) {
            $this->navParams = false;
        } else {
            $this->navParams['iNumPage'] = $this->navObject->getCurrentPage();
        }
        //echo '<pre>';print_r($this->navParams);echo '</pre>';
    }

    /**
     * get log items from highloadblock
     *
     * @return array
     */
    private function getData()
    {
        if (!Loader::includeModule('highloadblock')) {
            return [];
        }
        $this->arResult['NAV_OBJECT'] = $this->navObject;

        $filter = [];
        $filterOption = new Bitrix\Main\UI\Filter\Options(self::GRID_ID);
        $filterData = $filterOption->getFilter([]);
        foreach ($filterData as $k => $v) {
            $filter[$k] = $v;
        }
        $finalFilter = [];
        if (isset($filter['UF_DATE_from'])) {
            $finalFilter['>=UF_DATE'] = $filter['UF_DATE_from'];
        }
        if (isset($filter['UF_DATE_to'])) {
            $finalFilter['<=UF_DATE'] = $filter['UF_DATE_to'];
        }
        if (isset($filter['UF_USER_ID'])) {
            $finalFilter['UF_USER_ID'] = $filter['UF_USER_ID'];
        }

        $hlId = 0;
        $hlRes = \Bitrix\Highloadblock\HighloadBlockTable::getList(['filter' => ['NAME' => 'CrmUpdateLog']]);
        if ($hl = $hlRes->fetch()) {
            $hlId = $hl['ID'];
        }

        $items = [];
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlId);
        $entityDataClass = $entity->getDataClass();
        $res = $entityDataClass::getList([
            'filter' => $finalFilter,
            'order' => $this->gridSort,
            'offset' => $this->navObject->getOffset(),
            'limit' => $this->navObject->getLimit(),
            'count_total' => true
        ]);

        $this->navObject->setRecordCount($res->getCount());

        $userIds = [];
        $users = [];
        while ($item = $res->fetch()) {
            $changeLog = unserialize($item['UF_CHANGE_LOG']);
            array_walk($changeLog, function (&$it, $key) {
                $it = $key . ' = ' . $it;
            });
            $item['UF_CHANGE_LOG'] = implode(', ', $changeLog);
            $items[$item['ID']] = ['data' => $item];
            if (!in_array($item['UF_USER_ID'], $userIds)) {
                $userIds[] = $item['UF_USER_ID'];
            }
        }

        $resUser = UserTable::getList(['filter' => ['ID' => $userIds]]);
        while ($user = $resUser->fetch()) {
            $users[$user['ID']] = $user;
        }

        $nameTemplate = \Bitrix\Main\Context::getCurrent()->getCulture()->getFormatName();
        foreach ($items as &$item) {
            $item['data']['USER'] = '<a href="/company/personal/user/'. $users[$item['data']['UF_USER_ID']]['ID'] .'">' . \CUser::formatName($nameTemplate, $users[$item['data']['UF_USER_ID']], true, false) . '</a>';
        }
        return $items;
    }
}