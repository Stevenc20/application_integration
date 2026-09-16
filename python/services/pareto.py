import numpy as np


def pareto_breakdown(items, value_key, label_key):
    sorted_items = sorted(items, key=lambda x: x[value_key], reverse=True)
    total = sum(item[value_key] for item in sorted_items)
    if total == 0:
        return [], 0, []

    cumulative = 0
    result = []
    top_categories = []
    for item in sorted_items:
        val = item[value_key]
        pct = (val / total) * 100
        cumulative += pct
        entry = {
            label_key: item[label_key],
            value_key: round(val, 1),
            'pct': round(pct, 1),
            'cumulative': round(cumulative, 1),
        }
        result.append(entry)
        if cumulative <= 80:
            top_categories.append(item[label_key])

    return result, 80, top_categories


def downtime_pareto(df):
    if df.empty:
        return {'downtime': [], 'top_categories': []}
    grouped = df.groupby('jenis_downtime').agg(
        total_minutes=('duration_seconds', lambda s: s.sum() / 60.0),
        count=('id', 'count'),
    ).reset_index()

    items = []
    for _, row in grouped.iterrows():
        items.append({
            'jenis_downtime': row['jenis_downtime'],
            'total_minutes': row['total_minutes'],
            'count': int(row['count']),
        })

    breakdown, line, top = pareto_breakdown(items, 'total_minutes', 'jenis_downtime')
    return {'downtime': breakdown, 'pareto_line': line, 'top_categories': top}


def defect_pareto(df):
    if df.empty:
        return {'defects': [], 'top_categories': []}
    grouped = df.groupby('defect_name').agg(
        total_qty=('qty_a', 'sum'),
        count=('id', 'count'),
    ).reset_index()

    items = []
    for _, row in grouped.iterrows():
        items.append({
            'defect_name': row['defect_name'],
            'total_qty': float(row['total_qty']),
            'count': int(row['count']),
        })

    breakdown, line, top = pareto_breakdown(items, 'total_qty', 'defect_name')
    return {'defects': breakdown, 'pareto_line': line, 'top_categories': top}


def downtime_by_category_pareto(df):
    if df.empty:
        return {'categories': []}
    grouped = df.groupby('jenis_downtime').agg(
        total_minutes=('duration_seconds', lambda s: s.sum() / 60.0),
        count=('id', 'count'),
    ).reset_index()

    items = []
    for _, row in grouped.iterrows():
        items.append({
            'jenis_downtime': row['jenis_downtime'],
            'total_minutes': row['total_minutes'],
            'count': int(row['count']),
        })

    breakdown, line, top = pareto_breakdown(items, 'total_minutes', 'jenis_downtime')
    return {'categories': breakdown, 'pareto_line': line, 'top_categories': top}
