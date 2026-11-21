# SMART-CBT — Production Deployment & Resource Allocation Guide

## 1. Budget & Infrastructure Context

- **Monthly Budget Constraint**: **Rp300,000 / month** (Approx. \$18 – \$20 USD).
- **Target Examinee Scale**: 500 – 1,000 enrolled students.
- **Concurrent Active Examinee Capacity**: 150 – 250 students concurrently active per examination window.
- **Topology**: **Single VPS Deployment** (Nginx + PHP 8.4-FPM + MySQL 8.4 InnoDB + Redis 7.2).

---

## 2. VPS Sizing & Cloud Provider Comparison

To remain strictly within the **Rp300,000/month** limit while guaranteeing sufficient RAM and CPU for 100–250 concurrent examinees, evaluate the following options:

| Provider | Plan / Tier | Specs | Monthly Cost (IDR) | Suitability |
| :--- | :--- | :--- | :--- | :--- |
| **Hetzner Cloud** (Falkenstein/Singapore) | **CPX31** / **CCX13** | 4 vCPU AMD, 8 GB RAM, 160 GB NVMe | ~Rp230,000 (€13.50) | **Highly Recommended** (Best price/performance) |
| **IDCloudHost** (Indonesia - Cyber 2) | **Cloud VPS Pro 4** | 4 vCPU, 8 GB RAM, 80 GB SSD | ~Rp280,000 / bln | **Recommended** (Lowest Indonesian latency: 2–5ms) |
| **DomaiNesia / Niagahoster** | **VPS KVM 4G** | 2 vCPU, 4 GB RAM, 60 GB SSD | ~Rp250,000 / bln | Viable for up to 150 concurrent students |
| **DigitalOcean** (Singapore) | **Basic Droplet** | 2 vCPU, 4 GB RAM, 80 GB SSD | ~Rp300,000 (\$18.00) | Viable, but tighter CPU headroom on login bursts |

> [!TIP]
> **Recommended Hardware Spec**: **4 vCPU, 8 GB RAM, NVMe SSD**. For Indonesian schools, hosting in an Indonesian datacenter (e.g. IDCloudHost Cyber 2 / Biznet GIO) or Singapore (Hetzner / DO) ensures latency under 15ms.

---

## 3. Single-VPS Resource Budgeting (8 GB RAM Allocation)

On a single 8 GB VPS, memory must be explicitly partitioned to prevent the Linux OOM (Out-Of-Memory) killer from terminating MySQL or PHP-FPM:

```
+-------------------------------------------------------------------------+
| TOTAL SERVER MEMORY: 8,192 MB (8.0 GB)                                  |
|                                                                         |
| +-------------------------+ +-----------------------------------------+ |
| | OS & System Overhead    | | Nginx Web Server                        | |
| | 512 MB                  | | 256 MB                                  | |
| +-------------------------+ +-----------------------------------------+ |
| +-------------------------+ +-----------------------------------------+ |
| | Redis In-Memory Cache   | | MySQL 8.4 InnoDB Database Engine        | |
| | 512 MB (maxmemory)      | | 2,048 MB (innodb_buffer_pool_size)      | |
| +-------------------------+ +-----------------------------------------+ |
| +---------------------------------------------------------------------+ |
| | PHP 8.4-FPM Worker Pool (Static Allocation)                         | |
| | 40 Workers x ~80 MB = 3,200 MB                                      | |
| +---------------------------------------------------------------------+ |
| +-------------------------+ +-----------------------------------------+ |
| | Queue Workers & Cron    | | Free Buffer / OS Page Cache (Emergency) | |
| | 256 MB                  | | 1,408 MB                                | |
| +-------------------------+ +-----------------------------------------+ |
+-------------------------------------------------------------------------+
```

---

## 4. Production Service Tuning Directives

### 4.1 Nginx Tuning (`/etc/nginx/nginx.conf`)
```nginx
user www-data;
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    multi_accept on;
    use epoll;
}

http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    keepalive_requests 1000;
    types_hash_max_size 2048;

    # Gzip Compression for JSON API payloads
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 5;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Buffer Limits
    client_body_buffer_size 128k;
    client_max_body_size 10M;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;

    # FastCGI Keepalive Pool
    upstream php-fpm {
        server 127.0.0.1:9000;
        keepalive 32;
    }
}
```

### 4.2 PHP 8.4-FPM Tuning (`/etc/php/8.4/fpm/pool.d/www.conf`)
Use `pm = static` for production exam servers to eliminate the latency of worker spawning:
```ini
[www]
user = www-data
group = www-data
listen = 127.0.0.1:9000
listen.backlog = 1024

; Static worker allocation: 40 workers x 80MB = 3.2 GB
pm = static
pm.max_children = 40
pm.max_requests = 1000

pm.status_path = /status
ping.path = /ping

request_terminate_timeout = 60s
rlimit_files = 65535
```

### 4.3 MySQL 8.4 LTS Tuning (`/etc/mysql/conf.d/smart-cbt.cnf`)
```ini
[mysqld]
# Memory Allocation
innodb_buffer_pool_size = 2G
innodb_buffer_pool_instances = 2
innodb_log_buffer_size = 32M
innodb_redo_log_capacity = 256M

# High-Concurrency Transaction Tuning
# 2 = Flush log to disk once per second; safe against application crashes, 10x write throughput!
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connection Pool & Sizing
max_connections = 300
max_connect_errors = 10000
table_open_cache = 2000
thread_cache_size = 32

# Concurrency & Deadlock Protection
innodb_lock_wait_timeout = 10
transaction_isolation = READ-COMMITTED
binlog_format = ROW
```

### 4.4 Redis 7.2 Tuning (`/etc/redis/redis.conf`)
```ini
maxmemory 512mb
maxmemory-policy volatile-lru
save ""                     # Disable RDB snapshots during active exams to prevent fork IO spikes
appendonly no               # Pure in-memory cache mode for CBT sessions & idempotency tokens
tcp-backlog 1024
```

### 4.5 Linux Kernel & Socket Tuning (`/etc/sysctl.d/99-cbt.conf`)
```ini
# Maximum open files and socket backlog
fs.file-max = 2097152
net.core.somaxconn = 4096
net.ipv4.tcp_max_syn_backlog = 4096

# Fast recycling of TIME_WAIT sockets
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15

# Ephemeral port range
net.ipv4.ip_local_port_range = 1024 65535
```

---

## 5. Operational Proctoring & Exam Staggering Guidelines

To eliminate initial login thundering herds on a single VPS:

### Staggered Schedule (Per Classroom / Lab)
| Wave | Time Window | Classes | Concurrent Students | Expected Activity |
| :--- | :--- | :--- | :--- | :--- |
| **Wave 1** | 08:00 – 08:03 | 9A & 9B (Lab 1) | 64 students | Login $\rightarrow$ Enter Token $\rightarrow$ Start Exam |
| **Wave 2** | 08:04 – 08:07 | 9C & 9D (Lab 2) | 64 students | Login $\rightarrow$ Enter Token $\rightarrow$ Start Exam |
| **Wave 3** | 08:08 – 08:11 | 9E & 9F (Lab 3) | 64 students | Login $\rightarrow$ Enter Token $\rightarrow$ Start Exam |
| **Wave 4** | 08:12 – 08:15 | 9G & 9H (Classroom A)| 64 students | Login $\rightarrow$ Enter Token $\rightarrow$ Start Exam |

### Why Staggering Works
- Staggering by just 3 minutes spreads 256 logins across 720 seconds ($~0.35$ logins/sec).
- CPU utilization remains below 15% throughout authentication.
- Once students are answering questions, the **delta autosave engine with randomized jitter (8s–12s)** automatically diffuses traffic into a smooth, flat arrival curve of 10–15 req/sec that requires fewer than 15 active PHP-FPM workers.
