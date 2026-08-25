
# Online School Platform на WordPress
## Архитектура и техническая документация

## 1. Обзор проекта

Платформа представляет собой production-grade SaaS систему для онлайн-школы, реализованную на WordPress с использованием кастомной темы и собственных плагинов.

Основные возможности:
- LMS
- CRM
- Scheduling
- Billing
- Notifications
- Homework & Content Management

---

## 2. Архитектура

Стек:
- WordPress
- PHP 8.3+
- MySQL
- Redis
- REST API
- Action Scheduler

Frontend:
- Custom Theme
- React/Vue dashboard
- FullCalendar

---

## 3. Роли

- administrator
- manager
- teacher
- parent
- student

---

## 4. База данных

Используются:
- стандартные таблицы WordPress
- custom tables с префиксом wp_school_

Примеры:
- wp_school_lessons
- wp_school_transactions
- wp_school_schedule_requests

---

## 5. Биллинг

- внутренний баланс
- автосписания
- teacher payouts
- подписки
- free cancellations
- no-show billing

---

## 6. Scheduling Engine

- recurring lessons
- Redis locks
- conflict prevention
- rescheduling system

---

## 7. Notifications

Pipeline:
Business Event → Queue → Worker → Telegram API

---

## 8. Безопасность

- JWT
- RBAC
- ownership checks
- XSS/CSRF protection
- capability validation

---

## 9. DevOps

Docker services:
- nginx
- php-fpm
- mysql
- redis
- wordpress

---

## 10. Рекомендации

НЕ использовать:
- wp_postmeta для сложной логики
- WP-Cron как единственный scheduler

Использовать:
- custom tables
- Redis
- очереди
- audit logs
