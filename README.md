
# rollun-parser

`rollun-service-skeleton` - скелет для построения сервисов на базе [zend-expressive](https://docs.zendframework.com/zend-expressive/).
В `rollun-service-skeleton` изначально подключены такие модули:
* [rollun-com/rollun-datastore](https://github.com/rollun-com/rollun-datastore) - абстрактное хранилище данных;
* [rollun-com/rollun-permission](https://github.com/rollun-com/rollun-permission) - проверка прав доступа и OAuth аутентификация;
* [rollun-com/rollun-logger](https://github.com/rollun-com/rollun-logger) - логирование;
* [zendframework/zend-expressive-fastroute](https://github.com/zendframework/zend-expressive-fastroute) - рутизация;
* [zendframework/zend-servicemanager](https://github.com/zendframework/zend-servicemanager) - реализация PSR-11.

`rollun-service-skeleton` имеет несколько роутов по умолчанию:
* `/` - тестовый хендлер
* `/oauth/redirect` - редирект на гугл аутентификацию
> Использовать `/oauth/redirect?action=login` для аутентификации на логин, `/oauth/redirect?action=register` для 
аутентификации на регистрацию.
* `/oauth/login` - роутинг на который google редиректит пользователя (при его успешной аутентификации) для логина
* `/oauth/register` - роутинг на который google редиректит пользователя (при его успешной аутентификации) для регистрации
* `/logout` - логаут пользователя
* `/api/datastore/{resourceName}[/{id}]` роутинг для доступу к абстрактному хранилищу, где `resourceName` название 
сервиса, а `id` - идентификатор записи.

### Установка

1. Установите зависимости.
    ```bash
    composer install
    ```

2. Для работы `rollun-com/rollun-datastore` и `rollun-com/rollun-permission` нужны таблицы в базе данных:
    * [create_table_logs.sql](https://github.com/rollun-com/rollun-logger/blob/4.2.1/src/create_table_logs.sql)
    * [acl.sql](https://github.com/rollun-com/rollun-permission/blob/4.0.0/src/Permission/src/acl.sql)
    
    Так же могут пригодиться настройки ACL по умолчанию: [acl_default.sql](/data/acl_default.sql).

3. Обязательные переменные окружения:
    * Для БД:
        - DB_DRIVER (`Pdo_Mysql` - по умолчанию)
        - DB_NAME
        - DB_USER
        - DB_PASS
        - DB_HOST
        - DB_PORT (`3306` - по умолчанию)
    
    * Для аутентификации:
        - GOOGLE_CLIENT_SECRET - client_secret в личном кабинете google
        - GOOGLE_CLIENT_ID - client_id в личном кабинете google
        - GOOGLE_PROJECT_ID - project_id в личном кабинете google
        - HOST - домен сайт где происходит авторизация
        - EMAIL_FROM - от кого отправить email для подтверждения регистрации
        - EMAIL_TO - кому отправить email для подтверждения регистрации
        
## Документация по парсингу ebay.com

**Парсеры**:
- `Ebay\Parser\Compatible` - парсинг совместимый моделей для мотоцыклов
- `Ebay\Parser\EbayMotorsPaginationSearch` - парсинг пагинации поисковой выдачи и проставка задач для парсинга поисковых
выдач для всех страниц выдачи (для верстки где родительский тэг на врепере `#SimpleSearch`)
- `Ebay\Parser\EbayMotorsSearch` - парсинг продуктов из поисковой выдачи (для верстки где родительский тэг на врепере `#SimpleSearch`)
- `Ebay\Parser\Product` - парсинг страницы продукта
- `Ebay\Parser\SimpleSearch` - парсинг продуктов из поисковой выдачи (для верстки где родительский тэг на врепере `.s-item__wrapper`)

Все воркер менеджери для загрузки и парсинга запускаються с `/api/webhook/cron`. Первый колбэк, который запускает задачу
на парсинг пагинации поисковой выдачи можна запустить с `/api/webhook/searchPaginationTask`

**Все колбєки на поставку задачи на парсинг**
- `/api/webhook/searchPaginationTask` парсинг конктретной пагинации поисковой выдачи
- `/api/webhook/searchTask`  парсинг конктретной поисковой выдачи (для теста)
- `/api/webhook/productTask`  парсинг конктретного продукта (для теста)
- `/api/webhook/compatibleTask`  парсинг конктретного документа моделй совместимости (для теста)

**Очереди которые используються для парсинга и загрузки страниц (это название очередей в клиентах очереди)**
- `ebaySearchPaginationTaskQueue`
- `ebaySearchPaginationDocumentQueue`
- `ebaySearchTaskQueue`
- `ebaySearchDocumentQueue`
- `ebayProductTaskQueue`
- `ebayProductDocumentQueue`
- `ebayCompatibleTaskQueue`
- `ebayCompatibleDocumentQueue`
- `pidQueue`

! Внутренее представление очереди для SQS и файлов могут отличаться

Для того чтобы узнать сколько сообщений в очереди и очистить очереди нужно зделать запрос на соответственные `uri`
`/queue/messages/{название_очереди}` и `/queue/purge/{название_очереди}`

## Proxy manager

Прокси менеджер уже настроен на внешний datastore, поэтому чтобы его использовать нужно указать переменную окружения
`PROXY_MANAGER_URI` (пример: PROXY_MANAGER_URI="http://proxy-provider.rollun.net/api/datastore/proxy")
