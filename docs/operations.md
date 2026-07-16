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
  минтов Фазы D).
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
`PARTNER_JOOMLA_DUAL_WRITE=false`.

**Откат в любой момент:** флаги `false` + `config:clear` — минт и баланс
мгновенно возвращаются к чисто-Joomla поведению, код не откатывается.
Rollback-матрица — план §3.2.

## 4. Интеграция с бэкендом Avicenna (Фаза D, этап 3)

Конфиг: `config/services.php → avicenna_backend` (env-ключи см. §3).

| Флаг | Что делает |
|---|---|
| `PARTNER_MINT_VIA_BACKEND` | минт купона идёт СНАЧАЛА в бэк (истина): `JoomlaCoupon::createCoupon` → `AvicennaBackendClient::mintPartnerCoupon`. Занятый код на бэке → 422 → фронт-ключ `errors.coupon_code_exists`. Off = старый чисто-Joomla минт |
| `PARTNER_JOOMLA_DUAL_WRITE` | после успеха бэка выполняется старый INSERT в Joomla (купон работает на ОБОИХ сайтах). Off при включённом минте = backend-only (этап 7, пока заглушка) |
| `PARTNER_ACCRUALS_FROM_BACKEND` | ЛК дотягивает данные нового сайта. **Начисления** (процентные купоны): accruals вливаются через единый шов `JoomlaCoupon::getPpOrders` (→ «Начисления», per-coupon сводки, модалка «Заказы», баланс), reversals (возвраты) → вкладка «Списания → Корректировки» + вычет из баланса (net). **Погашения** (бонусные купоны, этап 4): `getRedemptions` → `getPpOrders` подмешивает заказ бонусника + `getUserCoupons` метит `backend_used` → карточка бонусника показывает «Использован» + «О заказе», даже если погашён на новом сайте (баланс не трогает — у бонусника комиссии нет) |

Ключевые точки кода:
- `app/Services/AvicennaBackendClient.php` — s2s-клиент (минт + accruals +
  redemptions бонусников, пагинация, маппинг ошибок в `errors.<key>` — ключи
  есть в `resources/js/locales/ru.json`);
- `app/Models/JoomlaCoupon.php` — врезка dual-write в `createCoupon()`
  (backend-first; `partner_ref = Auth::id()`, НЕ Joomla-id), шов
  `getPpOrders()` + `loadBackend()` + `backendReversalsSummary()`;
- `app/Models/MintedCoupon.php` + миграция — журнал минтов:
  `mint_request_id` сохраняется ДО вызова (`firstOrCreate` по
  partner_ref+code) → сетевой ретрай переиспользует uuid → бэк отвечает
  200 idempotent, а не «код занят»; `joomla_written=false` помечает
  купоны, у которых бэк-минт прошёл, а Joomla-INSERT упал (follow-up:
  фоновый ретрай таких строк);
- `resources/js/components/dashboard/Agent/DebitsList/ReversalsTable.vue`
  (+ `ReversalDetailsModal.vue`) — вкладка «Корректировки».

## 5. Безопасность

- `/api/admin/*` защищены middleware **`admin`**
  (`app/Http/Middleware/EnsureUserIsAdmin.php`, alias в `bootstrap/app.php`):
  уровень доступа 1 или 2. До Фазы D группа висела на голом `auth:sanctum`,
  роль проверялась вразнобой в контроллерах (`adminIndex` — вообще никак).
  Проверки в контроллерах оставлены как дублирующая подстраховка.
- `AVICENNA_BACKEND_SOURCE_TOKEN` — секрет уровня пароля БД: даёт право
  минтить купоны на бэке. Только `.env`, не логировать.

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

`.env` локально: тестовые БД (см. §2), флаги — как удобно для задачи
(dual-write тестируется с локальным бэком на :8080; сеть между стеками —
через `host.docker.internal`).
