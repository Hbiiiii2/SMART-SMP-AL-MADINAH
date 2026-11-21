# DEPLOYMENT & INFRASTRUCTURE CONFIGURATION AUDIT — SMART-CBT

**Audit Date:** November 12, 2026  
**Auditor:** Principal Systems Architect & DevOps Reliability Engineer  
**Scope:** Runtime environment, PHP-FPM, MySQL configuration, Redis state, session storage, queue workers, web server tuning, and production hardening.  
**Overall Status:** `PARTIALLY VERIFIED` (Local development environment functional; Production stack requires Redis and configuration hardening)

---

## 1. Executive Summary

This audit compares the existing development deployment configuration against the mandatory production standards required to support **500 to 1,000 concurrent students** without latency degradation or system crashes.

**Critical Findings:**
1. **Redis Offline:** The Redis service is currently inactive on the host (`127.0.0.1:6379: Connection refused`). As a fallback, the application relies entirely on MySQL for sessions, application cache, and queued jobs.
2. **Database Connection Limits:** MySQL's default `max_connections = 151` is insufficient for a 500+ concurrent student body, where web server worker pools can easily spawn 200–400 simultaneous database connections.
3. **Queue Daemon Requirement:** Background jobs (such as SMART+ grade synchronization) currently rely on `QUEUE_CONNECTION=database`, but no persistent supervisor daemon is configured to process jobs asynchronously.

---

## 2. Infrastructure Comparison: Current vs. Production Target

| Component | Current Host Environment | Recommended Production Target | Gap / Risk Level |
|---|---|---|---|
| **Operating System** | Windows 11 (Laragon) | Ubuntu Linux 22.04 / 24.04 LTS | Medium (I/O, process fork performance) |
| **PHP Runtime** | PHP 8.4.26 ZTS CLI | PHP 8.2 or 8.3 PHP-FPM (NTS) | High (ZTS overhead; PHP-FPM required for scale) |
| **Web Server** | Apache 2.4 (Laragon) | Nginx 1.24+ (HTTP/2, FastCGI keepalive) | Medium (Apache prefork consumes more RAM) |
| **Database Engine** | MySQL 8.4.3 InnoDB | MySQL 8.0/8.4 InnoDB (Tuned Buffer Pool) | Low (Engine verified; parameter tuning needed) |
| **Session Driver** | `SESSION_DRIVER=database` | `SESSION_DRIVER=redis` | **CRITICAL** (MySQL session table bottleneck) |
| **Cache Driver** | `CACHE_STORE=database` | `CACHE_STORE=redis` | High (Reduces database query load) |
| **Queue Connection**| `QUEUE_CONNECTION=database`| `QUEUE_CONNECTION=redis` | Medium (Offloads background job polling) |
| **Process Manager** | None (Manual terminal) | Systemd / Supervisor | High (Guarantees queue worker uptime) |

---

## 3. Database Server Tuning (MySQL 8.4)

To support 500–1,000 active students on exam day, MySQL must be tuned via `my.cnf`:

```ini
[mysqld]
# 1. Connection Limits (Prevent "Too many connections")
max_connections = 600
max_user_connections = 550

# 2. InnoDB Buffer Pool (Allocate 60-70% of dedicated RAM)
# For 16GB RAM Server:
innodb_buffer_pool_size = 10G
innodb_buffer_pool_instances = 8

# 3. Transaction Log & I/O
innodb_log_file_size = 1G
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2  # Drastically speeds up writes; 1s crash durability
innodb_flush_method = O_DIRECT

# 4. Table Cache
table_open_cache = 4000
open_files_limit = 65535
```

---

## 4. PHP-FPM & OPcache Configuration

### 4.1 PHP-FPM Pool Settings (`/etc/php/8.3/fpm/pool.d/www.conf`)
```ini
pm = dynamic
pm.max_children = 250
pm.start_servers = 50
pm.min_spare_servers = 25
pm.max_spare_servers = 75
pm.max_requests = 1000
request_terminate_timeout = 60s
```

### 4.2 OPcache Hardening (`/etc/php/8.3/fpm/conf.d/10-opcache.ini`)
```ini
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 30000
opcache.validate_timestamps = 0  # Zero disk stat calls in production
opcache.save_comments = 1
```

---

## 5. Supervisor Worker Configuration

To ensure background synchronization to SMART+ does not block student HTTP requests:

Save as `/etc/supervisor/conf.d/smart-cbt-worker.conf`:
```ini
[program:smart-cbt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/smart-cbt/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/supervisor/smart-cbt-worker.log
stopwaitsecs=3600
```

---

## 6. Pre-Flight Production Deployment Checklist

Before conducting any live examination, execute these Laravel optimization commands:

```bash
# 1. Cache configuration & environment
php artisan config:cache

# 2. Compile route registration
php artisan route:cache

# 3. Pre-compile Blade templates
php artisan view:cache

# 4. Cache event discovery
php artisan event:cache

# 5. Run database migrations
php artisan migrate --force

# 6. Verify Redis connectivity
php artisan tinker --execute="Illuminate\Support\Facades\Redis::ping();"
```

---

## 7. Deployment Verdict

- **Development / Single Lab Testing:** `VERIFIED` — Current local setup is fully functional.
- **Production Scale (500–1,000 Students):** `CONDITIONALLY READY` — Blocked from full production until:
  1. Redis server is installed and verified.
  2. MySQL `max_connections` and buffer pool are expanded.
  3. Supervisor queue daemon is launched.
