```mermaid
classDiagram
    direction LR

    title ERD - PPC Schedule & Production Flow

    %% ═══════════════════════════════════
    %%  MASTER DATA
    %% ═══════════════════════════════════

    class line_masters {
        +id PK
        +line_code UK
        +line_name
        +shift
        +status
    }

    class job_masters {
        +id PK
        +job_number UK
        +job_name
        +target_qty
        +status
    }

    class machines {
        +id PK
        +name
        +line
    }

    class master_break_times {
        +id PK
        +hari
        +shift
        +waktu_mulai
        +waktu_selesai
        +type
    }

    %% ═══════════════════════════════════
    %%  PPC SCHEDULE
    %% ═══════════════════════════════════

    class job_processes {
        +id PK
        +job_master_id FK
        +process_name
        +standard_minutes
    }

    class master_stampings {
        +id PK
        +job_no
        +part_no
        +part_name
    }

    class production_plans {
        +id PK
        +line_master_id FK
        +plan_date
        +shift_name
        +press_name
        +job_no
        +plan
        +ok
        +repair
        +reject
    }

    class schedule_stampings {
        +id PK
        +upload_date
        +press_name
        +shift_name
        +job_no
    }

    class recovery_schedules {
        +id PK
        +plan_date
        +shift_name
        +status
    }

    class recovery_items {
        +id PK
        +recovery_schedule_id FK
        +production_plan_id FK
        +job_no
    }

    %% ═══════════════════════════════════
    %%  PRODUCTION FLOW
    %% ═══════════════════════════════════

    class production_sessions {
        +id PK
        +job_master_id FK
        +work_date
        +start_time
        +finish_time
        +status
    }

    class daily_productions {
        +id PK
        +job_master_id FK
        +work_date
        +actual_ok
        +actual_repair
        +actual_reject
    }

    class production_logs {
        +id PK
        +job_master_id
        +ok_qty
        +repair_qty
        +reject_qty
    }

    %% ═══════════════════════════════════
    %%  DOWNTIME
    %% ═══════════════════════════════════

    class machine_logs {
        +id PK
        +machine_id FK
        +status
        +downtime_start
        +downtime_end
    }

    class downtimes {
        +id PK
        +job_master_id FK
        +jenis_downtime
        +problem
        +duration_seconds
    }

    class hambatan_jalur {
        +id PK
        +downtime_id FK
        +line_name
        +mesin
        +jenis_hambatan
        +status
    }

    %% ═══════════════════════════════════
    %%  QUALITY
    %% ═══════════════════════════════════

    class repair_reject_logs {
        +id PK
        +job_master_id FK
        +type
        +defect_name
        +qty_a
        +root_cause
    }

    class repair_reject_images {
        +id PK
        +repair_reject_log_id FK
        +image_path
    }

    %% ═══════════════════════════════════
    %%  CHANGEOVER
    %% ═══════════════════════════════════

    class dandoris {
        +id PK
        +previous_job_id
        +next_job_id
        +duration_minutes
        +work_date
    }

    class dandori_sessions {
        +id PK
        +job_master_id
        +total_minutes
    }

    class dandori_groups {
        +id PK
        +session_id FK
        +group_name
        +total_minutes
    }

    class dandori_details {
        +id PK
        +group_id FK
        +activity_name
        +duration_minutes
    }

    %% ═══════════════════════════════════
    %%  RELATIONSHIPS
    %% ═══════════════════════════════════

    line_masters "1" --> "0..*" production_plans : line_master_id
    job_masters "1" --> "0..*" job_processes : job_master_id
    job_masters "1" --> "0..*" production_plans : job_no
    job_masters "1" --> "0..*" schedule_stampings : job_no
    job_masters "1" --> "0..*" master_stampings : job_no

    recovery_schedules "1" --> "0..*" recovery_items : recovery_schedule_id
    recovery_items "0..1" --> "1" production_plans : production_plan_id

    job_masters "1" --> "0..*" production_sessions : job_master_id
    job_masters "1" --> "0..*" daily_productions : job_master_id
    job_masters "1" --> "0..*" production_logs : job_master_id

    machines "1" --> "0..*" machine_logs : machine_id
    job_masters "1" --> "0..*" downtimes : job_master_id
    downtimes "1" --> "0..*" hambatan_jalur : downtime_id

    job_masters "1" --> "0..*" repair_reject_logs : job_master_id
    repair_reject_logs "1" --> "0..*" repair_reject_images : repair_reject_log_id

    job_masters "1" --> "0..*" dandori_sessions : job_master_id
    job_masters "1" --> "0..*" dandoris : next_job_id
    dandori_sessions "1" --> "0..*" dandori_groups : session_id
    dandori_groups "1" --> "0..*" dandori_details : group_id

    master_break_times ..> production_plans : "hari + shift"
```
