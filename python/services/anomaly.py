import numpy as np
import pandas as pd


def z_score_anomalies(values, threshold=2.0):
    arr = np.array(values, dtype=float)
    if len(arr) < 3:
        return []
    mean = np.nanmean(arr)
    std = np.nanstd(arr)
    if std == 0:
        return []
    z_scores = (arr - mean) / std
    return z_scores.tolist()


def detect_efficiency_anomaly(df, threshold=2.0):
    if df.empty or 'efficiency' not in df.columns:
        return []
    results = []
    df_clean = df.dropna(subset=['efficiency'])
    if df_clean.empty:
        return []
    grouped = df_clean.groupby(['work_date', 'line', 'shift'], sort=False).agg(
        efficiency=('efficiency', 'mean'),
        actual_ok=('actual_ok', 'sum'),
        actual_repair=('actual_repair', 'sum'),
        actual_reject=('actual_reject', 'sum'),
        target_qty=('target_qty', 'sum'),
    ).reset_index()

    values = grouped['efficiency'].values
    z_scores = z_score_anomalies(values, threshold)

    anomalies = []
    for i, row in grouped.iterrows():
        z = z_scores[i] if i < len(z_scores) else 0
        if abs(z) >= threshold:
            status = 'anomali_negatif' if z < -threshold else 'anomali_positif'
            anomalies.append({
                'date': str(row['work_date'].date()),
                'line': row['line'],
                'shift': row['shift'],
                'metric': 'efficiency',
                'value': round(float(row['efficiency']), 1),
                'z_score': round(z, 2),
                'status': status,
                'detail': f'Efisiensi {"jauh di bawah" if z < 0 else "jauh di atas"} rata-rata'
            })
    return anomalies


def detect_reject_anomaly(df, threshold=2.0):
    if df.empty:
        return []
    grouped = df.groupby(['work_date', 'line', 'shift']).agg(
        target_qty=('target_qty', 'sum'),
        actual_ok=('actual_ok', 'sum'),
        actual_reject=('actual_reject', 'sum'),
    ).reset_index()
    grouped['reject_rate'] = grouped.apply(
        lambda r: (r['actual_reject'] / (r['actual_ok'] + r['actual_reject']) * 100)
        if (r['actual_ok'] + r['actual_reject']) > 0 else 0, axis=1
    )

    values = grouped['reject_rate'].values
    z_scores = z_score_anomalies(values, threshold)

    anomalies = []
    for i, row in grouped.iterrows():
        z = z_scores[i] if i < len(z_scores) else 0
        if z >= threshold:
            anomalies.append({
                'date': str(row['work_date'].date()),
                'line': row['line'],
                'shift': row['shift'],
                'metric': 'reject_rate',
                'value': round(float(row['reject_rate']), 1),
                'z_score': round(z, 2),
                'status': 'anomali_positif',
                'detail': f'Reject rate tinggi ({row["actual_reject"]:.0f} pcs)'
            })
    return anomalies


def detect_downtime_anomaly(downtime_df, threshold=2.0):
    if downtime_df.empty:
        return []
    grouped = downtime_df.groupby(
        downtime_df['start_time'].dt.date
    ).agg(
        total_downtime_sec=('duration_seconds', 'sum'),
        count=('id', 'count'),
    ).reset_index()
    grouped.columns = ['date', 'total_downtime_sec', 'count']
    grouped['total_downtime_min'] = grouped['total_downtime_sec'] / 60.0

    values = grouped['total_downtime_min'].values
    z_scores = z_score_anomalies(values, threshold)

    anomalies = []
    for i, row in grouped.iterrows():
        z = z_scores[i] if i < len(z_scores) else 0
        if z >= threshold:
            anomalies.append({
                'date': str(row['date']),
                'metric': 'downtime',
                'value': round(float(row['total_downtime_min']), 1),
                'z_score': round(z, 2),
                'status': 'anomali_positif',
                'detail': f'Total downtime {row["total_downtime_min"]:.0f} menit ({row["count"]:.0f} kejadian)'
            })
    return anomalies


def detect_all(df_dp, df_dt, threshold=2.0):
    return {
        'efficiency': detect_efficiency_anomaly(df_dp, threshold),
        'reject': detect_reject_anomaly(df_dp, threshold),
        'downtime': detect_downtime_anomaly(df_dt, threshold),
    }
