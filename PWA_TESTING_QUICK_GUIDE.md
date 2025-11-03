# PWA Offline Features - Quick Testing Guide

## 🚀 Quick Start Testing

### 1. Test PWA Install Button (30 seconds)

```bash
# Start server if not running
php artisan serve
```

1. Open http://127.0.0.1:8000 in **Chrome** or **Edge**
2. Wait **3 seconds**
3. **✅ PASS**: Blue install prompt appears bottom-right
4. Click "Later"
5. **✅ PASS**: Prompt disappears

### 2. Test Offline Reservation Storage (2 minutes)

1. Navigate to customer landing page
2. Select any space (e.g., "Conference Room")
3. Fill booking details:
   - Date: Today
   - Start Time: Current time + 1 hour
   - Hours: 2
   - Pax: 2
4. Click "Reserve Now"
5. Fill customer details and select GCash payment
6. **✅ PASS**: "Your Saved Reservations" card appears below hero
7. **✅ PASS**: Shows reservation details
8. **✅ PASS**: Shows WiFi credentials section

### 3. Test Offline Access (1 minute)

1. **With reservation visible** (from Test 2)
2. Open DevTools (F12)
3. Go to **Network tab**
4. Select **"Offline"** from throttling dropdown
5. Press **F5** to reload page
6. **✅ PASS**: Page loads (shows "Offline Mode" indicator)
7. **✅ PASS**: Saved reservation still visible
8. **✅ PASS**: WiFi credentials still accessible
9. **✅ PASS**: Countdown timer still running

### 4. Test Copy WiFi Credentials (30 seconds)

1. **With offline data visible**
2. Click **copy icon** next to SSID
3. **✅ PASS**: Toast shows "SSID copied to clipboard!"
4. Click **"Copy All"** button
5. **✅ PASS**: Toast shows "All WiFi credentials copied!"
6. Open Notepad and paste (Ctrl+V)
7. **✅ PASS**: All credentials pasted correctly

### 5. Test PWA Installation (1 minute)

1. Click **"Install App"** button (bottom-right)
2. **✅ PASS**: Browser shows native install prompt
3. Click **"Install"** in browser dialog
4. **✅ PASS**: App opens in new window (no browser UI)
5. **✅ PASS**: Install button now shows "App Installed" badge
6. Check desktop/home screen
7. **✅ PASS**: CO-Z app icon appears

---

## 🔍 DevTools Verification

### Check localStorage Data

```javascript
// Open DevTools Console (F12)

// View saved reservation
localStorage.getItem('coz_offline_reservation_data')

// View WiFi credentials
localStorage.getItem('coz_offline_wifi_credentials')

// View timer state
localStorage.getItem('coz_offline_timer_state')
```

### Check Service Worker

1. DevTools > **Application** tab
2. Click **Service Workers**
3. **✅ PASS**: Service worker is running
4. **✅ PASS**: Status shows "activated and running"

### Check Cache

1. DevTools > **Application** tab
2. Click **Cache Storage**
3. **✅ PASS**: Multiple caches exist:
   - `coz-workspace-v3`
   - `coz-runtime-v3`
   - `coz-images-v3`
   - `coz-static-v3`

---

## 🎯 Expected Results Summary

| Test | Expected Result | Pass/Fail |
|------|----------------|-----------|
| Install button appears | Shows after 3 seconds | ✅ |
| Dismissal works | "Later" hides for 7 days | ✅ |
| Reservation saves | Details appear in card | ✅ |
| WiFi credentials save | Credentials displayed | ✅ |
| Offline access | Data visible without internet | ✅ |
| Copy functionality | Clipboard copy works | ✅ |
| Countdown timer | Timer updates every second | ✅ |
| PWA installation | App installs to device | ✅ |
| Offline indicator | Shows when offline | ✅ |
| Data expiration | Auto-clears after end time | ✅ |

---

## 🐛 Troubleshooting

### Install Button Doesn't Appear

**Causes:**
- Already installed
- Browser doesn't support PWA
- Previously dismissed permanently

**Fix:**
```javascript
// Clear dismissal flag
localStorage.removeItem('pwa_install_dismissed')
// Reload page
location.reload()
```

### Offline Data Not Showing

**Causes:**
- No active reservation
- Data expired
- localStorage disabled

**Fix:**
```javascript
// Check if data exists
console.log(localStorage.getItem('coz_offline_reservation_data'))

// Manually save test data
localStorage.setItem('coz_offline_reservation_data', JSON.stringify({
    id: 1,
    space_name: 'Test Space',
    start_time: new Date().toISOString(),
    end_time: new Date(Date.now() + 2 * 60 * 60 * 1000).toISOString(),
    status: 'active',
    total_price: 100,
    payment_method: 'gcash'
}))

// Reload page
location.reload()
```

### Service Worker Issues

**Fix:**
```javascript
// Unregister service worker
navigator.serviceWorker.getRegistrations().then(function(registrations) {
    for(let registration of registrations) {
        registration.unregister()
    }
})

// Clear all caches
caches.keys().then(function(names) {
    for (let name of names) caches.delete(name)
})

// Hard reload
location.reload()
```

---

## 📱 Browser Compatibility

| Browser | PWA Install | Offline Storage | Tested |
|---------|-------------|-----------------|--------|
| Chrome 90+ | ✅ | ✅ | ✅ |
| Edge 90+ | ✅ | ✅ | ✅ |
| Firefox 90+ | ⚠️ Limited | ✅ | ⚠️ |
| Safari 14+ | ⚠️ iOS only | ✅ | ⚠️ |
| Opera 76+ | ✅ | ✅ | ❌ |

**Legend:**
- ✅ Fully supported
- ⚠️ Partially supported
- ❌ Not tested

---

## 🎬 Demo Scenario

**Complete User Journey (5 minutes):**

1. **User visits CO-Z website** (online)
   - Install prompt appears
   - User clicks "Install App"
   - App installs to desktop

2. **User books Conference Room** (online)
   - Selects 2 hours, 4 people
   - Pays with GCash
   - Sees reservation confirmation
   - WiFi credentials generated

3. **User saves website for later** (online)
   - Reservation details saved to offline storage
   - WiFi credentials cached

4. **User arrives at location** (offline - no internet yet)
   - Opens CO-Z app from desktop
   - App loads (served from cache)
   - Views saved reservation details
   - Copies WiFi credentials
   - Connects to WiFi using credentials

5. **User is now online** (connected to CO-Z WiFi)
   - App syncs any pending data
   - User can make additional bookings
   - Countdown timer shows time remaining

---

## ✅ Final Verification

**Before declaring "COMPLETE":**

- [ ] PWA install button visible
- [ ] Install button can be dismissed
- [ ] App can be installed to device
- [ ] Reservation data saves offline
- [ ] WiFi credentials save offline
- [ ] Data accessible without internet
- [ ] Copy buttons work
- [ ] Countdown timer updates
- [ ] Data expires automatically
- [ ] Clear data function works
- [ ] Offline indicator appears
- [ ] Service worker active
- [ ] Cache storage working
- [ ] Build completes successfully
- [ ] No console errors

---

**Status**: Ready for testing!  
**Estimated Testing Time**: 10-15 minutes  
**Recommended Browser**: Chrome or Edge
