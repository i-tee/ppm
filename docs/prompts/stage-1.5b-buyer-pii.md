# Этап 1.5б – не отдавать в браузер партнёра данные покупателей

> Промпт для worker-сессии. Автор – мастер-чат партнёрки, 2026-09-25.
> Рекомендуемая модель: Sonnet. Удалить файл после приёмки этапа.
> Запускать только после согласия владельца. Параллельно могут идти
> этапы 1.5 (статистика, только фронт) и 1.7 (анкета: `Application.vue`,
> `PartnerApplications.vue`, `PartnerApplicationController`, миграция) –
> **их файлы не трогай.**

## Правила сессии

- Одна сессия, **без суб-агентов**. `app/Models/JoomlaCoupon.php`
  (~1700 строк) читай фрагментами по нужным методам.
- **Git-мутаций не делаешь** (add/commit/switch/stash). Read-only
  `git status/diff/log` – можно.
- Репо в WSL: `~/dev-partner`. Команды – через
  `wsl -d Ubuntu-22.04 bash -lc '...'`, не Windows-git по UNC (CRLF).
  Команды с кавычками или `$` – в `.sh`-файл в `/tmp` WSL, после удалить.
  PHP/artisan – только `docker exec laravel_backend php artisan ...`.
- ☠️ **Получил отказ в разрешении (permission / классификатор) – остановись
  и напиши об этом в отчёте. Не обходи другим способом.**
- **Нельзя:** схемы БД, запись в любые БД (только SELECT), основной бэкенд
  Avicenna, флаги `PARTNER_*`, `.env`, минт купонов и dual-write.
- Вне задачи не чинить – найденное в отчёт.
- Комментарии в коде – по-русски.

## Проблема

`GET /api/user/business-data` → `credits.orders[]` и
`POST /api/user/coupon/orders` отдают партнёру **целые строки заказов
Joomla** (`jshopping_orders`): ФИО, email, телефон (`d_phone`,
`mobil_phone`), адрес доставки, IP, `order_hash`, `file_hash` и десятки
служебных полей. Интерфейс показывает только имя и город, а email и
телефон рисует звёздочками прямо в шаблоне
(`Agent/CreditsList/OrderDetailsModal.vue`), но сами данные уходят в
браузер – любой партнёр видит их во вкладке Network. Это персональные
данные покупателей (152-ФЗ), передавать их партнёру нельзя.

## Что сделать

1. Найди, где строки заказов Joomla попадают в ответы: `getPpOrders()` и
   всё, что его зовёт (`UserCouponController::data`/`buildBusinessData`,
   `getOrderInfoByCouponId` → `/user/coupon/orders`), плюс любые другие
   места, отдающие `jshopping_orders` наружу (grep).
2. Отдавать наружу **только белый список полей** заказа:
   `order_id`, `order_number`, `order_date`, `order_status`,
   `order_total`, `order_subtotal`, `order_discount`, `cashback`,
   `coupon_id`, `coupon_type` (если добавляется), `source`, `f_name`
   (только имя, как сейчас на экране), `city`. Проверь по фронту
   (grep `order.` в `resources/js/`), какие поля заказа реально читаются,
   и если нужно что-то ещё неперсональное – добавь и объясни в отчёте.
   Персональные (email, телефоны, фамилия, отчество, адрес, IP, хеши) –
   **никогда**.
   Лучше всего – выбирать из БД сразу только нужные колонки (`select`),
   а не резать после; строки нового сайта (`mapBackendRowToOrder`) уже
   без ПДн – приведи их к тому же набору ключей.
3. Расчёты баланса и начислений не должны измениться. Сделай эталон ДО
   правок (как в этапе 1.2б: tinker, `Auth::login` + вызов контроллера,
   3–4 партнёра, JSON в `/tmp/ppm-before/`) и сравни ПОСЛЕ: `balance`,
   `credits.total_accruals`, `credits.orders_count`, суммы и число
   заказов должны совпасть; отличаться могут только убранные поля.
4. Кеш `business-data` (60 с, файловый) – после выката старые записи
   кеша с ПДн проживут до минуты; в `docs/rollout-ux.md` §4 допиши шаг
   `php artisan cache:clear --store=file` сразу после выката.
5. Доки: `docs/operations.md` §5 «Безопасность» – правило «заказы
   отдаются партнёру только белым списком полей, без ПДн покупателя»;
   `docs/cabinet-map.md` §2 – состав полей заказа.

## Проверка

- Сравнение до/после (п. 3).
- Grep по ответу: в JSON `business-data` и `/user/coupon/orders` нет
  `email`, `phone`, `d_`, `l_name`, `m_name`, `street`, `ip_address`,
  `hash`.
- Сборка фронта не нужна, если фронт не трогал; если трогал –
  `docker exec laravel_node npx vite build --config vite.config.local.js
  --outDir /tmp/ppm-build-check --emptyOutDir`.

## Отчёт в конце

1. Список изменённых файлов.
2. Итоговый белый список полей и где он задан.
3. Сверка до/после.
4. Найденное вне задачи.
5. Текст коммита – **одна строка**, например
   `fix(privacy): stop sending buyer personal data to the partner cabinet`.
