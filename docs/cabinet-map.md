# Карта кабинета партнёра (этап 0 переработки UX)

Снимок на 2026-09-25, ветка `boost-ux`. Составлен по коду, живой обход
в браузере ещё не сделан. Задача – карточка Trello
https://trello.com/c/RFWSNOMq, бриф – `docs/prompts/partner-ux-master-brief.md`.

Файл живёт, пока идёт переработка: воркеры берут отсюда контекст,
мастер-чат обновляет его после каждого этапа.

## 1. Экраны

Все пути фронта – от `resources/js/`. Роутер `router.js`, компоненты
подключены статически (lazy-loading нет). Проверок роли в роутере нет:
админ-экраны закрываются только внутри компонентов.

| Путь | Компонент | Кто видит | Запросы | Что показывает |
|---|---|---|---|---|
| `/welcome`, `/register`, `/reset-password` | `Welcome.vue`, `Register.vue`, `ResetPassword.vue` | гость | `/login`, `/register`, `/forgot-password`, `/reset-password`, соц-вход Yandex | вход, регистрация, сброс пароля |
| `/dashboard` | `dashboard/Overview.vue` → `Overview/AgentOverview.vue` | партнёр | `/ps`, `/user/business-data`, `/user/requisites`, `/email/resend` | подтверждение email; при одобренной заявке – сводка агента (баланс, начисления, расходы, промокоды) |
| `/dashboard/types` | `dashboard/Types.vue` | партнёр | `/ps`, `POST /partner-applications` | выбор режима сотрудничества, анкета, статус заявки |
| `/dashboard/agent` | `dashboard/Agent.vue` | партнёр с одобренной заявкой типа 2 | `/ps`, `/user/business-data`, `/user/coupons`, `/user/coupon/create`, `/user/coupon/orders`, `/payout-requests` | баланс, договор; вкладки: промокоды (`Agent/CouponsList.vue`), создание (`CreateCoupon.vue`), начисления (`CreditsList.vue`), списания (`DebitsList.vue`: выплаты, бонусники, корректировки, старое); модалки выплаты и чека |
| `/dashboard/requisite` | `dashboard/Requisite.vue` | партнёр с одобренной заявкой | `/rs`, `/user/requisites` | реквизиты для выплат |
| `/dashboard/account` | `dashboard/Account.vue` | все | `/email/resend`, `/user/change-password`, `/user/avatar` | профиль (в меню скрыт) |
| `/dashboard/influencer`, `wholesale`, `distributor` | заглушки по 7 строк | – | – | пусто; попасть можно только по `type.route` из `/ps` или прямым URL |
| `/dashboard/promocodes`, `referral-links` | `Promocodes.vue`, `ReferralLinks.vue` | – | – | маршруты есть, в меню нет |
| `/dashboard/partner-applications` | `PartnerApplications.vue` | админ (1, 2) | `/ps`, CRUD `/partner-applications` | заявки партнёров, фильтры, серверная пагинация |
| `/dashboard/requisite-verification` | `RequisiteVerification.vue` | админ | `/ps`, `/user/requisites-all`, `PUT /user/requisites/{id}/verify`, `DELETE /user/requisites/{id}` | неверифицированные реквизиты, одобрение |
| `/dashboard/payout-resolve` | `PayoutResolve.vue` | админ | `/admin/payout-requests-prepared`, `PUT /admin/payout-requests/{id}/20`, `…-ticket-abort`, `…-ticked-reminder`, `…-received` | заявки на выплату, чеки самозанятых, «выплачено» |
| `/dashboard/impersonate` | `Impersonate.vue` | админ | `/admin/users`, `POST /admin/impersonate/{id}` | вход под партнёром |

**Меню** – `components/dashboard/Sidebar.vue`: у админа пункты Impersonate,
Заявки, Реквизиты, Выплаты; у партнёра – Overview, Types, пункты режимов
из `/ps` (по одобренной заявке), Requisite.

**Стор** один – `stores/auth.js` (user, token). В `localStorage` лежат
токен и данные impersonate, профиль – нет: после перезагрузки он заново
приходит из `GET /user` вместе с `partner_applications`.

## 2. Откуда данные

Главный ответ – `GET /api/user/business-data` (`UserCouponController::data`):
`balance`, `credits.{total_accruals, orders_count, orders[]}`,
`coupons_full[]`, `trueBonusCode`, `withdrawals`, `payoutRequests`,
`expenseSummary`, `backendReversals`.

- У каждого заказа в `credits.orders[]` есть `order_date`, `cashback`,
  `order_total`, `coupon_id`, `coupon_type`, `source` (`backend` = новый
  сайт). Этого хватает для статистики по датам на фронте (п. 3) без правки
  сервера.
- Сервер собирает ответ так: для каждого купона партнёра – отдельный
  запрос в удалённую БД Joomla (`getPpOrders` в цикле) + два s2s-запроса
  к основному бэкенду (accruals, redemptions, с пагинацией). Кеша между
  запросами нет – всё заново на каждый вызов.

## 3. Уровни доступа

`config/settings.json → access_levels`: 1 superadmin, 2 admin, 3 manager
(нигде не используется). Таблица `user_access_levels`. Вычисляемые 0/−1 –
email подтверждён / нет (`User::getEffectiveAccessLevelsAttribute`).

- Сервер: middleware `admin` (`EnsureUserIsAdmin`, уровни 1 или 2) на
  `/api/admin/*`. Роуты реквизитов (`/user/requisites-all`, `/verify`,
  `DELETE`) – вне гейта, роль проверяется внутри `RequisiteController`.
- Фронт: проверка «1 или 2» скопирована в `stores/auth.js`, `Sidebar.vue`,
  `PartnerApplications.vue`, `RequisiteVerification.vue`,
  `PayoutResolve.vue`, `Impersonate.vue`.
- `UserAccessLevel::getAccessLevelAttribute` берёт уровень по позиции в
  массиве, а не по `id` – ошибка смещения на единицу.

## 4. Найденные проблемы

**Безопасность**
- `stores/auth.js:101` при сбросе пароля пишет новый пароль и токен в консоль.
- `POST /api/notifications/send` – любое уведомление любому пользователю
  от любого партнёра (известно, `docs/operations.md` §5).
- `GET /api/dev2|dev3|dev4` открыты любому партнёру.
- `joomlaUser` при создании купона берётся из тела запроса (известно).

**Баги**
- `routes/api.php`: `POST /admin/impersonate/{user}` объявлен раньше
  `/stop`; кнопки выхода из impersonate в интерфейсе нет.
- Редиректы на несуществующий `/login` (`Dashboard.vue`, `stores/auth.js`).
- `AgentOverview.vue` вызывает `t()` без `useI18n` – ветка ошибки упадёт.
- `PayoutResolve.vue`: битый тег `<d iv>`, параметр `statu_id` с опечаткой.
- В `payout_requests` есть статус `30`, которого нет в
  `settings.json → payout_statuses` (проверить, откуда он).

**Статус после одобрения (п. 7)** – `hasApplicationsWithStatus(2)`
вызывается один раз и возвращает boolean, а не computed (`Sidebar.vue`,
`Overview.vue`), и профиль не перезапрашивается при возврате на вкладку.

**Скорость (п. 8), гипотезы до замера**
- `GET /ps` – до 2 раз на экран (Sidebar + экран), кеша нет.
- `GET /partner-applications` – на каждом экране через
  `usePartnerApplications`, результат не используется.
- `business-data` грузится заново в `Agent` и `AgentOverview`; `CouponsList`
  отдельно тянет `/user/coupons`.
- Сервер: N запросов в удалённую Joomla по числу купонов + 2 запроса в
  бэкенд на каждый вызов `business-data`.

**Мусор** – около 40 `console.log`; мёртвые `api/axios.js`,
`services/authService.js`, `Dev.vue`; `axios.defaults.headers` меняется
на уровне модуля в двух компонентах.

## 4а. Замер скорости «до» (этап 1.1, 2026-09-25, локалка)

Локалка ходит в те же удалённые БД на Beget, что и прод (другие базы, тот
же тип хостинга), поэтому абсолютные цифры зависят от сети до Beget, а
число запросов – нет.

| Что | Время |
|---|---|
| Laravel без БД (`POST /api/login` пустой → 422) | 10–12 мс |
| Подключение к БД Beget / один `select 1` | 450–600 мс / ~175 мс |
| Любой запрос с авторизацией (`/api/ps` – просто отдаёт JSON) | 1,1–1,3 с |
| `GET /api/user` | 1,6–1,9 с |
| `GET /api/user/business-data` (партнёр с 5 купонами, 8 заказами) | **9–11,5 с**: 39 SQL в Joomla + 6 в свою БД, + 2 HTTP в бэкенд (~1 с) |
| Экран «Агент» целиком | **14,3 с**: `business-data` 11 с, затем последовательно `/user/coupons` 3 с |
| Главная партнёра | **13 с**: `business-data`, затем последовательно `/user/requisites` |
| Админ: «Заявки» / «Реквизиты» / «Выплаты» / «Impersonate» | 3,9 / 2,7 / 2,4 / 5,8 с |

Выводы:
- Время съедают **запросы к удалённой БД**: каждый ~175 мс, и
  `business-data` делает их десятками (цикл по купонам в `getPpOrders` и
  соседях) – растёт линейно с числом купонов партнёра.
- Фронт умножает это: `/api/ps`, `/partner-applications` на каждом экране,
  `business-data` заново на каждом заходе, запросы идут цепочкой, а не
  параллельно.
- Сессии и кеш Laravel – драйвер `database`, то есть тоже удалённая БД.
- Попутно: PHP-warning `"continue" targeting switch` в
  `JoomlaCoupon.php:1467` и `:1480` – вероятно, `continue` не делает того,
  что задумано. Проверить отдельно.

## 5. Решения владельца (2026-09-25)

1. **Анкета** – миграция: `first_name`, `last_name` (обязательны),
   `middle_name`, `specialty`, опыт числом (лет). Старые записи с
   `full_name` не трогаем.
2. **Скрытые промокоды** – своя таблица в БД партнёрки (партнёр, код,
   когда скрыт). Joomla и основной бэкенд не трогаем.
3. **Бухгалтер** – уровень 3 переименовать в `accountant`: только проверка
   реквизитов и выплаты. Даник – уровень 2, суперадмины – 1. Детальный
   план гейта – владельцу до промпта.
4. **Графики** – `chart.js` + `vue-chartjs`.
5. **Инструкции** – Markdown-файлы в репо, открываются из кабинета в
   модальном окне (ссылка из интерфейса), у каждой роли – свои.
6. **Этап 1.0 (гигиена и безопасность сервера) отложен** – решение после
   сдачи остального. В воркер-промптах серверные дыры не чинить.

## 6. План этапа 1

| # | Этап | Пункты карточки | Статус |
|---|---|---|---|
| 1.0 | Гигиена и безопасность сервера | часть 10 | ⏸ отложен владельцем |
| 1.1 | Замер скорости (мастер-чат в браузере) | 8 | – |
| 1.2 | Слой данных: стор настроек/профиля, свежий профиль, без лишних запросов, ленивые экраны | 7, 8 | – |
| 1.3 | Роли: единая проверка, `accountant`, серверный гейт | 10 | – |
| 1.4 | Только «Агент» + новое меню (десктоп) | 1, 2 | – |
| 1.5 | Статистика | 3 | – |
| 1.6 | Промокоды: скрыть / архив / восстановить | 9 | – |
| 1.7 | Анкета | 5 | – |
| 1.8 | «С чего начать?» + инструкции (md в модалке) | 6, 11 | – |
| 1.9 | Выкат на прод | – | – |

Промпты воркеров – `docs/prompts/stage-1.N-*.md`, удаляются после
выполнения этапа.

## 7. Анкета партнёра (п. 5)

`partner_applications`: `full_name` (varchar, одно поле), `experience`
(text, свободный текст), `city`, `links` (json), `company_name`,
`comment`. Поля «Специальность» нет и в истории git не было. Разбивка ФИО,
опыт в годах и специальность требуют миграции – решение владельца.
