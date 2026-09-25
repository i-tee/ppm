# Выкат переработанного кабинета (UX 2026-09) – runbook

Всё, что нужно сделать на прод-сервере, чтобы безопасно выкатить этап 1
переработки кабинета (карточка Trello https://trello.com/c/RFWSNOMq,
план – `docs/cabinet-map.md` §6). Файл **пополняется каждым этапом в том
же коммите**: новая миграция, пакет, команда, env-ключ – сразу строка сюда.

Базовый чек-лист деплоя (права, `umask`, stash, opcache) – в
`docs/operations.md` §3. Здесь – только то, что добавила переработка.

Прод: `i-tee@partner`, корень – `/home/dev-user/web/partner.avicenna.com.ru/public_html`,
ветка `main`. Деплоить под `i-tee` с `umask 002`, не под root.

## 0. До выката (read-only)

```bash
cd /home/dev-user/web/partner.avicenna.com.ru/public_html
git status                      # рабочее дерево чистое, ветка main
git log --oneline -1            # запомнить коммит – точка отката
php artisan ppm:access --list   # ⚠️ команды ещё нет до выката – см. ниже
```

До выката команды `ppm:access` на проде нет, поэтому список сотрудников и
их партнёрские данные смотрим после `git pull`, но **до** выдачи новых
ролей (шаг 3). Сотрудник (уровни 1/2/3) после выката **теряет доступ к
кабинету агента** – если у кого-то из суперадминов есть свои промокоды
или выплаты, решить заранее (снять роль или завести отдельный аккаунт).

Бэкап своей БД `Laravel_partner` перед миграциями – через панель Beget
(дамп в файл, в репо не класть).

## 1. Код и зависимости

```bash
umask 002
git pull                                  # из boost-ux смёржено в main владельцем
composer install --no-dev --optimize-autoloader
npm ci && npm run build                   # ⚠️ 1 ГБ RAM – см. operations.md §3
php artisan config:clear
php artisan route:cache                   # дубль имени password.reset устранён этапом 1.0а
php artisan route:clear
```

Новые npm-пакеты этапа 1 (ставятся через `npm ci` из lock-файла):

| Пакет | Этап | Зачем |
|---|---|---|
| `chart.js` (^4.5.1) | 1.5 | График начислений/заказов на экране «Статистика» |
| `vue-chartjs` (^5.3.4) | 1.5 | Vue-обёртка над `chart.js` (компонент `<Bar>`) |

## 2. Миграции

```bash
php artisan migrate --pretend   # посмотреть SQL, ничего не меняя
php artisan migrate --force
```

| Миграция | Этап | Что делает | Откат |
|---|---|---|---|
| `2026_09_25_140000_create_hidden_coupons_table` | 1.6 | Создаёт таблицу `hidden_coupons` (своя БД ppm, партнёр + код + когда скрыт) — признак «скрыт» для списка промокодов в ЛК, Joomla и основной бэкенд не трогает | `php artisan migrate:rollback --step=1` безопасен — таблица новая, ничего кроме неё не меняет |
| `2026_09_25_150000_add_name_parts_specialty_experience_years_to_partner_applications_table` | 1.7 | Добавляет в `partner_applications` колонки `last_name`, `first_name`, `middle_name`, `specialty` (nullable string), `experience_years` (nullable unsigned smallint) — разбивка ФИО и новые поля анкеты; старые колонки (`full_name`, `experience`) не трогает | `php artisan migrate:rollback --step=1` удаляет только новые колонки — данные новых анкет в них пропадут, `full_name`/`experience` старых заявок останутся как есть |
| `2026_09_25_160000_fix_legacy_payout_status_30` | А2 | **Миграция данных, не схемы.** `payout_requests`: легаси-статус `30` → `16` (`STATUS_TICKET_UPLOADED`) у заявок, созданных 24–25.12.2025, пока значением константы было `30`. Эти заявки не попадали в `PayoutRequest::withdrawals()`, поэтому баланс их партнёров был завышен — после миграции он уменьшится на сумму заявок. Затронутые id печатаются в вывод миграции и пишутся в лог (`migration.fix_legacy_payout_status_30`) | **Отката нет** (`down()` пустой): возвращать несуществующий статус — значит снова завысить баланс, а «все 16 → 30» откатывать нельзя (под 16 есть и нормальные заявки). Если откат всё же нужен — точечно по списку id из лога |

## 3. Роли (этап 1.3)

```bash
php artisan ppm:access --list                       # кто уже сотрудник (read-only)
php artisan ppm:access <email-даника> admin
php artisan ppm:access <email-бухгалтера> accountant
php artisan ppm:access --list                       # проверить итог
```

Команда сама предупредит, если у аккаунта есть партнёрские данные
(заявки, выплаты, реквизиты). Снять роль – тот же вызов с `--revoke`.

## 4. Кеш и права (этап 1.2б)

- Кеш `business-data` / `user/coupons` – файловый стор Laravel,
  `storage/framework/cache/data`, TTL 60 с (подробно – `docs/operations.md`
  §4а). Пишет его php-fpm (`www-data`) – каталог должен быть доступен на
  запись группе: после `git pull` права `storage/` не трогать.
- Сбросить кеш вручную: `php artisan cache:clear --store=file`.
- **После выката этапа 1.5б** (уборка ПДн покупателя из `business-data` /
  `/user/coupon/orders`) сразу выполнить `php artisan cache:clear --store=file`
  – иначе старые записи кеша со старым составом полей заказа (включая ПДн)
  проживут ещё до 60 с.
- `SESSION_DRIVER`/`CACHE_STORE` в прод-`.env` **не менять** (решено
  25.09: API на токенах, на скорость не влияет).
- Включить `DB_PERSISTENT=true` на проде – **только после проверки лимитов**
  (этап Г, 2026-09-25): владелец сверяет `pm.max_children` прод-пула
  php-fpm × 2 БД против `max_user_connections`/`max_connections` обеих
  прод-БД (`Laravel_partner`, `avicenna` – узнать через SELECT, см.
  `docs/operations.md` «Постоянные соединения с БД»). Отдельное решение
  владельца, не автоматически при обычном деплое.

## 4а. Сверка денег (этап А2)

Выкат меняет цифры в кабинете (миграция статуса `30`, правка сводки
промокодов), поэтому деньги всех партнёров снимаем **до** и **после** и
сверяем. Инструменты — `tools/balance-snapshot.php` и
`tools/balance-compare.php`, оба только читают (см. `docs/operations.md`
§4г).

**1. До `git pull`** — снимок «до» на старом коде. Скрипта в старом
дереве ещё нет, берём его из ветки, не трогая рабочее дерево:

```bash
cd /home/dev-user/web/partner.avicenna.com.ru/public_html
git fetch origin
git show origin/main:tools/balance-snapshot.php > /tmp/snap.php   # до мержа — origin/boost-ux
PPM_SNAPSHOT_OUT=/tmp/balance-before.json php artisan tinker /tmp/snap.php
```

Снимок берётся только по партнёрам с одобренной заявкой (у остальных нет
Joomla-пользователя, а чтение его создало бы — в боевую Joomla не пишем).
Идёт последовательно, по ~3–6 с на партнёра: на ~40 партнёрах это 3–5
минут, файл `/tmp/balance-before.json` **сохранить вне репо**.

**2. Выкат** — шаги §1–§3 этого файла как обычно.

**3. После выката** — сбросить кеш и снять снимок «после» уже новым
скриптом:

```bash
php artisan cache:clear --store=file
PPM_SNAPSHOT_OUT=/tmp/balance-after.json php artisan tinker tools/balance-snapshot.php
```

Сброс кеша обязателен: `business-data` кешируется на 60 с, иначе в снимок
«после» попадут старые цифры.

**4. Сравнить:**

```bash
PPM_SNAPSHOT_A=/tmp/balance-before.json \
PPM_SNAPSHOT_B=/tmp/balance-after.json \
  php artisan tinker tools/balance-compare.php
```

**5. Что считать нормой.** Единственное ожидаемое расхождение —
блок «ожидаемо: статус 30 → 16»: у партнёров с легаси-заявками баланс
уменьшается ровно на их сумму, а сами заявки переезжают из статуса `30` в
`16`. Всё, что попало в блок «Расхождения», — повод **откатить по §6** и
разбираться: деньги партнёра после выката меняться не должны. Сводка
промокодов (`couponsSummary`) на баланс не влияет и в снимок не входит
специально — её изменения ожидаемы и в сверку не попадают.

## 5. После выката

```bash
sudo systemctl reload php8.3-fpm   # из-под root, если после пересборки автолоада 500
tail -50 storage/logs/laravel.log
```

Смоук (браузер, прод):
1. Партнёр: вход → главная, промокоды, выплаты открываются; повторный
   переход между экранами – мгновенно.
2. Партнёр: минт тестового промокода – `docs/operations.md` §6 (dual-write
   не должен пострадать).
3. Админ: заявки, реквизиты, выплаты, «Партнёры»; партнёрских пунктов в
   меню нет.
4. Бухгалтер: только «Реквизиты» и «Выплаты».
5. Замер «после» на проде – `docs/cabinet-map.md` §4а (мастер-чат).

## 6. Откат

```bash
git checkout <коммит из шага 0>
composer install --no-dev && npm ci && npm run build
php artisan config:clear
```

Миграции этапа 1 откатывать только если мешают старому коду – см.
колонку «Откат» в §2. Роли в `user_access_levels` старому коду не мешают
(уровень 3 он просто не использует).
