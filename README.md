# item.subscribe
Класс для отправки уведомлений о приходе товара подписчикам на email

##### Использование

    <?php
        set_time_limit(0);
        define("NO_KEEP_STATISTIC", true);
        define("NOT_CHECK_PERMISSIONS", true);
        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

        CModule::IncludeModule("iblock");
        CModule::IncludeModule('mikka.sales');

        $subscribe = new Mikka\Sales\Subscribe\ItemSubHandler();
        $arSubscribes = $subscribe->getSubscribers();
        $subscribe->sendEmail($arSubscribes);

        require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
    ?>
