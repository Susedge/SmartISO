# 🔍 QUICK DEBUG GUIDE - Browser Console

## ✅ Changes Applied

Added debug logging to see `getStaffSchedules()` results in browser console.

---

## 🚀 How to Check (On Other Device)

### Step 1: Open Calendar
```
Schedule → Calendar
```

### Step 2: Open Console
```
Press F12 → Go to "Console" tab
```

### Step 3: Look for Debug Output
```
📅 Calendar Debug Info
  Debug Info: {user_type: "service_staff", user_id: 5, ...}
  Events Count: ?
  Events Array: [...]
```

---

## 📊 What You Should See

### ✅ **Correct Output (Expected):**
```javascript
Debug Info: {
  user_type: "service_staff",
  user_id: 5,
  raw_schedules_count: 5,        // ← Should be 5
  calendar_events_count: 5        // ← Should be 5
}

Events Array: [
  {id: 30, status: "pending_service"},
  {id: 31, status: "completed"},  // ← Should see 3 of these
  {id: 32, status: "completed"},  // ← Should see 3 of these
  {id: 33, status: "pending_service"},
  {id: 34, status: "completed"}   // ← Should see 3 of these
]
```

### ❌ **Wrong Output (Cache Issue):**
```javascript
Events Count: 2                   // ← Only 2 instead of 5!
Events Array: [
  {id: 30, status: "pending_service"},
  {id: 33, status: "pending_service"}
]
// Missing 3 completed events!
```

---

## 🎯 Diagnosis

| Console Shows | Means | Solution |
|--------------|-------|----------|
| **5 events with 3 completed** | ✅ Server correct, calendar hiding them | FullCalendar issue or browser bug |
| **2 events, only pending** | ❌ Browser cache serving old data | **Clear cache + hard refresh** |
| **5 events, all "pending"** | ❌ Status wrong in data | Status logic issue |

---

## 🔄 Quick Fix

If console shows **only 2 events**:

```
1. Ctrl + Shift + R  (Hard refresh)
2. Check console again
3. If still 2: Clear cache completely
4. If still 2: Different database or user
```

---

## 📸 Take Screenshot

If issue persists:
1. **Take screenshot of console output**
2. **Share with developer**
3. Shows exact data being received

---

## 🧹 Console Commands

Type these in console to inspect:

```javascript
// Count by status
console.log('Pending:', events.filter(e => e.status.includes('pending')).length);
console.log('Completed:', events.filter(e => e.status === 'completed').length);

// List all event IDs and statuses
events.forEach(e => console.log(`#${e.id}: ${e.status}`));

// Check if completed events exist
console.log('Has completed?', events.some(e => e.status === 'completed'));
```

---

## ✅ Success Indicator

**When fixed, you should see:**
```
Events Count: 5
Completed: 3
Pending: 2
```

**In calendar view:**
- 3 blue "Completed" badges
- 2 yellow "Pending" badges

---

**This debug info will show exactly what data the browser is receiving!** 🎉
