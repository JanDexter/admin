# 🧪 PWA Offline Features - Testing Guide

## Quick Test Instructions

### Prerequisites
- Browser: Chrome, Edge, or Firefox (latest version)
- DevTools access (F12)
- CO-Z application running (http://127.0.0.1:8000)

---

## 🎯 Test Scenarios

### Test 1: Service Worker Registration
**Objective**: Verify service worker is properly registered

**Steps:**
1. Open CO-Z application
2. Press F12 to open DevTools
3. Go to Console tab
4. Look for: `✅ ServiceWorker registered successfully`

**Expected Result:**
- ✅ Service worker registers without errors
- ✅ Console shows success message

---

### Test 2: WiFi Credentials Offline Persistence
**Objective**: Verify WiFi credentials remain accessible offline

**Steps:**
1. Navigate to CO-Z homepage
2. Book a space and complete payment (GCash/Maya or Cash)
3. Note the WiFi credentials displayed
4. Open DevTools → Application → Local Storage
5. Verify `coz_offline_wifi_credentials` exists
6. In DevTools → Network tab, change throttling to "Offline"
7. Refresh the page

**Expected Result:**
- ✅ WiFi credentials still visible on offline page
- ✅ All three fields (SSID, username, password) displayed
- ✅ Offline indicator badge shows
- ✅ Offline notice message appears

**Check localStorage:**
```json
{
  "ssid": "COZ-WORKSPACE",
  "username": "user_XXX_XXXXXX",
  "password": "XXXXXXXXXXXX",
  "expiresAt": "2025-10-29T14:00:00.000Z",
  "saved_at": "2025-10-29T12:00:00.000Z"
}
```

---

### Test 3: Timer Persistence
**Objective**: Verify timer continues running after page reload

**Steps:**
1. Complete a 2-hour reservation
2. Note the timer countdown (e.g., 01:59:45)
3. Close the browser tab completely
4. Wait 1 minute
5. Reopen the application
6. Navigate to the reservation

**Expected Result:**
- ✅ Timer restored with correct time remaining
- ✅ No timer reset
- ✅ Countdown continues accurately

**Check localStorage:**
```json
{
  "reservationId": 123,
  "startTime": "2025-10-29T12:00:00.000Z",
  "endTime": "2025-10-29T14:00:00.000Z",
  "saved_at": "2025-10-29T12:00:00.000Z"
}
```

---

### Test 4: Offline/Online Status Detection
**Objective**: Verify connection status is properly detected

**Steps:**
1. Have an active reservation with WiFi credentials
2. Open DevTools → Network tab
3. Change throttling to "Offline"
4. Observe the UI

**Expected Result:**
- ✅ Amber "Offline" badge appears next to WiFi title
- ✅ Toast notification: "⚠️ You are offline. Saved data is still available."
- ✅ Offline notice box displays in WiFi section

**Steps (Online):**
5. Change throttling back to "No throttling"
6. Observe the UI

**Expected Result:**
- ✅ Offline badge disappears
- ✅ Toast notification: "✅ Back online!"
- ✅ Offline notice box disappears

---

### Test 5: Beautiful Offline Page
**Objective**: Verify custom offline page displays correctly

**Steps:**
1. Navigate to any CO-Z page
2. Open DevTools → Network tab
3. Change throttling to "Offline"
4. Try to navigate to a different page or refresh

**Expected Result:**
- ✅ Custom offline page appears (not browser default)
- ✅ CO-Z branding and colors visible
- ✅ Gradient background (#eef3ff to #d4e3ff)
- ✅ Connection status shows "Offline" with pulsing dot
- ✅ "Try Again" button present
- ✅ "Go to Home" button present

**If you have saved data:**
- ✅ WiFi credentials section displays with all fields
- ✅ Reservation details section displays
- ✅ Data is correctly formatted

---

### Test 6: Cache Performance
**Objective**: Verify assets are properly cached

**Steps:**
1. Open CO-Z application for the first time
2. Note the page load time
3. Open DevTools → Application → Cache Storage
4. Expand cache storage to see all caches:
   - coz-workspace-v3
   - coz-runtime-v3
   - coz-images-v3
   - coz-static-v3

**Expected Result:**
- ✅ All cache stores exist
- ✅ Images cached in coz-images-v3
- ✅ CSS/JS files cached in coz-static-v3
- ✅ Total cache size ~15MB

**Steps (Second Load):**
5. Refresh the page (Ctrl+R)
6. Note the page load time

**Expected Result:**
- ✅ Page loads much faster (~0.5s vs 2s)
- ✅ Assets served from cache
- ✅ Network tab shows "(from ServiceWorker)"

---

### Test 7: Data Expiry
**Objective**: Verify expired data is automatically cleaned up

**Steps:**
1. Create a reservation that ends in 5 minutes
2. Wait for reservation to expire (or manually change localStorage expiry)
3. Open DevTools → Console
4. Type: `localStorage.getItem('coz_offline_wifi_credentials')`
5. Refresh the page
6. Check localStorage again

**Expected Result:**
- ✅ Expired credentials removed from localStorage
- ✅ Expired reservation removed from localStorage
- ✅ No stale data persists

---

### Test 8: Offline Booking Scenario (Real-World)
**Objective**: Simulate real user scenario

**Story:**
> User books a workspace, receives WiFi credentials, then loses internet 
> connection. They need to access WiFi to reconnect.

**Steps:**
1. Book a workspace and complete payment
2. Copy WiFi credentials to notepad (simulate writing down)
3. Enable Airplane Mode or disconnect WiFi
4. Close browser completely
5. Reopen browser and navigate to CO-Z
6. Wait for offline page to load

**Expected Result:**
- ✅ Offline page displays immediately
- ✅ WiFi credentials are visible and match what you copied
- ✅ Username and password can be used to connect
- ✅ Timer shows correct time remaining
- ✅ No error messages

---

## 🔍 DevTools Inspection

### Service Worker Panel
**Location**: DevTools → Application → Service Workers

**What to Check:**
- ✅ Status: "activated and is running"
- ✅ Source: `/sw.js`
- ✅ Scope: `/`
- ✅ No errors in status

**Actions to Test:**
- Click "Update" - should show: "Service Worker has been updated"
- Click "Unregister" - re-register on next page load

---

### Cache Storage
**Location**: DevTools → Application → Cache Storage

**Expected Caches:**
1. **coz-workspace-v3**
   - / (homepage)
   - /offline.html
   - /icons/favicon.svg
   - /icons/icon-192x192.png
   - /icons/icon-512x512.png

2. **coz-runtime-v3**
   - Dynamic pages as you navigate

3. **coz-images-v3**
   - All loaded images

4. **coz-static-v3**
   - /build/assets/app-*.js
   - /build/assets/app-*.css
   - Other static assets

---

### Local Storage
**Location**: DevTools → Application → Local Storage → http://127.0.0.1:8000

**Expected Keys:**
1. `coz_offline_wifi_credentials`
2. `coz_offline_reservation_data`
3. `coz_offline_timer_state`

**Verify Data Structure:**
```javascript
// WiFi Credentials
{
  "ssid": "COZ-WORKSPACE",
  "username": "user_123_456789",
  "password": "ABC123DEF456",
  "expiresAt": "2025-10-29T14:00:00.000Z",
  "saved_at": "2025-10-29T12:00:00.000Z"
}

// Reservation Data
{
  "id": 123,
  "space_name": "Exclusive Space",
  "start_time": "2025-10-29T12:00:00.000Z",
  "end_time": "2025-10-29T14:00:00.000Z",
  "status": "paid",
  "total_price": 200,
  "payment_method": "gcash",
  "saved_at": "2025-10-29T12:00:00.000Z",
  "expires_at": "2025-10-29T14:00:00.000Z"
}

// Timer State
{
  "reservationId": 123,
  "startTime": "2025-10-29T12:00:00.000Z",
  "endTime": "2025-10-29T14:00:00.000Z",
  "saved_at": "2025-10-29T12:00:00.000Z"
}
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Service Worker Not Registering
**Symptoms:** No console message, SW panel empty

**Solutions:**
- Ensure HTTPS or localhost
- Check browser supports service workers
- Clear cache and hard reload (Ctrl+Shift+R)
- Check `sw.js` file exists at `/public/sw.js`

---

### Issue 2: localStorage Empty
**Symptoms:** No data in localStorage

**Solutions:**
- Check you completed a reservation
- Verify not in Incognito/Private mode
- Check browser localStorage is enabled
- Look for console errors

---

### Issue 3: Timer Not Restoring
**Symptoms:** Timer resets on reload

**Solutions:**
- Check `coz_offline_timer_state` in localStorage
- Verify reservation hasn't expired
- Check browser console for errors
- Ensure service worker is active

---

### Issue 4: Offline Page Not Showing
**Symptoms:** Browser default offline page shows

**Solutions:**
- Verify service worker is registered
- Check `/offline.html` exists
- Clear service worker and re-register
- Hard reload (Ctrl+Shift+R)

---

## ✅ Success Checklist

After testing, verify all features work:

- [ ] Service worker registers successfully
- [ ] WiFi credentials saved to localStorage
- [ ] WiFi credentials persist offline
- [ ] Timer continues after page reload
- [ ] Online/offline status detected
- [ ] Toast notifications appear
- [ ] Offline badge shows when offline
- [ ] Custom offline page displays
- [ ] Saved data appears on offline page
- [ ] Connection status updates in real-time
- [ ] Auto-reload works when back online
- [ ] Expired data cleaned up automatically
- [ ] Cache stores all created
- [ ] Assets served from cache on repeat visits

---

## 📱 Mobile Testing

### iOS Safari
1. Add to Home Screen
2. Launch PWA from home screen
3. Test offline features
4. Verify standalone mode

### Android Chrome
1. Install PWA via browser prompt
2. Launch from app drawer
3. Test offline features
4. Verify fullscreen mode

---

## 📊 Performance Testing

### Lighthouse Audit
1. Open DevTools → Lighthouse
2. Select "Progressive Web App"
3. Click "Generate report"

**Target Scores:**
- PWA: 90+ (should be 100)
- Performance: 90+
- Best Practices: 90+

**PWA Checklist Items:**
- ✅ Registers a service worker
- ✅ Responds with 200 when offline
- ✅ Has a web app manifest
- ✅ Uses HTTPS
- ✅ Redirects HTTP to HTTPS
- ✅ Has a viewport meta tag
- ✅ Content sized correctly for viewport

---

**Testing Completed By**: _________________
**Date**: _________________
**All Tests Passed**: [ ] Yes [ ] No
**Issues Found**: _________________

---

*For issues, check `PWA_FEATURES.md` for detailed documentation*
