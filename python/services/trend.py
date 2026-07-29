import numpy as np
import pandas as pd


def sma(values, window=7):
    series = pd.Series(values)
    return series.rolling(window=window, min_periods=1).mean().tolist()


def exp_smoothing(values, alpha=0.3):
    result = []
    s = None
    for v in values:
        if s is None:
            s = float(v)
        else:
            s = alpha * float(v) + (1 - alpha) * s
        result.append(round(s, 2))
    return result


def trend_direction(recent_values):
    if len(recent_values) < 2:
        return 'stable', 0.0
    first = np.mean(recent_values[:len(recent_values)//2])
    last = np.mean(recent_values[len(recent_values)//2:])
    diff = ((last - first) / first * 100) if first != 0 else 0
    if diff > 2:
        return 'up', round(diff, 1)
    elif diff < -2:
        return 'down', round(diff, 1)
    else:
        return 'stable', round(diff, 1)


def calculate_efficiency_trend(df):
    if df.empty:
        return {'daily': [], 'summary': {}}
    daily = df.groupby('work_date').agg(
        efficiency=('efficiency', 'mean'),
        actual_ok=('actual_ok', 'sum'),
        actual_repair=('actual_repair', 'sum'),
        actual_reject=('actual_reject', 'sum'),
    ).reset_index().sort_values('work_date')

    eff_values = daily['efficiency'].values
    sma_7 = sma(eff_values, 7)
    sma_30 = sma(eff_values, 30)
    exp_smooth = exp_smoothing(eff_values)

    direction, change = trend_direction(eff_values)

    daily_list = []
    for i, row in daily.iterrows():
        daily_list.append({
            'date': str(row['work_date'].date()),
            'efficiency': round(float(row['efficiency']), 1),
            'sma_7': round(float(sma_7[i]), 1) if i < len(sma_7) else None,
            'sma_30': round(float(sma_30[i]), 1) if i < len(sma_30) else None,
            'exp_smooth': exp_smooth[i] if i < len(exp_smooth) else None,
        })

    valid_eff = [v for v in eff_values if v > 0]
    summary = {
        'current': round(float(eff_values[-1]), 1) if len(eff_values) > 0 else 0,
        'average': round(float(np.mean(valid_eff)), 1) if valid_eff else 0,
        'min': round(float(np.min(valid_eff)), 1) if valid_eff else 0,
        'max': round(float(np.max(valid_eff)), 1) if valid_eff else 0,
        'trend_direction': direction,
        'trend_change_pct': change,
        'data_points': len(daily_list),
    }

    return {'daily': daily_list, 'summary': summary}


def calculate_reject_trend(df):
    if df.empty:
        return {'daily': [], 'summary': {}}
    daily = df.groupby('work_date').agg(
        actual_ok=('actual_ok', 'sum'),
        actual_reject=('actual_reject', 'sum'),
    ).reset_index().sort_values('work_date')

    daily['reject_rate'] = daily.apply(
        lambda r: (r['actual_reject'] / (r['actual_ok'] + r['actual_reject']) * 100)
        if (r['actual_ok'] + r['actual_reject']) > 0 else 0, axis=1
    )

    values = daily['reject_rate'].values
    sma_7 = sma(values, 7)

    direction, change = trend_direction(values)

    daily_list = []
    for i, row in daily.iterrows():
        daily_list.append({
            'date': str(row['date'].date()),
            'reject_rate': round(float(row['reject_rate']), 2),
            'sma_7': round(float(sma_7[i]), 2) if i < len(sma_7) else None,
            'actual_reject': int(row['actual_reject']),
        })

    valid = [v for v in values if v > 0]
    summary = {
        'current': round(float(values[-1]), 2) if len(values) > 0 else 0,
        'average': round(float(np.mean(valid)), 2) if valid else 0,
        'trend_direction': direction,
        'trend_change_pct': change,
    }

    return {'daily': daily_list, 'summary': summary}


def calculate_downtime_trend(df):
    if df.empty:
        return {'daily': [], 'summary': {}}
    df_copy = df.copy()
    df_copy['date'] = df_copy['start_time'].dt.date
    daily = df_copy.groupby('date').agg(
        total_seconds=('duration_seconds', 'sum'),
        count=('id', 'count'),
    ).reset_index().sort_values('date')

    daily['total_minutes'] = daily['total_seconds'] / 60.0
    values = daily['total_minutes'].values
    sma_7 = sma(values, 7)

    direction, change = trend_direction(values)

    daily_list = []
    for i, row in daily.iterrows():
        daily_list.append({
            'date': str(row['date']),
            'total_minutes': round(float(row['total_minutes']), 1),
            'count': int(row['count']),
            'sma_7': round(float(sma_7[i]), 1) if i < len(sma_7) else None,
        })

    summary = {
        'current': round(float(values[-1]), 1) if len(values) > 0 else 0,
        'average': round(float(np.mean(values)), 1) if len(values) > 0 else 0,
        'trend_direction': direction,
        'trend_change_pct': change,
    }

    return {'daily': daily_list, 'summary': summary}
