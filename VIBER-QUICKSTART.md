# 🚀 ШВИДКИЙ СТАРТ - Viber Webhook

## Проблема вирішена ✅

**Було:** Логи записувались через curl, але не через реального Viber бота  
**Причина:** Код використовував локальні шляхи, а сайт на віддаленому сервері  
**Вирішення:** Використання `storage_path()` замість абсолютних шляхів

---

## 📋 Швидкий деплой (3 хвилини)

### Крок 1: Завантажити на сервер
```bash
cd /home/igor/developer/task.famhub.local

# Автоматичний деплой (рекомендовано)
./scripts/deploy-viber-webhook.sh

# АБО вручну через rsync
rsync -avz --exclude 'vendor' --exclude 'node_modules' \
  ./ user@server:/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/
```

### Крок 2: Налаштувати на сервері
```bash
ssh user@server
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub
mkdir -p storage/logs && chmod -R 777 storage/logs
php artisan optimize:clear
```

### Крок 3: Встановити webhook
```bash
curl -X POST \
  -H "X-Viber-Auth-Token: 479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://task.dev2025.ingsot.com/viber/webhook","event_types":["message","subscribed","unsubscribed","conversation_started"]}' \
  https://chatapi.viber.com/pa/set_webhook
```

### Крок 4: Тест
```bash
# Відправте повідомлення в Viber бота

# Перевірте логи (на сервері)
ssh user@server 'tail -f /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/storage/logs/viber_webhook.log'
```

---

## 🔍 Швидка діагностика

### Тест 1: Чи працює сервер?
```bash
curl https://task.dev2025.ingsot.com/ping
# Очікується: {"status":"ok","time":"..."}
```

### Тест 2: Чи працює webhook?
```bash
curl -X POST https://task.dev2025.ingsot.com/viber/webhook \
  -H "Content-Type: application/json" \
  -d '{"event":"message","sender":{"id":"test","name":"Test"},"message":{"text":"Hi"}}'
# Очікується: {"status":0,"message":"OK"}
```

### Тест 3: Чи встановлений webhook в Viber?
```bash
curl -X POST \
  -H "X-Viber-Auth-Token: 479d6bb020e7d3c0-10c469c78149798d-5cc4db7f99be936f" \
  https://chatapi.viber.com/pa/get_account_info | grep -o '"webhook":"[^"]*"'
# Очікується: "webhook":"https://task.dev2025.ingsot.com/viber/webhook"
```

---

## 📁 Де знаходяться логи?

**На сервері:**
```
/home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub/storage/logs/viber_webhook.log
```

**Моніторинг в реальному часі:**
```bash
ssh user@server
cd /home/igor/web/task.dev2025.ingsot.com/public_html/task.famhub
tail -f storage/logs/viber_webhook.log
```

---

## ⚠️ Типові проблеми

| Проблема | Рішення |
|----------|---------|
| 500 Error | `php artisan optimize:clear` на сервері |
| Permission denied | `chmod -R 777 storage/logs` на сервері |
| Viber не надсилає | Перевірте SSL сертифікат та webhook setup |
| Логів немає | Перевірте права доступу до storage/logs |

---

## 📚 Детальна документація

- **Повна інструкція:** [docs/VIBER-WEBHOOK-FIX.md](VIBER-WEBHOOK-FIX.md)
- **Діагностика:** [docs/viber-webhook-debug.md](viber-webhook-debug.md)
- **Розгортання:** [docs/viber-webhook-deployment.md](viber-webhook-deployment.md)

---

## ✅ Checklist

- [ ] Файли завантажені на сервер
- [ ] Виконано `php artisan optimize:clear`
- [ ] storage/logs має права 777
- [ ] Webhook встановлений в Viber
- [ ] Тест з curl працює
- [ ] Відправлено тестове повідомлення з Viber
- [ ] Логи з'являються

---

**Зроблено:** 2025-01-12  
**Час на деплой:** ~3 хвилини  
**Статус:** Готово до використання ✅

