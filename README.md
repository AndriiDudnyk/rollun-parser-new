
# rollun-service-skeleton

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