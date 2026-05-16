# Online School Work Plan

> Рабочий файл проекта. Обновлять после каждого завершенного этапа: переносить задачи из `Planned` в `Done`, добавлять дату и краткий результат.

**Цель:** превратить текущую WordPress/Elementor тему в основу полноценной системы онлайн-школы без смешивания дизайна, бизнес-логики и критичных данных.

**Текущий подход:** тема `mrepitnew` остается за публичные страницы, Elementor, стили и интеграции отображения. Основную логику школы рекомендуется выносить в отдельный плагин `mrepit-school-core`.

**Стек:** WordPress, Hello Elementor child theme, Elementor, Carbon Fields, PHP, MySQL, custom tables, REST API, Action Scheduler, Telegram API. Redis и отдельный frontend dashboard рассматриваются после MVP.

---

## Status Legend

- `Done` - завершено и проверено.
- `In Progress` - в работе.
- `Planned` - запланировано.
- `Blocked` - нужен ответ, доступ или решение.

---

## Current Project State

### Done

- [x] Есть child theme `mrepitnew` для `hello-elementor`.
- [x] Подключены стили темы и frontend phone mask.
- [x] Подключен Carbon Fields через Composer.
- [x] Созданы CPT `service`, `review`, `teacher`.
- [x] Добавлены Carbon Fields для услуг, отзывов, преподавателей и Telegram-настроек.
- [x] Есть роли `teacher`, `student`, `parent`, `manager`.
- [x] Есть кастомный admin menu `School`.
- [x] Есть CRUD-разделы для учителей, учеников и родителей.
- [x] Есть связь родитель-ученик через user meta.
- [x] Есть связь teacher user <-> teacher post.
- [x] Есть Elementor -> Telegram REST webhook.
- [x] Есть README с описанием текущей архитектуры.
- [x] Есть черновик ТЗ в `wordpress_online_school_ru.md`.

### Known Problems

- [x] Исправить mojibake/битую кодировку русских строк в PHP и markdown-файлах.
- [x] Убрать логирование персональных данных из Telegram webhook.
- [x] Усилить защиту Telegram webhook: обязательный secret и безопасный debug.
- [x] Добавить rate limiting/anti-spam для Telegram webhook.
- [ ] Перенести роли, capabilities и school business logic из темы в плагин.
- [ ] Перестать использовать user meta/post meta для сложных сущностей: уроки, оплаты, транзакции, расписание.
- [ ] Добавить нормальную миграционную систему для custom tables.
- [ ] Настроить Git `safe.directory`, чтобы можно было читать статус, историю и делать коммиты.

---

## Architecture Decision

### Recommended Direction

Создать отдельный плагин `mrepit-school-core`.

Плагин отвечает за:

- роли и capabilities;
- custom tables;
- миграции;
- уроки;
- расписание;
- заявки на перенос;
- домашние задания;
- балансы и транзакции;
- уведомления;
- REST API;
- audit logs;
- проверки ownership и RBAC.

Тема отвечает за:

- Elementor-шаблоны;
- публичные страницы;
- визуальные стили;
- динамические теги/шорткоды для вывода данных;
- формы заявок и маркетинговые страницы.

---

## Phase 0: Stabilize Existing Theme

**Goal:** привести текущий код в состояние, на которое можно безопасно опираться перед выделением платформенной логики.

### Planned

- [x] Настроить Git `safe.directory` для текущего каталога.
- [x] Проверить `git status` и зафиксировать исходное состояние.
- [x] Исправить кодировку русских строк.
- [x] Проверить PHP syntax для всех PHP-файлов темы.
- [x] Убрать или ограничить `error_log` с персональными данными.
- [x] Сделать Telegram webhook безопаснее.
  - [x] Сделать `tg_webhook_secret` обязательным.
  - [x] Убрать сырой payload из debug-ответов.
  - [x] Добавить rate limiting/anti-spam.
- [x] Проверить capabilities менеджера.
- [x] Проверить создание и редактирование teacher/student/parent.
- [x] Проверить синхронизацию parent/student связей.
- [x] Зафиксировать результат в README.

### Acceptance Criteria

- Админка не содержит битых русских строк.
- Все PHP-файлы проходят syntax check.
- Webhook не пишет персональные данные в лог по умолчанию.
- Роли и базовый CRUD работают как до изменений.
- Есть понятное описание текущего состояния.

---

## Phase 1: Extract School Core Plugin

**Goal:** отделить бизнес-логику школы от Elementor-темы.

### Planned

- [ ] Создать директорию плагина `wp-content/plugins/mrepit-school-core`.
- [ ] Добавить главный файл плагина.
- [ ] Перенести регистрацию ролей и capabilities из темы в плагин.
- [ ] Перенести permission helpers.
- [ ] Перенести admin menu `School` или оставить временный bridge в теме.
- [ ] Перенести CRUD teacher/student/parent.
- [ ] Перенести teacher user <-> teacher post link.
- [ ] Перенести parent/student sync.
- [ ] Добавить uninstall/deactivation policy без удаления данных по умолчанию.
- [ ] Оставить в теме только подключение визуальных Elementor-интеграций.

### Acceptance Criteria

- При активном плагине school-admin работает независимо от темы.
- При смене темы роли и данные пользователей не пропадают.
- Тема не содержит критичной бизнес-логики школы.

---

## Phase 2: Core Data Model

**Goal:** заложить базу данных для LMS/CRM/scheduling/billing без перегрузки `wp_postmeta`.

### Planned Custom Tables

- [ ] `wp_school_lessons` - уроки и их статус.
- [ ] `wp_school_lesson_participants` - участники урока.
- [ ] `wp_school_schedule_rules` - регулярное расписание.
- [ ] `wp_school_schedule_requests` - переносы/отмены.
- [ ] `wp_school_homework` - домашние задания.
- [ ] `wp_school_homework_submissions` - ответы учеников.
- [ ] `wp_school_accounts` - внутренние балансы.
- [ ] `wp_school_transactions` - финансовые операции.
- [ ] `wp_school_notifications` - очередь уведомлений.
- [ ] `wp_school_audit_log` - журнал действий.

### Planned

- [ ] Создать миграционную систему плагина.
- [ ] Добавить таблицу версии схемы.
- [ ] Добавить репозитории доступа к данным.
- [ ] Добавить валидацию входных данных.
- [ ] Добавить базовые unit/integration проверки, если окружение позволит.

### Acceptance Criteria

- Таблицы создаются при активации/миграции.
- Повторный запуск миграций безопасен.
- В коде нет прямого SQL без `$wpdb->prepare()` для пользовательского ввода.
- Сущности уроков, расписания и транзакций не хранятся в post meta.

---

## Phase 3: MVP CRM

**Goal:** получить управляемую базу пользователей школы.

### Planned

- [ ] Уточнить обязательные поля для teacher/student/parent.
- [ ] Добавить нормализацию телефонов и контактов в одном сервисе.
- [ ] Добавить поиск и фильтры по ролям.
- [ ] Добавить карточку ученика: родители, преподаватели, история уроков, баланс.
- [ ] Добавить карточку преподавателя: ученики, расписание, выплаты.
- [ ] Добавить карточку родителя: дети, баланс, уведомления.
- [ ] Добавить audit log для изменений профилей и связей.

### Acceptance Criteria

- Менеджер может управлять пользователями школы без доступа к администраторам.
- История важных изменений фиксируется.
- Связи между ролями проверяются на корректность.

---

## Phase 4: Scheduling MVP

**Goal:** создать рабочее расписание уроков и базовые переносы.

### Planned

- [ ] Описать модель статусов урока.
- [ ] Создать CRUD уроков.
- [ ] Добавить recurring lessons.
- [ ] Добавить проверку конфликтов преподавателя и ученика.
- [ ] Добавить заявки на перенос.
- [ ] Добавить правила бесплатной отмены.
- [ ] Подготовить интеграцию с FullCalendar.
- [ ] Добавить уведомления о создании/переносе/отмене урока.

### Acceptance Criteria

- Нельзя создать два урока в одно время для одного преподавателя или ученика.
- Можно создать регулярные уроки.
- Можно запросить и подтвердить перенос.
- Статусы уроков понятны менеджеру, преподавателю и родителю.

---

## Phase 5: Homework MVP

**Goal:** добавить задания и материалы после уроков.

### Planned

- [ ] Создать модель домашнего задания.
- [ ] Связать homework с lesson/student/teacher.
- [ ] Добавить загрузку материалов.
- [ ] Добавить комментарии преподавателя.
- [ ] Добавить уведомления родителю и ученику.
- [ ] Добавить frontend view для ученика/родителя.

### Acceptance Criteria

- Преподаватель может выдать задание конкретному ученику.
- Ученик/родитель видит задания в личном кабинете.
- Выполнение и комментарии сохраняются в истории.

---

## Phase 6: Billing MVP

**Goal:** реализовать внутренний баланс, списания и выплаты.

### Planned

- [ ] Описать финансовые события.
- [ ] Создать accounts и transactions.
- [ ] Добавить пополнение баланса.
- [ ] Добавить списание за проведенный урок.
- [ ] Добавить no-show списания.
- [ ] Добавить бесплатные отмены.
- [ ] Добавить расчет teacher payouts.
- [ ] Добавить audit log для финансовых операций.

### Acceptance Criteria

- Все изменения баланса проходят через transaction ledger.
- Баланс нельзя менять без audit log.
- Списание за урок связано с конкретным lesson ID.
- Ошибочные операции можно компенсировать обратной транзакцией.

---

## Phase 7: Notifications

**Goal:** сделать надежную очередь уведомлений.

### Planned

- [ ] Добавить business events.
- [ ] Добавить очередь уведомлений.
- [ ] Подключить Action Scheduler.
- [ ] Добавить Telegram provider.
- [ ] Добавить retry policy.
- [ ] Добавить opt-in/opt-out настройки по ролям.
- [ ] Добавить журнал отправок.

### Acceptance Criteria

- Уведомления не отправляются напрямую из бизнес-операций.
- Повторная отправка не создает дубли критичных сообщений.
- Ошибки Telegram фиксируются без раскрытия токенов.

---

## Phase 8: Frontend Dashboards

**Goal:** дать ролям личные кабинеты на сайте.

### Planned

- [ ] Выбрать подход: WordPress shortcodes, Elementor widgets, React/Vue dashboard.
- [ ] Сделать dashboard для manager.
- [ ] Сделать dashboard для teacher.
- [ ] Сделать dashboard для parent.
- [ ] Сделать dashboard для student.
- [ ] Добавить REST API endpoints с RBAC.
- [ ] Добавить ownership checks для всех endpoints.

### Acceptance Criteria

- Каждая роль видит только свои данные.
- Frontend не доверяет скрытым полям и user ID из клиента.
- Все действия проходят capability и ownership проверки.

---

## Open Questions

- [ ] Что делаем первым MVP: CRM, scheduling, homework, billing или личные кабинеты?
- [ ] Нужны ли онлайн-оплаты на первом этапе или только внутренний учет баланса?
- [ ] Нужны ли групповые занятия или только индивидуальные?
- [ ] Какие роли реально входят в первый релиз?
- [ ] Должны ли ученики иметь собственный вход или сначала достаточно кабинета родителя?
- [ ] Какие каналы уведомлений нужны кроме Telegram: email, WhatsApp, SMS?
- [ ] Будет ли несколько школ/филиалов или один учебный центр?

---

## Update Log

- 2026-05-16: Создан рабочий план после первичного анализа текущей темы и файла `wordpress_online_school_ru.md`.
- 2026-05-16: Создана ветка `phase-0-stabilize-theme`; проверен `git status`; все PHP-файлы темы прошли syntax check; raw payload logging в Telegram webhook заменен на безопасный debug summary; добавлен статический тест `tests/static/telegram-webhook-security.test.ps1`.
- 2026-05-16: Telegram webhook ужесточен: пустой `tg_webhook_secret` теперь блокирует endpoint, неверный secret возвращает `403`, debug-ответы больше не возвращают сырой payload/message/payload/telegram_raw.
- 2026-05-16: Добавлен transient-based rate limit для Telegram webhook: 10 запросов за 5 минут на хэш IP по умолчанию, с фильтрами `its_telegram_webhook_rate_limit_count` и `its_telegram_webhook_rate_limit_window`.
- 2026-05-16: Репозиторий добавлен в глобальный Git `safe.directory`; обычный `git status` работает без временного `-c safe.directory=...`.
- 2026-05-16: Исправлены найденные mojibake-строки в `capabilities.php`, `helpers.php` и role handlers; добавлен статический тест `tests/static/no-mojibake.test.ps1`.
- 2026-05-16: Добавлен статический тест `tests/static/school-users-security.test.ps1` для manager capabilities и CRUD handlers; relationship IDs в parent/student handlers теперь фильтруются по ожидаемой роли перед сохранением.
- 2026-05-16: Добавлен статический тест `tests/static/school-sync-security.test.ps1`; синхронизация parent/student теперь удаляет устаревшие обратные связи при отвязке.
- 2026-05-16: README обновлен по итогам Phase 0: описаны webhook hardening, parent/student sync cleanup, статические проверки и оставшиеся риски.
