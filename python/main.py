from fastapi import FastAPI, Query
from fastapi.middleware.cors import CORSMiddleware
import uvicorn

from services.data_loader import (
    load_daily_productions,
    load_downtimes,
    load_repair_rejects,
    load_production_plans,
)
from services.anomaly import detect_all
from services.trend import (
    calculate_efficiency_trend,
    calculate_reject_trend,
    calculate_downtime_trend,
)
from services.pareto import downtime_pareto, defect_pareto

app = FastAPI(title='Data Mining API - PT. IPPI', version='1.0.0')

app.add_middleware(
    CORSMiddleware,
    allow_origins=['*'],
    allow_credentials=True,
    allow_methods=['*'],
    allow_headers=['*'],
)


@app.get('/health')
def health():
    return {'status': 'ok', 'service': 'data-mining'}


# ─── TREND ───────────────────────────────────────────────────

@app.get('/api/trend/efficiency')
def trend_efficiency(days: int = Query(90, ge=7, le=365)):
    df = load_daily_productions(days)
    return calculate_efficiency_trend(df)


@app.get('/api/trend/reject')
def trend_reject(days: int = Query(90, ge=7, le=365)):
    df = load_daily_productions(days)
    return calculate_reject_trend(df)


@app.get('/api/trend/downtime')
def trend_downtime(days: int = Query(90, ge=7, le=365)):
    df = load_downtimes(days)
    return calculate_downtime_trend(df)


# ─── ANOMALY ─────────────────────────────────────────────────

@app.get('/api/anomaly/detection')
def anomaly_detection(
    days: int = Query(30, ge=7, le=365),
    threshold: float = Query(2.0, ge=1.0, le=5.0),
):
    df_dp = load_daily_productions(days)
    df_dt = load_downtimes(days)
    return detect_all(df_dp, df_dt, threshold)


@app.get('/api/anomaly/efficiency')
def anomaly_efficiency(
    days: int = Query(30, ge=7, le=365),
    threshold: float = Query(2.0, ge=1.0, le=5.0),
):
    from services.anomaly import detect_efficiency_anomaly
    df = load_daily_productions(days)
    return {'anomalies': detect_efficiency_anomaly(df, threshold)}


@app.get('/api/anomaly/reject')
def anomaly_reject(
    days: int = Query(30, ge=7, le=365),
    threshold: float = Query(2.0, ge=1.0, le=5.0),
):
    from services.anomaly import detect_reject_anomaly
    df = load_daily_productions(days)
    return {'anomalies': detect_reject_anomaly(df, threshold)}


@app.get('/api/anomaly/downtime')
def anomaly_downtime(
    days: int = Query(30, ge=7, le=365),
    threshold: float = Query(2.0, ge=1.0, le=5.0),
):
    from services.anomaly import detect_downtime_anomaly
    df = load_downtimes(days)
    return {'anomalies': detect_downtime_anomaly(df, threshold)}


# ─── PARETO ──────────────────────────────────────────────────

@app.get('/api/pareto/downtime')
def pareto_downtime(days: int = Query(30, ge=7, le=365)):
    df = load_downtimes(days)
    return downtime_pareto(df)


@app.get('/api/pareto/defect')
def pareto_defect(days: int = Query(30, ge=7, le=365)):
    df = load_repair_rejects(days)
    return defect_pareto(df)


# ─── SUMMARY ─────────────────────────────────────────────────

@app.get('/api/summary')
def summary(
    days: int = Query(30, ge=7, le=365),
    threshold: float = Query(2.0, ge=1.0, le=5.0),
):
    df_dp = load_daily_productions(days)
    df_dt = load_downtimes(days)
    df_rr = load_repair_rejects(days)

    anomalies = detect_all(df_dp, df_dt, threshold)
    total_anomalies = (
        len(anomalies.get('efficiency', [])) +
        len(anomalies.get('reject', [])) +
        len(anomalies.get('downtime', []))
    )

    efficiency_trend = calculate_efficiency_trend(df_dp)
    reject_trend = calculate_reject_trend(df_dp)
    downtime_trend = calculate_downtime_trend(df_dt)
    downtime_pareto_data = downtime_pareto(df_dt)
    defect_pareto_data = defect_pareto(df_rr)

    return {
        'total_anomalies': total_anomalies,
        'anomalies': anomalies,
        'efficiency': efficiency_trend,
        'reject': reject_trend,
        'downtime': downtime_trend,
        'pareto': {
            'downtime': downtime_pareto_data,
            'defect': defect_pareto_data,
        },
    }


if __name__ == '__main__':
    uvicorn.run(app, host='0.0.0.0', port=8001)
