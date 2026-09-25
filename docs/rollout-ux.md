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
php artisan route:clear                   # route:cache НЕ запускать: дубль имени
                                          # password.reset в web.php/api.php его ломает
```

Новые npm-пакеты этапа 1 (ставятся через `npm ci` из lock-файла):

| Пакет | Этап | Зачем |
|---|---|---|
| – | – | – |

## 2. Миграции

```bash
php artisan migrate --pretend   # посмотреть SQL, ничего не меняя
php artisan migrate --force
```

| Миграция | Этап | Что делает | Откат |
|---|---|---|---|
| `2026_09_25_140000_create_hidden_coupons_table` | 1.6 | Создаёт таблицу `hidden_coupons` (своя БД ppm, партнёр + код + когда скрыт) — признак «скрыт» для списка промокодов в ЛК, Joomla и основной бэкенд не трогает | `php artisan migrate:rollback --step=1` безопасен — таблица новая, ничего кроме неё не меняет |

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
- `SESSION_DRIVER`/`CACHE_STORE` в прод-`.env` **не менять** (решено
  25.09: API на токенах, на скорость не влияет).

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
