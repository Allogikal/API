<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Требования
Для безболезненного пользования запускать с конфигурацией (модулей) OpenServer:

HTTP: Apache_2.4-PHP_8.0-8.1+Nginx_1.21; <br>
PHP: 8.1; <br>
MySQL / MariaDB: MySQL-8.0-Win10.

### Примечание <br>
#### POSTMAN-коллекция настроена! НИЧЕГО ИЗМЕНЯТЬ НЕ НУЖНО!!!

### Небольшой список команд подсказок

> Для установки пакетов vendor
````
composer i
````
> Команда для миграций таблиц в БД с сидерами
````
php artisan migrate --seed
````
> Или же обновления БД
````
php artisan migrate:fresh --seed
````
> Для инициализации ключа приложения
````
php artisan key:generate
````
> Для создания символической ссылки хранилища
````
php artisan storage:link
````
> Для запуска сервера
````
php artisan serve
````

## Лицензия [MIT license](https://opensource.org/licenses/MIT).
