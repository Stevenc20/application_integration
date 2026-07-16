# Add notifyLineStatusChange() to all status-changing actions

## File
`resources/js/operational/production-engine.js`

## Problem
Only 3 of 9 status-changing actions broadcast via BroadcastChannel: `performSave`, `submitRRModalForm`, and finish save-log. The other 6 rely on `location.reload()` which only refreshes the current tab — other tabs (overview, line monitoring) stay stale.

## Changes (all in one file)

### 1. enqueueJob() — line 737
```js
// BEFORE:
showToast('Job berhasil dimasukkan ke antrian!', 'success');
setTimeout(() => location.reload(), 800);

// AFTER:
showToast('Job berhasil dimasukkan ke antrian!', 'success');
notifyLineStatusChange();
setTimeout(() => location.reload(), 800);
```

### 2. jsStartDandori() — line 766
```js
// BEFORE:
showToast('Dandori dimulai!', 'success');
setTimeout(() => location.reload(), 1000);

// AFTER:
showToast('Dandori dimulai!', 'success');
notifyLineStatusChange();
setTimeout(() => location.reload(), 1000);
```

### 3. jsStopDandori() — line 783
```js
// BEFORE:
showToast('Dandori selesai!', 'success');
location.reload();

// AFTER:
showToast('Dandori selesai!', 'success');
notifyLineStatusChange();
location.reload();
```

### 4. restartJob() — line 799-805
```js
// BEFORE:
.then(res => {
    if (res.success) location.reload();

// AFTER:
.then(res => {
    if (res.success) {
        notifyLineStatusChange();
        location.reload();
    }
```

### 5. startQuickDowntime() — line 973-984
```js
// BEFORE:
updateTimeline();
window.isPerformingAction = false;

// AFTER:
updateTimeline();
notifyLineStatusChange();
window.isPerformingAction = false;
```

### 6. finishQuickDowntime() — line 994-1023
```js
// BEFORE (after the if block ends, before window.isPerformingAction):
        updateTimeline();
        if (btnType === 'downtime') openDowntimeReport(jobId, res.downtime);
    }
    window.isPerformingAction = false;

// AFTER:
        updateTimeline();
        notifyLineStatusChange();
        if (btnType === 'downtime') openDowntimeReport(jobId, res.downtime);
    }
    window.isPerformingAction = false;
```
