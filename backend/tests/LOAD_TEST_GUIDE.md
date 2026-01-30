# Coffee POS SaaS - Load Testing Guide

## Prerequisites

1. **Backend server running**:

    ```bash
    cd backend
    php artisan serve --port=8001
    ```

2. **Database is up** (XAMPP/MariaDB running)

3. **Valid test data**:
    - At least one shop (shop_id = 1)
    - At least 2 products in the database
    - Update `order_payload.json` with valid product IDs

---

## Step 1: Update Test Payload

Edit `tests/order_payload.json` to use **real product IDs** from your database:

```bash
# Find valid product IDs
php artisan tinker
>>> \App\Models\Product::take(3)->pluck('id', 'name')
```

Then update `order_payload.json`:

```json
{
    "shop_id": 1,
    "items": [
        {
            "product_id": 1, // ← Use real product ID
            "quantity": 2,
            "price": 3.5,
            "variant_price": 0,
            "options": []
        }
    ],
    "payment_method": "cash",
    "payment_currency": "USD",
    "received_amount": 10.0
}
```

---

## Step 2: Run the Load Test

### Option A: Using PowerShell Script (Recommended)

```powershell
cd backend/tests
./test-concurrency.ps1

# Or with custom parameters:
./test-concurrency.ps1 -TotalRequests 100 -ConcurrentThreads 30
```

**What it does**:

- Creates 50 orders concurrently (20 threads)
- Automatically checks for duplicate order numbers
- Shows success/failure statistics
- Displays any errors

**Expected output**:

```
=====================================
Test Results
=====================================
Total Requests: 50
Successful: 50
Failed: 0
Duration: 3.45 seconds
Requests/sec: 14.49

Order Number Analysis:
  Total orders created: 50
  Unique order numbers: 50
  ✅ NO DUPLICATES FOUND - Test PASSED!
```

---

### Option B: Manual PowerShell (Quick Test)

```powershell
# Quick test with 10 concurrent requests
1..10 | ForEach-Object -Parallel {
    Invoke-RestMethod -Uri "http://localhost:8001/api/staff/orders" `
      -Method POST `
      -ContentType "application/json" `
      -Body (Get-Content order_payload.json -Raw)
} -ThrottleLimit 10 | Select-Object -ExpandProperty order | Select-Object order_number
```

---

## Step 3: Verify Results in Database

### Check for Duplicate Order Numbers

```bash
# Connect to database
mysql -u root -p coffee_pos_saas

# OR use Tinker:
php artisan tinker
```

**SQL Query**:

```sql
SELECT order_number, COUNT(*) as count
FROM orders
WHERE created_at >= NOW() - INTERVAL 10 MINUTE
GROUP BY order_number
HAVING count > 1;
```

**Expected**: 0 rows (no duplicates)

**Tinker Alternative**:

```php
>>> \App\Models\Order::where('created_at', '>', now()->subMinutes(10))
       ->get()
       ->groupBy('order_number')
       ->filter(fn($group) => $group->count() > 1)
       ->keys()
```

**Expected**: Empty collection

---

### Check Order Number Sequence

```sql
SELECT order_number, created_at
FROM orders
WHERE created_at >= NOW() - INTERVAL 10 MINUTE
ORDER BY created_at DESC
LIMIT 20;
```

**Expected**: Sequential numbers like:

- ORD-20260130-0045
- ORD-20260130-0046
- ORD-20260130-0047
- ...

---

## Step 4: Test KHQR Duplicate Prevention

### Manual Test (requires actual KHQR payment):

1. Create an order via guest checkout with KHQR payment
2. Get the `khqr_md5` from the response
3. Try to create another order with the same MD5:

```powershell
# First request
$response1 = Invoke-RestMethod -Uri "http://localhost:8001/api/guest/checkout/finalize-khqr" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"session_token":"YOUR_TOKEN","khqr_md5":"SAME_MD5"}'

# Second request (should return existing order)
$response2 = Invoke-RestMethod -Uri "http://localhost:8001/api/guest/checkout/finalize-khqr" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"session_token":"YOUR_TOKEN","khqr_md5":"SAME_MD5"}'

# Compare
$response1.order.id -eq $response2.order.id  # Should be TRUE
```

**Expected**: Both requests return the **same order**, no duplicate created

---

## Step 5: Test Transaction Rollback

**Simulate failure** to verify rollback works:

1. Temporarily break something (e.g., set invalid shop_id in payload)
2. Submit order
3. Check database - NO partial order should exist

```powershell
# Bad request (should fail)
Invoke-RestMethod -Uri "http://localhost:8001/api/staff/orders" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"shop_id":99999,"items":[],"payment_method":"cash"}' `
  -ErrorAction Stop
```

**Expected**: Error response, NO order in database

---

## Step 6: Monitor Performance

### Check Query Performance

```sql
-- Show slow queries (if enabled)
SHOW FULL PROCESSLIST;

-- Check index usage
EXPLAIN SELECT * FROM orders
WHERE shop_id = 1
AND order_number LIKE 'ORD-20260130-%'
ORDER BY id DESC LIMIT 1;
```

**Expected**: Uses `idx_unique_shop_order_number` or `idx_order_number` index

---

## Troubleshooting

### "Payload file not found"

```bash
cd backend/tests
# Make sure order_payload.json exists
ls order_payload.json
```

### "Authentication required" error

Add authentication header:

```powershell
$headers = @{
    "Authorization" = "Bearer YOUR_TOKEN_HERE"
}

Invoke-RestMethod -Uri "..." -Headers $headers -Body ...
```

### "Product not found" error

Update product IDs in `order_payload.json` to match your database

---

## Success Criteria

✅ **Test PASSED if**:

- All 50 requests successful
- 50 unique order numbers
- No duplicates in database
- Order numbers are sequential
- No errors in Laravel log

❌ **Test FAILED if**:

- Duplicate order numbers found
- Errors during creation
- Partial orders in database (order without items)

---

## Clean Up After Testing

```bash
# Delete test orders
php artisan tinker
>>> \App\Models\Order::where('created_at', '>', now()->subHour())->delete();

# Or keep them for analysis
```
