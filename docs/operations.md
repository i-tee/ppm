# ppm — операционная документация

Личный кабинет партнёра (`partner.avicenna.com.ru`). Laravel 12 + Vue 3 SPA
(Vite, Vuestic). Здесь — всё операционное: окружения, деплой, интеграция с
основным бэкендом Avicenna, тест-чеклист.

Стратегия миграции партнёрки на новую архитектуру (Фаза D, dual-write,
этапы 1–7) живёт в бэкенд-репо: `avicenna-backend/docs/partner-migration-plan.md`
(+ ADR-0034). Этот файл — практический runbook со стороны ppm.

---

## 1. Архитектура в двух словах

- **Своя БД** (`mysql`): users, user_access_levels, partner_applications,
  requisites, payout_requests, true_bonus_codes, **minted_coupons** (журнал
  минтов Фазы D), **hidden_coupons** (этап 1.6 — скрытые из списка ЛК
  промокоды партнёра, только отображение).
- **БД Joomla** (`mysql_joomla`, префикс `jm_`): ЛК читает/пишет её на каждый
  запрос — купоны (`jshopping_coupons`), заказы (`jshopping_orders`), связка
  партнёр↔купоны (`avicenna_user_coupons`), старые выплаты. Без этого
  подключения кабинет не работает.
- **Основной бэкенд Avicenna** (с Фазы D): s2s-вызовы по `X-Source-Token` —
  минт купонов (`POST /api/v1/coupons/partner`), чтение начислений
  (`GET /api/v1/partner/accruals`) и погашений бонусников
  (`GET /api/v1/partner/redemptions`). Управляется флагами (см. §4).

## 2. Окружения и env-матрица

| | LOCAL (docker) | PROD (bare VDS) |
|---|---|---|
| Запуск | `docker compose up -d` | без контейнеров: nginx + php-fpm на VDS |
| URL | http://localhost:**8081** (vite 5173, mailpit UI 8026) | https://partner.avicenna.com.ru (VDS 217.25.92.147, **1 CPU / 1 ГБ RAM**) |
| Своя БД (`DB_*`) | `dev-partner` @ dinabokan.beget.app | `Laravel_partner` @ Beget MySQL |
| Joomla БД (`DB_JOOMLA_*`) | `AviDev` @ dinabokan.beget.app (dev-копия) | `avicenna` (боевая) |
| Бэкенд Avicenna (`AVICENNA_BACKEND_BASE_URL`) | `http://host.docker.internal:8080` (локальный стек бэка) | `https://api.avicenna.com.ru` |
| Source-токен (`AVICENNA_BACKEND_SOURCE_TOKEN`) | источник `local_ppm` в локальной админке бэка | источник «Партнёрка» СОЗДАН в **прод**-Filament бэка (этап 5, 2026-07-16) |
| Флаги `PARTNER_*` | on (обкатка) | **✅ все ON с 2026-07-16** (этап 5 — параллельный режим на проде) |

> Порт 8081 локально — сознательно: основной бэкенд занимает 8080 на той же
> машине (его mailpit — 1025/8025, наш — 1026/8026). Vite 5173 не конфликтует.

> **Этап 4 (staging-репетиция):** dev-ppm направляется на **staging-бэк** —
> нужен отдельный источник с `can_mint_partner_coupons=true` в staging-админке
> (БД `Avicenna_dev`) и его токен в локальном `.env`.

Источник-токен — это строка в БД **конкретного** окружения бэка: локальный,
staging и прод токены — три разных значения.

## 3. Прод-деплой (РУЧНОЙ — CI нет)

Автодеплоя у ppm нет (в отличие от бэкенда с GitHub Actions). Исторический
минимум был «`git pull` + `npm run build`» — **после Фазы D этого мало**.

✅ **С 2026-07-16 параллельный режим ВКЛЮЧЁН на проде** (этап 5 выполнен):
все три флага `PARTNER_*=true` в прод-`.env`, интеграция боевая. Обычный
деплой (`git pull` + шаги ниже) тянет и активирует свежий код Фазы D —
это штатно.

Полный чек-лист прод-деплоя:

```bash
cd <корень ppm на VDS>
# 0. ⚠️ ГОТЧИ (ловлены на go-live 2026-07-16):
#    - деплоить из-под i-tee (НЕ root!) с `umask 002`; если git падает с
#      Permission denied (дерево после SFTP принадлежит dev-user) — из-под
#      root разово: `chmod -R g+w . && find . -type d -exec chmod g+s {} \;`
#    - если на проде лежит out-of-band код (SFTP-правки) и git pull ругается:
#      `git stash push -u` (бэкап) → pull → сверить diff → стэш дропнуть
git pull                          # 1. код
composer install --no-dev         # 2. зависимости/автолоад (безвреден, когда lock не менялся)
php artisan migrate --force       # 3. МИГРАЦИИ — обязательно (Фаза D: таблица minted_coupons;
                                  #    без неё минт купона упадёт 500)
# 4. env-ключи (уже вписаны на проде с этапа 5; проверить при переезде):
#    AVICENNA_BACKEND_BASE_URL=https://api.avicenna.com.ru
#    AVICENNA_BACKEND_SOURCE_TOKEN=<токен источника «Партнёрка» из прод-Filament бэка>
#    AVICENNA_BACKEND_TIMEOUT=10
#    PARTNER_MINT_VIA_BACKEND=true       ← прод-состояние с 2026-07-16
#    PARTNER_JOOMLA_DUAL_WRITE=true      ← прод-состояние с 2026-07-16
#    PARTNER_ACCRUALS_FROM_BACKEND=true  ← прод-состояние с 2026-07-16
php artisan config:clear          # 5. сброс кеша конфига (в репо следов config:cache нет,
                                  #    но clear дёшев и снимает вопрос)
npm ci && npm run build           # 6. фронт (Vite). ⚠️ VDS — 1 ГБ RAM: у сборки
                                  #    реальный риск OOM (на go-live прошла, своп ~22%).
                                  #    Если упало — включить своп ИЛИ собрать
                                  #    локально и залить public/build на сервер.
# 7. если после деплоя 500 при живом коде — возможен opcache-стейл:
#    из-под root `systemctl reload php8.3-fpm`
```

**Историческая справка:** параллельный режим включён 2026-07-16 по
чек-листу плана §7 (импорт 384 старых купонов + флаги on + смоук на обоих
сайтах). Этап 7 (будущее): после гашения старого сайта —
`PARTNER_JOOMLA_DUAL_WRITE=false`, **но только после того, как доделан
`backend_only_mint_stub`** (см. предупреждение ниже).

> ## ☠️ `PARTNER_JOOMLA_DUAL_WRITE` НЕ ВЫКЛЮЧАТЬ, пока не доделан `backend_only_mint_stub`
>
> При `PARTNER_MINT_VIA_BACKEND=true` + `PARTNER_JOOMLA_DUAL_WRITE=false`
> код уходит в заглушку (`JoomlaCoupon::createCoupon`, лог
> `partner.backend_only_mint_stub`): купон создаётся только на бэке,
> партнёр получает «успех», но пропускаются **три** вещи:
>
> 1. **Связь в `avicenna_user_coupons`** (Joomla) — купон не появится в
>    списке промокодов партнёра в ЛК.
> 2. **Запись в `true_bonus_codes`** — стоимость бонусного купона НЕ
>    спишется с баланса (баланс считает `sum(bonus_code_cost)` по этой
>    таблице): бонусники станут бесплатными.
> 3. **Уведомления компании** о новом купоне (`sendPercentCouponToCompany` /
>    `sendBonusCouponToCompany`).
>
> Перенос этих трёх шагов в backend-only ветку — задача этапа 7.

**Откат в любой момент:** **все** флаги `false` + `config:clear` — минт
и баланс мгновенно возвращаются к чисто-Joomla поведению, код не
откатывается. Выключать один `PARTNER_JOOMLA_DUAL_WRITE` при включённом
минте — это НЕ откат, а заглушка выше. Rollback-матрица — план §3.2.

## 4. Интеграция с бэкендом Avicenna (Фаза D, этап 3)

Конфиг: `config/services.php → avicenna_backend` (env-ключи см. §3).

| Флаг | Что делает |
|---|---|
| `PARTNER_MINT_VIA_BACKEND` | минт купона идёт СНАЧАЛА в бэк (истина): `JoomlaCoupon::createCoupon` → `AvicennaBackendClient::mintPartnerCoupon`. Занятый код на бэке → 422 → фронт-ключ `errors.coupon_code_exists`. Off = старый чисто-Joomla минт |
| `PARTNER_JOOMLA_DUAL_WRITE` | после успеха бэка выполняется старый INSERT в Joomla (купон работает на ОБОИХ сайтах). Off при включённом минте = backend-only-**заглушка**: ☠️ **НЕ выключать**, пока не доделан `backend_only_mint_stub` — пропускаются связь `avicenna_user_coupons`, списание бонусника (`true_bonus_codes`) и уведомления (см. §3) |
| `PARTNER_ACCRUALS_FROM_BACKEND` | ЛК дотягивает данные нового сайта. **Начисления** (процентные купоны): accruals вливаются через единый шов `JoomlaCoupon::getPpOrders` (→ «Начисления», per-coupon сводки, модалка «Заказы», баланс), reversals (возвраты) → вкладка «Списания → Корректировки» + вычет из баланса (net). **Погашения** (бонусные купоны, этап 4): `getRedemptions` → `getPpOrders` подмешивает заказ бонусника + `getUserCoupons` метит `backend_used` → карточка бонусника показывает «Использован» + «О заказе», даже если погашён на новом сайте (баланс не трогает — у бонусника комиссии нет) |

Ключевые точки кода:
- `app/Services/AvicennaBackendClient.php` — s2s-клиент (минт + accruals +
  redemptions бонусников, пагинация, маппинг ошибок в `errors.<key>` — ключи
  есть в `resources/js/locales/ru.json` и `en.json`);
- `app/Models/JoomlaCoupon.php` — врезка dual-write в `createCoupon()`
  (backend-first; `partner_ref = Auth::id()`, НЕ Joomla-id), шов
  `getPpOrders()` + `loadBackend()` + `backendReversalsSummary()`;
- `app/Models/MintedCoupon.php` + миграция — журнал минтов:
  `mint_request_id` сохраняется ДО вызова (`firstOrCreate` по
  partner_ref+code) → сетевой ретрай переиспользует uuid → бэк отвечает
  200 idempotent, а не «код занят»; `joomla_written=false` помечает
  купоны, у которых бэк-минт прошёл, а Joomla-INSERT упал. Фонового
  ретрая и алерта по таким строкам **нет** (follow-up); журнал сейчас
  только пишется, UI его не читает. ⚠️ В этом случае партнёр видит
  ошибку `coupon_insert_failed`, хотя купон уже действует на новом
  сайте; повтор с тем же кодом безопасен (тот же `mint_request_id` →
  бэк ответит idempotent, затем повторится Joomla-INSERT);
- `resources/js/components/dashboard/Agent/DebitsList/ReversalsTable.vue`
  (+ `ReversalDetailsModal.vue`) — вкладка «Корректировки».

## 4а. Производительность кабинета: пакетные запросы в Joomla + кеш `business-data`

Этап 1.2б (2026-09-25, `docs/prompts/stage-1.2b-server-speed.md`). Раньше
`GET /api/user/business-data` (`UserCouponController::data`) и
`getUserPercentCouponsSummary()` дёргали Joomla-БД в цикле «по одному
запросу на купон» (`JoomlaCoupon::getPpOrders`) плюс повторяли одни и те
же запросы (`jm_users`, `avicenna_user_coupons`) из разных методов.
У партнёра с 5+ купонами это было 39–55 SQL в Joomla на один вызов
`business-data` — см. `docs/cabinet-map.md` §4а.

**Пакетные запросы (`app/Models/JoomlaCoupon.php`).** Публичная сигнатура
`getPpOrders($couponId)` не изменилась, но при первом обращении она тянет
заказы **сразу по всем купонам текущего пользователя** одним `whereIn`-запросом
в `jshopping_coupons` и одним — в `jshopping_orders` (`loadPpOrdersBatch()`),
вместо пары запросов на каждый купон в цикле. Результат кладётся в
статический кеш класса (`self::$ppOrdersCache`, ключ — `coupon_id`) на время
запроса и отдаётся оттуда всем последующим вызовам — `data()`,
`credits()`/`orders()`, `getUserPercentCouponsSummary()` больше не бьют
по Joomla повторно за одни и те же заказы. Так же мемоизированы на время
запроса: `joomlaUser()` (SELECT из `jm_users`), результат `getUserCoupons()`
и запись `avicenna_user_coupons` (`getUserCouponRecord()`) — по образцу уже
существовавшего `loadBackend()`/`self::$backendCache` для HTTP в бэкенд.
Объекты заказов при чтении из кеша клонируются (`getPpOrders()`), чтобы
мутации на стороне вызывающего кода (`data()` дописывает `coupon_type` в
объект заказа) не утекали в кеш и другие места, читающие те же заказы.

Итог у партнёра с 5+ купонами: **6 SQL в Joomla** на `business-data`
(было 39–55), и число запросов **не растёт** с числом купонов партнёра
(один `whereIn` на пачку, а не на купон).

**Кеш ответа (`App\Helpers\BusinessDataCache`).** `GET /user/business-data`
и `GET /user/coupons` кешируются на **60 секунд** явно через
`Cache::store('file')` (не через дефолтный `CACHE_STORE` — на проде это
`database`, то есть та же удалённая БД, кешировать в неё же бессмысленно).
Ключ — `ppm:business-data:{user_id}` / `ppm:user-coupons:{user_id}`, где
`user_id` — id **фактического** пользователя запроса
(`$request->user()->id`), поэтому при impersonate кешируется именно
impersonated-партнёр, а не админ. Файлы кеша — `storage/framework/cache/data`
(локально) — стандартный `file`-стор Laravel, ничего дополнительно
настраивать не нужно.

Сброс кеша — `App\Helpers\BusinessDataCache::forget($userId)`, вызывается
после любого действия, меняющего баланс/купоны/выплаты партнёра:
- `UserCouponController::create()` — после успешного создания купона;
- `PayoutRequestController::store()` — после создания заявки на выплату;
- `PayoutRequestController::uploadTicket()` — после загрузки чека;
- `PayoutRequestController::adminReceived()`, `adminStatusUpdate()`,
  `adminTickedAbort()` — админские переходы статуса выплаты;
- `RequisiteController::store()`, `verify()`, `dalete()`, `destroy()` —
  свои и админские действия с реквизитами (сброс на случай, если реквизиты
  когда-нибудь попадут в ответ `business-data`; сейчас эндпоинт их не
  возвращает, но так безопаснее);
- `UserCouponController::hideCoupon()`, `restoreCoupon()` (этап 1.6) —
  после скрытия/возврата промокода из архива.

**Скрытые промокоды (этап 1.6).** `POST /api/user/coupons/hide` и
`POST /api/user/coupons/restore` (группа `partner`, тело `{ code }`) —
идемпотентно скрывают/возвращают промокод в списке ЛК, храня признак в
своей таблице `hidden_coupons` (партнёр, код в нижнем регистре, когда
скрыт). Код должен принадлежать партнёру (сверяется по
`JoomlaCoupon::getUserCoupons()`), иначе 404 (`errors.coupon_not_found`).
Joomla и основной бэкенд не трогаются — промокод продолжает работать на
сайте и начисляться, скрытие влияет только на отображение. Ответ
`business-data` дополнен полем `hidden_coupon_codes` (коды в нижнем
регистре); сводка на главной и статистика скрытые промокоды не
исключают — только список в `Agent/CouponsList.vue`.

Сбросить кеш вручную (например, при отладке): `php artisan tinker --execute="\App\Helpers\BusinessDataCache::forget(<user_id>);"`
либо просто подождать 60 секунд.

Начисления из основного бэкенда Avicenna (accruals/redemptions,
`loadBackend()`) при этом могут отображаться в ЛК с задержкой до 60 секунд
после реального события на бэке — это принято как приемлемый компромисс
(см. промпт этапа).

## 4б. Постоянные соединения с БД (этап Г, 2026-09-25)

Обе БД (`mysql`, `mysql_joomla`) — удалённый MySQL на Beget: подключение
с нуля стоит ~430-500 мс локально (~255 мс на проде по замеру мастер-чата),
каждый следующий SQL-запрос — ~170-180 мс локально (~101 мс на проде)
независимо от того, что это за запрос — это сетевая задержка до Beget, а
не время выполнения. Обычный php-fpm-воркер переподключается заново на
каждый HTTP-запрос, поэтому эта стоимость подключения платится каждый раз.

**Что даёт.** `PDO::ATTR_PERSISTENT` (флаг `DB_PERSISTENT`, дефолт
`false` — поведение не меняется) заставляет PDO переиспользовать уже
открытый TCP/MySQL-канал для одного и того же DSN в рамках процесса
php-fpm-воркера, вместо разрыва и нового подключения на каждый запрос.
Замер локально (`config/database.php`, 8 повторов, DB::purge +
переподключение, медиана):

| Соединение | Connect, было (мс) | Connect, стало (мс) | Query (не меняется, мс) |
|---|---|---|---|
| `mysql` | 434 | 264 | ~170-180 |
| `mysql_joomla` | 454 | 266 | ~170-180 |

Экономия — ~40% времени подключения (persistent-переподключение всё
равно платит сетевой round-trip на проверку/сброс сессии MySQL, это не
«бесплатно», но дешевле полного TCP+MySQL-handshake). Время самих
запросов не меняется — persistent-соединение не ускоряет SQL, оно
убирает повторное подключение.

**Как включить.** `DB_PERSISTENT=true` в `.env` (локально или на
проде) + `php artisan config:clear` (конфиг не кешируется отдельно, но
сброс дешёвый). Выключить — `DB_PERSISTENT=false` (или убрать ключ) +
`config:clear`.

**Риски (оценка, не устранены кодом):**

- **Лимит одновременных соединений на MySQL Beget.** Локальные
  dev-копии (`dev-partner`, `AviDev` — разные пользователи БД, но один
  физический сервер Beget) сейчас: `max_connections = 128`,
  `max_user_connections = 128` у обоих (замерено
  `SHOW VARIABLES LIKE 'max_connections'/'max_user_connections'`,
  25.09.2026). При persistent-соединениях каждый php-fpm-воркер держит
  **до 2 открытых соединений постоянно** (по одному на `mysql` и
  `mysql_joomla`) — не только во время запроса, а всё время жизни
  воркера. Прод-лимит **может отличаться** от dev-копий (другой
  Beget-тариф/аккаунт для `Laravel_partner`/`avicenna`) — владелец должен
  проверить те же `SHOW VARIABLES` на проде **и** сверить
  `pm.max_children` прод-пула php-fpm (VDS 1 CPU/1 ГБ — воркеров мало,
  но проверить нужно явно, локальный дефолт для сравнения —
  `pm.max_children = 5`). Формула: `pm.max_children × 2 ≤
  max_user_connections` с запасом на другие подключения к тем же БД
  (Filament бэка, cron, ручные tinker-сессии).
- **«Залипшие» транзакции / сессионные переменные.** Persistent-соединение
  переживает между HTTP-запросами одного воркера — если код (текущий
  или будущий) оставит открытую транзакцию или временную сессионную
  переменную (`SET @var`, `SET SESSION ...`) без явного коммита/сброса,
  следующий запрос того же воркера унаследует это состояние. Laravel
  обычно коммитит/ролбэкает транзакции в конце запроса и не использует
  сессионные переменные в этом проекте — риск низкий при текущем коде,
  но это не проверяется автоматически при включении флага.
- **Поведение при разрыве соединения.** Если Beget закроет
  «залежавшееся» соединение по своему таймауту (или сетевой сбой), PDO
  обнаружит это только на следующем запросе — тот запрос упадёт с
  ошибкой `MySQL server has gone away` / `Lost connection`, Laravel
  штатно не переподключается автоматически на persistent-соединении
  (в отличие от `mysqli` reconnect). Это разовая 500-ка, не деградация
  на постоянной основе, но UX хуже, чем при обычном (непостоянном)
  подключении, где реконнект происходит каждый раз бесшумно.

**Рекомендация:** локально/на dev — можно включать, лимиты с запасом.
На проде — **не включать** в этом этапе; включение — отдельное решение
владельца после проверки прод-лимитов (см. выше) и оценки рисков
(`docs/rollout-ux.md` §4).

## 5. Безопасность

### Роли и гейты (этап 1.3, 2026-09-25)

Уровни доступа — `config/settings.json → access_levels` + таблица
`user_access_levels`: 1 `superadmin`, 2 `admin`, 3 `accountant`. Права 1 и 2
одинаковые. Проверки собраны в `App\Models\User`: `isAdmin()` (1|2),
`isAccountant()` (3), `isStaff()` (1|2|3), `canManageFinance()` (1|2|3) —
все работают от уже загруженной связи `accessLevels`, без лишнего SQL на
каждый вызов. Сотрудник (любой из 1/2/3) не может быть партнёром —
партнёрские функции ему закрыты и на сервере, и в интерфейсе; impersonate
это не ломает, под ним запросы идут с токеном партнёра.

Middleware-алиасы (`bootstrap/app.php`):

| Роут → гейт | Middleware | Кто проходит |
|---|---|---|
| `/admin/users`, `/admin/impersonate/*`, CRUD `/partner-applications` (кроме `POST`) | `admin` | 1, 2 |
| `/admin/payout-requests*` (кроме `DELETE`, включая `PUT .../{id}/cancel` — этап А), `/admin/payout-ticked-reminder/{id}` | `finance` | 1, 2, 3 |
| `POST /partner-applications`, `/payout-requests*`, `/user/coupons`, `/user/check-promocode`, `/user/business-data`, `/user/coupon/*`, `/user/coupons/hide`, `/user/coupons/restore`, `GET/POST /user/requisites` (свои реквизиты) | `partner` | не-сотрудник |
| `/user/requisites-all`, `PUT /user/requisites/{id}/verify`, `DELETE /user/requisites/{id}` | без middleware | роль проверяется внутри `RequisiteController` через `canManageFinance()`: сотрудник видит/одобряет/удаляет любые, партнёр – удаляет только свои |
| `/user`, `/user/avatar`, `/user/change-password`, `/logout`, `/email/resend`, `/ps`, `/rs` | без middleware | любой залогиненный |

Роли выдаются и снимаются только командой `php artisan ppm:access {email}
{superadmin|admin|accountant} [--revoke] [--list]` — экрана для этого нет.
`--list` — read-only, показывает всех сотрудников; полезно перед выдачей
роли новому человеку — команда сама предупредит, если у аккаунта есть
партнёрские данные (заявки/выплаты/реквизиты).

`DELETE /admin/payout-requests/{id}` вёл на несуществующий метод
`adminDestroy` — роут удалён этапом 1.0а (§5 ниже).

**Отмена заявки на выплату (статус 50, этап А, 2026-09-25).**
`PUT /admin/payout-requests/{id}/cancel` (`PayoutRequestController::adminCancel`) —
админ/бухгалтер отменяют заявку в статусе 0 (создана) или 10 (одобрена);
после «выплачено» (14/16/20) — 422. Причина обязательна (`reason`, до 500
символов), под `lockForUpdate` заявки (двойной клик / два сотрудника
сериализуются), дописывается в `note` с датой и автором; `approver_id` —
кто отменил (то же поле, что и у остальных админских переходов статуса).
Деньги возвращаются на баланс партнёра сами — `PayoutRequest::withdrawals()`
не учитывает статус 50 в сумме списаний. Письма — партнёру
(`PayoutCancelledNotification`) и компании
(`PayoutCancelledToCompanyNotification`), по образцу `PayoutPaidNotification`
/`...ToCompany`. Кеш `business-data` партнёра сбрасывается.

- `AVICENNA_BACKEND_SOURCE_TOKEN` — секрет уровня пароля БД: даёт право
  минтить купоны на бэке. Только `.env`, не логировать.

### ПДн покупателя в заказах (этап 1.5б, 2026-09-25)

Заказы `jshopping_orders` отдаются партнёру **только белым списком полей**,
без персональных данных покупателя (152-ФЗ): `order_id`, `order_number`,
`order_date`, `order_status`, `order_total`, `order_subtotal`,
`order_discount`, `cashback`, `coupon_id`, `f_name` (только имя), `city`.
Список задан в `App\Models\JoomlaCoupon::ORDER_SAFE_FIELDS` и применяется
через `->select()` в `loadPpOrdersBatch()` — единственном месте, которое
селектит `jshopping_orders` (используется `getPpOrders()` и всем, что его
зовёт: `/user/business-data`, `/user/coupon/orders`,
`getUserPercentCouponsSummary()`). Заказы нового сайта (бэк, `source:
'backend'`, `mapBackendRowToOrder()`) приводятся к тому же набору ключей
(`f_name`/`city` = `null`, у бэка их и не было). Email, телефоны
(`phone`, `mobil_phone`, `d_phone`, `d_mobil_phone`), фамилия/отчество
(`l_name`, `m_name`), улица/дом (`street`, `street_nr`, `home`, `apartment`,
`zip`), IP (`ip_address`) и хеши файлов (`order_hash`, `file_hash`) —
**никогда** не добавлять в белый список.

### Известные проблемы (задачи в бэклоге, код пока не менялся)

- ~~Роуты вне гейта — `POST /api/notifications/send`
  (`NotificationController::send`), `GET /api/dev2`, `/api/dev3`,
  `/api/dev4`~~ — закрыто этапом 1.0а: роуты и мёртвые контроллеры
  (`NotificationController`, `DevController`, `MailController`) удалены.
- ~~`joomlaUser` из тела запроса в `POST /api/user/coupon/create`~~ —
  закрыто этапом 1.0а: Joomla-id берётся на сервере
  (`JoomlaCoupon::joomlaUser()` текущего пользователя), значение из
  запроса больше не принимается.
- ~~`DELETE /api/admin/payout-requests/{id}` вёл на несуществующий
  `adminDestroy` (500)~~ — роут удалён этапом 1.0а; отмену выплаты
  делает отдельный этап (https://trello.com/c/nDIM81xt).
- ~~Дубль имени роута `password.reset`~~ — закрыто этапом 1.0а: API-роут
  переименован в `password.reset.submit`, GET-страница из `web.php`
  осталась `password.reset` (на неё ссылается письмо сброса пароля).
- ~~`GET/PUT/DELETE /partner-applications` без роль-гейта — любой партнёр
  читал все заявки (с телефонами и почтами), мог одобрить сам себя и
  удалить чужие~~ — закрыто в этапе 1.3 (роуты под `admin`, `POST` —
  под `partner`, статус всегда `0` на сервере).

### Выход из impersonate (этап 1.0а, 2026-09-25)

`POST /api/admin/impersonate/{user}` объявлен раньше `.../stop` (роут
`{user}` ограничен `whereNumber`, так что `stop` больше не попадает в
него ни при каком порядке). `POST /api/admin/impersonate/stop`:
- лежит вне гейта `admin` (зовётся с impersonation-токеном, а под
  impersonate запрос идёт от лица партнёра, не админа);
- принимает только impersonation-токен — `ImpersonateController::stop`
  проверяет имя текущего токена (`currentAccessToken()->name`) на
  префикс `impersonation-token-` (токены создаются в
  `ImpersonateController::impersonate` с именем
  `impersonation-token-<adminId>`); обычный партнёрский токен → 403;
- отзывает этот токен и отвечает 200.

Контракт для фронта: зовёт `POST /api/admin/impersonate/stop` с текущим
(impersonation) токеном, затем сам восстанавливает исходный токен
админа из `localStorage`. Кнопки выхода в интерфейсе на момент 1.0а нет
(добавляет фронт-этап).

## 6. Тест-чеклист (смоук после деплоя / включения флагов)

1. Логин партнёра → баланс не изменился (при off-флагах — идентичен прежнему).
2. Минт процентного купона из UI → купон в ОБЕИХ БД (бэк: coupons
   `source='partner'`; Joomla: `jm_jshopping_coupons`).
3. Повторный минт того же кода → ошибка «Промокод с таким кодом уже существует».
4. Строка в `minted_coupons`: `joomla_written=1`.
5. (при `ACCRUALS_FROM_BACKEND=on`) Заказ с ПРОЦЕНТНЫМ купоном на новом сайте →
   paid → начисление видно в «Начисления» и в балансе; refund → строка в
   «Списания → Корректировки», баланс уменьшился.
6. (при `ACCRUALS_FROM_BACKEND=on`) БОНУСНЫЙ купон погашён на новом сайте →
   карточка бонусника показывает «Использован» + кнопка «О заказе» открывает
   заказ нового сайта; баланс НЕ изменился (у бонусника комиссии нет).
7. `/api/admin/*` под НЕ-админом → 403.

## 7. Локальная разработка

```bash
docker compose up -d     # backend(fpm) + nginx :8081 + node(vite :5173) + mailpit :8026
docker exec laravel_backend php artisan migrate
docker exec laravel_backend php artisan config:clear   # после правок .env
```

⚠️ Контейнер `node` запускает `npm run dev:local` =
`vite --config vite.config.local.js`, а этот файл **в `.gitignore`** — на
чистом checkout его нет, и vite не стартует. Создать вручную: копия
`vite.config.js` без HTTPS-блока (`server.https` читает сертификаты с
путей VDS) и с `server.hmr = { host: "localhost", protocol: "ws" }`.

`.env` локально: тестовые БД (см. §2), флаги — как удобно для задачи
(dual-write тестируется с локальным бэком на :8080; сеть между стеками —
через `host.docker.internal`).
