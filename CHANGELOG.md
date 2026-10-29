# Журнал изменений · Changelog

## Не выпущено — исправление 29 октября 2026 года

**Исправлено:** обрезка текста с многоточием резала буквы. `rtrim()` со списком знаков, где есть «—», «–» или «…», работает с байтами, а не со знаками: в UTF-8 эти знаки занимают по три байта, и функция срезала с конца строки отдельные байты 0x80, 0x93, 0x94, 0xE2 — вместе с ними второй байт таких букв, как «р» и «ғ». Оставалась строка с обрывком буквы; в отрывке карточки могла оказаться битая буква, а разметка schema.org (JSON-LD) с такой строкой не кодировалась — страница с модулем падала с ошибкой 500 (так случилось с модулем «Фото» на vrknews.kz/kk, 29 октября 2026 года). Теперь хвост снимается `preg_replace('/[…]+$/u', '', …)` — по знакам.

## 1.1.0 — 2026-10-15

**Исправлено**
- Категории, метки и авторы выбираются так же, как в ядре Joomla: «чипами» из выпадающего списка с поиском. Убран двойной селект у меток.

**Добавлено**
- Проверка обновлений: Joomla сообщает о новой версии на панели управления и в «Система → Обновление → Расширения» и ставит её в один клик; целостность архива проверяется по SHA-256.
- Архив файлов для распаковки в корень сайта поверх установленного модуля.
- Казахский интерфейс (kk-KZ): настройки модуля, тексты на сайте и сообщения установщика.
- README на трёх языках: английском, русском и казахском.

**Fixed**
- Categories, tags and authors are picked as in the Joomla core: chips from a searchable dropdown. The doubled tag select is gone.

**Added**
- Update checks: Joomla announces a new version in the control panel and in “System → Update → Extensions” and installs it in one click; the archive is verified by SHA-256.
- An archive of files to unpack into the site root over an installed module.
- Kazakh interface (kk-KZ): module settings, site texts and installer messages.
- README in three languages: English, Russian and Kazakh.

## 1.0.0 — 2026-10-06

Первый публичный выпуск.

First public release.
