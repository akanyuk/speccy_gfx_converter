# Speccy GFX converter

Конвертер изображений ZX Spectrum в формат PNG или GIF

## Поддерживаемые форматы:
* Обычный экран (6912 байт)
* Обычный экран без атрибутов (6144 байт)
* Gigascreen (13824 байт)
* MultiGigascreen (mgs, mg1..mg8 with header)
* Неупакованный 8color (three 256x192 RGB screen bitplanes)


## Установка и запуск 

Для запуска необходим web-сервер с поддержкой PHP не ниже v5.4, а так же [Composer](https://getcomposer.org/)

* Скопировать файлы проекта на web-сервер
* Выполнить `composer update`


## Online demo

https://nyuk.retroscene.org/gfx_converter


## Готовый сервер (Windows)

Готовый сервер nginx + php7 для Windows доступен по этой ссылке: https://disk.yandex.ru/d/nWg7CATT5Vsegg. Скачанный архив необходимо распаковать в каталог проекта.

* Запустить `win-server-start.cmd`
* Открыть в браузере `http://localhost:48128` если не открылся автоматически
