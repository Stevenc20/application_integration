import mysql.connector
import pandas as pd
from config import DB_CONFIG


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def load_daily_productions(days: int = 90) -> pd.DataFrame:
    query = """
        SELECT
            dp.id,
            dp.work_date,
            dp.line,
            dp.shift,
            dp.target_qty,
            dp.actual_ok,
            dp.actual_repair,
            dp.actual_reject,
            dp.efficiency,
            dp.runtime_seconds,
            dp.downtime_seconds,
            jm.job_number,
            jm.job_name,
            jm.line AS job_line
        FROM daily_productions dp
        LEFT JOIN job_masters jm ON jm.id = dp.job_master_id
        WHERE dp.work_date >= CURDATE() - INTERVAL %(days)s DAY
        ORDER BY dp.work_date, dp.line, dp.shift
    """
    conn = get_connection()
    df = pd.read_sql(query, conn, params={'days': days})
    conn.close()
    if not df.empty:
        df['work_date'] = pd.to_datetime(df['work_date'])
    return df


def load_downtimes(days: int = 90) -> pd.DataFrame:
    query = """
        SELECT
            dt.id,
            dt.job_master_id,
            dt.jenis_downtime,
            dt.problem,
            dt.penyebab,
            dt.action,
            dt.pic,
            dt.start_time,
            dt.finish_time,
            dt.duration_seconds,
            jm.job_number,
            jm.line
        FROM downtimes dt
        LEFT JOIN job_masters jm ON jm.id = dt.job_master_id
        WHERE dt.start_time >= CURDATE() - INTERVAL %(days)s DAY
        ORDER BY dt.start_time
    """
    conn = get_connection()
    df = pd.read_sql(query, conn, params={'days': days})
    conn.close()
    if not df.empty:
        df['start_time'] = pd.to_datetime(df['start_time'])
        df['finish_time'] = pd.to_datetime(df['finish_time'])
    return df


def load_repair_rejects(days: int = 90) -> pd.DataFrame:
    query = """
        SELECT
            rr.id,
            rr.job_master_id,
            rr.type,
            rr.defect_name,
            rr.qty_a,
            rr.qty_b,
            rr.root_cause,
            rr.countermeasure,
            rr.created_at,
            jm.job_number,
            jm.line
        FROM repair_reject_logs rr
        LEFT JOIN job_masters jm ON jm.id = rr.job_master_id
        WHERE rr.created_at >= CURDATE() - INTERVAL %(days)s DAY
        ORDER BY rr.created_at
    """
    conn = get_connection()
    df = pd.read_sql(query, conn, params={'days': days})
    conn.close()
    if not df.empty:
        df['created_at'] = pd.to_datetime(df['created_at'])
    return df


def load_production_plans(days: int = 30) -> pd.DataFrame:
    query = """
        SELECT
            pp.id,
            pp.plan_date,
            pp.shift_name,
            pp.press_name,
            pp.job_no,
            pp.job_master,
            pp.row_type,
            pp.plan,
            pp.ok,
            pp.repair,
            pp.reject,
            pp.ct_detik,
            pp.dct,
            pp.mct,
            pp.gsph_item,
            pp.tpt,
            pp.total_pcs,
            pp.p1, pp.p2, pp.p3, pp.p4,
            pp.start_time,
            pp.finish_time,
            pp.act_start,
            pp.act_finish,
            pp.source_type
        FROM production_plans pp
        WHERE pp.plan_date >= CURDATE() - INTERVAL %(days)s DAY
        ORDER BY pp.plan_date, pp.press_name
    """
    conn = get_connection()
    df = pd.read_sql(query, conn, params={'days': days})
    conn.close()
    if not df.empty:
        df['plan_date'] = pd.to_datetime(df['plan_date'])
    return df
