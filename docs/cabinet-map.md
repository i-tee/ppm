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

## 5. Анкета партнёра (п. 5)

`partner_applications`: `full_name` (varchar, одно поле), `experience`
(text, свободный текст), `city`, `links` (json), `company_name`,
`comment`. Поля «Специальность» нет и в истории git не было. Разбивка ФИО,
опыт в годах и специальность требуют миграции – решение владельца.
