# Webmarka Articles Grid

![version](https://img.shields.io/badge/version-1.0.0-1e5b54) ![Joomla](https://img.shields.io/badge/Joomla-5%20%7C%206-1e5b54) ![PHP](https://img.shields.io/badge/PHP-8.1%2B-1e5b54) ![UIkit](https://img.shields.io/badge/UIkit-3-1e5b54) ![license](https://img.shields.io/badge/license-GPL--2.0--or--later-1e5b54)

[Русский](#русский) · [English](#english)

---

## Русский

Модуль для Joomla 5 и 6: материалы сайта сеткой карточек или мозаикой на UIkit 3 — с миниатюрами WebP, фильтром на странице и группировкой.

Сетка выводит материалы Joomla карточками UIkit: обложка, заголовок, вводный текст и кнопка «Читать далее». Карточки складываются в адаптивную сетку от одной до пяти колонок или в мозаику, где карточки разной высоты ложатся без пустот. Над сеткой можно поставить фильтр: посетитель переключает категории, метки, авторов, годы или месяцы, и лишние карточки исчезают без перезагрузки страницы.

### Возможности

#### Подбор материалов

- **Категории** — одна или несколько, с подкатегориями на заданную глубину.
- **Динамический режим:** модуль сам берёт категорию открытой страницы — на странице категории выводит её материалы, на странице материала выводит материалы той же категории (последнее можно отключить).
- **Избранные материалы:** показывать вместе с остальными, скрыть или выводить только их.
- **Метки и авторы:** только материалы с выбранными метками; выбранных авторов включить или исключить либо показывать материалы только текущего пользователя.
- **Даты:** диапазон или последние N дней — по дате создания, изменения или публикации.
- **Сортировка** по девяти признакам: порядок материалов, порядок избранных, заголовок, ID, даты создания, изменения и публикации, число просмотров, случайный порядок; по возрастанию или убыванию.
- **Группировка** по году, месяцу, автору, категории или меткам, в прямом или обратном порядке: каждая группа выводится отдельным блоком с подзаголовком, материалы без меток собираются в группу «Без меток».
- Выборку делает модель материалов самой Joomla, поэтому учитываются права доступа, языковой фильтр, сроки публикации и сроки, в которые материал считается избранным.

#### Вывод

- **Два макета данных:** карточки с фото или только заголовки с вводным текстом.
- **Шесть положений обложки:** сверху, снизу, слева, справа и две «зебры» — вертикальная (фото то сверху, то снизу) и горизонтальная (то слева, то справа). «Зебра» — журнальный приём: ряд карточек перестаёт быть монотонным.
- **Четыре структуры карточки:** только тело; шапка и тело; тело и подвал с кнопкой; шапка, тело и подвал. Подвал всегда прижат к низу карточки, даже когда тексты разной длины.
- **Сетка:** одна или две колонки на смартфоне, от одной до четырёх на планшете, от двух до пяти на компьютере; четыре размера отступов; одинаковая высота карточек в ряду.
- **Мозаика (masonry).** Слово пришло из английского, где оно значит «каменная кладка»: карточки разной высоты укладываются по столбцам, как камни в стене, без пустых просветов.
- **Горизонтальные карточки** на смартфоне сами встают в столбик: фото и текст идут друг под другом.

### Фильтр на странице

- Фильтр UIkit (`uk-filter`) над сеткой: по категории, метке, автору, году или месяцу. Кнопки строятся из тех материалов, что выведены, — пустых кнопок не бывает.
- Четыре вида меню: разделитель, таблетки, вкладки, группа кнопок; выравнивание слева, по центру или справа.
- Анимация: сдвиг, затухание, затухание по очереди или без анимации.
- Текст кнопки «Все» задаётся в настройках, по умолчанию берётся перевод.
- При включённой группировке фильтр не выводится: каждая группа и так отдельный блок.

### Изображения

- **Источник превью** — вводное или полное изображение материала, с запасным вариантом или без него. По умолчанию — полное, а если его нет, вводное.
- **Обработка:** миниатюры JUImage или оригинал как есть. JUImage режет обложку по центру точно в заданный размер (по умолчанию 800 × 500) и сохраняет в WebP; режим «оригинал» не создаёт ни одной миниатюры — удобно, когда вводные изображения уже подготовлены нужного размера.
- **srcset и sizes:** браузер сам выбирает файл по ширине экрана и плотности пикселей; атрибут `sizes` модуль считает по колонкам сетки. Телефон получает свою миниатюру (400 × 300, плюс вдвое крупнее для плотных экранов) через элемент `<picture>`.
- Обложка держит пропорцию заданного размера на любом экране: высоту задаёт невидимый `<canvas>` внутри `uk-cover-container`, а фото заполняет его, как обложка журнала.
- **Режимы наложения** (blend modes) — те же, что в графических редакторах: цветная маска смешивается с фото по выбранному правилу. Пятнадцать режимов UIkit, от умножения до яркости; цвет и непрозрачность маски настраиваются.

### Оформление

- **Тег заголовка в паре с классом UIkit:** тег (h1–h6 или div) выбирается ради структуры страницы и поисковиков, а визуальный размер — ради дизайна: `uk-h1`–`uk-h6`, `uk-heading-small`, `uk-heading-medium`, `uk-heading-large`, `uk-text-lead`, `uk-text-large`. Так `<h3 class="uk-h2">` выглядит как h2, оставаясь h3 в структуре. Когда размер выбран, класс `uk-card-title` из дополнительных классов снимается сам: в CSS UIkit он стоит позже и перебил бы размер.
- Шапка модуля: свой заголовок со ссылкой, тег, размер и стиль UIkit — линия, маркер или разделитель.
- Свои классы заголовка и текста карточки (по умолчанию `uk-card-title uk-link-heading` и `uk-text-muted uk-text-small`).
- Суффикс класса модуля, альтернативные макеты, кэш модуля: по глобальной настройке или выключен, время кэша.

### Вводный текст

- **Вид:** простой текст — без тегов и картинок, одним абзацем, его ровно оформляют классы UIkit; или HTML материала — разметка сохраняется.
- **Обрезка по символам или по словам.** Модуль ядра Joomla режет только по символам; здесь можно оставить, скажем, двадцать слов, и слово никогда не рвётся посередине. В конце — многоточие.
- **Оформление классами UIkit:** размер (`uk-text-meta`, `uk-text-small`, `uk-text-large`, `uk-text-lead`), цвет (`uk-text-muted`, `uk-text-emphasis`, `uk-text-primary`, `uk-text-secondary`), начертание (курсив, светлое, полужирное), выравнивание (влево, по центру, вправо, по ширине) и свои дополнительные классы.
- **Не больше N строк:** текст обрывается многоточием на заданной строке, и карточки выходят ровными. В UIkit такого класса нет — это делает свойство CSS `line-clamp`.

### SEO и соцсети

- **Микроразметка JSON-LD** (по умолчанию): один блок `ItemList` в коде страницы — ссылки, заголовки и картинки материалов. Google советует именно JSON-LD: разметка не смешивается с вёрсткой и не ломается при переопределении макетов.
- **Микроданные schema.org** — по желанию: атрибуты прямо в разметке (`Article` у каждой карточки). Или ничего, если разметку строит шаблон.
- **Картинка для соцсетей (`og:image`).** Когда ссылку на страницу публикуют в Telegram, Facebook или WhatsApp, превью берётся из `og:image`. Модуль добавляет полное изображение первого материала (или нескольких — до десяти) с `og:image:width`, `og:image:height` и `og:image:alt`. В режиме «Авто» — только если у страницы своего `og:image` нет и это не страница материала: у материала своя картинка, и модуль её не перебивает. Есть режимы «Всегда» и «Не добавлять».

### Преимущества

1. **Под капотом — ядро Joomla.** Материалы выбирает та же модель `com_content`, что и у модуля ядра «Материалы» (`mod_articles`). Модуль не пишет собственных SQL-запросов, а значит не обходит права доступа, языки и сроки публикации и не ломается от обновлений Joomla.
2. **Чистый UIkit.** Разметка собрана из штатных компонентов UIkit — card, grid, cover, slider, filter, modal, lightbox. Модуль сразу говорит языком вашего шаблона на UIkit: его цветами, шрифтами, отступами, тёмными секциями. Собственных стилей — несколько строк, и только там, где без них не обойтись.
3. **Лёгкие страницы.** Миниатюры WebP точного размера, `srcset` под ширину экрана и плотность пикселей, отдельные миниатюры для телефона, ленивая загрузка картинок. JUImage делает файл один раз и дальше отдаёт его из папки `img/`. А если миниатюры не нужны, модуль выводит оригиналы и не создаёт ни одного нового файла.
4. **Работает в любой обстановке.** Нет JUImage — выводит исходные картинки. Нет UIkit в шаблоне — подгружает UIkit с CDN с проверкой целостности. Никаких зависимостей, кроме UIkit: ни jQuery, ни своих библиотек.
5. **Поисковики и соцсети.** JSON-LD или микроданные schema.org и `og:image` для превью ссылок — с осторожным режимом «Авто», который не перебивает картинку материала.
6. **Аккуратный вывод.** Всё, что приходит из настроек и материалов, экранируется; теги заголовков проверяются по белому списку; на тестовой странице при уровне ошибок PHP «максимум» — ни одного предупреждения.
7. **Переопределяется по частям.** Каждый подшаблон можно переопределить в шаблоне сайта отдельно, а вспомогательные методы помощника доступны и в своих макетах.
8. **Два языка.** Интерфейс и тексты на сайте — на русском и английском; все строки в языковых файлах.
9. **Проверено в деле:** Joomla 6.1.4, PHP 8.3, UIkit 3.25.26, JUImage 5.21 — без предупреждений PHP, без ошибок в консоли браузера, без прокрутки вбок на телефоне, планшете и компьютере.

### Требования

- Joomla 5.0 или новее, Joomla 6 (проверено на 6.1.4);
- PHP 8.1 или новее (проверено на 8.3);
- UIkit 3 — из шаблона сайта, а если его нет, модуль подгрузит UIkit сам (см. «UIkit»);
- JUImage — необязательна; пакет поставит её сам, если на сайте её нет.

### Установка

В разделе **Releases** лежат два архива:

| архив | что внутри | когда брать |
|---|---|---|
| `pkg_wmarka_articles_grid_v1.0.0.zip` | модуль и библиотека JUImage 5.21 | обычно — его |
| `mod_wmarka_articles_grid_v1.0.0.zip` | только модуль | JUImage уже стоит или не нужна |

Архив ставится через «Система → Установка → Расширения». Затем «Содержимое → Модули сайта → Создать», выберите «Webmarka: Сетка новостей (UIkit 3)», позицию и страницы.

Что делает пакет при установке:

1. проверяет версии PHP (от 8.1) и Joomla (от 5.0) и останавливает установку, если они ниже;
2. ставит модуль;
3. ищет на сайте `libraries/juimage/vendor/autoload.php` — файл, через который модуль подключает JUImage. Нашёл — библиотеку не трогает, какой бы версии она ни была. Не нашёл — ставит JUImage 5.21 из официального выпуска, без изменений;
4. сообщает, откуда модуль возьмёт UIkit.

JUImage ставится **самостоятельной библиотекой, а не частью пакета**. Пакет Joomla при удалении удаляет всё, что перечислено в его манифесте, а библиотекой могут пользоваться шаблон и другие модули — поэтому при удалении пакета исчезает только модуль, а JUImage остаётся. Удалить её можно вручную в «Расширения: управление».

Обновление — тем же путём: установите новый пакет поверх прежнего, настройки модулей сохранятся.

### UIkit

Модуль собран из компонентов UIkit 3 и рассчитан на шаблоны на UIkit — например, [wmarka](https://github.com/bsakhanov/wmarka). Как модуль получает UIkit, решает настройка «Подключение UIkit» на вкладке «Дополнительно»:

- **Авто** (по умолчанию). Сначала сервер ищет UIkit среди ресурсов страницы — в WebAssetManager Joomla и в подключённых скриптах и стилях: если имя или адрес ресурса содержит «uikit», модуль ничего не добавляет. Если не нашёл (например, шаблон вписал UIkit прямо в `index.php`), модуль добавляет крошечный скрипт: когда страница загружена, он проверяет `window.UIkit` и только при его отсутствии подключает UIkit с CDN. Сколько бы модулей ни стояло на странице, скрипт один.
- **Всегда с CDN:** стили и скрипты UIkit подключаются в шапке страницы через WebAssetManager, скрипты — с атрибутом `defer`.
- **Не подключать:** за UIkit целиком отвечает шаблон.

CDN — сеть доставки содержимого: файлы отдаются с ближайшего к посетителю сервера. По умолчанию это jsDelivr и UIkit 3.25.26: `uikit.min.css`, `uikit.min.js`, `uikit-icons.min.js`. Каждый файл подключается с проверкой целостности (Subresource Integrity, SRI): браузер сверяет хеш sha384 и не запустит подменённый файл. В поле «Адрес CDN для UIkit» можно указать другую версию, другой CDN или локальную копию — папку `dist` UIkit; проверка целостности действует только для адреса по умолчанию.

Что важно знать:

- UIkit — полноценный фреймворк со своими базовыми стилями. Подключённый к шаблону без UIkit (например, к Cassiopeia), он поменяет и типографику самого шаблона: заголовки, ссылки, отступы. Автоподключение — страховка, а естественная среда модуля — шаблон на UIkit.
- В режиме «Авто» с подгрузкой из браузера стили приходят после первой отрисовки, и на долю секунды модуль виден без оформления. Если UIkit на сайте берётся только из модуля, поставьте «Всегда с CDN».
- Если на сайте действует политика безопасности содержимого (CSP), разрешите `cdn.jsdelivr.net` или укажите локальную копию UIkit.

### Изображения и JUImage

[JUImage](https://github.com/Joomla-Ukraine/JUImage) — библиотека Дениса Носова (Joomla! Ukraine) на основе phpThumb: она делает миниатюры и складывает их в папку `img/` в корне сайта. Модуль просит у неё:

- обрезку по центру точно в заданный размер (ключ `zc`);
- формат WebP — при том же качестве он заметно легче JPEG — с качеством из настроек (по умолчанию 70);
- набор ширин для `srcset` (по умолчанию 400, 800 и 1200 пикселей плюс основная ширина); шире оригинала миниатюры не делаются — JUImage умеет растягивать, но это лишний вес без пользы;
- телефону — отдельную миниатюру (и вдвое крупнее для плотных экранов), которую браузер выбирает через `<picture>` на экранах до 640 пикселей;
- источник — выбранное в настройках изображение материала (по умолчанию полное, затем вводное); хвост `#joomlaImage://…`, который Joomla дописывает к путям, отрезается.

Режим «Оригинал, без миниатюр» не обращается к JUImage вовсе: выводится файл материала как есть, с настоящими `width` и `height` — из данных Joomla или из самого файла.

Миниатюра создаётся при первом показе, дальше отдаётся готовый файл. Имя файла учитывает размеры и качество: изменили настройки — появятся новые файлы, а старые можно удалить вместе с папкой `img/`. JUImage узнаёт миниатюру по имени исходника, поэтому заменённую картинку загружайте под новым именем.

Без JUImage модуль выводит исходные картинки — ничего не ломается.

### Переопределение макетов

Скопируйте нужные файлы из `modules/mod_wmarka_articles_grid/tmpl/` в `templates/<ваш шаблон>/html/mod_wmarka_articles_grid/` и правьте копии:

| файл | что выводит |
|---|---|
| `default.php` | обёртка: шапка модуля, фильтр, группы |
| `default_items.php` | карточки с фото |
| `default_titles.php` | карточки без фото |

Если у альтернативного макета нет своего подшаблона, берётся стандартный. В макетах доступны `$module`, `$params`, `$list` (материалы, а при группировке — массив «группа → материалы»), `$grouped`, `$items` (материалы текущей группы, в подшаблонах), `$moduleClass`, `$juImg`, `$blendMode`, `$overlayRgba`, а также статические методы `ArticlesGridHelper`: `picture()` и `pictureTag()` (превью с `srcset`), `fullImage()`, `formatIntro()`, `introAttributes()`, `titleClass()`, `sizes()`, `filterClasses()`, `filterOptions()`, `rgba()`, `seo()`, `getJuImage()`, `loadUikit()`.

### Устройство

Модуль написан по правилам Joomla 4 и новее. `services/provider.php` регистрирует диспетчер и помощник. Диспетчер `Dispatcher` готовит данные для макетов: список материалов через кэш модулей Joomla, экземпляр JUImage, цвет маски; подключает UIkit, JSON-LD и `og:image`; пустой список ничего не выводит. Помощник `ArticlesGridHelper` строит выборку через модель `Articles` компонента `com_content`. Пространство имён — `Webmarka\Module\ArticlesGrid\Site`.

Репозиторий:

```
module/                 исходники модуля
package/                манифест пакета, установочный скрипт, языковые файлы пакета
third_party/juimage/    официальный архив JUImage 5.21 без изменений и его лицензия
build.sh                сборка архивов в dist/
```

Сборка: `./build.sh` — нужны bash и zip, а если есть php, скрипт заодно проверит синтаксис. В `dist/` появятся оба архива и файл `SHA256SUMS`.

### Лицензия

Модуль распространяется по GNU GPL версии 2 или новее (файл `LICENSE`). Логика выборки материалов выведена из модуля ядра `mod_articles` (© Open Source Matters, Inc., GNU GPL).

Сторонние компоненты:

- **JUImage** — © Денис Носов, Joomla! Ukraine; GNU GPL версии 2 или новее, см. `third_party/juimage/LICENSE.md`. Входит в пакет без изменений.
- **UIkit** — © YOOtheme, лицензия MIT. В репозиторий и архивы не входит: берётся из шаблона или с CDN.

### Автор

Beibit Sakhanov — [webmarka.kz](https://webmarka.kz)

---

## English

A Joomla 5 and 6 module that shows articles as a UIkit 3 card grid or masonry, with WebP thumbnails, an on-page filter and grouping.

The grid shows Joomla articles as UIkit cards: cover image, title, intro text and a «Read more» button. Cards line up in a responsive grid of one to five columns or in a masonry layout where cards of different heights fit together without gaps. A filter can sit above the grid: visitors switch between categories, tags, authors, years or months, and the other cards disappear without a page reload.

### Features

#### Article selection

- **Categories** — one or several, with subcategories down to the depth you set.
- **Dynamic mode:** the module takes the category of the current page — on a category page it shows that category, on an article page the articles of the same category (the latter can be switched off).
- **Featured articles:** show them with the rest, hide them or show only them.
- **Tags and authors:** only articles with the chosen tags; include or exclude the chosen authors, or show only the current user's articles.
- **Dates:** a range or the last N days — by creation, modification or publishing date.
- **Ordering** by nine keys: article order, featured order, title, ID, creation, modification and publishing dates, hits, random; ascending or descending.
- **Grouping** by year, month, author, category or tags, in either direction: each group is a separate block with a sub-heading; untagged articles go into an «Untagged» group.
- Articles are selected by Joomla's own article model, so access levels, the language filter, publishing dates and featured dates are respected.

#### Output

- **Two data layouts:** cards with images, or titles with intro text only.
- **Six cover positions:** top, bottom, left, right and two «zebras» — vertical (image alternately on top and at the bottom) and horizontal (alternately left and right), a magazine technique that breaks the monotony of a row.
- **Four card structures:** body only; header and body; body and footer with a button; header, body and footer. The footer always sticks to the bottom of the card, whatever the text length.
- **Grid:** one or two columns on phones, one to four on tablets, two to five on desktops; four gutter sizes; equal card height per row.
- **Masonry.** The word means stonework: cards of different heights are laid in columns like stones in a wall, with no empty gaps.
- **Horizontal cards** stack on phones by themselves: image and text go one under the other.

### On-page filter

- A UIkit filter (`uk-filter`) above the grid: by category, tag, author, year or month. Buttons are built from the articles on display, so there are never empty buttons.
- Four menu styles: divider, pills, tabs, button group; left, centre or right alignment.
- Animation: slide, fade, delayed fade or none.
- The «All» button text is set in the options; by default it is translated.
- When grouping is on the filter is not shown: each group is already a separate block.

### Images

- **Preview source** — the article's intro or full image, with or without a fallback. Full by default, intro when there is no full image.
- **Processing:** JUImage thumbnails or the original as it is. JUImage crops the cover from the centre to the exact size (800 × 500 by default) and saves WebP; the original mode makes no thumbnail at all — handy when intro images are already prepared at the right size.
- **srcset and sizes:** the browser picks the file by screen width and pixel density; the module computes `sizes` from the grid columns. Phones get their own thumbnail (400 × 300, plus a double one for dense screens) through a `<picture>` element.
- The cover keeps the ratio of that size on any screen: an invisible `<canvas>` inside `uk-cover-container` sets the height and the image fills it like a magazine cover.
- **Blend modes** work as in image editors: a coloured mask is mixed with the photo by the chosen rule. Fifteen UIkit modes, from multiply to luminosity; mask colour and opacity are adjustable.

### Design

- **Heading tag paired with a UIkit class:** the tag (h1–h6 or div) is chosen for page structure and search engines, the visual size for design: `uk-h1`–`uk-h6`, `uk-heading-small`, `uk-heading-medium`, `uk-heading-large`, `uk-text-lead`, `uk-text-large`. So `<h3 class="uk-h2">` looks like h2 and stays h3 in the structure. When a size is chosen, `uk-card-title` is dropped from the extra classes by itself: it comes later in the UIkit CSS and would override the size.
- Module heading: your own title with a link, tag, size and UIkit style — line, bullet or divider.
- Your own classes for the card title and text (`uk-card-title uk-link-heading` and `uk-text-muted uk-text-small` by default).
- Module class suffix, alternative layouts, module caching: global setting or off, cache time.

### Intro text

- **Format:** plain text — no tags or images, one paragraph that UIkit classes style evenly; or the article HTML with its markup kept.
- **Trim by characters or by words.** Joomla's core module trims by characters only; here you can keep, say, twenty words, and a word is never cut in half. An ellipsis ends the text.
- **UIkit styling:** size (`uk-text-meta`, `uk-text-small`, `uk-text-large`, `uk-text-lead`), colour (`uk-text-muted`, `uk-text-emphasis`, `uk-text-primary`, `uk-text-secondary`), style (italic, light, bold), alignment (left, centre, right, justify) and your own extra classes.
- **At most N lines:** the text ends with an ellipsis on the given line, so cards line up. UIkit has no class for this — the CSS `line-clamp` property does it.

### SEO and social networks

- **JSON-LD structured data** (default): one `ItemList` block in the page code — links, titles and images of the articles. Google recommends JSON-LD: it does not mix with the markup and survives layout overrides.
- **schema.org microdata** — optional: attributes right in the markup (`Article` on every card). Or nothing, when the template builds the structured data.
- **Social image (`og:image`).** When a page link is shared in Telegram, Facebook or WhatsApp, the preview comes from `og:image`. The module adds the full image of the first article (or of several, up to ten) with `og:image:width`, `og:image:height` and `og:image:alt`. In Auto mode only when the page has no `og:image` of its own and it is not an article page: an article has its own picture, and the module does not override it. There are Always and Off modes too.

### Advantages

1. **Joomla core under the hood.** Articles are selected by the same `com_content` model as the core «Articles» module (`mod_articles`). The module writes no SQL of its own, so it never bypasses access levels, languages or publishing dates and does not break with Joomla updates.
2. **Pure UIkit.** The markup is built from standard UIkit components — card, grid, cover, slider, filter, modal, lightbox. The module speaks the language of your UIkit template at once: its colours, fonts, spacing, dark sections. It has a few lines of its own CSS, only where there is no way around them.
3. **Light pages.** Exact-size WebP thumbnails, `srcset` for screen width and pixel density, separate phone thumbnails, lazy-loaded images. JUImage makes a file once and then serves it from the `img/` folder. And when thumbnails are not wanted, the module shows the originals and creates no new files.
4. **Works anywhere.** No JUImage — the original images are shown. No UIkit in the template — UIkit is loaded from a CDN with an integrity check. No dependencies besides UIkit: no jQuery, no libraries of its own.
5. **Search engines and social networks.** JSON-LD or schema.org microdata and `og:image` for link previews — with a careful Auto mode that never overrides the article's own picture.
6. **Careful output.** Everything that comes from the options and the articles is escaped; heading tags are checked against a whitelist; with PHP error reporting at «maximum» the test page shows not a single warning.
7. **Override piece by piece.** Each sublayout can be overridden in the site template on its own, and the helper's static methods are available to your layouts.
8. **Two languages.** Admin interface and site texts in Russian and English; all strings live in language files.
9. **Tested:** Joomla 6.1.4, PHP 8.3, UIkit 3.25.26, JUImage 5.21 — no PHP warnings, no browser console errors, no horizontal scroll on phones, tablets or desktops.

### Requirements

- Joomla 5.0 or later, Joomla 6 (tested on 6.1.4);
- PHP 8.1 or later (tested on 8.3);
- UIkit 3 — from the site template; when there is none, the module loads UIkit itself (see «UIkit»);
- JUImage — optional; the package installs it when the site has none.

### Installation

The **Releases** section has two archives:

| archive | contents | when to use |
|---|---|---|
| `pkg_wmarka_articles_grid_v1.0.0.zip` | the module and the JUImage 5.21 library | usually this one |
| `mod_wmarka_articles_grid_v1.0.0.zip` | the module only | JUImage is already installed or not needed |

Install it in «System → Install → Extensions». Then «Content → Site Modules → New», choose «Webmarka: Articles Grid (UIkit 3)», a position and pages.

What the package does on install:

1. checks the PHP (8.1+) and Joomla (5.0+) versions and stops if they are lower;
2. installs the module;
3. looks for `libraries/juimage/vendor/autoload.php`, the file the module loads JUImage through. If it is there, the library is left alone, whatever its version. If not, JUImage 5.21 is installed from the official release, unchanged;
4. tells you where the module will take UIkit from.

JUImage is installed as a **standalone library, not as a child of the package**. When a Joomla package is uninstalled it removes everything listed in its manifest, and the library may be used by the template and other modules — so uninstalling the package removes only the module and JUImage stays. You can remove it by hand in «Extensions: Manage».

Updating works the same way: install the new package over the old one; module settings are kept.

### UIkit

The module is built from UIkit 3 components and is meant for UIkit templates such as [wmarka](https://github.com/bsakhanov/wmarka). The «UIkit Loading» option on the Advanced tab decides how it gets UIkit:

- **Auto** (default). First the server looks for UIkit among the page assets — in Joomla's WebAssetManager and in the attached scripts and styles: if an asset name or address contains «uikit», the module adds nothing. If nothing is found (the template may have written UIkit straight into `index.php`), the module adds a tiny script: once the page has loaded it checks `window.UIkit` and loads UIkit from the CDN only when it is missing. However many modules are on the page, there is one script.
- **Always from CDN:** UIkit styles and scripts are added to the page head through the WebAssetManager, scripts with `defer`.
- **Do not load:** the template is fully responsible for UIkit.

A CDN (content delivery network) serves files from the server nearest to the visitor. By default it is jsDelivr with UIkit 3.25.26: `uikit.min.css`, `uikit.min.js`, `uikit-icons.min.js`. Every file comes with Subresource Integrity (SRI): the browser checks its sha384 hash and refuses a tampered file. The «UIkit CDN Address» field takes another version, another CDN or a local copy — the UIkit `dist` folder; the integrity check applies to the default address only.

Worth knowing:

- UIkit is a full framework with its own base styles. Loaded into a template without UIkit (Cassiopeia, for one), it also changes that template's typography: headings, links, spacing. Auto-loading is a safety net; the module's natural home is a UIkit template.
- In Auto mode with loading from the browser, styles arrive after the first paint, so for a split second the module is unstyled. If the module is the only source of UIkit on the site, choose «Always from CDN».
- If the site has a Content Security Policy (CSP), allow `cdn.jsdelivr.net` or point to a local copy of UIkit.

### Images and JUImage

[JUImage](https://github.com/Joomla-Ukraine/JUImage) is a library by Denys Nosov (Joomla! Ukraine) built on phpThumb: it makes thumbnails and keeps them in the `img/` folder at the site root. The module asks it for:

- a centre crop to the exact size (the `zc` option);
- the WebP format — noticeably lighter than JPEG at the same quality — with the quality from the options (70 by default);
- a width set for `srcset` (400, 800 and 1200 pixels by default, plus the main width); no thumbnail is made wider than the original — JUImage can enlarge, but that is extra weight with no gain;
- for phones, a separate thumbnail (and a double one for dense screens) the browser picks through `<picture>` on screens up to 640 pixels;
- the source is the article image chosen in the options (full, then intro by default); the `#joomlaImage://…` suffix Joomla adds to paths is stripped.

The «Original, no thumbnails» mode does not touch JUImage at all: the article file is shown as it is, with real `width` and `height` — from Joomla's data or from the file itself.

A thumbnail is made on first display and served as a ready file afterwards. Its name reflects size and quality: change the options and new files appear; old ones can be deleted together with the `img/` folder. JUImage recognises a thumbnail by the source file name, so upload a replaced image under a new name.

Without JUImage the module shows the original images — nothing breaks.

### Layout overrides

Copy the files you need from `modules/mod_wmarka_articles_grid/tmpl/` to `templates/<your template>/html/mod_wmarka_articles_grid/` and edit the copies:

| file | renders |
|---|---|
| `default.php` | wrapper: module heading, filter, groups |
| `default_items.php` | cards with images |
| `default_titles.php` | cards without images |

If an alternative layout has no sublayout of its own, the default one is used. Layouts get `$module`, `$params`, `$list` (articles, or a «group → articles» array when grouped), `$grouped`, `$items` (articles of the current group, in sublayouts), `$moduleClass`, `$juImg`, `$blendMode`, `$overlayRgba`, and the static methods of `ArticlesGridHelper`: `picture()` and `pictureTag()` (previews with `srcset`), `fullImage()`, `formatIntro()`, `introAttributes()`, `titleClass()`, `sizes()`, `filterClasses()`, `filterOptions()`, `rgba()`, `seo()`, `getJuImage()`, `loadUikit()`.

### How it works

The module follows the Joomla 4+ structure. `services/provider.php` registers the dispatcher and the helper. The `Dispatcher` prepares the layout data: the article list through Joomla's module cache, the JUImage instance, the mask colour; adds UIkit, JSON-LD and `og:image`; an empty list renders nothing. The `ArticlesGridHelper` helper selects articles through the `Articles` model of `com_content`. Namespace: `Webmarka\Module\ArticlesGrid\Site`.

Repository:

```
module/                 module sources
package/                package manifest, installer script, package language files
third_party/juimage/    the official JUImage 5.21 archive, unchanged, and its license
build.sh                builds the archives into dist/
```

Build: `./build.sh` — needs bash and zip; if php is available the script also checks the syntax. `dist/` gets both archives and a `SHA256SUMS` file.

### License

The module is distributed under the GNU GPL version 2 or later (`LICENSE`). The article selection logic is derived from the core module `mod_articles` (© Open Source Matters, Inc., GNU GPL).

Third-party components:

- **JUImage** — © Denys Nosov, Joomla! Ukraine; GNU GPL version 2 or later, see `third_party/juimage/LICENSE.md`. Included in the package unchanged.
- **UIkit** — © YOOtheme, MIT license. Not part of the repository or the archives: it comes from the template or a CDN.

### Author

Beibit Sakhanov — [webmarka.kz](https://webmarka.kz)
