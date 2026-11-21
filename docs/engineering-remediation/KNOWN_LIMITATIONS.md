# Known Limitations & Future Roadmap
## SMART-CBT Examination System

This document describes the operational constraints, system boundaries, and future evolutionary opportunities for the SMART-CBT platform.

---

### 1. Operational Constraints & Edge Cases

#### A. Remedial / Multi-Attempt Frontend Lifecycle
- **Status**: Database Ready, Frontend Single-Attempt by Default.
- **Details**: The database schema and unique constraint were upgraded to `(exam_id, siswa_id, attempt_number)` to eliminate DB-03. At present, the student interface defaults to `attempt_number = 1`. A teacher interface to grant an explicit "Remedial / Retake Token" (`attempt_number = 2`) is the next logical user feature.

#### B. Student Physical Device Switching
- **Status**: Operational Tolerance of 15–30 seconds.
- **Details**: Student answers are queued in browser `localStorage` and dispatched via background autosave. If a student's machine experiences total hardware failure and the student moves to another PC, the new workstation will load all answers persisted to the server. Any answer entered within the last 15–30 seconds that had not yet reached the server will remain in the un-synced device's local storage.

#### C. Essay Answer Formatting
- **Status**: Plain Text & Standard Markdown Supported.
- **Details**: Student essay answers are stored as `TEXT`. File attachment uploads (e.g., student handwritten work scanned via phone camera or PDF uploads) are not currently supported in the essay answer schema.

---

### 2. Recommended Future Enhancements

1. **Automated Master Data Synchronization Schedule**:
   Register the new `cbt:sync-master-data` command in `bootstrap/app.php` scheduler:
   ```php
   $schedule->command('cbt:sync-master-data')->dailyAt('02:00')->runInBackground();
   ```
2. **Teacher Remedial Retake Granting UI**:
   Allow teachers from `/guru/monitoring` or `/guru/hasil-ujian` to click "Beri Kesempatan Remedial", which increments `attempt_number` to 2 for that specific student.
3. **Student Essay Image Uploads**:
   Add `lampiran_file` to `answers` table allowing students to upload handwritten math equations or diagrams.
