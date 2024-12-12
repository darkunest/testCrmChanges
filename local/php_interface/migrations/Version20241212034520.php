<?php

namespace Sprint\Migration;


class Version20241212034520 extends Version
{
    protected $author = "admin_wehive";

    protected $description = "Установка агента по удалению данных";

    protected $moduleVersion = "4.15.1";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $helper->Agent()->saveAgent(array (
            'MODULE_ID' => 'main',
            'USER_ID' => '1',
            'SORT' => '0',
            'NAME' => 'RemoveLogAgent();',
            'ACTIVE' => 'Y',
            'NEXT_EXEC' => '12.12.2024 12:00:00',
            'AGENT_INTERVAL' => '2678400', // месяц в секундах
            'IS_PERIOD' => 'N',
            'RETRY_COUNT' => '0',
        ));
    }
}