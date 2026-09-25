# Этап 1.7 – анкета партнёра: ФИО тремя полями, опыт в годах, специальность

> Промпт для worker-сессии. Автор – мастер-чат партнёрки, 2026-09-25.
> Рекомендуемая модель: Sonnet. Удалить файл после приёмки этапа.
> Параллельно идёт этап 1.5 (статистика) – он правит `Statistics.vue`,
> `Sidebar.vue`, `CouponsList.vue`, карточки промокодов, `package.json`.
> **Эти файлы не трогай.** Общий файл – `resources/js/locales/*.json`:
> правь точечно (Edit по конкретным ключам), перед каждой правкой
> перечитывай файл, не переписывай его целиком.

## Правила сессии

- Одна сессия, **без суб-агентов**. Большие файлы читай фрагментами.
- **Git-мутаций не делаешь** (add/commit/switch/stash). Read-only
  `git status/diff/log` – можно.
- Репо в WSL: `~/dev-partner`. Команды – через
  `wsl -d Ubuntu-22.04 bash -lc '...'`, не Windows-git по UNC (CRLF).
  Команды с кавычками или `$` – в `.sh`-файл в `/tmp` WSL, после удалить.
  PHP/artisan – только `docker exec laravel_backend php artisan ...`.
- ☠️ **Получил отказ в разрешении (permission / классификатор) – остановись
  и напиши об этом в отчёте. Не обходи другим способом.**
- **Нельзя:** БД Joomla, основной бэкенд Avicenna, флаги `PARTNER_*`,
  `.env`, минт купонов, кеш `BusinessDataCache` (только не сломать).
  Новые пакеты не ставить.
- БД локально удалённые (Beget, dev-копии). Разрешено: **новая миграция**
  в своей БД (`php artisan migrate` локально) и тестовая заявка при
  проверке – **удалить её в конце**. Остальное – только SELECT.
- Вне задачи не чинить – найденное в отчёт.
- Тексты – через i18n (`ru.json` и `en.json`), «Avicenna®», тире «–», на
  «вы». Комментарии в коде – по-русски.
- Стек: Laravel 12 / PHP 8.3; Vue 3.5, Pinia 3 (геттеры – через `this`),
  Vuestic UI 1.10. Сверяйся с версиями в `composer.lock` / `package.json`.

## Контекст

Прочитай `docs/cabinet-map.md` §1, §5 (решение 1), §7 и `docs/operations.md`
§5 (гейты).

- Таблица `partner_applications`: `full_name` (varchar, NOT NULL, одно
  поле), `experience` (text), `phone`, `email`, `city`, `links` (json),
  `company_name`, `comment`, `partner_type_id`, `cooperation_type_id`,
  `status_id`, `responsible_user_id`.
- Анкета партнёра – `resources/js/components/dashboard/Application.vue`
  (вынесена из `Types.vue` на этапе 1.4). ⚠️ Поле `experience` в ней
  подписано как **«Специальность»** (`partnerApplications.specialty`), а в
  админской форме (`PartnerApplications.vue`) – как «Опыт». Значит, в
  старых заявках в `experience` лежит смесь «специальности» и «опыта».
- Админ-экран заявок – `resources/js/components/dashboard/PartnerApplications.vue`
  (список + форма создания/правки).
- Сервер – `PartnerApplicationController` (`store` – партнёр, гейт
  `partner`, статус всегда 0; `update` – админ), модель `PartnerApplication`
  (в ней же автосоздание одобренной заявки для старых партнёров Joomla –
  заполняет только `full_name`, его не трогать).
- `full_name` читают уведомления (`AutoApprovedPartnerApplicationToCompanyNotification`
  и др.) – поле должно оставаться заполненным.

## Решение владельца

ФИО – три поля: **фамилия и имя обязательны**, отчество – нет. «Опыт» –
количество лет (число). Вернуть поле «Специальность». Старые записи
(`full_name`, `experience`) **не трогать и не разбивать**.

## Что сделать

### Сервер

1. Миграция: в `partner_applications` добавить `last_name`, `first_name`,
   `middle_name` (string, nullable – из-за старых записей),
   `specialty` (string 255, nullable), `experience_years` (unsigned small
   integer, nullable). `down()` – удалить эти колонки. Старые колонки не
   менять.
2. `store` и `update`: валидация новых полей – `last_name`, `first_name`
   обязательны (строка до 100), `middle_name` – нет, `specialty` –
   необязательна (до 255), `experience_years` – необязательное целое
   0–80. `full_name` сервер **собирает сам** – «Фамилия Имя Отчество» через
   пробел, без лишних пробелов; с фронта его больше не принимать. Поле
   `experience` новые формы не присылают – в `store` не требовать; в
   `update` оставить возможность не трогать старое значение.
3. `PartnerApplication`: `$fillable` + приведение типов.

### Фронт

4. `Application.vue` (партнёр): вместо «ФИО» – три поля «Фамилия»*,
   «Имя»*, «Отчество»; «Специальность» – отдельное поле (новое
   `specialty`); «Опыт» – число лет (`experience_years`, ввод только
   цифр, подсказка «например, 3»). Обязательность – с понятными
   сообщениями под полями. Предзаполнение из `user.name`: если в нём ровно
   одно слово – в «Имя», иначе ничего не угадывать.
5. `PartnerApplications.vue` (админ): в форме – те же поля. Для старых
   заявок, где новых полей нет, показать `full_name` и старое `experience`
   только для чтения с подписью «Из старой анкеты» – чтобы админ видел,
   что там было. В таблице списка колонка ФИО остаётся (`full_name`),
   добавь колонки «Специальность» и «Опыт, лет» (для старых – пусто).
6. Статус заявки у партнёра после отправки – как сейчас (показывается
   сразу, профиль обновляется через `authStore.fetchUser()`).

### Доки

7. `docs/rollout-ux.md` §2 – строка миграции (что делает; откат
   `migrate:rollback --step=1` – удаляет только новые колонки, данные
   новых анкет в них пропадут, `full_name` останется).
   `docs/cabinet-map.md` §7 – новые поля. `docs/operations.md` §1 – если
   там описаны поля заявки.

## Проверка

- `php artisan migrate` локально, `route:list` без ошибок.
- Запросами (tinker/curl с токеном партнёра без заявки – подбери SELECT'ом,
  или проверь только валидацию, если такого нет): без фамилии → 422;
  с полями → заявка создаётся, `full_name` собран, статус 0. Созданную
  заявку удалить в конце, токены отозвать.
- Админом: правка старой заявки не ломает её `full_name`/`experience`.
- Сборка: `docker exec laravel_node npx vite build --config
  vite.config.local.js --outDir /tmp/ppm-build-check --emptyOutDir`.
- Браузер (если есть) – http://localhost:8081; если нет – напиши, проверит
  мастер-чат.

## Отчёт в конце

1. Список изменённых/созданных файлов.
2. Результаты проверок (запрос → ответ).
3. Найденное вне задачи.
4. Текст коммита – **одна строка**, Conventional Commits, английский, без
   body и Co-Authored-By, например
   `feat(application): split full name, add specialty and years of experience`.
