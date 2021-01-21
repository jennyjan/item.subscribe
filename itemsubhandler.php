<?php

namespace Mikka\Sales\Subscribe;

use Bitrix\Main\Loader;
use \Datetime;
use Bitrix\Main\Diag\Debug;
Loader::includeModule('catalog');

Class ItemSubHandler
{
    /**
     * Список подписчиков на товары
     * @returns {Array}
     */
    public function getSubscribers()
    {
        $arSubscribers = [];
        $results = \Bitrix\Catalog\SubscribeTable::getList(array(
            "select"  => array("USER_CONTACT", "ID", "ITEM_ID"),
            "filter"  => array("NEED_SENDING" => "Y"),
        ));
        while ($row = $results->Fetch()) {
            $arSubscriber["EMAIL"] = $row["USER_CONTACT"];
            $arSubscriber["ID"] = $row["ID"];
            $arSubscriber["ITEM_ID"] = $row["ITEM_ID"];
            $arSubscribers[] = $arSubscriber;
        }
        return $arSubscribers;
    }
    
    /**
     * Установка поля 'NEED_SENDING' => 'N'
     */
    public function disableSendingForProduct($subscribeId)
    {
        $arError = [];
        $result = \Bitrix\Catalog\SubscribeTable::update($subscribeId, array(
            'NEED_SENDING' => 'N',
        ));
        if (!$result->isSuccess()) {
            $arError["MSG"] = $result->getErrorMessages();
            $arError["SUB_ID"] = $subscribeId;
            return $arError;
        }
    }
    
    /**
     * Запись ошибок в файл
     */
    public function writeToLogFile($arErrors)
    {
        $objDateTime = new DateTime();
        Debug::writeToFile($arErrors, "", sprintf('subscribeSendEmailErr.log', $objDateTime->getTimestamp()));
    }
    
    /**
     * Отправка уведомлений о приходе товара 
     * подписчикам на email
     */
    public function sendEmail($arSubscribes)
    {
        $arErrors = [];
        $arProducts = [];
        foreach ($arSubscribes as $arSubscribe) {
            $arProductsId[] = $arSubscribe["ITEM_ID"];
        }
        $objElement = \CIblockElement::GetList(
            array(), array("ID" => $arProductsId, "ACTIVE" => "Y"), false, false, 
            array("DETAIL_PAGE_URL", "NAME", "ID")
        ); 
        while ($arElement = $objElement->GetNext()) {
            $arProduct["NAME"] = $arElement["NAME"];
            $arProduct["PAGE_URL"] = "https://" . $_SERVER["SERVER_NAME"] . $arElement["DETAIL_PAGE_URL"];
            $arProduct["CHECKOUT_URL"] = $arProduct["PAGE_URL"]."?action=BUY&id=".$arElement["ID"];
            $arProduct["ID"] = $arElement["ID"];
            $arProducts[] = $arProduct;
        }
        foreach ($arSubscribes as $arSubscribe) {
            // Отправим уведомления всем подписавшимся пользователям
            $productIndex = array_search($arSubscribe["ITEM_ID"], array_column($arProducts, "ID"));
            \Bitrix\Main\Mail\Event::send(array(
                "EVENT_NAME" => "CATALOG_PRODUCT_SUBSCRIBE_NOTIFY",
                "LID" => "s1",
                "C_FIELDS" => array(
                    "EMAIL_TO" => $arSubscribe['EMAIL'],
                    "NAME" => $arProducts[$productIndex]["NAME"],
                    "PAGE_URL" => $arProducts[$productIndex]["PAGE_URL"],
                    "USER_NAME" => "Клиент",
                    "CHECKOUT_URL" => $arProducts[$productIndex]["CHECKOUT_URL"],
                ),
            ));
            $arError = $this->disableSendingForProduct($arSubscribe["ID"]);
            if (!empty($arError)) {
                $arErrors[] = $arError;
            }
       }
       if (!empty($arErrors)) {
           $this->writeToLogFile($arErrors);
       }
    }
}